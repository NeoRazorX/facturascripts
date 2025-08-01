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
use FacturaScripts\Core\DataSrc\Paises;
use FacturaScripts\Core\Lib\Vies;
use FacturaScripts\Core\Model\Base\EmailAndPhonesTrait;
use FacturaScripts\Core\Model\Base\FiscalNumberTrait;
use FacturaScripts\Core\Model\Base\GravatarTrait;
use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Validator;
use FacturaScripts\Dinamic\Lib\RegimenIVA;
use FacturaScripts\Dinamic\Model\Contacto as DinContacto;
use FacturaScripts\Dinamic\Model\CuentaBancoCliente as DinCuentaBancoCliente;
use FacturaScripts\Dinamic\Model\CuentaEspecial as DinCuentaEspecial;
use FacturaScripts\Dinamic\Model\FormaPago;
use FacturaScripts\Dinamic\Model\GrupoClientes as DinGrupoClientes;
use FacturaScripts\Dinamic\Model\Retencion;
use FacturaScripts\Dinamic\Model\Serie;
use FacturaScripts\Dinamic\Model\Subcuenta as DinSubcuenta;

/**
 * The client. You can have one or more associated addresses and accounts.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Cliente extends ModelClass
{
    use ModelTrait;
    use EmailAndPhonesTrait;
    use FiscalNumberTrait;
    use GravatarTrait;

    const SPECIAL_ACCOUNT = 'CLIENT';

    /** @var string */
    public $codagente;

    /** @var string */
    public $codcliente;

    /** @var string */
    public $codgrupo;

    /** @var string */
    public $codpago;

    /** @var string */
    public $codproveedor;

    /** @var string */
    public $codretencion;

    /** @var string */
    public $codserie;

    /** @var string */
    public $codsubcuenta;

    /** @var string */
    public $codtarifa;

    /** @var bool */
    public $debaja;

    /** @var string */
    public $diaspago;

    /** @var string */
    public $excepcioniva;

    /** @var string */
    public $fax;

    /** @var string */
    public $fechaalta;

    /** @var string */
    public $fechabaja;

    /** @var integer */
    public $idcontactoenv;

    /** @var integer */
    public $idcontactofact;

    /** @var string */
    public $langcode;

    /** @var string */
    public $nombre;

    /** @var string */
    public $observaciones;

    /** @var bool */
    public $personafisica;

    /** @var string */
    public $razonsocial;

    /** @var string */
    public $regimeniva;

    /** @var float */
    public $riesgoalcanzado;

    /** @var float */
    public $riesgomax;

    /** @var string */
    public $web;

    public function checkVies(bool $msg = true): bool
    {
        $codiso = Paises::get($this->getDefaultAddress()->codpais)->codiso ?? '';
        return Vies::check($this->cifnif ?? '', $codiso, $msg) === 1;
    }

    public function clear(): void
    {
        parent::clear();
        $this->codretencion = Tools::settings('default', 'codretencion');
        $this->debaja = false;
        $this->fechaalta = Tools::date();
        $this->personafisica = true;
        $this->regimeniva = RegimenIVA::defaultValue();
        $this->tipoidfiscal = Tools::settings('default', 'tipoidfiscal');
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
        $where = [new DataBaseWhere($this->primaryColumn(), $this->id())];
        return DinContacto::all($where, [], 0, 0);
    }

    /**
     * Returns the bank accounts associated with this customer.
     *
     * @return DinCuentaBancoCliente[]
     */
    public function getBankAccounts(): array
    {
        $where = [new DataBaseWhere($this->primaryColumn(), $this->id())];
        return DinCuentaBancoCliente::all($where, [], 0, 0);
    }

    /**
     * Return the default billing or shipping address.
     *
     * @return DinContacto
     */
    public function getDefaultAddress(string $type = 'billing'): Contacto
    {
        $contact = new DinContacto();
        $id = $type === 'shipping' ? $this->idcontactoenv : $this->idcontactofact;
        $contact->load($id);
        return $contact;
    }

    public function getGroup(): GrupoClientes
    {
        $group = new DinGrupoClientes();
        $group->load($this->codgrupo);
        return $group;
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

    public function getSubcuenta(string $codejercicio, bool $crear): Subcuenta
    {
        // ya tiene una subcuenta asignada
        if ($this->codsubcuenta) {
            // buscamos la subcuenta para el ejercicio
            $subAccount = new DinSubcuenta();
            $where = [
                new DataBaseWhere('codsubcuenta', $this->codsubcuenta),
                new DataBaseWhere('codejercicio', $codejercicio),
            ];
            if ($subAccount->loadWhere($where)) {
                return $subAccount;
            }

            // no hemos encontrado la subcuenta
            // si no queremos crearla, devolvemos una vacía
            if (false === $crear) {
                return new DinSubcuenta();
            }

            // buscamos la cuenta especial
            $special = new DinCuentaEspecial();
            if (false === $special->load(static::SPECIAL_ACCOUNT)) {
                return new DinSubcuenta();
            }

            // ahora creamos la subcuenta
            return $special->getCuenta($codejercicio)->createSubcuenta($this->codsubcuenta, $this->razonsocial);
        }

        // ¿El grupo tiene subcuenta?
        $group = $this->getGroup();
        if ($group->codsubcuenta) {
            return $group->getSubcuenta($codejercicio, $crear);
        }

        // si no creamos la subcuenta, devolvemos una vacía
        return $crear ?
            $this->createSubcuenta($codejercicio) :
            new DinSubcuenta();
    }

    public function install(): string
    {
        // we need exits Contacto before, but we can't check it because it would create a cyclic check
        // we need to check Agente and GrupoClientes models before
        new Agente();
        new FormaPago();
        new GrupoClientes();
        new Retencion();
        new Serie();

        return parent::install();
    }

    public function irpf(): float
    {
        if (empty($this->codretencion)) {
            return 0.0;
        }

        $retention = new Retencion();
        if ($retention->load($this->codretencion)) {
            return $retention->porcentaje;
        }

        return 0.0;
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
        $this->debaja = !empty($this->fechabaja);
        $this->fax = Tools::noHtml($this->fax) ?? '';
        $this->langcode = Tools::noHtml($this->langcode);
        $this->nombre = Tools::noHtml($this->nombre);
        $this->observaciones = Tools::noHtml($this->observaciones) ?? '';
        $this->razonsocial = Tools::noHtml($this->razonsocial);
        $this->web = Tools::noHtml($this->web);

        if (!empty($this->codcliente) && 1 !== preg_match('/^[A-Z0-9_\+\.\-]{1,10}$/i', $this->codcliente)) {
            Tools::log()->error(
                'invalid-alphanumeric-code',
                ['%value%' => $this->codcliente, '%column%' => 'codcliente', '%min%' => '1', '%max%' => '10']
            );
            return false;
        }

        if (empty($this->nombre)) {
            Tools::log()->warning(
                'field-can-not-be-null',
                ['%fieldName%' => 'nombre', '%tableName%' => static::tableName()]
            );
            return false;
        }

        if (empty($this->razonsocial)) {
            $this->razonsocial = $this->nombre;
        }

        // check if the web is a valid url
        if (!empty($this->web) && false === Validator::url($this->web)) {
            Tools::log()->warning('invalid-web', ['%web%' => $this->web]);
            return false;
        }

        return parent::test() && $this->testFiscalNumber() && $this->testEmailAndPhones() && $this->testDiasPago();
    }

    protected function createSubcuenta(string $codejercicio): Subcuenta
    {
        // buscamos la cuenta especial
        $special = new DinCuentaEspecial();
        if (false === $special->load(static::SPECIAL_ACCOUNT)) {
            return new DinSubcuenta();
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

    protected function saveInsert(): bool
    {
        if (empty($this->codcliente)) {
            $this->codcliente = (string)$this->newCode();
        }

        $return = parent::saveInsert();
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

    protected function testDiasPago(): bool
    {
        $arrayDias = [];
        foreach (explode(',', $this->diaspago ?? '') as $day) {
            if ((int)$day >= 1 && (int)$day <= 31) {
                $arrayDias[] = (int)$day;
            }
        }
        $this->diaspago = empty($arrayDias) ? null : implode(',', $arrayDias);

        return true;
    }
}
