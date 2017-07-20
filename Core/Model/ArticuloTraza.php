<?php

/*
 * This file is part of facturacion_base
 * Copyright (C) 2016      Luismipr               <luismipr@gmail.com>.
 * Copyright (C) 2016-2017 Carlos García Gómez    <neorazorx@gmail.com>.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * Lpublished by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * LeGNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\Base\Model;

/**
 * Esta clase sirve para guardar la información de trazabilidad del artículo.
 * Números de serie, de lote y albaranes y facturas relacionadas.
 *
 * @author Luismipr              <luismipr@gmail.com>
 * @author Carlos García Gómez   <neorazorx@gmail.com>
 */
class ArticuloTraza
{
    Use Model;
    
    /**
     * Clave primaria
     * @var type 
     */
    public $id;

    /**
     * Referencia del artículo
     * @var type varchar 
     */
    public $referencia;

    /**
     * Numero de serie
     * Clave primaria.
     * @var type varchar 
     */
    public $numserie;

    /**
     * Número o identificador del lote
     * @var type 
     */
    public $lote;

    /**
     * Id linea albaran venta
     * @var type serial
     */
    public $idlalbventa;

    /**
     * id linea factura venta
     * @var type serial
     */
    public $idlfacventa;

    /**
     * Id linea albaran compra
     * @var type serial
     */
    public $idlalbcompra;

    /**
     * Id linea factura compra
     * @var type serial
     */
    public $idlfaccompra;
    public $fecha_entrada;
    public $fecha_salida;

    public function __construct(array $data = []) 
	{
        $this->init(__CLASS__, 'articulo_trazas', 'id');
        if (!empty($data)) {
            $this->loadFromData($data);
        } else {
            $this->clear();
        }
    }
    
    public function clear() 
    {
        $this->id = NULL;
        $this->referencia = NULL;
        $this->numserie = NULL;
        $this->lote = NULL;
        $this->idlalbventa = NULL;
        $this->idlfacventa = NULL;
        $this->idlalbcompra = NULL;
        $this->idlfaccompra = NULL;
        $this->fecha_entrada = NULL;
        $this->fecha_salida = NULL;
    }

    protected function install() {
        /// forzamos la comprobación de las tablas necesarias
        //new \articulo();
        //new \linea_albaran_cliente();
        //new \linea_albaran_proveedor();
        //new \linea_factura_cliente();
        //new \linea_factura_proveedor();

        return '';
    }

    /**
     * Devuelve la url del albarán o la factura de compra.
     * @return string
     */
    public function documento_compra_url() {
        if ($this->idlalbcompra) {
            $lin0 = new \linea_albaran_proveedor();
            $linea = $lin0->get($this->idlalbcompra);
            if ($linea) {
                return $linea->url();
            }
        } else if ($this->idlfaccompra) {
            $lin0 = new \linea_factura_proveedor();
            $linea = $lin0->get($this->idlfaccompra);
            if ($linea) {
                return $linea->url();
            }
        } else {
            return '#';
        }
    }

    /**
     * Devuelve la url del albarán o factura de venta.
     * @return string
     */
    public function documento_venta_url() {
        if ($this->idlalbventa) {
            $lin0 = new \linea_albaran_cliente();
            $linea = $lin0->get($this->idlalbventa);
            if ($linea) {
                return $linea->url();
            }
        } else if ($this->idlfaccompra) {
            $lin0 = new \linea_factura_proveedor();
            $linea = $lin0->get($this->idlfaccompra);
            if ($linea) {
                return $linea->url();
            }
        } else {
            return '#';
        }
    }

    /**
     * Devuelve una traza a partir de un $id.
     * @param type $id
     * @return boolean|\articulo_traza
     */
    public function get($id) {
        $data = self::$dataBase->select("SELECT * FROM " . $this->table_name . " WHERE id = " . $this->var2str($id) . ";");
        if ($data) {
            return new \articulo_traza($data[0]);
        } else {
            return FALSE;
        }
    }

