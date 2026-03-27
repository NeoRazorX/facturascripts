<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Asiento;
use FacturaScripts\Dinamic\Model\Partida;
use FacturaScripts\Dinamic\Model\Subcuenta;

/**
 * Clase base para la generación de asientos contables
 *
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 */
abstract class AccountingClass extends AccountingAccounts
{
    /** @var ModelClass */
    protected $document;

    /**
     * Genera los asientos contables para una factura.
     *
     * @param ModelClass $model
     */
    public function generate($model)
    {
        $this->document = $model;
        $this->exercise->idempresa = $model->idempresa ?? Tools::settings('default', 'idempresa');
    }

    /**
     * Añade una línea básica al asiento contable en base a la subcuenta reportada
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
     * Añade un grupo de líneas desde un array de subcuentas/importes.
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
                Tools::log()->warning($accountError);
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
     * Añade una línea de impuestos al asiento contable en base a la subcuenta
     * y valores informados
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

        // añadir datos básicos
        $line = $this->getBasicLine($accountEntry, $subaccount, $isDebit, $values['totalrecargo']);

        // counterpart?
        if (!empty($counterpart)) {
            $line->setCounterpart($counterpart);
        }

        // añadir datos de registro de impuestos
        $line->baseimponible = (float)$values['neto'];
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
     * Añade una línea de impuestos al asiento contable en base a la subcuenta
     * y valores informados
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
        // obtener una linea
        $line = $this->getBasicLine($accountEntry, $subaccount, $isDebit, $values['totaliva']);

        // counterpart?
        if (!empty($counterpart)) {
            $line->setCounterpart($counterpart);
        }

        // añadir datos de registro de impuestos
        $line->baseimponible = (float)$values['neto'];
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
     * Devuelve una línea estándar para el asiento contable en base a la subcuenta reportada
     *
     * @param Asiento $accountEntry
     * @param Subcuenta $subaccount
     * @param bool $isDebit
     * @param float $amount
     *
     * @return Partida
     */
    protected function getBasicLine($accountEntry, $subaccount, $isDebit, $amount = null)
    {
        $line = $accountEntry->getNewLine();
        $line->setAccount($subaccount);

        $total = ($amount === null) ? $this->document->total : $amount;
        if ($isDebit) {
            $line->debe = max($total, 0);
            $line->haber = $total < 0 ? abs($total) : 0;
            return $line;
        }

        $line->debe = $total < 0 ? abs($total) : 0;
        $line->haber = max($total, 0);
        return $line;
    }
}
