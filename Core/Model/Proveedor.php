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
 You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\Base\Model;

/**
 * Un proveedor. Puede estar relacionado con varias direcciones o subcuentas.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class Proveedor
{
    use Model;

    /**
     * TODO
     * @var array
     */
    private static $regimenes_iva;
    /**
     * Clave primaria. Varchar (6).
     * @var
     */
    public $codproveedor;
    /**
     * Nombre por el que se conoce al proveedor, puede ser el nombre oficial o no.
     * @var
     */
    public $nombre;
    /**
     * Razón social del proveedor, es decir, el nombre oficial, el que se usa en
     * las facturas.
     * @var
     */
    public $razonsocial;
    /**
     * Tipo de identificador fiscal del proveedor.
     * Ejemplo: NIF, CIF, CUIT...
     * @var
     */
    public $tipoidfiscal;
    /**
     * Identificador fiscal del proveedor.
     * @var
     */
    public $cifnif;
    /**
     * TODO
     * @var
     */
    public $telefono1;
    /**
     * TODO
     * @var
     */
    public $telefono2;
    /**
     * TODO
     * @var
     */
    public $fax;
    /**
     * TODO
     * @var
     */
    public $email;
    /**
     * TODO
     * @var
     */
    public $web;
    /**
     * Serie predeterminada para este proveedor.
     * @var
     */
    public $codserie;
    /**
     * Divisa predeterminada para este proveedor.
     * @var
     */
    public $coddivisa;
    /**
     * Forma de pago predeterminada para este proveedor.
     * @var
     */
    public $codpago;
    /**
     * TODO
     * @var
     */
    public $observaciones;
    /**
     * Régimen de fiscalidad del proveedor. Por ahora solo están implementados
     * general y exento.
     * @var
     */
    public $regimeniva;
    /**
     * TRUE -> el proveedor es un acreedor, es decir, no le compramos mercancia,
     * le compramos servicios, etc.
     * @var
     */
    public $acreedor;
    /**
     * TRUE  -> el cliente es una persona física.
     * FALSE -> el cliente es una persona jurídica (empresa).
     * @var
     */
    public $personafisica;
    /**
     * TRUE -> ya no queremos nada con el proveedor.
     * @var
     */
    public $debaja;
    /**
     * Fecha en la que se dió de baja al proveedor.
     * @var
     */
    public $fechabaja;
    /**
     * Cliente asociado equivalente
     * @var
     */
    public $codcliente;

    /**
     * Proveedor constructor.
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->init(__CLASS__, 'proveedores', 'codproveedor');
        $this->clear();
        if (!empty($data)) {
            $this->loadFromData($data);
        }
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
            if ($data) {
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
            $data = $this->database->select($sql);
            if ($data) {
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
     * Acorta el texto de observaciones
     * @return string
     */
    public function observacionesResume()
    {
        if ($this->observaciones === '') {
            return '-';
        }
        if (strlen($this->observaciones) < 60) {
            return $this->observaciones;
        }
        return substr($this->observaciones, 0, 50) . '...';
    }

    /**
     * Devuelve la url donde ver/modificar estos datos
     * @return string
     */
    public function url()
    {
        if ($this->codproveedor === null) {
            return 'index.php?page=ComprasProveedores';
        }
        return 'index.php?page=ComprasProveedor&cod=' . $this->codproveedor;
    }

    /**
     * TODO
     * @deprecated since version 50
     * @return bool
     */
    public function isDefault()
    {
        return false;
    }

    /**
     * Devuelve el primer proveedor que tenga ese cifnif.
     * Si el cifnif está en blanco y se proporciona una razón social, se devuelve
     * el primer proveedor con esa razón social.
     *
     * @param $cifnif
     * @param $razon
     *
     * @return bool|Proveedor
     */
    public function getByCifnif($cifnif, $razon = false)
    {
        if ($cifnif === '' && $razon) {
            $razon = mb_strtolower(static::noHtml($razon), 'UTF8');
            $sql = 'SELECT * FROM ' . $this->tableName() . " WHERE cifnif = ''"
                . ' AND lower(razonsocial) = ' . $this->var2str($razon) . ';';
        } else {
            $cifnif = mb_strtolower($cifnif, 'UTF8');
            $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE lower(cifnif) = ' . $this->var2str($cifnif) . ';';
        }

        $data = $this->database->select($sql);
        if ($data) {
            return new Proveedor($data[0]);
        }
        return false;
    }

    /**
     * Devuelve el primer proveedor con $email como email.
     *
     * @param $email
     *
     * @return bool|Proveedor
     */
    public function getByEmail($email)
    {
        $email = mb_strtolower($email, 'UTF8');
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE lower(email) = ' . $this->var2str($email) . ';';

        $data = $this->database->select($sql);
        if ($data) {
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
        $sql = 'SELECT MAX(' . $this->database->sql2Int('codproveedor') . ') as cod FROM ' . $this->tableName() . ';';
        $cod = $this->database->select($sql);
        if ($cod) {
            return sprintf('%06s', 1 + (int)$cod[0]['cod']);
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

            if ($this->acreedor) {
                $cpro = $cuenta->getCuentaesp('ACREED', $codeje);
                if (!$cpro) {
                    $cpro = $cuenta->getByCodigo('410', $codeje);
                }
                if (!$cpro) {
                    $cpro = $cuenta->getCuentaesp('PROVEE', $codeje);
                }
            } else {
                $cpro = $cuenta->getCuentaesp('PROVEE', $codeje);
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
                $eje_url = '';
                $eje0 = new Ejercicio();
                $ejercicio = $eje0->get($codeje);
                if ($ejercicio) {
                    $eje_url = $ejercicio->url();
                }

                $this->miniLog->alert('No se encuentra ninguna cuenta especial para proveedores en el ejercicio '
                    . $codeje . ' ¿<a href="' . $eje_url . '">Has importado los datos del ejercicio</a>?');
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

        $this->nombre = static::noHtml($this->nombre);
        $this->razonsocial = static::noHtml($this->razonsocial);
        $this->cifnif = static::noHtml($this->cifnif);
        $this->observaciones = static::noHtml($this->observaciones);

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
     * @param int $offset
     * @param bool $solo_acreedores
     *
     * @return array
     */
    public function all($offset = 0, $solo_acreedores = false)
    {
        $provelist = [];
        $sql = 'SELECT * FROM ' . $this->tableName() . ' ORDER BY lower(nombre) ASC';
        if ($solo_acreedores) {
            $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE acreedor ORDER BY lower(nombre) ASC';
        }

        $data = $this->database->selectLimit($sql, FS_ITEM_LIMIT, $offset);
        if ($data) {
            foreach ($data as $p) {
                $provelist[] = new Proveedor($p);
            }
        }

        return $provelist;
    }

    /**
     * Devuelve un array con la lista completa de proveedores.
     * @return Proveedor
     */
    public function allFull()
    {
        /// leemos la lista de la caché
        $provelist = $this->cache->get('m_proveedor_all');
        if (!$provelist) {
            /// si no la encontramos en la caché, leemos de la base de datos
            $sql = 'SELECT * FROM ' . $this->tableName() . ' ORDER BY lower(nombre) ASC;';
            $data = $this->database->select($sql);
            if ($data) {
                foreach ($data as $d) {
                    $provelist[] = new Proveedor($d);
                }
            }

            /// guardamos la lista en la caché
            $this->cache->set('m_proveedor_all', $provelist);
        }

        return $provelist;
    }

    /**
     * TODO
     * @param $query
     * @param int $offset
     *
     * @return array
     */
    public function search($query, $offset = 0)
    {
        $prolist = [];
        $query = mb_strtolower(static::noHtml($query), 'UTF8');

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

        $data = $this->database->selectLimit($consulta, FS_ITEM_LIMIT, $offset);
        if ($data) {
            foreach ($data as $d) {
                $prolist[] = new Proveedor($d);
            }
        }

        return $prolist;
    }

    /**
     * Aplicamos algunas correcciones a la tabla.
     */
    public function fixDb()
    {
        $fixes = [
            /// ponemos debaja a false en los casos que sea null
            'UPDATE ' . $this->tableName() . ' SET debaja = false WHERE debaja IS NULL;',
            /// desvinculamos de clientes que no existan
            'UPDATE ' . $this->tableName() . ' SET codcliente = null WHERE codcliente IS NOT NULL'
            . ' AND codcliente NOT IN (SELECT codcliente FROM clientes);'
        ];

        foreach ($fixes as $sql) {
            $this->database->exec($sql);
        }
    }

    /**
     * TODO
     */
    private function cleanCache()
    {
        $this->cache->delete('m_proveedor_all');
    }
}