    /**
     * Devuelve la traza correspondiente al número de serie $numserie.
     * @param type $numserie
     * @return boolean|\articulo_traza
     */
    public function get_by_numserie($numserie) {
        $data = self::$dataBase->select("SELECT * FROM " . $this->table_name . " WHERE numserie = " . $this->var2str($numserie) . ";");
        if ($data) {
            return new \articulo_traza($data[0]);
        } else {
            return FALSE;
        }
    }

    public function exists() {
        if (is_null($this->id)) {
            return FALSE;
        } else {
            return self::$dataBase->select("SELECT * FROM " . $this->table_name . " WHERE id = " . $this->var2str($this->id) . ";");
        }
    }

    public function save() {
        if ($this->exists()) {
            $sql = "UPDATE " . $this->table_name . " SET referencia = " . $this->var2str($this->referencia)
                    . ", numserie = " . $this->var2str($this->numserie)
                    . ", lote = " . $this->var2str($this->lote)
                    . ", idlalbventa = " . $this->var2str($this->idlalbventa)
                    . ", idlfacventa = " . $this->var2str($this->idlfacventa)
                    . ", idlalbcompra = " . $this->var2str($this->idlalbcompra)
                    . ", idlfaccompra = " . $this->var2str($this->idlfaccompra)
                    . ", fecha_entrada = " . $this->var2str($this->fecha_entrada)
                    . ", fecha_salida = " . $this->var2str($this->fecha_salida)
                    . "  WHERE id = " . $this->var2str($this->id) . ";";

            return self::$dataBase->exec($sql);
        } else {
            $sql = "INSERT INTO " . $this->table_name . " (referencia,numserie,lote,idlalbventa,"
                    . "idlfacventa,idlalbcompra,idlfaccompra,fecha_entrada,fecha_salida) VALUES "
                    . "(" . $this->var2str($this->referencia)
                    . "," . $this->var2str($this->numserie)
                    . "," . $this->var2str($this->lote)
                    . "," . $this->var2str($this->idlalbventa)
                    . "," . $this->var2str($this->idlfacventa)
                    . "," . $this->var2str($this->idlalbcompra)
                    . "," . $this->var2str($this->idlfaccompra)
                    . "," . $this->var2str($this->fecha_entrada)
                    . "," . $this->var2str($this->fecha_salida) . ");";

            if (self::$dataBase->exec($sql)) {
                $this->id = self::$dataBase->lastval();
                return TRUE;
            } else {
                return FALSE;
            }
        }
    }

    public function delete() {
        return self::$dataBase->exec("DELETE FROM " . $this->table_name . " WHERE id = " . $this->var2str($this->id) . ";");
    }

    /**
     * Devuelve todas las trazas de un artículo.
     * @param type $ref
     * @param type $sololibre
     * @return \articulo_traza
     */
    public function all_from_ref($ref, $sololibre = FALSE) {
        $lista = array();

        $sql = "SELECT * FROM " . $this->table_name . " WHERE referencia = " . $this->var2str($ref);
        if ($sololibre) {
            $sql .= " AND idlalbventa IS NULL AND idlfacventa IS NULL";
        }
        $sql .= " ORDER BY id ASC;";

        $data = self::$dataBase->select($sql);
        if ($data) {
            foreach ($data as $d) {
                $lista[] = new \articulo_traza($d);
            }
        }

        return $lista;
    }

    /**
     * Devuelve todas las trazas cuya columna $tipo tenga valor $idlinea
     * @param type $tipo
     * @param type $idlinea
     * @return \articulo_traza
     */
    public function all_from_linea($tipo, $idlinea) {
        $lista = array();

        $sql = "SELECT * FROM " . $this->table_name . " WHERE " . $tipo . " = " . $this->var2str($idlinea) . " ORDER BY id DESC;";
        $data = self::$dataBase->select($sql);
        if ($data) {
            foreach ($data as $d) {
                $lista[] = new \articulo_traza($d);
            }
        }

        return $lista;
    }

}
