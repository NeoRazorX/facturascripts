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
use FacturaScripts\Dinamic\Model\CuentaBancoCliente as DinCuentaBancoCliente;
use FacturaScripts\Dinamic\Model\Contacto as DinContacto;

/**
 * The client. You can have one or more associated addresses and sub-accounts.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Cliente extends Base\ComercialContact
{

    use Base\ModelTrait;

    /**
     * Agent assigned to this customer. Agent model.
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
     *
     * @var string
     */
    public $codtarifa;

    /**
     * Preferred payment days when calculating the due date of invoices.
     * Days separated by commas: 1,15,31
     *
     * @var string
     */
    public $diaspago;

    /**
     * Default contact for the shipment of products
     *
     * @var integer
     */
    public $idcontactoenv;

    /**
     * Default contact for sending documentation
     *
     * @var integer
     */
    public $idcontactofact;

    /**
     *
     * @var float
     */
    public $riesgoalcanzado;

    /**
     *
     * @var float
     */
    public $riesgomax;

    public function clear()
    {
        parent::clear();
        $this->codretencion = $this->toolBox()->appSettings()->get('default', 'codretencion');
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
        $fields = 'cifnif|codcliente|email|nombre|observaciones|razonsocial|telefono1|telefono2';
        $where[] = new DataBaseWhere($fields, \mb_strtolower($query, 'UTF8'), 'LIKE');
        return CodeModel::all($this->tableName(), $field, $this->primaryDescriptionColumn(), false, $where);
    }

    /**
     * Returns an array with the addresses associated with this customer.
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
     * Returns the bank accounts associated with this customer.
     * 
     * @return DinCuentaBancoCliente[]
     */
    public function getBankAccounts()
    {
        $contactAccounts = new DinCuentaBancoCliente();
        $where = [new DataBaseWhere($this->primaryColumn(), $this->primaryColumnValue())];
        return $contactAccounts->all($where, [], 0, 0);
    }

    /**
     * Return the default billing or shipping address.
     *
     * @return DinContacto
     */
    public function getDefaultAddress($type = 'billing')
    {
        $contact = new DinContacto();
        $idcontact = $type === 'shipping' ? $this->idcontactoenv : $this->idcontactofact;
        $contact->loadFromCode($idcontact);
        return $contact;
    }

    /**
     * Returns the preferred payment days for this customer.
     * 
     * @return array
     */
    public function getPaymentDays()
    {
        $days = [];
        foreach (\explode(',', $this->diaspago . ',') as $str) {
            if (\is_numeric(\trim($str))) {
                $days[] = \trim($str);
            }
        }

        return $days;
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
        /// we need to check Agente and GrupoClientes models before
        new Agente();
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
        if (!empty($this->codcliente) && 1 !== \preg_match('/^[A-Z0-9_\+\.\-]{1,10}$/i', $this->codcliente)) {
            $this->toolBox()->i18nLog()->error(
                'invalid-alphanumeric-code',
                ['%value%' => $this->codcliente, '%column%' => 'codcliente', '%min%' => '1', '%max%' => '10']
            );
            return false;
        }

        /// we validate the days of payment
        $arrayDias = [];
        foreach (\str_getcsv($this->diaspago) as $day) {
            if ((int) $day >= 1 && (int) $day <= 31) {
                $arrayDias[] = (int) $day;
            }
        }
        $this->diaspago = empty($arrayDias) ? null : \implode(',', $arrayDias);
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
        if (empty($this->codcliente)) {
            $this->codcliente = (string) $this->newCode();
        }

        $return = parent::saveInsert($values);
        if ($return && empty($this->idcontactofact)) {
            $parts = \explode(' ', $this->nombre);

            /// creates new contact
            $contact = new DinContacto();
            $contact->apellidos = \count($parts) > 1 ? \implode(' ', \array_slice($parts, 1)) : '';
            $contact->cifnif = $this->cifnif;
            $contact->codcliente = $this->codcliente;
            $contact->descripcion = $this->nombre;
            $contact->email = $this->email;
            $contact->empresa = $this->razonsocial;
            $contact->fax = $this->fax;
            $contact->nombre = $parts[0];
            $contact->personafisica = $this->personafisica;
            $contact->telefono1 = $this->telefono1;
            $contact->telefono2 = $this->telefono2;
            if ($contact->save()) {
                $this->idcontactofact = $contact->idcontacto;
                return $this->save();
            }
        }

        return $return;
    }
}
