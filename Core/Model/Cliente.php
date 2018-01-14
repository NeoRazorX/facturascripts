<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

/**
 * The client. You can have one or more associated addresses and sub-accounts.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Cliente extends Base\Persona
{

    use Base\ModelTrait {
        __construct as private traitConstruct;
        clear as private traitClear;
    }

    /**
     * Group to which the client belongs.
     *
     * @var string
     */
    public $codgrupo;

    /**
     * True -> equivalence surcharge is applied to the client.
     *
     * @var boolean
     */
    public $recargo;

    /**
     * Preferred payment days when calculating the due date of invoices.
     * Days separated by commas: 1,15,31
     *
     * @var string
     */
    public $diaspago;

    /**
     * Cliente constructor.
     *
     * @param array $data
     */
    public function __construct($data = [])
    {
        parent::__construct();
        $this->traitConstruct($data);
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
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'codcliente';
    }

    public function primaryDescriptionColumn() 
    {
        return 'nombre';
    }

    /**
     * This function is called when creating the model table. Returns the SQL
     * that will be executed after the creation of the table. Useful to insert values
     * default.
     */
    public function install()
    {
        /// necesitamos la tabla de grupos comprobada para la clave ajena
        new GrupoClientes();

        return '';
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        $this->traitClear();
        parent::clear();

        $this->recargo = false;
        $this->regimeniva = 'general';
    }

    /**
     * Returns the first client that has $ cifnif as cifnif.
     * If the cifnif is blank and a company name is provided,
     * the first client with that company name is returned.
     *
     * @param string $cifnif
     * @param string $razon
     *
     * @return Cliente|false
     */
    public function getByCifnif($cifnif, $razon = '')
    {
        if ($cifnif === '' && $razon !== '') {
            $razon = self::noHtml(mb_strtolower($razon, 'UTF8'));
            $sql = 'SELECT * FROM ' . static::tableName()
                . " WHERE cifnif = '' AND lower(razonsocial) = " . self::$dataBase->var2str($razon) . ';';
        } else {
            $cifnif = mb_strtolower($cifnif, 'UTF8');
            $sql = 'SELECT * FROM ' . static::tableName() . ' WHERE lower(cifnif) = ' . self::$dataBase->var2str($cifnif) . ';';
        }

        $data = self::$dataBase->select($sql);
        if (!empty($data)) {
            return new self($data[0]);
        }

        return false;
    }

    /**
     * Returns an array with the addresses associated with the client.
     *
     * @return DireccionCliente[]
     */
    public function getDirecciones()
    {
        $dirModel = new DireccionCliente();

        return $dirModel->all([new DataBaseWhere('codcliente', $this->codcliente)]);
    }

    /**
     * Returns an array with all the subaccounts associated with the client.
     * One for each exercise.
     *
     * @return Subcuenta[]
     */
    public function getSubcuentas()
    {
        $sublist = [];
        $subcpModel = new SubcuentaCliente();
        foreach ($subcpModel->all([new DataBaseWhere('codcliente', $this->codcliente)]) as $subcp) {
            $subcuenta = $subcp->getSubcuenta();
            if ($subcuenta !== false) {
                $sublist[] = $subcuenta;
            }
        }

        return $sublist;
    }

    /**
     * Returns the sub-account associated with the client for the year $ axis.
     * If it does not exist, try to create it. If it fails, it returns False.
     *
     * @param string $codejercicio
     *
     * @return Subcuenta|false
     */
    public function getSubcuenta($codejercicio)
    {
        foreach ($this->getSubcuentas() as $subc) {
            if ($subc->codejercicio === $codejercicio) {
                return $subc;
            }
        }

        $cuentaModel = new Cuenta();
        $ccli = $cuentaModel->getCuentaesp('CLIENT', $codejercicio);
        if ($ccli) {
            $continuar = false;

            $subcuenta = $ccli->newSubcuenta($this->codcliente);
            if ($subcuenta) {
                $subcuenta->descripcion = $this->razonsocial;
                if ($subcuenta->save()) {
                    $continuar = true;
                }
            }

            if ($continuar) {
                $sccli = new SubcuentaCliente();
                $sccli->codcliente = $this->codcliente;
                $sccli->codejercicio = $codejercicio;
                $sccli->codsubcuenta = $subcuenta->codsubcuenta;
                $sccli->idsubcuenta = $subcuenta->idsubcuenta;
                if ($sccli->save()) {
                    return $subcuenta;
                }

                self::$miniLog->alert(self::$i18n->trans('cant-associate-customer-subaccount', ['%customerCode%' => $this->codcliente]));

                return false;
            }

            self::$miniLog->alert(self::$i18n->trans('cant-create-customer-subaccount', ['%customerCode%' => $this->codcliente]));

            return false;
        }

        self::$miniLog->alert(self::$i18n->trans('account-not-found'));
        self::$miniLog->alert(self::$i18n->trans('accounting-plan-imported?'));

        return false;
    }

    /**
     * Returns True if there is no erros on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $status = false;

        if ($this->codcliente === null) {
            $this->codcliente = (string) $this->newCode();
        } else {
            $this->codcliente = trim($this->codcliente);
        }

        $this->nombre = self::noHtml($this->nombre);
        $this->razonsocial = self::noHtml($this->razonsocial);
        $this->cifnif = self::noHtml($this->cifnif);
        $this->observaciones = self::noHtml($this->observaciones);

        if ($this->debaja) {
            if ($this->fechabaja === null) {
                $this->fechabaja = date('d-m-Y');
            }
        } else {
            $this->fechabaja = null;
        }

        /// we validate the days of payment
        $arrayDias = [];
        foreach (str_getcsv($this->diaspago) as $d) {
            if ((int) $d >= 1 && (int) $d <= 31) {
                $arrayDias[] = (int) $d;
            }
        }
        $this->diaspago = null;
        if (!empty($arrayDias)) {
            $this->diaspago = implode(',', $arrayDias);
        }

        if (!preg_match('/^[A-Z0-9]{1,6}$/i', $this->codcliente)) {
            self::$miniLog->alert(self::$i18n->trans('not-valid-client-code', ['%customerCode%' => $this->codcliente, '%fieldName%' => 'codcliente']));
        } elseif (empty($this->nombre) || strlen($this->nombre) > 100) {
            self::$miniLog->alert(self::$i18n->trans('not-valid-client-name', ['%customerName%' => $this->nombre, '%fieldName%' => 'nombre']));
        } elseif (empty($this->razonsocial) || strlen($this->razonsocial) > 100) {
            self::$miniLog->alert(self::$i18n->trans('not-valid-client-business-name', ['%businessName%' => $this->razonsocial, '%fieldName%' => 'razonsocial']));
        } else {
            $status = true;
        }

        return $status;
    }

    /**
     * Returns an array with combinations containing $query in its name
     * or reason or code or cifnif or telefono1 or telefono2 or observations.
     *
     * @param string $query
     * @param int    $offset
     *
     * @return self[]
     */
    public function search($query, $offset = 0)
    {
        $clilist = [];
        $query = mb_strtolower(self::noHtml($query), 'UTF8');

        $consulta = 'SELECT * FROM ' . static::tableName() . ' WHERE debaja = FALSE AND ';
        if (is_numeric($query)) {
            $consulta .= "(nombre LIKE '%" . $query . "%' OR razonsocial LIKE '%" . $query . "%'"
                . " OR codcliente LIKE '%" . $query . "%' OR cifnif LIKE '%" . $query . "%'"
                . " OR telefono1 LIKE '" . $query . "%' OR telefono2 LIKE '" . $query . "%'"
                . " OR observaciones LIKE '%" . $query . "%')";
        } else {
            $buscar = str_replace(' ', '%', $query);
            $consulta .= "(lower(nombre) LIKE '%" . $buscar . "%' OR lower(razonsocial) LIKE '%" . $buscar . "%'"
                . " OR lower(cifnif) LIKE '%" . $buscar . "%' OR lower(observaciones) LIKE '%" . $buscar . "%'"
                . " OR lower(email) LIKE '%" . $buscar . "%')";
        }
        $consulta .= ' ORDER BY lower(nombre) ASC';

        $data = self::$dataBase->selectLimit($consulta, FS_ITEM_LIMIT, $offset);
        if (!empty($data)) {
            foreach ($data as $d) {
                $clilist[] = new self($d);
            }
        }

        return $clilist;
    }
}
