<?php

/*
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

use FacturaScripts\Core\Base\Model;

/**
 * Albarán de proveedor o albarán de compra. Representa la recepción
 * de un material que se ha comprado. Implica la entrada de ese material
 * al almacén.
 * 
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class AlbaranProveedor 
{
    Use Model;

    /**
     * Clave primaria. Integer
     * @var integer 
     */
    public $idalbaran;

    /**
     * ID de la factura relacionada, si la hay.
     * @var integer 
     */
    public $idfactura;

    /**
     * Identificador único de cara a humanos.
     * @var type 
     */
    public $codigo;

    /**
     * Número del albarán.
     * Único dentro de la serie+ejercicio.
     * @var type 
     */
    public $numero;

    /**
     * Número de albarán de proveedor, si lo hay.
     * Puede contener letras.
     * @var type 
     */
    public $numproveedor;

    /**
     * Ejercicio relacionado. El que corresponde a la fecha.
     * @var type 
     */
    public $codejercicio;

    /**
     * Serie relacionada.
     * @var type 
     */
    public $codserie;

    /**
     * Divisa del albarán.
     * @var type 
     */
    public $coddivisa;

    /**
     * Forma de pago asociada.
     * @var type 
     */
    public $codpago;

    /**
     * Empleado que ha creado este albarán.
     * @var type 
     */
    public $codagente;

    /**
     * Almacén en el que entra la mercancía.
     * @var type 
     */
    public $codalmacen;
    public $fecha;
    public $hora;

    /**
     * Código del proveedor de este albarán.
     * @var type 
     */
    public $codproveedor;
    public $nombre;
    public $cifnif;

    /**
     * Suma del pvptotal de líneas. Total del albarán antes de impuestos.
     * @var type 
     */
    public $neto;

    /**
     * Suma total del albarán, con impuestos.
     * @var type 
     */
    public $total;

    /**
     * Suma del IVA de las líneas.
     * @var type 
     */
    public $totaliva;

    /**
     * Total expresado en euros, por si no fuese la divisa del albarán.
     * totaleuros = total/tasaconv
     * No hace falta rellenarlo, al hacer save() se calcula el valor.
     * @var type 
     */
    public $totaleuros;

    /**
     * % de retención IRPF del albarán. Se obtiene de la serie.
     * Cada línea puede tener un % distinto.
     * @var type 
     */
    public $irpf;

    /**
     * Suma total de las retenciones IRPF de las líneas.
     * @var type 
     */
    public $totalirpf;

    /**
     * Tasa de conversión a Euros de la divisa seleccionada.
     * @var type 
     */
    public $tasaconv;

    /**
     * Suma total del recargo de equivalencia de las líneas.
     * @var type 
     */
    public $totalrecargo;
    public $observaciones;

    /**
     * TRUE => está pendiente de factura.
     * @var type 
     */
    public $ptefactura;

    /**
     * Número de documentos adjuntos.
     * @var integer 
     */
    public $numdocs;

    public function __construct(array $data = []) 
    {
        $this->init(__CLASS__, 'albaranesprov', 'idalbaran');
        if (!empty($data)) {
            $this->loadFromData($data);
        } else {
            $this->clear();
        }
    }
	
    public function clear()
    {
        $this->idalbaran = NULL;
        $this->idfactura = NULL;
        $this->codigo = '';
        $this->numero = '';
        $this->numproveedor = '';
        $this->codejercicio = NULL;
        $this->codserie = $this->defaultItems->codserie();
        $this->coddivisa = NULL;
        $this->codpago = $this->defaultItems->codpago();
        $this->codagente = NULL;
        $this->codalmacen = $this->defaultItems->codalmacen();
        $this->fecha = Date('d-m-Y');
        $this->hora = Date('H:i:s');
        $this->codproveedor = NULL;
        $this->nombre = '';
        $this->cifnif = '';
        $this->neto = 0;
        $this->total = 0;
        $this->totaliva = 0;
        $this->totaleuros = 0;
        $this->irpf = 0;
        $this->totalirpf = 0;
        $this->tasaconv = 1;
        $this->totalrecargo = 0;
        $this->observaciones = '';
        $this->ptefactura = TRUE;

        $this->numdocs = 0;
    }

    protected function install() {
        /// nos aseguramos de que se comprueban las tablas de facturas y series antes
        // new \serie();
        // new \factura_proveedor();

        return '';
    }

    public function observaciones_resume() {
        if ($this->observaciones == '') {
            return '-';
        } else if (strlen($this->observaciones) < 60) {
            return $this->observaciones;
        } else {
                    return substr($this->observaciones, 0, 50) . '...';
        }
    }

    public function url() {
        if (is_null($this->idalbaran)) {
            return 'index.php?page=compras_albaranes';
        } else {
                    return 'index.php?page=compras_albaran&id=' . $this->idalbaran;
        }
    }

    public function factura_url() {
        if (is_null($this->idfactura)) {
            return '#';
        } else {
                    return 'index.php?page=compras_factura&id=' . $this->idfactura;
        }
    }

    public function agente_url() {
        if (is_null($this->codagente)) {
            return "index.php?page=admin_agentes";
        } else {
                    return "index.php?page=admin_agente&cod=" . $this->codagente;
        }
    }

    public function proveedor_url() {
        if (is_null($this->codproveedor)) {
            return "index.php?page=compras_proveedores";
        } else {
                    return "index.php?page=compras_proveedor&cod=" . $this->codproveedor;
        }
    }

    public function get_lineas() {
        $linea = new \linea_albaran_proveedor();
        return $linea->all_from_albaran($this->idalbaran);
    }

    /**
     * Devuelve el albraán solicitado o false si no lo encuentra.
     * @param type $id
     * @return \albaran_proveedor|boolean
     */
    public function get($id) {
        $albaran = self::$dataBase->select("SELECT * FROM " . $this->table_name . " WHERE idalbaran = " . $this->var2str($id) . ";");
        if ($albaran) {
            return new \albaran_proveedor($albaran[0]);
        } else {
                    return FALSE;
        }
    }

    public function exists() {
        if (is_null($this->idalbaran)) {
            return FALSE;
        } else {
                    return self::$dataBase->select("SELECT * FROM " . $this->table_name . " WHERE idalbaran = " . $this->var2str($this->idalbaran) . ";");
        }
    }

    /**
     * Genera un nuevo código y número para el albarán
     */
    public function new_codigo() {
        $this->numero = fs_documento_new_numero($this->db, $this->table_name, $this->codejercicio, $this->codserie, 'nalbaranprov');
        $this->codigo = fs_documento_new_codigo(FS_ALBARAN, $this->codejercicio, $this->codserie, $this->numero, 'C');
    }

    /**
     * Comprueba los datos del albarán, devuelve TRUE si está todo correcto
     * @return boolean
     */
    public function test() {
        $this->nombre = $this->no_html($this->nombre);
        if ($this->nombre == '') {
            $this->nombre = '-';
        }

        $this->numproveedor = $this->no_html($this->numproveedor);
        $this->observaciones = $this->no_html($this->observaciones);

        /**
         * Usamos el euro como divisa puente a la hora de sumar, comparar
         * o convertir cantidades en varias divisas. Por este motivo necesimos
         * muchos decimales.
         */
        $this->totaleuros = round($this->total / $this->tasaconv, 5);

        if ($this->idfactura) {
            $this->ptefactura = FALSE;
        }

        if ($this->floatcmp($this->total, $this->neto + $this->totaliva - $this->totalirpf + $this->totalrecargo, FS_NF0, TRUE)) {
            return TRUE;
        } else {
            $this->new_error_msg("Error grave: El total está mal calculado. ¡Avisa al informático!");
            return FALSE;
        }
    }

    /**
     * Comprobaciones extra para el albarán. Devuelve TRUE si está todo correcto
     * @param type $duplicados
     * @return boolean
     */
    public function full_test($duplicados = TRUE) {
        $status = TRUE;

        /// comprobamos las líneas
        $neto = 0;
        $iva = 0;
        $irpf = 0;
        $recargo = 0;
        foreach ($this->get_lineas() as $l) {
            if (!$l->test()) {
                $status = FALSE;
            }

            $neto += $l->pvptotal;
            $iva += $l->pvptotal * $l->iva / 100;
            $irpf += $l->pvptotal * $l->irpf / 100;
            $recargo += $l->pvptotal * $l->recargo / 100;
        }

        $neto = round($neto, FS_NF0);
        $iva = round($iva, FS_NF0);
        $irpf = round($irpf, FS_NF0);
        $recargo = round($recargo, FS_NF0);
        $total = $neto + $iva - $irpf + $recargo;

        if (!$this->floatcmp($this->neto, $neto, FS_NF0, TRUE)) {
            $this->new_error_msg("Valor neto del " . FS_ALBARAN . ' ' . $this->codigo . " incorrecto. Valor correcto: " . $neto);
            $status = FALSE;
        } else if (!$this->floatcmp($this->totaliva, $iva, FS_NF0, TRUE)) {
            $this->new_error_msg("Valor totaliva del " . FS_ALBARAN . ' ' . $this->codigo . " incorrecto. Valor correcto: " . $iva);
            $status = FALSE;
        } else if (!$this->floatcmp($this->totalirpf, $irpf, FS_NF0, TRUE)) {
            $this->new_error_msg("Valor totalirpf del " . FS_ALBARAN . ' ' . $this->codigo . " incorrecto. Valor correcto: " . $irpf);
            $status = FALSE;
        } else if (!$this->floatcmp($this->totalrecargo, $recargo, FS_NF0, TRUE)) {
            $this->new_error_msg("Valor totalrecargo del " . FS_ALBARAN . ' ' . $this->codigo . " incorrecto. Valor correcto: " . $recargo);
            $status = FALSE;
        } else if (!$this->floatcmp($this->total, $total, FS_NF0, TRUE)) {
            $this->new_error_msg("Valor total del " . FS_ALBARAN . ' ' . $this->codigo . " incorrecto. Valor correcto: " . $total);
            $status = FALSE;
        }

        if ($this->total != 0) {
            /// comprobamos las facturas asociadas
            $linea_factura = new \linea_factura_proveedor();
            $facturas = $linea_factura->facturas_from_albaran($this->idalbaran);
            if ($facturas) {
                if (count($facturas) > 1) {
                    $msg = "Este " . FS_ALBARAN . " esta asociado a las siguientes facturas (y no debería):";
                    foreach ($facturas as $f) {
                        $msg .= " <a href='" . $f->url() . "'>" . $f->codigo . "</a>";
                    }
                    $this->new_error_msg($msg);
                    $status = FALSE;
                } else if ($facturas[0]->idfactura != $this->idfactura) {
                    $this->new_error_msg("Este " . FS_ALBARAN . " esta asociado a una <a href='" . $this->factura_url() .
                            "'>factura</a> incorrecta. La correcta es <a href='" . $facturas[0]->url() . "'>esta</a>.");
                    $status = FALSE;
                }
            } else if (isset($this->idfactura)) {
                $this->new_error_msg("Este " . FS_ALBARAN . " esta asociado a una <a href='" . $this->factura_url()
                        . "'>factura</a> que ya no existe. <b>Corregido</b>.");
                $this->idfactura = NULL;
                $this->save();

                $status = FALSE;
            }
        }

        if ($status AND $duplicados) {
            /// comprobamos si es un duplicado
            $data = self::$dataBase->select("SELECT * FROM " . $this->table_name . " WHERE fecha = " . $this->var2str($this->fecha)
                    . " AND codproveedor = " . $this->var2str($this->codproveedor)
                    . " AND total = " . $this->var2str($this->total)
                    . " AND codagente = " . $this->var2str($this->codagente)
                    . " AND numproveedor = " . $this->var2str($this->numproveedor)
                    . " AND observaciones = " . $this->var2str($this->observaciones)
                    . " AND idalbaran != " . $this->var2str($this->idalbaran) . ";");
            if ($data) {
                foreach ($data as $alb) {
                    /// comprobamos las líneas
                    $aux = self::$dataBase->select("SELECT referencia FROM lineasalbaranesprov WHERE
                  idalbaran = " . $this->var2str($this->idalbaran) . "
                  AND referencia NOT IN (SELECT referencia FROM lineasalbaranesprov
                  WHERE idalbaran = " . $this->var2str($alb['idalbaran']) . ");");
                    if (!$aux) {
                        $this->new_error_msg("Este " . FS_ALBARAN . " es un posible duplicado de
                     <a href='index.php?page=compras_albaran&id=" . $alb['idalbaran'] . "'>este otro</a>.
                     Si no lo es, para evitar este mensaje, simplemente modifica las observaciones.");
                        $status = FALSE;
                    }
                }
            }
        }

        return $status;
    }

    /**
     * Guarda los datos en la base de datos
     * @return boolean
     */
    public function save() {
        if ($this->test()) {
            if ($this->exists()) {
                $sql = "UPDATE " . $this->table_name . " SET idfactura = " . $this->var2str($this->idfactura)
                        . ", codigo = " . $this->var2str($this->codigo)
                        . ", numero = " . $this->var2str($this->numero)
                        . ", numproveedor = " . $this->var2str($this->numproveedor)
                        . ", codejercicio = " . $this->var2str($this->codejercicio)
                        . ", codserie = " . $this->var2str($this->codserie)
                        . ", coddivisa = " . $this->var2str($this->coddivisa)
                        . ", codpago = " . $this->var2str($this->codpago)
                        . ", codagente = " . $this->var2str($this->codagente)
                        . ", codalmacen = " . $this->var2str($this->codalmacen)
                        . ", fecha = " . $this->var2str($this->fecha)
                        . ", codproveedor = " . $this->var2str($this->codproveedor)
                        . ", nombre = " . $this->var2str($this->nombre)
                        . ", cifnif = " . $this->var2str($this->cifnif)
                        . ", neto = " . $this->var2str($this->neto)
                        . ", total = " . $this->var2str($this->total)
                        . ", totaliva = " . $this->var2str($this->totaliva)
                        . ", totaleuros = " . $this->var2str($this->totaleuros)
                        . ", irpf = " . $this->var2str($this->irpf)
                        . ", totalirpf = " . $this->var2str($this->totalirpf)
                        . ", tasaconv = " . $this->var2str($this->tasaconv)
                        . ", totalrecargo = " . $this->var2str($this->totalrecargo)
                        . ", observaciones = " . $this->var2str($this->observaciones)
                        . ", hora = " . $this->var2str($this->hora)
                        . ", ptefactura = " . $this->var2str($this->ptefactura)
                        . ", numdocs = " . $this->var2str($this->numdocs)
                        . "  WHERE idalbaran = " . $this->var2str($this->idalbaran) . ";";

                return self::$dataBase->exec($sql);
            } else {
                $this->new_codigo();
                $sql = "INSERT INTO " . $this->table_name . " (codigo,numero,numproveedor,
               codejercicio,codserie,coddivisa,codpago,codagente,codalmacen,fecha,codproveedor,
               nombre,cifnif,neto,total,totaliva,totaleuros,irpf,totalirpf,tasaconv,
               totalrecargo,observaciones,ptefactura,hora,numdocs) VALUES
                      (" . $this->var2str($this->codigo)
                        . "," . $this->var2str($this->numero)
                        . "," . $this->var2str($this->numproveedor)
                        . "," . $this->var2str($this->codejercicio)
                        . "," . $this->var2str($this->codserie)
                        . "," . $this->var2str($this->coddivisa)
                        . "," . $this->var2str($this->codpago)
                        . "," . $this->var2str($this->codagente)
                        . "," . $this->var2str($this->codalmacen)
                        . "," . $this->var2str($this->fecha)
                        . "," . $this->var2str($this->codproveedor)
                        . "," . $this->var2str($this->nombre)
                        . "," . $this->var2str($this->cifnif)
                        . "," . $this->var2str($this->neto)
                        . "," . $this->var2str($this->total)
                        . "," . $this->var2str($this->totaliva)
                        . "," . $this->var2str($this->totaleuros)
                        . "," . $this->var2str($this->irpf)
                        . "," . $this->var2str($this->totalirpf)
                        . "," . $this->var2str($this->tasaconv)
                        . "," . $this->var2str($this->totalrecargo)
                        . "," . $this->var2str($this->observaciones)
                        . "," . $this->var2str($this->ptefactura)
                        . "," . $this->var2str($this->hora)
                        . "," . $this->var2str($this->numdocs) . ");";

                if (self::$dataBase->exec($sql)) {
                    $this->idalbaran = self::$dataBase->lastval();
                    return TRUE;
                } else {
                                    return FALSE;
                }
            }
        } else {
                    return FALSE;
        }
    }

    /**
     * Elimina el albarán de la base de datos
     * @return boolean
     */
    public function delete() {
        if (self::$dataBase->exec("DELETE FROM " . $this->table_name . " WHERE idalbaran = " . $this->var2str($this->idalbaran) . ";")) {
            if ($this->idfactura) {
                /**
                 * Delegamos la eliminación de la factura en la clase correspondiente,
                 * que tendrá que hacer más cosas.
                 */
                $factura = new \factura_proveedor();
                $factura0 = $factura->get($this->idfactura);
                if ($factura0) {
                    $factura0->delete();
                }
            }

            $this->new_message(ucfirst(FS_ALBARAN) . " de compra " . $this->codigo . " eliminado correctamente.");
            return TRUE;
        } else {
                    return FALSE;
        }
    }

    /**
     * Devuelve un array con los últimos albaranes
     * @param integer $offset
     * @param type $order
     * @return \albaran_proveedor
     */
    public function all($offset = 0, $order = 'fecha DESC, codigo DESC', $limit = FS_ITEM_LIMIT) {
        $albalist = array();
        $sql = "SELECT * FROM " . $this->table_name . " ORDER BY " . $order;

        $data = self::$dataBase->select_limit($sql, $limit, $offset);
        if ($data) {
            foreach ($data as $a) {
                $albalist[] = new \albaran_proveedor($a);
            }
        }

        return $albalist;
    }

    /**
     * Devuelve un array con los albaranes pendientes
     * @param integer $offset
     * @param type $order
     * @return \albaran_proveedor
     */
    public function all_ptefactura($offset = 0, $order = 'fecha ASC, codigo ASC', $limit = FS_ITEM_LIMIT) {
        $albalist = array();
        $sql = "SELECT * FROM " . $this->table_name . " WHERE ptefactura = true ORDER BY " . $order;

        $data = self::$dataBase->select_limit($sql, $limit, $offset);
        if ($data) {
            foreach ($data as $a) {
                $albalist[] = new \albaran_proveedor($a);
            }
        }

        return $albalist;
    }

    /**
     * Devuelve un array con los albaranes del proveedor
     * @param type $codproveedor
     * @param integer $offset
     * @return \albaran_proveedor
     */
    public function all_from_proveedor($codproveedor, $offset = 0) {
        $alblist = array();
        $sql = "SELECT * FROM " . $this->table_name . " WHERE codproveedor = "
                . $this->var2str($codproveedor) . " ORDER BY fecha DESC, codigo DESC";

        $data = self::$dataBase->select_limit($sql, FS_ITEM_LIMIT, $offset);
        if ($data) {
            foreach ($data as $a) {
                $alblist[] = new \albaran_proveedor($a);
            }
        }

        return $alblist;
    }

    /**
     * Devuelve un array con los albaranes del agente/empleado
     * @param type $codagente
     * @param integer $offset
     * @return \albaran_proveedor
     */
    public function all_from_agente($codagente, $offset = 0) {
        $alblist = array();
        $sql = "SELECT * FROM " . $this->table_name . " WHERE codagente = "
                . $this->var2str($codagente) . " ORDER BY fecha DESC, codigo DESC";

        $data = self::$dataBase->select_limit($sql, FS_ITEM_LIMIT, $offset);
        if ($data) {
            foreach ($data as $a) {
                $alblist[] = new \albaran_proveedor($a);
            }
        }

        return $alblist;
    }

    /**
     * Devuelve un array con los albaranes relacionados con la factura $id
     * @param type $id
     * @return \albaran_proveedor
     */
    public function all_from_factura($id) {
        $alblist = array();
        $sql = "SELECT * FROM " . $this->table_name . " WHERE idfactura = "
                . $this->var2str($id) . " ORDER BY fecha DESC, codigo DESC";

        $data = self::$dataBase->select($sql);
        if ($data) {
            foreach ($data as $a) {
                $alblist[] = new \albaran_proveedor($a);
            }
        }

        return $alblist;
    }

    /**
     * Devuelve un array con los albaranes comprendidos entre $desde y $hasta
     * @param type $desde
     * @param type $hasta
     * @return \albaran_proveedor
     */
    public function all_desde($desde, $hasta) {
        $alblist = array();
        $sql = "SELECT * FROM " . $this->table_name . " WHERE fecha >= "
                . $this->var2str($desde) . " AND fecha <= " . $this->var2str($hasta)
                . " ORDER BY codigo ASC;";

        $data = self::$dataBase->select($sql);
        if ($data) {
            foreach ($data as $a) {
                $alblist[] = new \albaran_proveedor($a);
            }
        }

        return $alblist;
    }

    /**
     * Devuelve un array con los albaranes que coinciden con $query
     * @param type $query
     * @param integer $offset
     * @return \albaran_proveedor
     */
    public function search($query, $offset = 0) {
        $alblist = array();
        $query = $this->no_html(mb_strtolower($query, 'UTF8'));

        $consulta = "SELECT * FROM " . $this->table_name . " WHERE ";
        if (is_numeric($query)) {
            $consulta .= "codigo LIKE '%" . $query . "%' OR numproveedor LIKE '%" . $query . "%' OR observaciones LIKE '%" . $query . "%'";
        } else {
            $consulta .= "lower(codigo) LIKE '%" . $query . "%' OR lower(numproveedor) LIKE '%" . $query . "%' "
                    . "OR lower(observaciones) LIKE '%" . str_replace(' ', '%', $query) . "%'";
        }
        $consulta .= " ORDER BY fecha DESC, codigo DESC";

        $data = self::$dataBase->select_limit($consulta, FS_ITEM_LIMIT, $offset);
        if ($data) {
            foreach ($data as $a) {
                $alblist[] = new \albaran_proveedor($a);
            }
        }

        return $alblist;
    }

    /**
     * Devuelve un array con los albaranes del proveedor $codproveedor
     * que coincidan con los filtros.
     * @param type $codproveedor
     * @param type $desde
     * @param type $hasta
     * @param type $codserie
     * @param type $coddivisa
     * @return \albaran_proveedor
     */
    public function search_from_proveedor($codproveedor, $desde, $hasta, $codserie = '', $coddivisa = '') {
        $albalist = array();
        $sql = "SELECT * FROM " . $this->table_name . " WHERE codproveedor = "
                . $this->var2str($codproveedor) . " AND ptefactura AND fecha BETWEEN "
                . $this->var2str($desde) . " AND " . $this->var2str($hasta);

        if ($codserie) {
            $sql .= " AND codserie = " . $this->var2str($codserie);
        }

        if ($coddivisa) {
            $sql .= " AND coddivisa = " . $this->var2str($coddivisa);
        }

        $sql .= " ORDER BY fecha ASC, codigo ASC";

        $data = self::$dataBase->select($sql);
        if ($data) {
            foreach ($data as $a) {
                $albalist[] = new \albaran_proveedor($a);
            }
        }

        return $albalist;
    }

    public function cron_job() {
        /**
         * Ponemos a NULL todos los idfactura que no están en facturasprov
         */
        self::$dataBase->exec("UPDATE " . $this->table_name . " SET idfactura = NULL WHERE idfactura IS NOT NULL"
                . " AND idfactura NOT IN (SELECT idfactura FROM facturasprov);");
    }

}
