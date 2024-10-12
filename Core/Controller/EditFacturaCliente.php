<?php
/**
 * Copyright (C) 2021-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
 */

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\AjaxForms\SalesController;
use FacturaScripts\Core\Lib\Calculator;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\Accounting\InvoiceToAccounting;
use FacturaScripts\Dinamic\Lib\ReceiptGenerator;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\ReciboCliente;

/**
 * Description of EditFacturaCliente
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class EditFacturaCliente extends SalesController
{
    private const VIEW_ACCOUNTS = 'ListAsiento';
    private const VIEW_RECEIPTS = 'ListReciboCliente';

    public function getModelClassName(): string
    {
        return 'FacturaCliente';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'sales';
        $data['title'] = 'invoice';
        $data['icon'] = 'fa-solid fa-file-invoice-dollar';
        $data['showonmenu'] = false;
        return $data;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        parent::createViews();
        $this->createViewsReceipts();
        $this->createViewsAccounting();
        $this->createViewsRefunds();
    }

    /**
     * Add view for account detail of the invoice.
     *
     * @param string $viewName
     */
    private function createViewsAccounting(string $viewName = self::VIEW_ACCOUNTS): void
    {
        $this->addListView($viewName, 'Asiento', 'accounting-entries', 'fa-solid fa-balance-scale');

        // buttons
        $this->addButton($viewName, [
            'action' => 'generate-accounting',
            'icon' => 'fa-solid fa-wand-magic-sparkles',
            'label' => 'generate-accounting-entry'
        ]);

        // settings
        $this->setSettings($viewName, 'btnNew', false);
    }

    /**
     * Add view for refund invoice.
     */
    private function createViewsRefunds(string $viewName = 'refunds'): void
    {
        $this->addHtmlView($viewName, 'Tab/RefundFacturaCliente', 'FacturaCliente', 'refunds', 'fa-solid fa-share-square');
    }

    /**
     * Add view for receipts of the invoice.
     *
     * @param string $viewName
     */
    private function createViewsReceipts(string $viewName = self::VIEW_RECEIPTS): void
    {
        $this->addListView($viewName, 'ReciboCliente', 'receipts', 'fa-solid fa-dollar-sign')
            ->addOrderBy(['vencimiento'], 'expiration');

        // buttons
        $this->addButton($viewName, [
            'action' => 'generate-receipts',
            'confirm' => 'true',
            'icon' => 'fa-solid fa-wand-magic-sparkles',
            'label' => 'generate-receipts'
        ]);

        $this->addButton($viewName, [
            'action' => 'paid',
            'confirm' => 'true',
            'icon' => 'fa-solid fa-check',
            'label' => 'paid'
        ]);

        // disable columns
        $this->views[$viewName]->disableColumn('customer');
        $this->views[$viewName]->disableColumn('invoice');

        // settings
        $this->setSettings($viewName, 'modalInsert', 'generate-receipts');
    }

    /**
     * Run the actions that alter data before reading it.
     *
     * @param string $action
     *
     * @return bool
     */
    protected function execPreviousAction($action)
    {
        switch ($action) {
            case 'generate-accounting':
                return $this->generateAccountingAction();

            case 'generate-receipts':
                return $this->generateReceiptsAction();

            case 'new-refund':
                return $this->newRefundAction();

            case 'paid':
                return $this->paidAction();
        }

        return parent::execPreviousAction($action);
    }

    private function generateAccountingAction(): bool
    {
        $invoice = new FacturaCliente();
        if (false === $invoice->loadFromCode($this->request->query->get('code'))) {
            Tools::log()->warning('record-not-found');
            return true;
        } elseif (false === $this->permissions->allowUpdate) {
            Tools::log()->warning('not-allowed-modify');
            return true;
        } elseif (false === $this->validateFormToken()) {
            return true;
        }

        $generator = new InvoiceToAccounting();
        $generator->generate($invoice);
        if (empty($invoice->idasiento)) {
            Tools::log()->error('record-save-error');
            return true;
        }

        if ($invoice->save()) {
            Tools::log()->notice('record-updated-correctly');
            return true;
        }

        Tools::log()->error('record-save-error');
        return true;
    }

    private function generateReceiptsAction(): bool
    {
        $invoice = new FacturaCliente();
        if (false === $invoice->loadFromCode($this->request->query->get('code'))) {
            Tools::log()->warning('record-not-found');
            return true;
        } elseif (false === $this->permissions->allowUpdate) {
            Tools::log()->warning('not-allowed-modify');
            return true;
        } elseif (false === $this->validateFormToken()) {
            return true;
        }

        $generator = new ReceiptGenerator();
        $number = (int)$this->request->request->get('number', '0');
        if ($generator->generate($invoice, $number)) {
            $generator->update($invoice);
            $invoice->save();

            Tools::log()->notice('record-updated-correctly');
            return true;
        }

        Tools::log()->error('record-save-error');
        return true;
    }

    /**
     * Load data view procedure
     *
     * @param string $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        $mvn = $this->getMainViewName();

        switch ($viewName) {
            case self::VIEW_RECEIPTS:
                $where = [new DataBaseWhere('idfactura', $this->getViewModelValue($mvn, 'idfactura'))];
                $view->loadData('', $where);
                $this->checkReceiptsTotal($view->cursor);
                break;

            case self::VIEW_ACCOUNTS:
                $where = [new DataBaseWhere('idasiento', $this->getViewModelValue($mvn, 'idasiento'))];
                $view->loadData('', $where);
                break;

            case 'refunds':
                if ($this->getViewModelValue($mvn, 'idfacturarect')) {
                    $this->setSettings($viewName, 'active', false);
                    break;
                }
                $where = [new DataBaseWhere('idfacturarect', $this->getViewModelValue($mvn, 'idfactura'))];
                $view->loadData('', $where);
                break;

            default:
                parent::loadData($viewName, $view);
                break;
        }
    }

    protected function newRefundAction(): bool
    {
        $invoice = new FacturaCliente();
        if (false === $invoice->loadFromCode($this->request->request->get('idfactura'))) {
            Tools::log()->warning('record-not-found');
            return true;
        } elseif (false === $this->permissions->allowUpdate) {
            Tools::log()->warning('not-allowed-modify');
            return true;
        } elseif (false === $this->validateFormToken()) {
            return true;
        }

        $lines = [];
        foreach ($invoice->getLines() as $line) {
            $quantity = (float)$this->request->request->get('refund_' . $line->primaryColumnValue(), '0');
            if (!empty($quantity)) {
                $lines[] = $line;
            }
        }
        if (empty($lines)) {
            Tools::log()->warning('no-selected-item');
            return true;
        }

        $this->dataBase->beginTransaction();

        if ($invoice->editable) {
            foreach ($invoice->getAvailableStatus() as $status) {
                if ($status->editable || !$status->activo) {
                    continue;
                }

                $invoice->idestado = $status->idestado;
                if (false === $invoice->save()) {
                    Tools::log()->error('record-save-error');
                    $this->dataBase->rollback();
                    return true;
                }
            }
        }

        $newRefund = new FacturaCliente();
        $newRefund->loadFromData($invoice->toArray(), $invoice::dontCopyFields());
        $newRefund->codigorect = $invoice->codigo;
        $newRefund->codserie = $this->request->request->get('codserie');
        $newRefund->idfacturarect = $invoice->idfactura;
        $newRefund->nick = $this->user->nick;
        $newRefund->observaciones = $this->request->request->get('observaciones');
        $newRefund->setDate($this->request->request->get('fecha'), date(FacturaCliente::HOUR_STYLE));
        if (false === $newRefund->save()) {
            Tools::log()->error('record-save-error');
            $this->dataBase->rollback();
            return true;
        }

        foreach ($lines as $line) {
            $newLine = $newRefund->getNewLine($line->toArray());
            $newLine->cantidad = 0 - (float)$this->request->request->get('refund_' . $line->primaryColumnValue(), '0');
            $newLine->idlinearect = $line->idlinea;
            if (false === $newLine->save()) {
                Tools::log()->error('record-save-error');
                $this->dataBase->rollback();
                return true;
            }
        }

        $newLines = $newRefund->getLines();
        $newRefund->idestado = $invoice->idestado;
        if (false === Calculator::calculate($newRefund, $newLines, true)) {
            Tools::log()->error('record-save-error');
            $this->dataBase->rollback();
            return true;
        }

        // si la factura estaba pagada, marcamos los recibos de la nueva como pagados
        if ($invoice->pagada) {
            foreach ($newRefund->getReceipts() as $receipt) {
                $receipt->pagado = true;
                $receipt->save();
            }
        }

        // asignamos el estado de la factura
        $newRefund->idestado = $this->request->request->get('idestado');
        if (false === $newRefund->save()) {
            Tools::log()->error('record-save-error');
            $this->dataBase->rollback();
            return true;
        }

        $this->dataBase->commit();
        Tools::log()->notice('record-updated-correctly');
        $this->redirect($newRefund->url() . '&action=save-ok');
        return false;
    }

    /**
     * Adds a warning message if the sum of the receipts is not equal
     * to the total of the invoice.
     *
     * @param ReciboCliente[] $receipts
     */
    private function checkReceiptsTotal(array &$receipts): void
    {
        $total = 0.00;
        foreach ($receipts as $row) {
            $total += $row->importe;
        }

        $diff = $this->getModel()->total - $total;
        if (abs($diff) > 0.01) {
            Tools::log()->warning('invoice-receipts-diff', ['%diff%' => $diff]);
        }
    }

    private function paidAction(): bool
    {
        if (false === $this->permissions->allowUpdate) {
            Tools::log()->warning('not-allowed-modify');
            return true;
        } elseif (false === $this->validateFormToken()) {
            return true;
        }

        $codes = $this->request->request->getArray('codes');
        $model = $this->views[$this->active]->model;
        if (empty($codes) || empty($model)) {
            Tools::log()->warning('no-selected-item');
            return true;
        }

        foreach ($codes as $code) {
            if (false === $model->loadFromCode($code)) {
                Tools::log()->error('record-not-found');
                continue;
            }

            $model->nick = $this->user->nick;
            $model->pagado = true;
            if (false === $model->save()) {
                Tools::log()->error('record-save-error');
                return true;
            }
        }

        Tools::log()->notice('record-updated-correctly');
        $model->clear();
        return true;
    }
}
