<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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

/**
 * The client. You can have one or more associated addresses and sub-accounts.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Cliente extends Base\ComercialContact
{

    use Base\ModelTrait;

    /**
     * Employee assigned to this customer. Agent model.
     *
     * @var string
     */
    public $codagente;

    /**
     * Group to which the client belongs.
     *
     * @var string
     */
    public $codgrupo;

    /**
     * Preferred payment days when calculating the due date of invoices.
     * Days separated by commas: 1,15,31
     *
     * @var string
     */
    public $diaspago;

    /**
     * Default contact for sending documentation
     *
     * @var integer
     */
    public $idcontactofact;

    /**
     * Default contact for the shipment of products
     *
     * @var integer
     */
    public $idcontactoenv;

    /**
     * 
     * @param string $query
     * @param string $fieldcode
     *
     * @return CodeModel[]
     */
    public function codeModelSearch(string $query, string $fieldcode = '')
    {
        $field = empty($fieldcode) ? $this->primaryColumn() : $fieldcode;
        $fields = 'cifnif|codcliente|email|nombre|observaciones|razonsocial|telefono1|telefono2';
        $where = [new DataBaseWhere($fields, mb_strtolower($query, 'UTF8'), 'LIKE')];
        return CodeModel::all($this->tableName(), $field, $this->primaryDescriptionColumn(), false, $where);
    }

    /**
     * Returns an array with the addresses associated with the client.
     *
     * @return Contacto[]
     */
    public function getAdresses()
    {
        $contactModel = new Contacto();
        return $contactModel->all([new DataBaseWhere('codcliente', $this->codcliente)]);
    }

    /**
     * Return the default billing or shipping address.
     *
     * @return Contacto
     */
    public function getDefaultAddress($type = 'billing')
    {
        $contact = new Contacto();
        switch ($type) {
            case 'shipping':
                $where = [new DataBaseWhere('idcontacto', $this->idcontactoenv)];
                $contact->loadFromCode('', $where);
                break;

            default:
                $where = [new DataBaseWhere('idcontacto', $this->idcontactofact)];
                $contact->loadFromCode('', $where);
                break;
        }

        return $contact;
    }

    /**
     * This function is called when creating the model table. Returns the SQL
     * that will be executed after the creation of the table. Useful to insert values
     * default.
     *
     * @return string
     */
    public function install()
    {
        /// we need exits Contacto before, but we can't check it because it would create a cyclic check
        /// we need to check model GrupoClientes before
        new GrupoClientes();

        return parent::install();
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'codcliente';
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
        return 'clientes';
    }

    /**
     * Returns True if there is no erros on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $this->codcliente = empty($this->codcliente) ? (string) $this->newCode() : trim($this->codcliente);

        /// we validate the days of payment
        $arrayDias = [];
        foreach (str_getcsv($this->diaspago) as $day) {
            if ((int) $day >= 1 && (int) $day <= 31) {
                $arrayDias[] = (int) $day;
            }
        }
        $this->diaspago = empty($arrayDias) ? null : implode(',', $arrayDias);

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
        $return = parent::saveInsert($values);
        if ($return && empty($this->idcontactofact)) {
            /// creates new contact
            $contact = new Contacto();
            $contact->cifnif = $this->cifnif;
            $contact->codcliente = $this->codcliente;
            $contact->descripcion = $this->nombre;
            $contact->email = $this->email;
            $contact->empresa = $this->razonsocial;
            $contact->fax = $this->fax;
            $contact->nombre = $this->nombre;
            $contact->personafisica = $this->personafisica;
            $contact->telefono1 = $this->telefono1;
            $contact->telefono2 = $this->telefono2;
            if ($contact->save()) {
                $this->idcontactoenv = $contact->idcontacto;
                $this->idcontactofact = $contact->idcontacto;
                return $this->save();
            }
        }

        return $return;
    }
}
