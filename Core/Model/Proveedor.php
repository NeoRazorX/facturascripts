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
 * Un proveedor. Puede estar relacionado con varias direcciones o subcuentas.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Proveedor extends Base\Persona
{
    use Base\ModelTrait {
        __construct as private traitConstruct;
        clear as private traitClear;
        url as private traitURL;
    }

    /**
     * True -> el proveedor es un acreedor, es decir, no le compramos mercancia,
     * le compramos servicios, etc.
     *
     * @var bool
     */
    public $acreedor;

    /**
     * Proveedor constructor.
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
    public function tableName()
    {
        return 'proveedores';
    }

    /**
     * Devuelve el nombre de la columna que es clave primaria del modelo.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'codproveedor';
    }

    /**
     * Resetea los valores de todas las propiedades modelo.
     */
    public function clear()
    {
        $this->traitClear();
        parent::clear();

        $this->acreedor = false;
    }

    /**
     * Devuelve el primer proveedor que tenga ese cifnif.
     * Si el cifnif está en blanco y se proporciona una razón social, se devuelve
     * el primer proveedor con esa razón social.
     *
     * @param string $cifnif
     * @param string $razon
     *
     * @return bool|Proveedor
     */
    public function getByCifnif($cifnif, $razon = '')
    {
        if ($cifnif === '' && $razon !== '') {
            $razon = mb_strtolower(self::noHtml($razon), 'UTF8');
            $sql = 'SELECT * FROM ' . $this->tableName() . " WHERE cifnif = ''"
                . ' AND lower(razonsocial) = ' . $this->var2str($razon) . ';';
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
     * Devuelve las direcciones asociadas al proveedor.
     *
     * @return DireccionProveedor[]
     */
    public function getDirecciones()
    {
        $dirModel = new DireccionProveedor();

        return $dirModel->all([new DataBaseWhere('codproveedor', $this->codproveedor)]);
    }

    /**
     * Devuelve las subcuentas asociadas al proveedor, una para cada ejercicio.
     *
     * @return Subcuenta[]
     */
    public function getSubcuentas()
    {
        $sublist = [];
        $subcpModel = new SubcuentaProveedor();
        foreach ($subcpModel->all([new DataBaseWhere('codproveedor', $this->codproveedor)]) as $subcp) {
            $subcuenta = $subcp->getSubcuenta();
            if ($subcuenta !== false) {
                $sublist[] = $subcuenta;
            }
        }

        return $sublist;
    }

    /**
     * Devuelve la subcuenta asignada al proveedor para el ejercicio $codeje,
     * si no hay una subcuenta asignada, intenta crearla. Si falla devuelve False.
     *
     * @param string $codeje
     *
     * @return Subcuenta|false
     */
    public function getSubcuenta($codeje)
    {
        foreach ($this->getSubcuentas() as $subc) {
            if ($subc->codejercicio === $codeje) {
                return $subc;
            }
        }

        $cuentaModel = new Cuenta();
        $cpro = $cuentaModel->getCuentaesp('PROVEE', $codeje);
        if ($this->acreedor) {
            $cpro = $cuentaModel->getCuentaesp('ACREED', $codeje);
            if (!$cpro) {
                $cpro = $cuentaModel->getByCodigo('410', $codeje);
            }
            if (!$cpro) {
                $cpro = $cuentaModel->getCuentaesp('PROVEE', $codeje);
            }
        }

        if ($cpro) {
            $continuar = false;

            $subcuenta = $cpro->newSubcuenta($this->codproveedor);
            if ($subcuenta) {
                $subcuenta->descripcion = $this->razonsocial;
                if ($subcuenta->save()) {
                    $continuar = true;
                }
            }

            if ($continuar) {
                $scpro = new SubcuentaProveedor();
                $scpro->codejercicio = $codeje;
                $scpro->codproveedor = $this->codproveedor;
                $scpro->codsubcuenta = $subcuenta->codsubcuenta;
                $scpro->idsubcuenta = $subcuenta->idsubcuenta;
                if ($scpro->save()) {
                    return $subcuenta;
                }

                $this->miniLog->alert($this->i18n->trans('cant-assing-subaccount-supplier', [$this->codproveedor]));

                return false;
            }

            $this->miniLog->alert($this->i18n->trans('cant-create-subaccount-supplier', [$this->codproveedor]));

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

        if ($this->codproveedor === null) {
            $this->codproveedor = $this->getNewCodigo();
        } else {
            $this->codproveedor = trim($this->codproveedor);
        }

        $this->nombre = self::noHtml($this->nombre);
        $this->razonsocial = self::noHtml($this->razonsocial);
        $this->cifnif = self::noHtml($this->cifnif);
        $this->observaciones = self::noHtml($this->observaciones);

        if (!preg_match('/^[A-Z0-9]{1,6}$/i', $this->codproveedor)) {
            $this->miniLog->alert($this->i18n->trans('not-valid-supplier-code'));
        } elseif (empty($this->nombre) || strlen($this->nombre) > 100) {
            $this->miniLog->alert($this->i18n->trans('not-valid-supplier-name'));
        } elseif (empty($this->razonsocial) || strlen($this->razonsocial) > 100) {
            $this->miniLog->alert($this->i18n->trans('not-valid-supplier-business-name'));
        } else {
            $status = true;
        }

        return $status;
    }

    /**
     * Devuelve un array con las combinaciones que contienen $query en su nombre
     * o razonsocial o codproveedor o cifnif o telefono1 o telefono2 o observaciones.
     *
     * @param string $query
     * @param int    $offset
     *
     * @return self[]
     */
    public function search($query, $offset = 0)
    {
        $prolist = [];
        $query = mb_strtolower(self::noHtml($query), 'UTF8');

        $consulta = 'SELECT * FROM ' . $this->tableName() . ' WHERE ';
        if (is_numeric($query)) {
            $consulta .= "nombre LIKE '%" . $query . "%' OR razonsocial LIKE '%" . $query . "%'"
                . " OR codproveedor LIKE '%" . $query . "%' OR cifnif LIKE '%" . $query . "%'"
                . " OR telefono1 LIKE '" . $query . "%' OR telefono2 LIKE '" . $query . "%'"
                . " OR observaciones LIKE '%" . $query . "%'";
        } else {
            $buscar = str_replace(' ', '%', $query);
            $consulta .= "lower(nombre) LIKE '%" . $buscar . "%' OR lower(razonsocial) LIKE '%" . $buscar . "%'"
                . " OR lower(cifnif) LIKE '%" . $buscar . "%' OR lower(email) LIKE '%" . $buscar . "%'"
                . " OR lower(observaciones) LIKE '%" . $buscar . "%'";
        }
        $consulta .= ' ORDER BY lower(nombre) ASC';

        $data = $this->dataBase->selectLimit($consulta, FS_ITEM_LIMIT, $offset);
        if (!empty($data)) {
            foreach ($data as $d) {
                $prolist[] = new self($d);
            }
        }

        return $prolist;
    }
    
    /**
     * Devuelve la url donde ver/modificar los datos
     *
     * @param mixed $type
     *
     * @return string
     */
    public function url($type = 'auto')
    {
        $result = 'index.php?page=';
        switch ($type) {
            case 'edit':
                $value = $this->primaryColumnValue();
                $result .= 'PanelProveedor' . '&code=' . $value;
                break;

            case 'new':
                $result .= 'PanelProveedor';
                break;

            default:
                $result = $this->traitURL($type);
                break;
        }

        return $result;
    }
}
