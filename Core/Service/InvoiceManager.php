<?php

namespace FacturaScripts\Core\Service;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Params\RefundInvoiceParams;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\Calculator;
use FacturaScripts\Dinamic\Model\FacturaCliente;

class InvoiceManager
{
    private static ?DataBase $dataBase = null;

    public static function createRefund(FacturaCliente $invoice, RefundInvoiceParams $params): ?FacturaCliente
    {
        if (empty($params->lines) && $params->includeAllLinesIfEmpty) {
            $params->lines = $invoice->getLines();
        }

        if (empty($params->lines)) {
            Tools::log()->warning('no-selected-item');
            return null;
        }

        self::dataBase()->beginTransaction();

        if (false === self::updateOriginalInvoiceStatus($invoice)) {
            self::dataBase()->rollback();
            return null;
        }

        $newRefund = self::createRefundInvoice($invoice, $params);
        if ($newRefund === null) {
            self::dataBase()->rollback();
            return null;
        }

        if (false === self::createRefundLines($newRefund, $params)) {
            self::dataBase()->rollback();
            return null;
        }

        if (false === self::calculateRefundTotals($newRefund)) {
            self::dataBase()->rollback();
            return null;
        }

        if ($invoice->pagada) {
            self::markReceiptsAsPaid($newRefund);
        }

        if (false === self::saveRefundState($newRefund, $invoice, $params)) {
            self::dataBase()->rollback();
            return null;
        }

        self::dataBase()->commit();
        Tools::log()->notice('record-updated-correctly');

        return $newRefund;
    }

    private static function dataBase(): DataBase
    {
        if (self::$dataBase === null) {
            self::$dataBase = new DataBase();
        }
        return self::$dataBase;
    }

    private static function createRefundInvoice(FacturaCliente $invoice, RefundInvoiceParams $params): ?FacturaCliente
    {
        $newRefund = new FacturaCliente();
        $newRefund->loadFromData($invoice->toArray(), $invoice::dontCopyFields());
        $newRefund->codigorect = $invoice->codigo;
        $newRefund->codserie = $params->codserie ?: $invoice->codserie;
        $newRefund->idfacturarect = $invoice->idfactura;
        $newRefund->nick = $params->nick;
        $newRefund->observaciones = $params->observaciones;

        $hour = $params->hora ?? date(Tools::HOUR_STYLE);
        if (false === $newRefund->setDate($params->fecha, $hour)) {
            Tools::log()->error('error-set-date');
            return null;
        }

        if (false === $newRefund->save()) {
            Tools::log()->error('record-save-error');
            return null;
        }

        return $newRefund;
    }

    private static function createRefundLines(FacturaCliente $newRefund, RefundInvoiceParams $params): bool
    {
        foreach ($params->lines as $line) {
            $quantity = $line->cantidad;

            $newLine = $newRefund->getNewLine($line->toArray());
            $newLine->cantidad = 0 - $quantity;
            $newLine->idlinearect = $line->idlinea;

            if (false === $newLine->save()) {
                Tools::log()->error('record-save-error');
                return false;
            }
        }

        return true;
    }

    private static function calculateRefundTotals(FacturaCliente $newRefund): bool
    {
        $newLines = $newRefund->getLines();
        if (false === Calculator::calculate($newRefund, $newLines, true)) {
            Tools::log()->error('record-save-error');
            return false;
        }

        return true;
    }

    private static function markReceiptsAsPaid(FacturaCliente $newRefund): void
    {
        foreach ($newRefund->getReceipts() as $receipt) {
            $receipt->pagado = true;
            $receipt->save();
        }
    }

    private static function saveRefundState(FacturaCliente $newRefund, FacturaCliente $originalInvoice, RefundInvoiceParams $params): bool
    {
        $newRefund->idestado = $params->idestado ?: $originalInvoice->idestado;
        if (false === $newRefund->save()) {
            Tools::log()->error('record-save-error');
            return false;
        }

        return true;
    }

    private static function updateOriginalInvoiceStatus(FacturaCliente $invoice): bool
    {
        if (false === $invoice->editable) {
            return true;
        }

        foreach ($invoice->getAvailableStatus() as $status) {
            if ($status->editable || !$status->activo) {
                continue;
            }

            $invoice->idestado = $status->idestado;
            if (false === $invoice->save()) {
                Tools::log()->error('record-save-error');
                return false;
            }
        }

        return true;
    }
}
