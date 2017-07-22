<?php

/*
 * @author Carlos García Gómez      neorazorx@gmail.com
 * @copyright 2016-2017, Carlos García Gómez. All Rights Reserved.
 */

namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\Base\Model;

/**
 * Description of linea_transferencia_stock
 *
 * @author Carlos García Gómez
 */
class LineaTransferenciaStock 
{
    use Model;

    /// clave primaria. integer
    public $idlinea;
    public $idtrans;
    public $referencia;
    public $cantidad;
    public $descripcion;
    private $fecha;
    private $hora;

    public function __construct(array $data = []) 
    {
        $this->init(__CLASS__, 'lineastransstock', 'idlinea');
        if (!empty($data)) {
            $this->loadFromData($data);
        } else {
            $this->clear();
        }
    }
	
    public function clear() {
        $this->idlinea = NULL;
        $this->idtrans = NULL;
        $this->referencia = NULL;
        $this->cantidad = 0;
        $this->descripcion = NULL;
        $this->fecha = NULL;
        $this->hora = NULL;
    }

    public function install() {
        /// forzamos la comprobación de la tabla de transferencias de stock
        //new \transferencia_stock();

        return '';
    }

    public function fecha() {
        return $this->fecha;
    }

    public function hora() {
        return $this->hora;
    }

    public function exists() {
        if (is_null($this->idlinea)) {
            return FALSE;
        } else {
            return self::$dataBase->select('SELECT * FROM lineastransstock WHERE idlinea = ' . $this->var2str($this->idlinea) . ';');
        }
    }

    public function save() {
        if ($this->exists()) {
            $sql = "UPDATE lineastransstock SET idtrans = " . $this->var2str($this->idtrans)
                    . ", referencia = " . $this->var2str($this->referencia)
                    . ", cantidad = " . $this->var2str($this->cantidad)
                    . ", descripcion = " . $this->var2str($this->descripcion)
                    . "  WHERE idlinea = " . $this->var2str($this->idlinea) . ";";

            return self::$dataBase->exec($sql);
        } else {
            $sql = "INSERT INTO lineastransstock (idtrans,referencia,cantidad,descripcion) VALUES "
                    . "(" . $this->var2str($this->idtrans)
                    . "," . $this->var2str($this->referencia)
                    . "," . $this->var2str($this->cantidad)
                    . "," . $this->var2str($this->descripcion) . ");";

            if (self::$dataBase->exec($sql)) {
                $this->idlinea = self::$dataBase->lastval();
                return TRUE;
            } else {
                return FALSE;
            }
        }
    }

    public function delete() {
        return self::$dataBase->exec('DELETE FROM lineastransstock WHERE idlinea = ' . $this->var2str($this->idlinea) . ';');
    }

    public function all_from_transferencia($id) {
        $list = array();

        $data = self::$dataBase->select("SELECT * FROM lineastransstock WHERE idtrans = " . $this->var2str($id) . " ORDER BY referencia ASC;");
        if ($data) {
            foreach ($data as $d) {
                $list[] = new \linea_transferencia_stock($d);
            }
        }

        return $list;
    }

    public function all_from_referencia($ref, $codalmaorigen = '', $codalmadestino = '', $desde = '', $hasta = '') {
        $list = array();

        $sql = "SELECT l.idlinea,l.idtrans,l.referencia,l.cantidad,l.descripcion,t.fecha,t.hora FROM lineastransstock l"
                . " LEFT JOIN transstock t ON l.idtrans = t.idtrans"
                . " WHERE l.referencia = " . $this->var2str($ref);
        if ($codalmaorigen) {
            $sql .= " AND t.codalmaorigen = " . $this->var2str($codalmaorigen);
        }
        if ($codalmadestino) {
            $sql .= " AND t.codalmadestino = " . $this->var2str($codalmadestino);
        }
        if ($desde) {
            $sql .= " AND t.fecha >= " . $this->var2str($desde);
        }
        if ($hasta) {
            $sql .= " AND t.fecha >= " . $this->var2str($hasta);
        }
        $sql .= " ORDER BY t.fecha ASC, t.hora ASC;";

        $data = self::$dataBase->select($sql);
        if ($data) {
            foreach ($data as $d) {
                $list[] = new \linea_transferencia_stock($d);
            }
        }

        return $list;
    }

}
