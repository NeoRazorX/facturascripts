<?php
/**
 * Copyright (C) 2021-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
 */

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Base\AjaxForms\PurchasesController;
use FacturaScripts\Core\Base\Calculator;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Dinamic\Lib\Accounting\InvoiceToAccounting;
use FacturaScripts\Dinamic\Lib\ReceiptGenerator;
use FacturaScripts\Dinamic\Model\FacturaProveedor;
use FacturaScripts\Dinamic\Model\ReciboProveedor;

/**
 * Description of EditFacturaProveedor
 *
 * @author Carlos Garcia Gomez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class EditFacturaProveedor extends PurchasesController
{
    private const VIEW_ACCOUNTS = 'ListAsiento';
    private const VIEW_RECEIPTS = 'ListReciboProveedor';

    public function getModelClassName(): string
    {
        return 'FacturaProveedor';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'purchases';
        $data['title'] = 'invoice';
        $data['icon'] = 'fas fa-file-invoice-dollar';
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
    private function createViewsAccounting(string $viewName = self::VIEW_ACCOUNTS)
    {
        $this->addListView($viewName, 'Asiento', 'accounting-entries', 'fas fa-balance-scale');

        // buttons
        $this->addButton($viewName, [
            'action' => 'generate-accounting',
            'icon' => 'fas fa-magic',
            'label' => 'generate-accounting-entry'
        ]);

        // settings
        $this->setSettings($viewName, 'btnNew', false);
    }

    /**
     * Add view for refund invoice.
     */
    private function createViewsRefunds(string $viewName = 'refunds')
    {
        $this->addHtmlView($viewName, 'Tab/RefundFacturaProveedor', 'FacturaProveedor', 'refunds', 'fas fa-share-square');
    }

    /**
     * Add view for receipts of the invoice.
     *
     * @param string $viewName
     */
    private function createViewsReceipts(string $viewName = self::VIEW_RECEIPTS)
    {
        $this->addListView($viewName, 'ReciboProveedor', 'receipts', 'fas fa-dollar-sign');
        $this->views[$viewName]->addOrderBy(['vencimiento'], 'expiration');

        // buttons
        $this->addButton($viewName, [
            'action' => 'generate-receipts',
            'confirm' => 'true',
            'icon' => 'fas fa-magic',
            'label' => 'generate-receipts'
        ]);

        $this->addButton($viewName, [
            'action' => 'paid',
            'confirm' => 'true',
            'icon' => 'fas fa-check',
            'label' => 'paid'
        ]);

        // disable columns
        $this->views[$viewName]->disableColumn('supplier');
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
        $invoice = new FacturaProveedor();
        if (false === $invoice->loadFromCode($this->request->query->get('code'))) {
            $this->toolBox()->i18nLog()->warning('record-not-found');
            return true;
        } elseif (false === $this->permissions->allowUpdate) {
            $this->toolBox()->i18nLog()->warning('not-allowed-modify');
            return true;
        } elseif (false === $this->validateFormToken()) {
            return true;
        }

        $generator = new InvoiceToAccounting();
        $generator->generate($invoice);
        if (empty($invoice->idasiento)) {
            $this->toolBox()->i18nLog()->error('record-save-error');
            return true;
        }

        if ($invoice->save()) {
            $this->toolBox()->i18nLog()->notice('record-updated-correctly');
            return true;
        }

        $this->toolBox()->i18nLog()->error('record-save-error');
        return true;
    }

    private function generateReceiptsAction(): bool
    {
        $invoice = new FacturaProveedor();
        if (false === $invoice->loadFromCode($this->request->query->get('code'))) {
            $this->toolBox()->i18nLog()->warning('record-not-found');
            return true;
        } elseif (false === $this->permissions->allowUpdate) {
            $this->toolBox()->i18nLog()->warning('not-allowed-modify');
            return true;
        } elseif (false === $this->validateFormToken()) {
            return true;
        }

        $generator = new ReceiptGenerator();
        $number = (int)$this->request->request->get('number', '0');
        if ($generator->generate($invoice, $number)) {
            $generator->update($invoice);
            $invoice->save();

            $this->toolBox()->i18nLog()->notice('record-updated-correctly');
            return true;
        }

        $this->toolBox()->i18nLog()->error('record-save-error');
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
        $invoice = new FacturaProveedor();
        if (false === $invoice->loadFromCode($this->request->request->get('idfactura'))) {
            $this->toolBox()->i18nLog()->warning('record-not-found');
            return true;
        } elseif (false === $this->permissions->allowUpdate) {
            $this->toolBox()->i18nLog()->warning('not-allowed-modify');
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
            $this->toolBox()->i18nLog()->warning('no-selected-item');
            return true;
        }

        $this->dataBase->beginTransaction();

        if ($invoice->editable) {
            foreach ($invoice->getAvailableStatus() as $status) {
                if ($status->editable) {
                    continue;
                }

                $invoice->idestado = $status->idestado;
                if (false === $invoice->save()) {
                    $this->toolBox()->i18nLog()->error('record-save-error');
                    $this->dataBase->rollback();
                    return true;
                }
            }
        }

        $newRefund = new FacturaProveedor();
        $newRefund->loadFromData($invoice->toArray(), $invoice::dontCopyFields());
        $newRefund->codigorect = $invoice->codigo;
        $newRefund->codserie = $this->request->request->get('codserie');
        $newRefund->idfacturarect = $invoice->idfactura;
        $newRefund->nick = $this->user->nick;
        $newRefund->numproveedor = $this->request->request->get('numproveedor');
        $newRefund->observaciones = $this->request->request->get('observaciones');
        $newRefund->setDate($this->request->request->get('fecha'), date(FacturaProveedor::HOUR_STYLE));
        if (false === $newRefund->save()) {
            $this->toolBox()->i18nLog()->error('record-save-error');
            $this->dataBase->rollback();
            return true;
        }

        foreach ($lines as $line) {
            $newLine = $newRefund->getNewLine($line->toArray());
            $newLine->cantidad = 0 - (float)$this->request->request->get('refund_' . $line->primaryColumnValue(), '0');
            $newLine->idlinearect = $line->idlinea;
            if (false === $newLine->save()) {
                $this->toolBox()->i18nLog()->error('record-save-error');
                $this->dataBase->rollback();
                return true;
            }
        }

        $newLines = $newRefund->getLines();
        Calculator::calculate($newRefund, $newLines, false);
        $newRefund->idestado = $invoice->idestado;
        if (false === $newRefund->save()) {
            $this->toolBox()->i18nLog()->error('record-save-error');
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

        $this->dataBase->commit();
        $this->toolBox()->i18nLog()->notice('record-updated-correctly');
        $this->redirect($newRefund->url() . '&action=save-ok');
        return false;
    }

    /**
     * Adds a warning message if the sum of the receipts is not equal
     * to the total of the invoice.
     *
     * @param ReciboProveedor[] $receipts
     */
    private function checkReceiptsTotal(array &$receipts)
    {
        $total = 0.00;
        foreach ($receipts as $row) {
            $total += $row->importe;
        }

        $diff = $this->getModel()->total - $total;
        if (false === $this->toolBox()->utils()->floatcmp($diff, 0.0, FS_NF0, true)) {
            $this->toolBox()->i18nLog()->warning('invoice-receipts-diff', ['%diff%' => $diff]);
        }
    }

    private function paidAction(): bool
    {
        if (false === $this->permissions->allowUpdate) {
            $this->toolBox()->i18nLog()->warning('not-allowed-modify');
            return true;
        } elseif (false === $this->validateFormToken()) {
            return true;
        }

        $codes = $this->request->request->get('code');
        $model = $this->views[$this->active]->model;
        if (false === is_array($codes) || empty($model)) {
            $this->toolBox()->i18nLog()->warning('no-selected-item');
            return true;
        }

        foreach ($codes as $code) {
            if (false === $model->loadFromCode($code)) {
                $this->toolBox()->i18nLog()->error('record-not-found');
                continue;
            }

            $model->nick = $this->user->nick;
            $model->pagado = true;
            if (false === $model->save()) {
                $this->toolBox()->i18nLog()->error('record-save-error');
                return true;
            }
        }

        $this->toolBox()->i18nLog()->notice('record-updated-correctly');
        $model->clear();
        return true;
    }
}
