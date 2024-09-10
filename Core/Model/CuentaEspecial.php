<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Cuenta as DinCuenta;
use FacturaScripts\Dinamic\Model\Subcuenta as DinSubcuenta;

/**
 * Allows to relate special accounts (SALES, for example)
 * with the real account or subaccount.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class CuentaEspecial extends Base\ModelClass
{
    use Base\ModelTrait;

    /** @var string */
    public $codcuentaesp;

    /** @var string */
    public $descripcion;

    public function getCuenta(string $codejercicio): Cuenta
    {
        // buscamos la primera cuenta relacionada
        $cuenta = new DinCuenta();
        $where = [
            new DataBaseWhere('codcuentaesp', $this->codcuentaesp),
            new DataBaseWhere('codejercicio', $codejercicio)
        ];
        if ($cuenta->loadFromCode('', $where)) {
            return $cuenta;
        }

        // si no hay una cuenta definida, devolvemos una vacía
        return new DinCuenta();
    }

    public function getSubcuenta(string $codejercicio): Subcuenta
    {
        // buscamos la primera subcuenta relacionada
        $subcuenta = new DinSubcuenta();
        $where = [
            new DataBaseWhere('codcuentaesp', $this->codcuentaesp),
            new DataBaseWhere('codejercicio', $codejercicio)
        ];
        if ($subcuenta->loadFromCode('', $where)) {
            return $subcuenta;
        }

        // buscamos la primera cuenta relacionada
        $cuenta = new DinCuenta();
        if ($cuenta->loadFromCode('', $where)) {
            // devolvemos su primera subcuenta
            foreach ($cuenta->getSubcuentas() as $sub) {
                return $sub;
            }
        }

        // si no hay una subcuenta definida, devolvemos una vacía
        return new DinSubcuenta();
    }

    public static function primaryColumn(): string
    {
        return 'codcuentaesp';
    }

    public function primaryDescriptionColumn(): string
    {
        return 'codcuentaesp';
    }

    public static function tableName(): string
    {
        return 'cuentasesp';
    }

    public function test(): bool
    {
        $this->codcuentaesp = Tools::noHtml($this->codcuentaesp);
        if ($this->codcuentaesp && 1 !== preg_match('/^[A-Z0-9_\+\.\-]{1,6}$/i', $this->codcuentaesp)) {
            Tools::log()->error(
                'invalid-alphanumeric-code',
                ['%value%' => $this->codcuentaesp, '%column%' => 'codcuentaesp', '%min%' => '1', '%max%' => '6']
            );
            return false;
        }

        $this->descripcion = Tools::noHtml($this->descripcion);
        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'ListCuenta?activetab=List'): string
    {
        return parent::url($type, $list);
    }
}
