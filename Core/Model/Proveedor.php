<?php
/**
 * This file is part of facturacion_base
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  neorazorx@gmail.com
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

/**
 * Un proveedor. Puede estar relacionado con varias direcciones o subcuentas.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class Proveedor
{

    use Base\ModelTrait;
    use Base\Persona;

    /**
     * TODO
     * @var array
     */
    private static $regimenes_iva;

    /**
     * Clave primaria. Varchar (6).
     * @var string
     */
    public $codproveedor;

    /**
     * Régimen de fiscalidad del proveedor. Por ahora solo están implementados
     * general y exento.
     * @var string
     */
    public $regimeniva;

    /**
     * TRUE -> el proveedor es un acreedor, es decir, no le compramos mercancia,
     * le compramos servicios, etc.
     * @var bool
     */
    public $acreedor;

    /**
     * TRUE -> ya no queremos nada con el proveedor.
     * @var bool
     */
    public $debaja;

    /**
     * Fecha en la que se dió de baja al proveedor.
     * @var string
     */
    public $fechabaja;

    /**
     * Cliente asociado equivalente
     * @var string
     */
    public $codcliente;

    public function tableName()
    {
        return 'proveedores';
    }

    public function primaryColumn()
    {
        return 'codproveedor';
    }

    /**
     * Resetea los valores de todas las propiedades modelo.
     */
    public function clear()
    {
        $this->codproveedor = null;
        $this->nombre = '';
        $this->razonsocial = '';
        //$this->tipoidfiscal = FS_CIFNIF;
        $this->cifnif = '';
        $this->telefono1 = '';
        $this->telefono2 = '';
        $this->fax = '';
        $this->email = '';
        $this->web = '';

        /**
         * Ponemos por defecto la serie a NULL para que en las nuevas compras
         * a este proveedor se utilice la serie por defecto de la empresa.
         * NULL => usamos la serie de la empresa.
         */
        $this->codserie = null;


        $this->coddivisa = $this->defaultItems->codDivisa();
        $this->codpago = $this->defaultItems->codPago();
        $this->observaciones = '';
        $this->regimeniva = 'General';
        $this->acreedor = false;
        $this->personafisica = true;

        $this->debaja = false;
        $this->fechabaja = null;
        $this->codcliente = null;
    }

    /**
     * Devuelve un array con los regimenes de iva disponibles.
     * @return array
     */
    public function regimenesIva()
    {
        if (self::$regimenes_iva === null) {
            /// Si hay usa lista personalizada en fs_vars, la usamos
            $fsvar = new FsVar();
            $data = $fsvar->simpleGet('proveedor::regimenes_iva');
            if (!empty($data)) {
                self::$regimenes_iva = [];
                foreach (explode(',', $data) as $d) {
                    self::$regimenes_iva[] = trim($d);
                }
            } else {
                /// sino usamos estos
                self::$regimenes_iva = ['General', 'Exento'];
            }

            /// además de los que haya en la base de datos
            $sql = 'SELECT DISTINCT regimeniva FROM proveedores ORDER BY regimeniva ASC;';
            $data = $this->dataBase->select($sql);
            if (!empty($data)) {
                foreach ($data as $d) {
                    if (!in_array($d['regimeniva'], self::$regimenes_iva, false)) {
                        self::$regimenes_iva[] = $d['regimeniva'];
                    }
                }
            }
        }

        return self::$regimenes_iva;
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
            return new Proveedor($data[0]);
        }
        return false;
    }

    /**
     * Devuelve el primer proveedor con $email como email.
     *
     * @param string $email
     *
     * @return bool|Proveedor
     */
    public function getByEmail($email)
    {
        $email = mb_strtolower($email, 'UTF8');
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE lower(email) = ' . $this->var2str($email) . ';';

        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            return new Proveedor($data[0]);
        }
        return false;
    }

    /**
     * Devuelve un nuevo código que se usará como clave primaria/identificador único para este proveedor.
     * @return string
     */
    public function getNewCodigo()
    {
        $sql = 'SELECT MAX(' . $this->dataBase->sql2Int('codproveedor') . ') as cod FROM ' . $this->tableName() . ';';
        $cod = $this->dataBase->select($sql);
        if (!empty($cod)) {
            return sprintf('%06s', 1 + (int) $cod[0]['cod']);
        }
        return '000001';
    }

    /**
     * Devuelve las subcuentas asociadas al proveedor, una para cada ejercicio.
     * @return array
     */
    public function getSubcuentas()
    {
        $sublist = [];
        $subcp = new SubcuentaProveedor();
        foreach ($subcp->allFromProveedor($this->codproveedor) as $s) {
            $s2 = $s->getSubcuenta();
            if ($s2) {
                $sublist[] = $s2;
            } else {
                $s->delete();
            }
        }

        return $sublist;
    }

    /**
     * Devuelve la subcuenta asignada al proveedor para el ejercicio $codeje,
     * si no hay una subcuenta asignada, intenta crearla. Si falla devuelve FALSE.
     *
     * @param string $codeje
     *
     * @return subcuenta
     */
    public function getSubcuenta($codeje)
    {
        $subcuenta = false;

        foreach ($this->getSubcuentas() as $s) {
            if ($s->codejercicio === $codeje) {
                $subcuenta = $s;
                break;
            }
        }

        if (!$subcuenta) {
            /// intentamos crear la subcuenta y asociarla
            $continuar = true;
            $cuenta = new Cuenta();

            $cpro = $cuenta->getCuentaesp('PROVEE', $codeje);
            if ($this->acreedor) {
                $cpro = $cuenta->getCuentaesp('ACREED', $codeje);
                if (!$cpro) {
                    $cpro = $cuenta->getByCodigo('410', $codeje);
                }
                if (!$cpro) {
                    $cpro = $cuenta->getCuentaesp('PROVEE', $codeje);
                }
            }

            if ($cpro) {
                $continuar = false;

                $subc0 = $cpro->newSubcuenta($this->codproveedor);
                if ($subc0) {
                    $subc0->descripcion = $this->razonsocial;
                    if ($subc0->save()) {
                        $continuar = true;
                    }
                }

                if ($continuar) {
                    $scpro = new SubcuentaProveedor();
                    $scpro->codejercicio = $codeje;
                    $scpro->codproveedor = $this->codproveedor;
                    $scpro->codsubcuenta = $subc0->codsubcuenta;
                    $scpro->idsubcuenta = $subc0->idsubcuenta;
                    if ($scpro->save()) {
                        $subcuenta = $subc0;
                    } else {
                        $this->miniLog->alert('Imposible asociar la subcuenta para el proveedor '
                            . $this->codproveedor);
                    }
                } else {
                    $this->miniLog->alert('Imposible crear la subcuenta para el proveedor ' . $this->codproveedor);
                }
            } else {
                /// obtenemos una url para el mensaje, pero a prueba de errores.
                $ejeUrl = '';
                $eje0 = new Ejercicio();
                $ejercicio = $eje0->get($codeje);
                if ($ejercicio) {
                    $ejeUrl = $ejercicio->url();
                }

                $this->miniLog->alert('No se encuentra ninguna cuenta especial para proveedores en el ejercicio '
                    . $codeje . ' ¿<a href="' . $ejeUrl . '">Has importado los datos del ejercicio</a>?');
            }
        }

        return $subcuenta;
    }

    /**
     * Devuelve las direcciones asociadas al proveedor.
     * @return array
     */
    public function getDirecciones()
    {
        $dir = new DireccionProveedor();
        return $dir->allFromProveedor($this->codproveedor);
    }

    /**
     * TODO
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
            $this->miniLog->alert('Código de proveedor no válido.');
        } elseif (empty($this->nombre) || strlen($this->nombre) > 100) {
            $this->miniLog->alert('Nombre de proveedor no válido.');
        } elseif (empty($this->razonsocial) || strlen($this->razonsocial) > 100) {
            $this->miniLog->alert('Razón social del proveedor no válida.');
        } else {
            $status = true;
        }

        return $status;
    }

    /**
     * TODO
     *
     * @param string $query
     * @param int $offset
     *
     * @return array
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
                $prolist[] = new Proveedor($d);
            }
        }

        return $prolist;
    }
}
