<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\DataSrc\FormasPago;
use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Core\Model\Base\ModelTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\CuentaBanco as DinCuentaBanco;

/**
 * Payment method of an invoice, delivery note, order or estimation.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class FormaPago extends ModelClass
{
    use ModelTrait;

    /** @var bool */
    public $activa;

    /** @var string */
    public $codcuentabanco;

    /** @var string */
    public $codpago;

    /** @var string */
    public $descripcion;

    /** @var bool */
    public $domiciliado;

    /** @var int */
    public $idempresa;

    /** @var bool */
    public $imprimir;

    /** @var bool */
    public $pagado;

    /** @var int */
    public $plazovencimiento;

    /** @var string */
    public $tipovencimiento;

    public function clear()
    {
        parent::clear();
        $this->activa = true;
        $this->domiciliado = false;
        $this->imprimir = true;
        $this->plazovencimiento = 0;
        $this->tipovencimiento = 'days';
    }

    public function delete(): bool
    {
        if ($this->isDefault()) {
            Tools::log()->warning('cant-delete-default-payment-method');
            return false;
        }

        if (false === parent::delete()) {
            return false;
        }

        // limpiamos la caché
        FormasPago::clear();
        return true;
    }

    /**
     * Return the bank account.
     *
     * @return DinCuentaBanco
     */
    public function getBankAccount(): CuentaBanco
    {
        $bank = new DinCuentaBanco();
        $bank->loadFromCode($this->codcuentabanco);
        return $bank;
    }

    public function getSubcuenta(string $codejercicio, bool $create): Subcuenta
    {
        return $this->getBankAccount()->getSubcuenta($codejercicio, $create);
    }

    public function getSubcuentaGastos(string $codejercicio, bool $create): Subcuenta
    {
        return $this->getBankAccount()->getSubcuentaGastos($codejercicio, $create);
    }

    /**
     * Returns the date with the expiration term applied.
     *
     * @param string $date
     *
     * @return string
     */
    public function getExpiration(string $date): string
    {
        return Tools::date($date . ' +' . $this->plazovencimiento . ' ' . $this->tipovencimiento);
    }

    public function install(): string
    {
        // needed dependencies
        new CuentaBanco();

        return parent::install();
    }

    /**
     * Returns True if this is the default payment method.
     *
     * @return bool
     */
    public function isDefault(): bool
    {
        return $this->codpago === Tools::settings('default', 'codpago');
    }

    public static function primaryColumn(): string
    {
        return 'codpago';
    }

    public function save(): bool
    {
        if (false === parent::save()) {
            return false;
        }

        // limpiamos la caché
        FormasPago::clear();
        return true;
    }

    public static function tableName(): string
    {
        return 'formaspago';
    }

    public function test(): bool
    {
        $this->codpago = Tools::noHtml($this->codpago);
        $this->descripcion = Tools::noHtml($this->descripcion);

        if ($this->codpago && 1 !== preg_match('/^[A-Z0-9_\+\.\-\s]{1,10}$/i', $this->codpago)) {
            Tools::log()->error(
                'invalid-alphanumeric-code',
                ['%value%' => $this->codpago, '%column%' => 'codpago', '%min%' => '1', '%max%' => '10']
            );
            return false;
        } elseif ($this->plazovencimiento < 0) {
            Tools::log()->warning('number-expiration-invalid');
            return false;
        }

        if (empty($this->idempresa)) {
            $this->idempresa = Tools::settings('default', 'idempresa');
        }

        return parent::test();
    }

    protected function saveInsert(array $values = []): bool
    {
        if (empty($this->codpago)) {
            $this->codpago = (string)$this->newCode();
        }

        return parent::saveInsert($values);
    }
}
