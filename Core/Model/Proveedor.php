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
use FacturaScripts\Core\DataSrc\Paises;
use FacturaScripts\Core\Lib\Vies;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Contacto as DinContacto;
use FacturaScripts\Dinamic\Model\CuentaBancoProveedor as DinCuentaBancoProveedor;
use FacturaScripts\Dinamic\Model\CuentaEspecial as DinCuentaEspecial;
use FacturaScripts\Dinamic\Model\Subcuenta as DinSubcuenta;

/**
 * A supplier. It can be related to several addresses or accounts.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Proveedor extends Base\ComercialContact
{
    use Base\ModelTrait;

    const SPECIAL_ACCOUNT = 'PROVEE';
    const SPECIAL_CREDITOR_ACCOUNT = 'ACREED';

    /** @var bool */
    public $acreedor;

    /** @var string */
    public $codimpuestoportes;

    /** @var int */
    public $idcontacto;

    public function checkVies(bool $msg = true): bool
    {
        $codiso = Paises::get($this->getDefaultAddress()->codpais)->codiso ?? '';
        return Vies::check($this->cifnif ?? '', $codiso, $msg) === 1;
    }

    public function clear()
    {
        parent::clear();
        $this->acreedor = false;
        $this->codimpuestoportes = Tools::settings('default', 'codimpuesto');
    }

    public function codeModelSearch(string $query, string $fieldCode = '', array $where = []): array
    {
        $field = empty($fieldCode) ? $this->primaryColumn() : $fieldCode;
        $fields = 'cifnif|codproveedor|email|nombre|observaciones|razonsocial|telefono1|telefono2';
        $where[] = new DataBaseWhere($fields, mb_strtolower($query, 'UTF8'), 'LIKE');
        $where[] = new DataBaseWhere('fechabaja', null, 'IS');
        return CodeModel::all($this->tableName(), $field, $this->primaryDescriptionColumn(), false, $where);
    }

    /**
     * Returns the addresses associated with this supplier.
     *
     * @return DinContacto[]
     */
    public function getAddresses(): array
    {
        $contactModel = new DinContacto();
        $where = [new DataBaseWhere($this->primaryColumn(), $this->primaryColumnValue())];
        return $contactModel->all($where, [], 0, 0);
    }

    /**
     * Returns the bank accounts associated with the provider.
     *
     * @return DinCuentaBancoProveedor[]
     */
    public function getBankAccounts(): array
    {
        $contactAccounts = new DinCuentaBancoProveedor();
        $where = [new DataBaseWhere($this->primaryColumn(), $this->primaryColumnValue())];
        return $contactAccounts->all($where, [], 0, 0);
    }

    /**
     * Return the default billing or shipping address.
     *
     * @return DinContacto
     */
    public function getDefaultAddress(): Contacto
    {
        $contact = new DinContacto();
        $contact->loadFromCode($this->idcontacto);
        return $contact;
    }

    public function getSubcuenta(string $codejercicio, bool $crear): Subcuenta
    {
        $specialAccount = $this->acreedor ?
            static::SPECIAL_CREDITOR_ACCOUNT :
            static::SPECIAL_ACCOUNT;

        // ya tiene una subcuenta asignada
        if ($this->codsubcuenta) {
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
            if (false === $special->loadFromCode($specialAccount)) {
                return new DinSubcuenta();
            }

            // ahora creamos la subcuenta
            return $special->getCuenta($codejercicio)->createSubcuenta($this->codsubcuenta, $this->razonsocial);
        }

        // si no creamos la subcuenta, devolvemos una vacía
        return $crear ?
            $this->createSubcuenta($codejercicio, $specialAccount) :
            new DinSubcuenta();
    }

    public static function primaryColumn(): string
    {
        return 'codproveedor';
    }

    public function primaryDescriptionColumn(): string
    {
        return 'nombre';
    }

    public static function tableName(): string
    {
        return 'proveedores';
    }

    public function test(): bool
    {
        if (empty($this->nombre)) {
            Tools::log()->warning(
                'field-can-not-be-null',
                ['%fieldName%' => 'nombre', '%tableName%' => static::tableName()]
            );
            return false;
        }

        if (!empty($this->codproveedor) && 1 !== preg_match('/^[A-Z0-9_\+\.\-]{1,10}$/i', $this->codproveedor)) {
            Tools::log()->warning(
                'invalid-alphanumeric-code',
                ['%value%' => $this->codproveedor, '%column%' => 'codproveedor', '%min%' => '1', '%max%' => '10']
            );
            return false;
        }

        return parent::test();
    }

    protected function createSubcuenta(string $codejercicio, string $specialAccount): Subcuenta
    {
        // buscamos la cuenta especial
        $special = new DinCuentaEspecial();
        if (false === $special->loadFromCode($specialAccount)) {
            return new Subcuenta();
        }

        // buscamos la cuenta
        $cuenta = $special->getCuenta($codejercicio);
        if (empty($cuenta->codcuenta)) {
            return new DinSubcuenta();
        }

        // obtenemos un código de subcuenta libre
        $code = $cuenta->getFreeSubjectAccountCode($this);
        if (empty($code)) {
            return new DinSubcuenta();
        }

        // creamos la subcuenta
        $subAccount = $cuenta->createSubcuenta($code, $this->razonsocial);
        if (false === $subAccount->save()) {
            return new DinSubcuenta();
        }

        // guardamos el código de subcuenta
        $this->codsubcuenta = $subAccount->codsubcuenta;
        $this->save();

        return $subAccount;
    }

    protected function saveInsert(array $values = []): bool
    {
        if (empty($this->codproveedor)) {
            $this->codproveedor = (string)$this->newCode();
        }

        $return = parent::saveInsert($values);
        if ($return && empty($this->idcontacto)) {
            // creates new contact
            $contact = new DinContacto();
            $contact->cifnif = $this->cifnif;
            $contact->codproveedor = $this->codproveedor;
            $contact->descripcion = $this->nombre;
            $contact->email = $this->email;
            $contact->empresa = $this->razonsocial;
            $contact->fax = $this->fax;
            $contact->nombre = $this->nombre;
            $contact->personafisica = $this->personafisica;
            $contact->telefono1 = $this->telefono1;
            $contact->telefono2 = $this->telefono2;
            $contact->tipoidfiscal = $this->tipoidfiscal;
            if ($contact->save()) {
                $this->idcontacto = $contact->idcontacto;
                return $this->save();
            }
        }

        return $return;
    }
}
