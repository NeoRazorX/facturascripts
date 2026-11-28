<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Core\Lib\Accounting;

use FacturaScripts\Core\DataSrc\Divisas;
use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Asiento;
use FacturaScripts\Dinamic\Model\Partida;
use FacturaScripts\Dinamic\Model\Subcuenta;

/**
 * Base class for creation of accounting processes
 *
 * Los asientos se generan siempre en la divisa de la empresa. Si el documento está en otra
 * divisa, la subclase asigna a $exchangeRate la tasa de conversión (ver exchangeRate()) y
 * getBasicLine() convierte todos los importes de las partidas al crearlas.
 *
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 */
abstract class AccountingClass extends AccountingAccounts
{
    /** @var ModelClass */
    protected $document;

    /**
     * Tasa para pasar los importes del documento a la divisa de la empresa:
     * importe en la divisa de la empresa = importe del documento / $exchangeRate.
     * Con 1.0 (valor por defecto) los importes no se convierten.
     *
     * @var float
     */
    protected $exchangeRate = 1.0;

    /**
     * Devuelve la tasa para pasar los importes de un documento a la divisa de la empresa.
     * Las tasas de las divisas son respecto al euro (totaleuros = total / tasaconv), así que
     * la tasa es la del documento entre la de la divisa de la empresa; si la empresa usa el
     * euro, es directamente el tasaconv del documento.
     *
     * Devuelve 1.0 (sin conversión) si el documento está en la divisa de la empresa, no tiene
     * divisa o su tasa está vacía, es 0 o negativa, para no dividir por cero.
     *
     * @param object $document documento con coddivisa y tasaconv (factura, albarán...)
     *
     * @return float
     */
    public static function exchangeRate($document): float
    {
        $docRate = (float)($document->tasaconv ?? 0.0);
        if (empty($document->coddivisa) || $docRate <= 0.0) {
            return 1.0;
        }

        $default = Divisas::default();
        if ($document->coddivisa === $default->coddivisa) {
            return 1.0;
        }

        $companyRate = (float)($default->tasaconv ?? 0.0);
        return $companyRate > 0.0 ? $docRate / $companyRate : $docRate;
    }

    /**
     * Method to launch the accounting process
     *
     * @param ModelClass $model
     */
    public function generate($model)
    {
        $this->document = $model;
        $this->exercise->idempresa = $model->idempresa ?? Tools::settings('default', 'idempresa');
    }

    /**
     * Add a standard line to the accounting entry based on the reported sub-account
     *
     * @param Asiento $accountEntry
     * @param Subcuenta $subaccount
     * @param bool $isDebit
     * @param float $amount
     *
     * @return bool
     */
    protected function addBasicLine($accountEntry, $subaccount, $isDebit, $amount = null): bool
    {
        return $this->getBasicLine($accountEntry, $subaccount, $isDebit, $amount)->save();
    }

    /**
     * Add a group of lines from array of subaccounts/amount.
     *
     * @param Asiento $accountEntry
     * @param array $totals
     * @param bool $isDebit
     * @param Subcuenta $counterpart
     * @param string $accountError
     * @param string $saveError
     *
     * @return bool
     */
    protected function addLinesFromTotals($accountEntry, $totals, $isDebit, $counterpart, $accountError, $saveError): bool
    {
        foreach ($totals as $code => $total) {
            $subaccount = $this->getSubAccount($code);
            if (empty($subaccount->codsubcuenta)) {
                Tools::log()->warning($accountError, [
                    '%subaccount%' => $code,
                    '%exercise%' => $this->exercise->codejercicio
                ]);
                return false;
            }

            $line = $this->getBasicLine($accountEntry, $subaccount, $isDebit, $total);

            if (!empty($counterpart)) {
                $line->setCounterpart($counterpart);
            }

            if (false === $line->save()) {
                Tools::log()->warning($saveError);
                return false;
            }
        }

        return true;
    }

