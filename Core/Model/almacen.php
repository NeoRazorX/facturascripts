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
 * El almacén donde están físicamente los artículos.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class almacen extends \FacturaScripts\Core\Base\Model {

    /**
     * Clave primaria. Varchar (4).
     * @var string
     */
    public $codalmacen;

    /**
     * Nombre del almacen.
     * @var string 
     */
    public $nombre;

    /**
     * Código que representa al páis donde está ubicado el almacen.
     * @var string 
     */
    public $codpais;

    /**
     * Nombre de la provincia donde está ubicado el almacen.
     * @var string 
     */
    public $provincia;

    /**
     * Nombre de la población donde está ubicado el almacen.
     * @var string 
     */
    public $poblacion;

    /**
     * Código postal donde está ubicado el almacen.
     * @var string 
     */
    public $codpostal;

    /**
     * Dirección donde está ubicado el almacen.
     * @var string 
     */
    public $direccion;

    /**
     * Persona de contacto del almacen.
     * @var string 
     */
    public $contacto;

    /**
     * Número de fax del almacen.
     * @var string 
     */
    public $fax;

    /**
     * Número de teléfono del almacen.
     * @var string 
     */
    public $telefono;

    /**
     * Todavía sin uso.
     * @var string 
     */
    public $observaciones;

    /**
     * Constructor por defecto
     * @param array $data Array con los valores para crear un nuevo almacen
     */
    public function __construct($data = FALSE) {
        parent::__construct('almacenes');
        if ($data) {
            $this->codalmacen = $data['codalmacen'];
            $this->nombre = $data['nombre'];
            $this->codpais = $data['codpais'];
            $this->provincia = $data['provincia'];
            $this->poblacion = $data['poblacion'];
            $this->codpostal = $data['codpostal'];
            $this->direccion = $data['direccion'];
            $this->contacto = $data['contacto'];
            $this->fax = $data['fax'];
            $this->telefono = $data['telefono'];
            $this->observaciones = $data['observaciones'];
        } else {
            $this->codalmacen = NULL;
            $this->nombre = '';
            $this->codpais = NULL;
            $this->provincia = NULL;
            $this->poblacion = NULL;
            $this->codpostal = '';
            $this->direccion = '';
            $this->contacto = '';
            $this->fax = '';
            $this->telefono = '';
            $this->observaciones = '';
        }
    }

    /**
     * Crea la consulta necesaria para crear un nuevo almacen en la base de datos.
     * @return string
     */
    public function install() {
        return "INSERT INTO " . $this->tableName . " (codalmacen,nombre,poblacion,direccion,codpostal,telefono,fax,contacto)
         VALUES ('ALG','ALMACEN GENERAL','','','','','','');";
    }

    /**
     * Devuelve la URL para ver/modificar los datos de este almacén
     * @return string
     */
    public function url() {
        if (is_null($this->codalmacen)) {
            return 'index.php?page=admin_almacenes';
        }

        return 'index.php?page=admin_almacenes#' . $this->codalmacen;
    }

    /**
     * Devuelve TRUE si este es almacén predeterminado de la empresa.
     * @return boolean
     */
    public function is_default() {
        return ( $this->codalmacen == $this->defaultItems->codAlmacen() );
    }

    /**
     * Devuelve el almacén con codalmacen = $cod
     * @param string $cod
     * @return \almacen|boolean
     */
    public function get($cod) {
        $almacen = $this->dataBase->select("SELECT * FROM " . $this->tableName . " WHERE codalmacen = " . $this->var2str($cod) . ";");
        if ($almacen) {
            return new almacen($almacen[0]);
        }

        return FALSE;
    }

    /**
     * Devuelve TRUE si el almacén existe
     * @return boolean
     */
    public function exists() {
        if (is_null($this->codalmacen)) {
            return FALSE;
        }

        return $this->dataBase->select("SELECT * FROM " . $this->tableName . " WHERE codalmacen = " . $this->var2str($this->codalmacen) . ";");
    }

    /**
     * Comprueba los datos del almacén, devuelve TRUE si son correctos
     * @return boolean
     */
    public function test() {
        $status = FALSE;

        $this->codalmacen = trim($this->codalmacen);
        $this->nombre = $this->noHtml($this->nombre);
        $this->provincia = $this->noHtml($this->provincia);
        $this->poblacion = $this->noHtml($this->poblacion);
        $this->direccion = $this->noHtml($this->direccion);
        $this->codpostal = $this->noHtml($this->codpostal);
        $this->telefono = $this->noHtml($this->telefono);
        $this->fax = $this->noHtml($this->fax);
        $this->contacto = $this->noHtml($this->contacto);

        if (!preg_match("/^[A-Z0-9]{1,4}$/i", $this->codalmacen)) {
            $this->miniLog->alert("Código de almacén no válido.");
        } else if (strlen($this->nombre) < 1 || strlen($this->nombre) > 100) {
            $this->miniLog->alert("Nombre de almacén no válido.");
        } else {
            $status = TRUE;
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
                $sql = "UPDATE " . $this->tableName . " SET nombre = " . $this->var2str($this->nombre)
                        . ", codpais = " . $this->var2str($this->codpais)
                        . ", provincia = " . $this->var2str($this->provincia)
                        . ", poblacion = " . $this->var2str($this->poblacion)
                        . ", direccion = " . $this->var2str($this->direccion)
                        . ", codpostal = " . $this->var2str($this->codpostal)
                        . ", telefono = " . $this->var2str($this->telefono)
                        . ", fax = " . $this->var2str($this->fax)
                        . ", contacto = " . $this->var2str($this->contacto)
                        . "  WHERE codalmacen = " . $this->var2str($this->codalmacen) . ";";
            } else {
                $sql = "INSERT INTO " . $this->tableName . " (codalmacen,nombre,codpais,provincia,
               poblacion,direccion,codpostal,telefono,fax,contacto) VALUES
                      (" . $this->var2str($this->codalmacen)
                        . "," . $this->var2str($this->nombre)
                        . "," . $this->var2str($this->codpais)
                        . "," . $this->var2str($this->provincia)
                        . "," . $this->var2str($this->poblacion)
                        . "," . $this->var2str($this->direccion)
                        . "," . $this->var2str($this->codpostal)
                        . "," . $this->var2str($this->telefono)
                        . "," . $this->var2str($this->fax)
                        . "," . $this->var2str($this->contacto) . ");";
            }
            return $this->dataBase->exec($sql);
        }

        return FALSE;
    }

    /**
     * Elimina el almacén
     * @return type
     */
    public function delete() {
        return $this->dataBase->exec("DELETE FROM " . $this->tableName . " WHERE codalmacen = " . $this->var2str($this->codalmacen) . ";");
    }

    /**
     * Devuelve un array con todos los almacenes
     * @return \almacen
     */
    public function all() {
        $listaa = array();
        
        $data = $this->dataBase->select("SELECT * FROM " . $this->tableName . " ORDER BY codalmacen ASC;");
        if ($data) {
            foreach ($data as $a) {
                $listaa[] = new almacen($a);
            }
        }

        return $listaa;
    }

}
