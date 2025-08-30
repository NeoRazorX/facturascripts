<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Model\Base\CompanyRelationTrait;
use FacturaScripts\Core\Model\Base\IbanTrait;
use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\CuentaEspecial as DinCuentaEspecial;
use FacturaScripts\Dinamic\Model\Subcuenta as DinSubcuenta;

/**
 * A bank account of the company itself.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class CuentaBanco extends ModelClass
{
    use ModelTrait;
    use CompanyRelationTrait;
    use IbanTrait;

    const SPECIAL_ACCOUNT = 'CAJA';

    /** @var bool */
    public $activa;

    /** @var string */
    public $codcuenta;

    /** @var string */
    public $codsubcuenta;

    /** @var string */
    public $codsubcuentagasto;

    /** @var string */
    public $descripcion;

    /** @var string */
    public $sufijosepa;

    /** @var string */
    public $swift;

    public function clear(): void
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
            if ($especial->load(static::SPECIAL_ACCOUNT)) {
                return $especial->getSubcuenta($codejercicio);
            }
        }

        // buscamos la subcuenta
        $subcuenta = new DinSubcuenta();
        $where = [
            new DataBaseWhere('codsubcuenta', $this->codsubcuenta),
            new DataBaseWhere('codejercicio', $codejercicio),
        ];
        if ($subcuenta->loadWhere($where)) {
            return $subcuenta;
        }

        // no la hemos encontrado, ¿La creamos?
        if ($create) {
            // buscamos la cuenta especial
            $especial = new DinCuentaEspecial();
            if (false === $especial->load(static::SPECIAL_ACCOUNT)) {
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
            if ($especial->load(static::SPECIAL_ACCOUNT)) {
                return $especial->getSubcuenta($codejercicio);
            }
        }

        // buscamos la subcuenta
        $subcuenta = new DinSubcuenta();
        $where = [
            new DataBaseWhere('codsubcuenta', $this->codsubcuentagasto),
            new DataBaseWhere('codejercicio', $codejercicio),
        ];
        if ($subcuenta->loadWhere($where)) {
            return $subcuenta;
        }

        // no la hemos encontrado, ¿La creamos?
        if ($create) {
            // buscamos la cuenta especial
            $especial = new DinCuentaEspecial();
            if (false === $especial->load(static::SPECIAL_ACCOUNT)) {
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

    public static function primaryColumn(): string
    {
        return 'codcuenta';
    }

    public static function tableName(): string
    {
        return 'cuentasbanco';
    }

    public function test(): bool
    {
        $this->codcuenta = Tools::noHtml($this->codcuenta);
        $this->codsubcuenta = Tools::noHtml($this->codsubcuenta);
        $this->codsubcuentagasto = Tools::noHtml($this->codsubcuentagasto);
        $this->descripcion = Tools::noHtml($this->descripcion);
        $this->sufijosepa = Tools::noHtml($this->sufijosepa);
        $this->swift = Tools::noHtml($this->swift);

        if (!empty($this->codcuenta) && false === is_numeric($this->codcuenta)) {
            Tools::log()->error('invalid-number', ['%number%' => $this->codcuenta]);
            return false;
        }

        if (empty($this->idempresa)) {
            $this->idempresa = Tools::settings('default', 'idempresa');
        }

        return parent::test() && $this->testIBAN();
    }

    public function url(string $type = 'auto', string $list = 'ListFormaPago?activetab=List'): string
    {
        return parent::url($type, $list);
    }

    protected function saveInsert(): bool
    {
        if (empty($this->codcuenta)) {
            $this->codcuenta = $this->newCode();
        }

        return parent::saveInsert();
    }

    protected function saveUpdate(): bool
    {
        if (false === parent::saveUpdate()) {
            return false;
        }

        // si ha cambiado el iban, añadimos un aviso al log
        if (!empty($this->getOriginal('iban')) && $this->isDirty('iban')) {
            Tools::log(LogMessage::AUDIT_CHANNEL)->warning('company-iban-changed', [
                '%account%' => $this->codcuenta,
                '%old%' => $this->getOriginal('iban'),
                '%new%' => $this->iban,
            ]);
        }

        return true;
    }
}
