<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2014-2023 Carlos Garcia Gomez  <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Model\Cliente as DinCliente;
use FacturaScripts\Dinamic\Model\CuentaEspecial as DinCuentaEspecial;
use FacturaScripts\Dinamic\Model\Subcuenta as DinSubcuenta;

/**
 * A group of customers, which may be associated with a rate.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class GrupoClientes extends Base\ModelClass
{
    use Base\ModelTrait;

    /** @var string */
    public $codgrupo;

    /** @var string */
    public $codsubcuenta;

    /** @var string */
    public $codtarifa;

    /** @var string */
    public $nombre;

    public function getSubcuenta(string $codejercicio, bool $crear): Subcuenta
    {
        // si no tiene una subcuenta asignada, devolvemos una vacía
        if (empty($this->codsubcuenta)) {
            return new DinSubcuenta();
        }

        // buscamos la subcuenta para el ejercicio
        $subAccount = new DinSubcuenta();
        $where = [
            new DataBaseWhere('codsubcuenta', $this->codsubcuenta),
            new DataBaseWhere('codejercicio', $codejercicio),
        ];
        if ($subAccount->loadFromCode('', $where)) {
            return $subAccount;
        }

        // no hemos encontrado la subcuenta
        // si no queremos crearla, devolvemos una vacía
        if (false === $crear) {
            return new DinSubcuenta();
        }

        // buscamos la cuenta especial
        $special = new DinCuentaEspecial();
        if (false === $special->loadFromCode(DinCliente::SPECIAL_ACCOUNT)) {
            return new DinSubcuenta();
        }

        // ahora creamos la subcuenta
        return $special->getCuenta($codejercicio)->createSubcuenta($this->codsubcuenta, $this->nombre);
    }

    public function install(): string
    {
        // As there is a key outside of tariffs, we have to check that table before
        new Tarifa();

        return parent::install();
    }

    public static function primaryColumn(): string
    {
        return 'codgrupo';
    }

    public function primaryDescriptionColumn(): string
    {
        return 'nombre';
    }

    public static function tableName(): string
    {
        return 'gruposclientes';
    }

    public function test(): bool
    {
        if (!empty($this->codgrupo) && 1 !== preg_match('/^[A-Z0-9_\+\.\-]{1,6}$/i', $this->codgrupo)) {
            Tools::log()->warning(
                'invalid-alphanumeric-code',
                ['%value%' => $this->codgrupo, '%column%' => 'codgrupo', '%min%' => '1', '%max%' => '6']
            );
            return false;
        }

        $this->nombre = Tools::noHtml($this->nombre);
        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'ListCliente?activetab=List'): string
    {
        return parent::url($type, $list);
    }

    protected function saveInsert(array $values = []): bool
    {
        if (empty($this->codgrupo)) {
            $this->codgrupo = (string)$this->newCode();
        }

        return parent::saveInsert($values);
    }
}
