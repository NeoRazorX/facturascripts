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
use FacturaScripts\Dinamic\Model\Proveedor as DinProveedor;

/**
 * A bank account of a provider.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class CuentaBancoProveedor extends ModelClass
{
    use ModelTrait;
    use IbanTrait;

    /** @var string */
    public $codcuenta;

    /** @var string */
    public $codproveedor;

    /** @var string */
    public $descripcion;

    /** @var bool */
    public $principal;

    /** @var string */
    public $swift;

    public function clear(): void
    {
        parent::clear();
        $this->principal = true;
    }

    public function getSubject(): DinProveedor
    {
        $provider = new DinProveedor();
        $provider->load($this->codproveedor);
        return $provider;
    }

    public function install(): string
    {
        // needed dependencies
        new Proveedor();

        return parent::install();
    }

    public static function primaryColumn(): string
    {
        return 'codcuenta';
    }

    public function save(): bool
    {
        if (false === parent::save()) {
            return false;
        }

        $this->updatePrimaryAccount();

        // si ha cambiado el iban, añadimos un aviso al log
        if (!empty($this->getOriginal('iban')) && $this->isDirty('iban')) {
            Tools::log(LogMessage::AUDIT_CHANNEL)->warning('supplier-iban-changed', [
                '%account%' => $this->codcuenta,
                '%old%' => $this->getOriginal('iban'),
                '%new%' => $this->iban,
                '%codproveedor%' => $this->codproveedor,
            ]);
        }

        return true;
    }

    public static function tableName(): string
    {
        return 'cuentasbcopro';
    }

    public function test(): bool
    {
        $this->codcuenta = Tools::noHtml($this->codcuenta);
        $this->descripcion = Tools::noHtml($this->descripcion);
        $this->swift = Tools::noHtml($this->swift);

        if (!empty($this->codcuenta) && false === is_numeric($this->codcuenta)) {
            Tools::log()->error('invalid-number', ['%number%' => $this->codcuenta]);
            return false;
        }

        return parent::test() && $this->testIBAN();
    }

    protected function updatePrimaryAccount(): void
    {
        if ($this->principal) {
            // If this account is the main one, we demarcate the others
            $sql = 'UPDATE ' . static::tableName()
                . ' SET principal = false'
                . ' WHERE codproveedor = ' . self::db()->var2str($this->codproveedor)
                . ' AND codcuenta <> ' . self::db()->var2str($this->codcuenta) . ';';
            self::db()->exec($sql);
        }
    }

    public function url(string $type = 'auto', string $list = 'List'): string
    {
        return empty($this->codproveedor) || $type == 'list' ? parent::url($type, $list) : $this->getSubject()->url();
    }

    protected function saveInsert(): bool
    {
        if (empty($this->codcuenta)) {
            $this->codcuenta = $this->newCode();
        }

        return parent::saveInsert();
    }
}
