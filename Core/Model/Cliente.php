<?php
/**
 * This file is part of facturacion_base
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
 * El cliente. Puede tener una o varias direcciones y subcuentas asociadas.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Cliente extends Base\Persona
{

    use Base\ModelTrait {
        __construct as private traitConstruct;
        clear as private traitClear;
        url as private traitURL;
    }

    /**
     * Grupo al que pertenece el cliente.
     *
     * @var string
     */
    public $codgrupo;

    /**
     * TRUE -> al cliente se le aplica recargo de equivalencia.
     *
     * @var boolean
     */
    public $recargo;

    /**
     * Dias de pago preferidos a la hora de calcular el vencimiento de las facturas.
     * Días separados por comas: 1,15,31
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
     * Devuelve el nombre de la tabla que usa este modelo.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'clientes';
    }

    /**
     * Devuelve el nombre de la columna que es clave primaria del modelo.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'codcliente';
    }

    /**
     * Esta función es llamada al crear la tabla del modelo. Devuelve el SQL
     * que se ejecutará tras la creación de la tabla. útil para insertar valores
     * por defecto.
     */
    public function install()
    {
        /// necesitamos la tabla de grupos comprobada para la clave ajena
        new GrupoClientes();

        return '';
    }

    /**
     * Resetea los valores de todas las propiedades modelo.
     */
    public function clear()
    {
        $this->traitClear();
        parent::clear();

        $this->recargo = false;
    }

    /**
     * Devuelve el primer cliente que tenga $cifnif como cifnif.
     * Si el cifnif está en blanco y se proporciona una razón social,
     * se devuelve el primer cliente que tenga esa razón social.
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
            $sql = 'SELECT * FROM ' . $this->tableName()
                . " WHERE cifnif = '' AND lower(razonsocial) = " . $this->var2str($razon) . ';';
        } else {
            $cifnif = mb_strtolower($cifnif, 'UTF8');
            $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE lower(cifnif) = ' . $this->var2str($cifnif) . ';';
        }

        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            return new self($data[0]);
        }

        return false;
    }

    /**
     * Devuelve un array con las direcciones asociadas al cliente.
     *
     * @return DireccionCliente[]
     */
    public function getDirecciones()
    {
        $dirModel = new DireccionCliente();

        return $dirModel->all([new DataBaseWhere('codcliente', $this->codcliente)]);
    }

    /**
     * Devuelve un array con todas las subcuentas asociadas al cliente.
     * Una para cada ejercicio.
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
     * Devuelve la subcuenta asociada al cliente para el ejercicio $eje.
     * Si no existe intenta crearla. Si falla devuelve False.
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

                $this->miniLog->alert($this->i18n->trans('cant-associate-customer-subaccount', [$this->codcliente]));

                return false;
            }

            $this->miniLog->alert($this->i18n->trans('cant-create-customer-subaccount', [$this->codcliente]));

            return false;
        }

        $this->miniLog->alert($this->i18n->trans('account-not-found'));
        $this->miniLog->alert($this->i18n->trans('accounting-plan-imported?'));

        return false;
    }

    /**
     * Devuelve true si no hay errores en los valores de las propiedades del modelo.
     *
     * @return bool
     */
    public function test()
    {
        $status = false;

        if ($this->codcliente === null) {
            $this->codcliente = $this->getNewCodigo();
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

        /// validamos los dias de pago
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
            $this->miniLog->alert($this->i18n->trans('not-valid-client-code', [$this->codcliente]), ['fieldname' => 'codcliente']);
        } elseif (empty($this->nombre) || strlen($this->nombre) > 100) {
            $this->miniLog->alert($this->i18n->trans('not-valid-client-name', [$this->nombre]), ['fieldname' => 'nombre']);
        } elseif (empty($this->razonsocial) || strlen($this->razonsocial) > 100) {
            $this->miniLog->alert($this->i18n->trans('not-valid-client-business-name', [$this->razonsocial]), ['fieldname' => 'razonsocial']);
        } else {
            $status = true;
        }

        return $status;
    }

    /**
     * Devuelve un array con las combinaciones que contienen $query en su nombre
     * o razonsocial o codcliente o cifnif o telefono1 o telefono2 o observaciones.
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

        $consulta = 'SELECT * FROM ' . $this->tableName() . ' WHERE debaja = FALSE AND ';
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

        $data = $this->dataBase->selectLimit($consulta, FS_ITEM_LIMIT, $offset);
        if (!empty($data)) {
            foreach ($data as $d) {
                $clilist[] = new self($d);
            }
        }

        return $clilist;
    }
}
