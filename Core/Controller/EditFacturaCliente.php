<?php
/**
 * Copyright (C) 2021-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
 */

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\PDF\Dynamic\ModelTableHelper;
use FacturaScripts\Core\Lib\PDF\Dynamic\PDFBuilder;
use FacturaScripts\Core\Lib\PDF\Dynamic\PDFPreviewTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\Empresa;
use FacturaScripts\Dinamic\Lib\Accounting\InvoiceToAccounting;
use FacturaScripts\Dinamic\Lib\AjaxForms\SalesController;
use FacturaScripts\Dinamic\Lib\Calculator;
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
    use PDFPreviewTrait;

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
    protected function createViews(): void
    {
        parent::createViews();
        $this->loadPdfViewerAssets();

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
        $this->addListView($viewName, 'Asiento', 'accounting-entries', 'fa-solid fa-balance-scale')
            ->addSearchFields(['concepto'])
            ->addOrderBy(['fecha'], 'date', 1);

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
            ->addSearchFields(['observaciones'])
            ->addOrderBy(['vencimiento'], 'expiration')
            ->addOrderBy(['importe'], 'amount');

        // buttons
        $this->addButton($viewName, [
            'action' => 'generate-receipts',
            'confirm' => 'true',
            'icon' => 'fa-solid fa-wand-magic-sparkles',
            'label' => 'generate-receipts'
        ]);

        $this->addButton($viewName, [
            'action' => 'paid',
            'color' => 'outline-success',
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
            case 'pdf-preview':
                return $this->pdfPreviewAction();

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

    protected function buildPdf(): PDFBuilder
    {
        $invoice = new FacturaCliente();
        $invoice->load($this->request->queryOrInput('code'));
        $i18n = Tools::lang();

        $empresa = new Empresa();
        $empresa->load($invoice->idempresa);

        // cabecera general + datos del documento, como insertBusinessDocHeader()
        $subject = $invoice->getSubject();
        $doc = PDFBuilder::create()
            ->setTitle($invoice->codigo)
            ->addDocumentHeader($empresa)
            ->addTitle($i18n->trans('invoice') . ': ' . $invoice->codigo)
            ->addHtml('<hr/>')
            ->addParallelTable([
                $i18n->trans('customer') => $invoice->nombrecliente,
                $i18n->trans('date') => $invoice->fecha,
                $i18n->trans('address') => $this->pdfDocAddress($invoice),
                $i18n->trans('code') => $invoice->codigo,
                $subject->tipoidfiscal ?: $i18n->trans('cifnif') => $invoice->cifnif,
                $i18n->trans('number') => $invoice->numero,
                $i18n->trans('serie') => $invoice->getSerie()->descripcion,
            ])
            ->addSpacer(3);

        // líneas del documento, como insertBusinessDocBody()
        $titles = [
            'referencia' => $i18n->trans('reference') . ' - ' . $i18n->trans('description'),
            'cantidad' => $i18n->trans('quantity'),
            'pvpunitario' => $i18n->trans('price'),
            'dtopor' => $i18n->trans('dto'),
            'dtopor2' => $i18n->trans('dto-2'),
            'pvptotal' => $i18n->trans('net'),
            'iva' => $i18n->trans('tax'),
            'recargo' => $i18n->trans('re'),
            'irpf' => $i18n->trans('irpf'),
        ];
        $alignments = array_fill_keys(array_keys($titles), 'right');
        $alignments['referencia'] = 'left';

        $rows = [];
        foreach ($invoice->getLines() as $line) {
            $rows[] = [
                'referencia' => empty($line->referencia) ? $line->descripcion : $line->referencia . ' - ' . $line->descripcion,
                'cantidad' => Tools::number($line->cantidad),
                'pvpunitario' => Tools::number($line->pvpunitario),
                'dtopor' => Tools::number($line->dtopor) . '%',
                'dtopor2' => Tools::number($line->dtopor2) . '%',
                'pvptotal' => Tools::number($line->pvptotal),
                'iva' => Tools::number($line->iva) . '%',
                'recargo' => Tools::number($line->recargo) . '%',
                'irpf' => Tools::number($line->irpf) . '%',
            ];
        }

        $zero = Tools::number(0);
        ModelTableHelper::removeEmptyColumns($rows, $titles, $alignments, [$zero, $zero . '%']);
        $doc->addTable(array_map('array_values', $rows), array_values($titles), array_values($alignments));

        // totales, como insertBusinessDocFooter()
        $totalTitles = [
            'net' => $i18n->trans('net'),
            'taxes' => $i18n->trans('taxes'),
            'totalSurcharge' => $i18n->trans('re'),
            'totalIrpf' => $i18n->trans('retention'),
            'totalSupplied' => $i18n->trans('supplied-amount'),
            'total' => $i18n->trans('total'),
        ];
        $totalAlignments = array_fill_keys(array_keys($totalTitles), 'right');
        $totalRows = [[
            'net' => Tools::number($invoice->neto),
            'taxes' => Tools::number($invoice->totaliva),
            'totalSurcharge' => Tools::number($invoice->totalrecargo),
            'totalIrpf' => Tools::number(0 - $invoice->totalirpf),
            'totalSupplied' => Tools::number($invoice->totalsuplidos),
            'total' => Tools::number($invoice->total),
        ]];
        ModelTableHelper::removeEmptyColumns($totalRows, $totalTitles, $totalAlignments, [$zero]);
        $doc->addSpacer(3)
            ->addTable(array_map('array_values', $totalRows), array_values($totalTitles), array_values($totalAlignments));

        // recibos, como insertInvoiceReceipts()
        $receiptRows = [];
        foreach ($invoice->getReceipts() as $receipt) {
            $receiptRows[] = [
                $receipt->numero,
                Tools::number($receipt->importe),
                Tools::date($receipt->vencimiento),
                $i18n->trans($receipt->pagado ? 'paid' : 'unpaid'),
            ];
        }

        if (false === empty($receiptRows)) {
            $doc->addSpacer(3)
                ->addTitle($i18n->trans('receipts'), 3)
                ->addTable($receiptRows, [
                    $i18n->trans('receipt'),
                    $i18n->trans('amount'),
                    $i18n->trans('expiration'),
                    $i18n->trans('status'),
                ], ['left', 'right', 'right', 'right']);
        }

        if (false === empty($invoice->observaciones)) {
            $doc->addSpacer(3)
                ->addTitle($i18n->trans('observations'), 3)
                ->addText($invoice->observaciones);
        }

        // aviso de factura no emitida, como el export original
        if ($invoice->editable) {
            $doc->addWatermarkText($i18n->trans('sketch-invoice-warning'));
        }

        return $doc->addPageFooter('1 / 1', $i18n->trans('generated-at', ['%when%' => Tools::dateTime()]));
    }

    private function pdfDocAddress(FacturaCliente $invoice): string
    {
        $address = $invoice->direccion ?? '';
        if (!empty($invoice->codpostal)) {
            $address .= empty($address) ? $invoice->codpostal : ', ' . $invoice->codpostal;
        }
        if (!empty($invoice->ciudad)) {
            $address .= empty($address) ? $invoice->ciudad : ', ' . $invoice->ciudad;
        }
        if (!empty($invoice->provincia)) {
            $address .= ' (' . $invoice->provincia . ')';
        }

        return $address;
    }

    private function generateAccountingAction(): bool
    {
        $invoice = new FacturaCliente();
        if (false === $invoice->load($this->request->query('code'))) {
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
        if (false === $invoice->load($this->request->query('code'))) {
            Tools::log()->warning('record-not-found');
            return true;
        } elseif (false === $this->permissions->allowUpdate) {
            Tools::log()->warning('not-allowed-modify');
            return true;
        } elseif (false === $this->validateFormToken()) {
            return true;
        }

        // comprobamos si se han recibido importes específicos
        $amounts = [];
        for ($i = 1; $i <= 5; $i++) {
            $amount = $this->request->input('amount_' . $i);
            if ($amount !== null && $amount !== '') {
                $amountFloat = (float)$amount;
                if ($amountFloat > 0) {
                    $amounts[] = $amountFloat;
                }
            }
        }

        // si hay importes específicos, creamos los recibos manualmente
        if (!empty($amounts)) {
            return $this->generateReceiptsWithAmounts($invoice, $amounts);
        }

        // si no hay importes específicos, usamos el generador automático
        $generator = new ReceiptGenerator();
        $number = (int)$this->request->input('number', '0');
        if ($generator->generate($invoice, $number)) {
            $generator->update($invoice);
            $invoice->save();

            Tools::log()->notice('record-updated-correctly');
            return true;
        }

        Tools::log()->error('record-save-error');
        return true;
    }

    private function generateReceiptsWithAmounts(FacturaCliente $invoice, array $amounts): bool
    {
        // creamos los recibos con los importes especificados
        $numero = count($invoice->getReceipts()) + 1;

        foreach ($amounts as $amount) {
            $receipt = $invoice->getNewReceipt($numero, [
                'importe' => $amount,
                'nick' => $this->user->nick
            ]);

            $receipt->disableInvoiceUpdate(true);
            if (false === $receipt->save()) {
                Tools::log()->error('record-save-error');
                return true;
            }

            $numero++;
        }

        // actualizamos la factura
        $generator = new ReceiptGenerator();
        $generator->update($invoice);
        $invoice->save();

        Tools::log()->notice('record-updated-correctly');
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
                $where = [Where::eq('idfactura', $this->getViewModelValue($mvn, 'idfactura'))];
                $view->loadData('', $where);
                if (empty($view->query)) {
                    $this->checkReceiptsTotal($view->cursor);
                }
                break;

            case self::VIEW_ACCOUNTS:
                $where = [Where::eq('idasiento', $this->getViewModelValue($mvn, 'idasiento'))];
                $view->loadData('', $where);
                break;

            case 'refunds':
                if ($this->getViewModelValue($mvn, 'idfacturarect')) {
                    $this->setSettings($viewName, 'active', false);
                    break;
                }
                $where = [Where::eq('idfacturarect', $this->getViewModelValue($mvn, 'idfactura'))];
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
        if (false === $invoice->load($this->request->input('idfactura'))) {
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
            $quantity = (float)$this->request->input('refund_' . $line->id(), '0');
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
                break;
            }
        }

        $newRefund = new FacturaCliente();
        $newRefund->loadFromData($invoice->toArray(), $invoice::dontCopyFields());
        $newRefund->codigorect = $invoice->codigo;
        $newRefund->codserie = $this->request->input('codserie');
        $newRefund->idfacturarect = $invoice->idfactura;
        $newRefund->nick = $this->user->nick;
        $newRefund->observaciones = $this->request->input('observaciones');
        $newRefund->setDate($this->request->input('fecha'), date(Tools::HOUR_STYLE));
        if (false === $newRefund->save()) {
            Tools::log()->error('record-save-error');
            $this->dataBase->rollback();
            return true;
        }

        foreach ($lines as $line) {
            $newLine = $newRefund->getNewLine($line->toArray());
            $newLine->cantidad = 0 - (float)$this->request->input('refund_' . $line->id(), '0');
            $newLine->idlinearect = $line->idlinea;
            if (false === $newLine->save()) {
                Tools::log()->error('record-save-error');
                $this->dataBase->rollback();
                return true;
            }
        }

        $newLines = $newRefund->getLines();
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
        $newRefund->idestado = $this->request->input('idestado');
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
