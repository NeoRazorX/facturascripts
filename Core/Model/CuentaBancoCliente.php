<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2014-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Model\Base\IbanTrait;
use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Cliente as DinCliente;

/**
 * A bank account of a client.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class CuentaBancoCliente extends ModelClass
{
    use ModelTrait;
    use IbanTrait;

    /** @var string */
    public $codcliente;

    /** @var string */
    public $codcuenta;

    /** @var string */
    public $descripcion;

    /** @var string */
    public $fmandato;

    /** @var string */
    public $mandato;

    /** @var bool */
    public $principal;

    /** @var string */
    public $swift;

    public function clear(): void
    {
        parent::clear();
        $this->fmandato = Tools::date();
        $this->principal = true;
    }

    public function getSubject(): DinCliente
    {
        $customer = new DinCliente();
        $customer->load($this->codcliente);
        return $customer;
    }

    public function install(): string
    {
        // needed dependencies
        new DinCliente();

        return parent::install();
    }

    public static function primaryColumn(): string
    {
        return 'codcuenta';
    }

    public function test(): bool
    {
        $this->codcuenta = Tools::noHtml($this->codcuenta);
        $this->descripcion = Tools::noHtml($this->descripcion);
        $this->mandato = Tools::noHtml($this->mandato);
        $this->swift = Tools::noHtml($this->swift);

        if (!empty($this->codcuenta) && false === is_numeric($this->codcuenta)) {
            Tools::log()->error('invalid-number', ['%number%' => $this->codcuenta]);
            return false;
        }

        return parent::test() && $this->testIBAN();
    }

    public function save(): bool
    {
        if (empty($this->mandato)) {
            $this->mandato = empty($this->codcuenta) ?
                max($this->newCode('mandato'), $this->newCode('codcuenta')) :
                $this->codcuenta;
        }

        if (false === parent::save()) {
            return false;
        }

        $this->updatePrimaryAccount();

        return true;
    }

    public static function tableName(): string
    {
        return 'cuentasbcocli';
    }

    protected function updatePrimaryAccount(): void
    {
        if ($this->principal) {
            // If this account is the main one, we demarcate the others
            $sql = 'UPDATE ' . static::tableName()
                . ' SET principal = false'
                . ' WHERE codcliente = ' . self::db()->var2str($this->codcliente)
                . ' AND codcuenta != ' . self::db()->var2str($this->codcuenta) . ';';
            self::db()->exec($sql);
        }
    }

    public function url(string $type = 'auto', string $list = 'List'): string
    {
        return empty($this->codcliente) || $type == 'list' ? parent::url($type, $list) : $this->getSubject()->url();
    }

    protected function saveInsert(): bool
    {
        if (empty($this->codcuenta)) {
            $this->codcuenta = $this->newCode();
        }

        return parent::saveInsert();
    }
}
