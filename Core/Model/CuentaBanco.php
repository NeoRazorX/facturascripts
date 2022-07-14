<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

/**
 * A bank account of the company itself.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class CuentaBanco extends Base\BankAccount
{

    use Base\ModelTrait;

    /**
     * @var string
     */
    public $codsubcuenta;

    /**
     * @var string
     */
    public $codsubcuentagasto;

    /**
     * Foreign Key with Empresas table.
     *
     * @var int
     */
    public $idempresa;

    /**
     * @var string
     */
    public $sufijosepa;

    public function clear()
    {
        parent::clear();
        $this->sufijosepa = '000';
    }

    public function install(): string
    {
        // needed dependencies
        new Empresa();

        return parent::install();
    }

    public static function tableName(): string
    {
        return 'cuentasbanco';
    }

    public function test(): bool
    {
        if (empty($this->idempresa)) {
            $this->idempresa = self::toolBox()::appSettings()::get('default', 'idempresa');
        }

        $this->codsubcuenta = self::toolBox()::utils()::noHtml($this->codsubcuenta);
        $this->codsubcuentagasto = self::toolBox()::utils()::noHtml($this->codsubcuentagasto);
        $this->sufijosepa = self::toolBox()::utils()::noHtml($this->sufijosepa);
        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'ListFormaPago?activetab=List'): string
    {
        return parent::url($type, $list);
    }
}
