<?php

/*
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2016  Carlos Garcia Gomez  neorazorx@gmail.com
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
 * Una serie de facturación o contabilidad, para tener distinta numeración
 * en cada serie.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class serie extends \FacturaScripts\Core\Base\Model {

    /**
     * Clave primaria. Varchar (2).
     * @var string 
     */
    public $codserie;
    
    /**
     * Descripción de la serie de facturación
     * @var string 
     */
    public $descripcion;

    /**
     * TRUE -> las facturas asociadas no encluyen IVA.
     * @var boolean
     */
    public $siniva;

    /**
     * % de retención IRPF de las facturas asociadas.
     * @var float
     */
    public $irpf;

    /**
     * ejercicio para el que asignamos la numeración inicial de la serie.
     * @var string
     */
    public $codejercicio;

    /**
     * numeración inicial para las facturas de esta serie.
     * @var integer
     */
    public $numfactura;
    /**
     * Constructor por defecto
     * @param array $s Array con los valores para crear una nueva serie
     */
    public function __construct($s = FALSE) {
        parent::__construct('series');
        if ($s) {
            $this->codserie = $s['codserie'];
            $this->descripcion = $s['descripcion'];
            $this->siniva = $this->str2bool($s['siniva']);
            $this->irpf = floatval($s['irpf']);
            $this->codejercicio = $s['codejercicio'];
            $this->numfactura = max(array(1, intval($s['numfactura'])));
        } else {
            $this->codserie = '';
            $this->descripcion = '';
            $this->siniva = FALSE;
            $this->irpf = 0.00;
            $this->codejercicio = NULL;
            $this->numfactura = 1;
        }
    }
    
    /**
     * Crea la consulta necesaria para crear una nueva serie en la base de datos.
     * @return string
     */
    public function install() {
        return "INSERT INTO " . $this->tableName . " (codserie,descripcion,siniva,irpf) VALUES "
                . "('A','SERIE A',FALSE,'0'),('R','RECTIFICATIVAS',FALSE,'0');";
    }

    /**
     * Devuelve la url donde ver/modificar la serie
     * @return string
     */
    public function url() {
        if (is_null($this->codserie)) {
            return 'index.php?page=contabilidad_series';
        } else
            return 'index.php?page=contabilidad_series#' . $this->codserie;
    }

    /**
     * Devuelve TRUE si la serie es la predeterminada de la empresa
     * @return boolean
     */
    public function is_default() {
        return ( $this->codserie == $this->default_items->codserie() );
    }

    /**
     * Devuelve la serie solicitada o false si no la encuentra.
     * @param string $cod
     * @return \serie|boolean
     */
    public function get($cod) {
        $serie = $this->dataBase->select("SELECT * FROM " . $this->tableName . " WHERE codserie = " . $this->var2str($cod) . ";");
        if ($serie) {
            return new \serie($serie[0]);
        } else
            return FALSE;
    }

    /**
     * Devuelve TRUE si la serie existe
     * @return boolean
     */
    public function exists() {
        if (is_null($this->codserie)) {
            return FALSE;
        } else
            return $this->dataBase->select("SELECT * FROM " . $this->tableName . " WHERE codserie = " . $this->var2str($this->codserie) . ";");
    }

    /**
     * Comprueba los datos de la serie, devuelve TRUE si son correctos
     * @return boolean
     */
    public function test() {
        $status = FALSE;

        $this->codserie = trim($this->codserie);
        $this->descripcion = $this->noHtml($this->descripcion);

        if ($this->numfactura < 1) {
            $this->numfactura = 1;
        }

        if (!preg_match("/^[A-Z0-9]{1,2}$/i", $this->codserie)) {
            $this->miniLog->alert("Código de serie no válido.");
        } else if (strlen($this->descripcion) < 1 || strlen($this->descripcion) > 100) {
            $this->miniLog->alert("Descripción de serie no válida.");
        } else
            $status = TRUE;

        return $status;
    }

    /**
     * Guarda los datos en la base de datos
     * @return boolean
     */
    public function save() {
        if ($this->test()) {
            if ($this->exists()) {
                $sql = "UPDATE " . $this->tableName . " SET descripcion = " . $this->var2str($this->descripcion)
                        . ", siniva = " . $this->var2str($this->siniva)
                        . ", irpf = " . $this->var2str($this->irpf)
                        . ", codejercicio = " . $this->var2str($this->codejercicio)
                        . ", numfactura = " . $this->var2str($this->numfactura)
                        . "  WHERE codserie = " . $this->var2str($this->codserie) . ";";
            } else {
                $sql = "INSERT INTO " . $this->tableName . " (codserie,descripcion,siniva,irpf,codejercicio,numfactura) VALUES "
                        . "(" . $this->var2str($this->codserie)
                        . "," . $this->var2str($this->descripcion)
                        . "," . $this->var2str($this->siniva)
                        . "," . $this->var2str($this->irpf)
                        . "," . $this->var2str($this->codejercicio)
                        . "," . $this->var2str($this->numfactura) . ");";
            }

            return $this->dataBase->exec($sql);
        } else
            return FALSE;
    }

    /**
     * Elimina la serie
     * @return type
     */
    public function delete() {
        return $this->dataBase->exec("DELETE FROM " . $this->tableName . " WHERE codserie = " . $this->var2str($this->codserie) . ";");
    }

    /**
     * Devuelve un array con todas las series
     * @return \serie
     */
    public function all() {
        $serielist = array();
            $data = $this->dataBase->select("SELECT * FROM " . $this->tableName . " ORDER BY codserie ASC;");
            if ($data) {
                foreach ($data as $s) {
                    $serielist[] = new \serie($s);
                }
            }


        return $serielist;
    }

}
