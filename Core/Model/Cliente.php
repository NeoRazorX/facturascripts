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
use FacturaScripts\Core\DataSrc\Paises;
use FacturaScripts\Core\Lib\Vies;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Contacto as DinContacto;
use FacturaScripts\Dinamic\Model\CuentaBancoCliente as DinCuentaBancoCliente;

/**
 * The client. You can have one or more associated addresses and subaccounts.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Cliente extends Base\ComercialContact
{
    use Base\ModelTrait;

    /** @var string */
    public $codagente;

    /** @var string */
    public $codgrupo;

    /** @var string */
    public $codtarifa;

    /** @var string */
    public $diaspago;

    /** @var integer */
    public $idcontactoenv;

    /** @var integer */
    public $idcontactofact;

    /** @var float */
    public $riesgoalcanzado;

    /** @var float */
    public $riesgomax;

    public function checkVies(): bool
    {
        $codiso = Paises::get($this->getDefaultAddress()->codpais)->codiso ?? '';
        return Vies::check($this->cifnif ?? '', $codiso) === 1;
    }

    public function clear()
    {
        parent::clear();
        $this->codretencion = Tools::settings('default', 'codretencion');
    }

    /**
     * @param string $query
     * @param string $fieldCode
     * @param DataBaseWhere[] $where
     *
     * @return CodeModel[]
     */
    public function codeModelSearch(string $query, string $fieldCode = '', array $where = []): array
    {
        $field = empty($fieldCode) ? $this->primaryColumn() : $fieldCode;
        $fields = 'cifnif|codcliente|email|nombre|observaciones|razonsocial|telefono1|telefono2';
        $where[] = new DataBaseWhere($fields, mb_strtolower($query, 'UTF8'), 'LIKE');
        $where[] = new DataBaseWhere('fechabaja', null, 'IS');
        return CodeModel::all($this->tableName(), $field, $this->primaryDescriptionColumn(), false, $where);
    }

    /**
     * Returns an array with the addresses associated with this customer.
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
     * Returns the bank accounts associated with this customer.
     *
     * @return DinCuentaBancoCliente[]
     */
    public function getBankAccounts(): array
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
    public function getDefaultAddress($type = 'billing'): Contacto
    {
        $contact = new DinContacto();
        $idcontacto = $type === 'shipping' ? $this->idcontactoenv : $this->idcontactofact;
        $contact->loadFromCode($idcontacto);
        return $contact;
    }

    /**
     * Returns the preferred payment days for this customer.
     *
     * @return array
     */
    public function getPaymentDays(): array
    {
        $days = [];
        foreach (explode(',', $this->diaspago . ',') as $str) {
            if (is_numeric(trim($str))) {
                $days[] = trim($str);
            }
        }

        return $days;
    }

    public function install(): string
    {
        // we need exits Contacto before, but we can't check it because it would create a cyclic check
        // we need to check Agente and GrupoClientes models before
        new Agente();
        new GrupoClientes();

        return parent::install();
    }

    public static function primaryColumn(): string
    {
        return 'codcliente';
    }

    public function primaryDescriptionColumn(): string
    {
        return 'nombre';
    }

    public static function tableName(): string
    {
        return 'clientes';
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

        if (!empty($this->codcliente) && 1 !== preg_match('/^[A-Z0-9_\+\.\-]{1,10}$/i', $this->codcliente)) {
            Tools::log()->error(
                'invalid-alphanumeric-code',
                ['%value%' => $this->codcliente, '%column%' => 'codcliente', '%min%' => '1', '%max%' => '10']
            );
            return false;
        }

        // we validate the days of payment
        $arrayDias = [];
        foreach (str_getcsv($this->diaspago ?? '') as $day) {
            if ((int)$day >= 1 && (int)$day <= 31) {
                $arrayDias[] = (int)$day;
            }
        }
        $this->diaspago = empty($arrayDias) ? null : implode(',', $arrayDias);
        return parent::test();
    }

    protected function saveInsert(array $values = []): bool
    {
        if (empty($this->codcliente)) {
            $this->codcliente = (string)$this->newCode();
        }

        $return = parent::saveInsert($values);
        if ($return && empty($this->idcontactofact)) {
            $parts = explode(' ', $this->nombre);

            // creates new contact
            $contact = new DinContacto();
            $contact->apellidos = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : '';
            $contact->cifnif = $this->cifnif;
            $contact->codagente = $this->codagente;
            $contact->codcliente = $this->codcliente;
            $contact->descripcion = $this->nombre;
            $contact->email = $this->email;
            $contact->empresa = $this->razonsocial;
            $contact->fax = $this->fax;
            $contact->nombre = $parts[0];
            $contact->personafisica = $this->personafisica;
            $contact->telefono1 = $this->telefono1;
            $contact->telefono2 = $this->telefono2;
            $contact->tipoidfiscal = $this->tipoidfiscal;
            if ($contact->save()) {
                $this->idcontactofact = $contact->idcontacto;
                return $this->save();
            }
        }

        return $return;
    }
}