    /**
     * Add a line of taxes to the accounting entry based on the sub-account
     * and values reported
     *
     * @param Asiento $accountEntry
     * @param Subcuenta $subaccount
     * @param Subcuenta $counterpart
     * @param bool $isDebit
     * @param array $values
     *
     * @return bool
     */
    protected function addSurchargeLine($accountEntry, $subaccount, $counterpart, $isDebit, $values): bool
    {
        if (empty($values['totalrecargo'])) {
            return true;
        }

        // add basic data
        $line = $this->getBasicLine($accountEntry, $subaccount, $isDebit, $values['totalrecargo']);

        // counterpart?
        if (!empty($counterpart)) {
            $line->setCounterpart($counterpart);
        }

        // add tax register data
        $line->baseimponible = $this->toCompanyCurrency((float)$values['neto']);
        $line->iva = 0;
        $line->recargo = (float)$values['recargo'];
        $line->cifnif = $this->document->cifnif;
        $line->codserie = $this->document->codserie;
        $line->documento = $this->document->codigo;
        $line->factura = $this->document->numero;

        // save new line
        return $line->save();
    }

    /**
     * Add a line of taxes to the accounting entry based on the sub-account
     * and values reported
     *
     * @param Asiento $accountEntry
     * @param Subcuenta $subaccount
     * @param Subcuenta $counterpart
     * @param bool $isDebit
     * @param array $values
     *
     * @return bool
     */
    protected function addTaxLine($accountEntry, $subaccount, $counterpart, $isDebit, $values): bool
    {
        // add basic data
        $line = $this->getBasicLine($accountEntry, $subaccount, $isDebit, $values['totaliva']);

        // counterpart?
        if (!empty($counterpart)) {
            $line->setCounterpart($counterpart);
        }

        // add tax register data
        $line->baseimponible = $this->toCompanyCurrency((float)$values['neto']);
        $line->iva = (float)$values['iva'];
        $line->recargo = 0;
        $line->cifnif = $this->document->cifnif;
        $line->codserie = $this->document->codserie;
        $line->documento = $this->document->codigo;
        $line->factura = $this->document->numero;

        // save new line
        return $line->save();
    }

    /**
     * Obtain a standard line to the accounting entry based on the reported sub-account
     *
     * El importe se indica en la divisa del documento y aquí se convierte a la divisa de la
     * empresa con $exchangeRate. Si hay conversión, la partida guarda en coddivisa y tasaconv
     * la divisa del documento y la tasa usada, para dejar constancia del importe original
     * (importe original = importe de la partida * tasaconv).
     *
     * @param Asiento $accountEntry
     * @param Subcuenta $subaccount
     * @param bool $isDebit
     * @param float $amount importe en la divisa del documento; si es null, el total del documento
     *
     * @return Partida
     */
    protected function getBasicLine($accountEntry, $subaccount, $isDebit, $amount = null)
    {
        $line = $accountEntry->getNewLine();
        $line->setAccount($subaccount);

        if ($this->exchangeRate != 1.0) {
            $line->coddivisa = $this->document->coddivisa ?? $line->coddivisa;
            $line->tasaconv = $this->exchangeRate;
        }

        $total = $this->toCompanyCurrency(($amount === null) ? $this->document->total : $amount);
        if ($isDebit) {
            $line->debe = max($total, 0);
            $line->haber = $total < 0 ? abs($total) : 0;
            return $line;
        }

        $line->debe = $total < 0 ? abs($total) : 0;
        $line->haber = max($total, 0);
        return $line;
    }

    /**
     * Convierte un importe de la divisa del documento a la divisa de la empresa, redondeado
     * a los decimales configurados. Sin conversión ($exchangeRate = 1.0) lo devuelve tal cual.
     *
     * @param float|int|null $amount
     *
     * @return float
     */
    protected function toCompanyCurrency($amount): float
    {
        if ($this->exchangeRate == 1.0) {
            return (float)$amount;
        }

        return Tools::round((float)$amount / $this->exchangeRate);
    }
}
