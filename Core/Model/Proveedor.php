<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Model\CuentaBancoProveedor as DinCuentaBancoProveedor;
use FacturaScripts\Dinamic\Model\Contacto as DinContacto;

/**
 * A supplier. It can be related to several addresses or sub-accounts.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Proveedor extends Base\ComercialContact
{

    use Base\ModelTrait;

    /**
     * True -> the supplier is a creditor, that is, we do not buy him merchandise,
     * we buy services, etc.
     *
     * @var bool
     */
    public $acreedor;

    /**
     * Transport tax.
     *
     * @var string
     */
    public $codimpuestoportes;

    /**
     * Default contact.
     *
     * @var int
     */
    public $idcontacto;

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->acreedor = false;
        $this->codimpuestoportes = $this->toolBox()->appSettings()->get('default', 'codimpuesto');
    }

    /**
     * 
     * @param string          $query
     * @param string          $fieldcode
     * @param DataBaseWhere[] $where
     *
     * @return CodeModel[]
     */
    public function codeModelSearch(string $query, string $fieldcode = '', $where = [])
    {
        $field = empty($fieldcode) ? $this->primaryColumn() : $fieldcode;
        $fields = 'cifnif|codproveedor|email|nombre|observaciones|razonsocial|telefono1|telefono2';
        $where[] = new DataBaseWhere($fields, \mb_strtolower($query, 'UTF8'), 'LIKE');
        return CodeModel::all($this->tableName(), $field, $this->primaryDescriptionColumn(), false, $where);
    }

    /**
     * Returns the addresses associated with this supplier.
     *
     * @return DinContacto[]
     */
    public function getAdresses()
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
    public function getBankAccounts()
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
    public function getDefaultAddress()
    {
        $contact = new DinContacto();
        $contact->loadFromCode($this->idcontacto);
        return $contact;
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'codproveedor';
    }

    /**
     * Returns the description of the column that is the model's primary key.
     *
     * @return string
     */
    public function primaryDescriptionColumn()
    {
        return 'nombre';
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'proveedores';
    }

    /**
     * Returns True if there is no erros on properties values.
     *
     * @return bool
     */
    public function test()
    {
        if (!empty($this->codproveedor) && 1 !== \preg_match('/^[A-Z0-9_\+\.\-]{1,10}$/i', $this->codproveedor)) {
            $this->toolBox()->i18nLog()->warning(
                'invalid-alphanumeric-code',
                ['%value%' => $this->codproveedor, '%column%' => 'codproveedor', '%min%' => '1', '%max%' => '10']
            );
            return false;
        }

        return parent::test();
    }

    /**
     * 
     * @param array $values
     *
     * @return bool
     */
    protected function saveInsert(array $values = [])
    {
        if (empty($this->codproveedor)) {
            $this->codproveedor = (string) $this->newCode();
        }

        $return = parent::saveInsert($values);
        if ($return && empty($this->idcontacto)) {
            /// creates new contact
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
            if ($contact->save()) {
                $this->idcontacto = $contact->idcontacto;
                return $this->save();
            }
        }

        return $return;
    }
}
