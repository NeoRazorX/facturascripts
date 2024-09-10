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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Base\BankAccount;
use FacturaScripts\Core\Model\Base\ModelTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\CuentaEspecial as DinCuentaEspecial;
use FacturaScripts\Dinamic\Model\Subcuenta as DinSubcuenta;

/**
 * A bank account of the company itself.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class CuentaBanco extends BankAccount
{
    use ModelTrait;

    const SPECIAL_ACCOUNT = 'CAJA';

    /** @var bool */
    public $activa;

    /** @var string */
    public $codsubcuenta;

    /** @var string */
    public $codsubcuentagasto;

    /** @var int */
    public $idempresa;

    /** @var string */
    public $sufijosepa;

    public function clear()
    {
        parent::clear();
        $this->activa = true;
        $this->sufijosepa = '000';
    }

    public function getSubcuenta(string $codejercicio, bool $create): Subcuenta
    {
        // si no hay una subcuenta definida, devolvemos la subcuenta especial de CAJA
        if (empty($this->codsubcuenta)) {
            $especial = new DinCuentaEspecial();
            if ($especial->loadFromCode(static::SPECIAL_ACCOUNT)) {
                return $especial->getSubcuenta($codejercicio);
            }
        }

        // buscamos la subcuenta
        $subcuenta = new DinSubcuenta();
        $where = [
            new DataBaseWhere('codsubcuenta', $this->codsubcuenta),
            new DataBaseWhere('codejercicio', $codejercicio),
        ];
        if ($subcuenta->loadFromCode('', $where)) {
            return $subcuenta;
        }

        // no la hemos encontrado, ¿La creamos?
        if ($create) {
            // buscamos la cuenta especial
            $especial = new DinCuentaEspecial();
            if (false === $especial->loadFromCode(static::SPECIAL_ACCOUNT)) {
                return new DinSubcuenta();
            }

            // creamos la subcuenta
            return $especial->getCuenta($codejercicio)->createSubcuenta($this->codsubcuenta, $this->descripcion);
        }

        // devolvemos una vacía
        return new DinSubcuenta();
    }

    public function getSubcuentaGastos(string $codejercicio, bool $create): Subcuenta
    {
        // si no hay una subcuenta definida, devolvemos la subcuenta especial de CAJA
        if (empty($this->codsubcuentagasto)) {
            $especial = new DinCuentaEspecial();
            if ($especial->loadFromCode(static::SPECIAL_ACCOUNT)) {
                return $especial->getSubcuenta($codejercicio);
            }
        }

        // buscamos la subcuenta
        $subcuenta = new DinSubcuenta();
        $where = [
            new DataBaseWhere('codsubcuenta', $this->codsubcuentagasto),
            new DataBaseWhere('codejercicio', $codejercicio),
        ];
        if ($subcuenta->loadFromCode('', $where)) {
            return $subcuenta;
        }

        // no la hemos encontrado, ¿La creamos?
        if ($create) {
            // buscamos la cuenta especial
            $especial = new DinCuentaEspecial();
            if (false === $especial->loadFromCode(static::SPECIAL_ACCOUNT)) {
                return new DinSubcuenta();
            }

            // creamos la subcuenta
            return $especial->getCuenta($codejercicio)->createSubcuenta($this->codsubcuentagasto, $this->descripcion);
        }

        // devolvemos una vacía
        return new DinSubcuenta();
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
            $this->idempresa = Tools::settings('default', 'idempresa');
        }

        $this->codsubcuenta = Tools::noHtml($this->codsubcuenta);
        $this->codsubcuentagasto = Tools::noHtml($this->codsubcuentagasto);
        $this->sufijosepa = Tools::noHtml($this->sufijosepa);

        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'ListFormaPago?activetab=List'): string
    {
        return parent::url($type, $list);
    }
}
