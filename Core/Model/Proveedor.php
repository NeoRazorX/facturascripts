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
 * A supplier. It can be related to several addresses or sub-accounts.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Proveedor extends Base\Persona
{

    use Base\ModelTrait {
        __construct as private traitConstruct;
        clear as private traitClear;
    }

    /**
     * True -> the supplier is a creditor, that is, we do not buy him merchandise,
     * we buy services, etc.
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
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'proveedores';
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'codproveedor';
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        $this->traitClear();
        parent::clear();

        $this->acreedor = false;
        $this->regimeniva = 'general';
    }

    /**
     * Returns the first provider that has that cifnif.
     * If the cifnif is blank and a business name is provided, it is returned
     * the first provider with that company name.
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
            $sql = 'SELECT * FROM ' . static::tableName() . " WHERE cifnif = ''"
                . ' AND lower(razonsocial) = ' . self::$dataBase->var2str($razon) . ';';
        } else {
            $cifnif = mb_strtolower($cifnif, 'UTF8');
            $sql = 'SELECT * FROM ' . static::tableName()
                . ' WHERE lower(cifnif) = ' . self::$dataBase->var2str($cifnif) . ';';
        }

        $data = self::$dataBase->select($sql);
        if (!empty($data)) {
            return new self($data[0]);
        }

        return false;
    }

    /**
     * Returns the addresses associated with the provider.
     *
     * @return DireccionProveedor[]
     */
    public function getDirecciones()
    {
        $dirModel = new DireccionProveedor();

        return $dirModel->all([new DataBaseWhere('codproveedor', $this->codproveedor)]);
    }

    /**
     * Returns the subaccounts associated with the provider, one for each fiscal year.
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
     * Returns the sub-account assigned to the provider for the year $codeje,
     * If there is not an assigned subaccount, try to create it. If it fails, it returns False.
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

                self::$miniLog->alert(self::$i18n->trans('cant-assing-subaccount-supplier', ['%supplierCode%' => $this->codproveedor]));

                return false;
            }

            self::$miniLog->alert(self::$i18n->trans('cant-create-subaccount-supplier', ['%supplierCode%' => $this->codproveedor]));

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

        if ($this->codproveedor === null) {
            $this->codproveedor = (string) $this->newCode();
        } else {
            $this->codproveedor = trim($this->codproveedor);
        }

        $this->nombre = self::noHtml($this->nombre);
        $this->razonsocial = self::noHtml($this->razonsocial);
        $this->cifnif = self::noHtml($this->cifnif);
        $this->observaciones = self::noHtml($this->observaciones);

        if (!preg_match('/^[A-Z0-9]{1,6}$/i', $this->codproveedor)) {
            self::$miniLog->alert(self::$i18n->trans('not-valid-supplier-code'));
        } elseif (empty($this->nombre) || strlen($this->nombre) > 100) {
            self::$miniLog->alert(self::$i18n->trans('not-valid-supplier-name'));
        } elseif (empty($this->razonsocial) || strlen($this->razonsocial) > 100) {
            self::$miniLog->alert(self::$i18n->trans('not-valid-supplier-business-name'));
        } else {
            $status = true;
        }

        return $status;
    }

    /**
     * Returns an array with combinations containing $query in its name
     * or endorsement or co-supplier or cifnif or telefono1 or telefono2 or observations.
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

        $consulta = 'SELECT * FROM ' . static::tableName() . ' WHERE ';
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

        $data = self::$dataBase->selectLimit($consulta, FS_ITEM_LIMIT, $offset);
        if (!empty($data)) {
            foreach ($data as $d) {
                $prolist[] = new self($d);
            }
        }

        return $prolist;
    }
}
