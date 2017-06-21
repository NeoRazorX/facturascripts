<?php

/*
 * This file is part of FacturaScripts
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
 * El agente/empleado es el que se asocia a un albarán, factura o caja.
 * Cada usuario puede estar asociado a un agente, y un agente puede
 * estar asociado a varios usuarios o a ninguno.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class agente extends \FacturaScripts\Core\Base\Model {

    /**
     * Clave primaria. Varchar (10).
     * @var integer
     */
    public $codagente;

    /**
     * Identificador fiscal (CIF/NIF).
     * @var string
     */
    public $dnicif;
    
    /**
     * Nombre del agente o empleado.
     * @var string 
     */
    public $nombre;
    
    /**
     * Apellidos del agente o empleado.
     * @var string 
     */
    public $apellidos;
    
    /**
     * Email del agente o empleado.
     * @var string 
     */
    public $email;
    
    /**
     * Teléfono del agente o empleado.
     * @var string 
     */
    public $telefono;
    
    /**
     * Código postal del agente o empleado.
     * @var string 
     */
    public $codpostal;
    
    /**
     * Provincia del agente o empleado.
     * @var string 
     */
    public $provincia;
    
    /**
     * Ciudad del agente o empleado.
     * @var string 
     */
    public $ciudad;
    
    /**
     * Dirección del agente o empleado.
     * @var string 
     */
    public $direccion;

    /**
     * Nº de la seguridad social.
     * @var string
     */
    public $seg_social;

    /**
     * cargo en la empresa.
     * @var string
     */
    public $cargo;

    /**
     * Cuenta bancaria
     * @var string
     */
    public $banco;

    /**
     * Fecha de nacimiento.
     * @var string
     */
    public $f_nacimiento;

    /**
     * Fecha de alta en la empresa.
     * @var string
     */
    public $f_alta;

    /**
     * Fecha de baja en la empresa.
     * @var string
     */
    public $f_baja;

    /**
     * Porcentaje de comisión del agente. Se utiliza en presupuestos, pedidos, albaranes y facturas.
     * @var float
     */
    public $porcomision;

    /**
     * Constructor por defecto
     * @param array $a Array con los valores para crear un nuevo agente
     */
    public function __construct($a = FALSE) {
        parent::__construct('agentes');
        if ($a) {
            $this->codagente = $a['codagente'];
            $this->nombre = $a['nombre'];
            $this->apellidos = $a['apellidos'];
            $this->dnicif = $a['dnicif'];
            $this->email = $a['email'];
            $this->telefono = $a['telefono'];
            $this->codpostal = $a['codpostal'];
            $this->provincia = $a['provincia'];
            $this->ciudad = $a['ciudad'];
            $this->direccion = $a['direccion'];
            $this->porcomision = floatval($a['porcomision']);
            $this->seg_social = $a['seg_social'];
            $this->banco = $a['banco'];
            $this->cargo = $a['cargo'];

            $this->f_alta = NULL;
            if ($a['f_alta'] != '') {
                $this->f_alta = Date('d-m-Y', strtotime($a['f_alta']));
            }

            $this->f_baja = NULL;
            if ($a['f_baja'] != '') {
                $this->f_baja = Date('d-m-Y', strtotime($a['f_baja']));
            }

            $this->f_nacimiento = NULL;
            if ($a['f_nacimiento'] != '') {
                $this->f_nacimiento = Date('d-m-Y', strtotime($a['f_nacimiento']));
            }
        } else {
            $this->codagente = NULL;
            $this->nombre = '';
            $this->apellidos = '';
            $this->dnicif = '';
            $this->email = NULL;
            $this->telefono = NULL;
            $this->codpostal = NULL;
            $this->provincia = NULL;
            $this->ciudad = NULL;
            $this->direccion = NULL;
            $this->porcomision = 0.00;
            $this->seg_social = NULL;
            $this->banco = NULL;
            $this->cargo = NULL;
            $this->f_alta = Date('d-m-Y');
            $this->f_baja = NULL;
            $this->f_nacimiento = Date('d-m-Y');
        }
    }
    /**
     * Crea la consulta necesaria para crear un nuevo agente en la base de datos.
     * @return string
     */
    protected function install() {
        $this->clean_cache();
        return "INSERT INTO " . $this->table_name . " (codagente,nombre,apellidos,dnicif)
         VALUES ('1','Paco','Pepe','00000014Z');";
    }

    /**
     * Devuelve nombre + apellidos del agente.
     * @return string
     */
    public function get_fullname() {
        return $this->nombre . " " . $this->apellidos;
    }

    /**
     * Genera un nuevo código de agente
     * @return int
     */
    public function get_new_codigo() {
        $sql = "SELECT MAX(" . $this->db->sql_to_int('codagente') . ") as cod FROM " . $this->table_name . ";";
        $cod = $this->db->select($sql);
        if ($cod) {
            return 1 + intval($cod[0]['cod']);
        } else
            return 1;
    }

    /**
     * Devuelve la url donde se pueden ver/modificar estos datos
     * @return string
     */
    public function url() {
        if (is_null($this->codagente)) {
            return "index.php?page=admin_agentes";
        } else
            return "index.php?page=admin_agente&cod=" . $this->codagente;
    }

    /**
     * Devuelve el empleado/agente con codagente = $cod
     * @param string $cod
     * @return \agente|boolean
     */
    public function get($cod) {
        $a = $this->db->select("SELECT * FROM " . $this->table_name . " WHERE codagente = " . $this->var2str($cod) . ";");
        if ($a) {
            return new \agente($a[0]);
        } else
            return FALSE;
    }

    /**
     * Devuelve TRUE si el agente/empleado existe, false en caso contrario
     * @return boolean
     */
    public function exists() {
        if (is_null($this->codagente)) {
            return FALSE;
        } else
            return $this->db->select("SELECT * FROM " . $this->table_name . " WHERE codagente = " . $this->var2str($this->codagente) . ";");
    }

    /**
     * Comprueba los datos del empleado/agente, devuelve TRUE si son correctos
     * @return boolean
     */
    public function test() {
        $this->apellidos = $this->no_html($this->apellidos);
        $this->banco = $this->no_html($this->banco);
        $this->cargo = $this->no_html($this->cargo);
        $this->ciudad = $this->no_html($this->ciudad);
        $this->codpostal = $this->no_html($this->codpostal);
        $this->direccion = $this->no_html($this->direccion);
        $this->dnicif = $this->no_html($this->dnicif);
        $this->email = $this->no_html($this->email);
        $this->nombre = $this->no_html($this->nombre);
        $this->provincia = $this->no_html($this->provincia);
        $this->seg_social = $this->no_html($this->seg_social);
        $this->telefono = $this->no_html($this->telefono);

        if (strlen($this->nombre) < 1 || strlen($this->nombre) > 50) {
            $this->miniLog->alert("El nombre del empleado debe tener entre 1 y 50 caracteres.");
            return FALSE;
        } else {
            return TRUE;
        }
    }

    /**
     * Guarda los datos en la base de datos
     * @return boolean
     */
    public function save() {
        if ($this->test()) {
            $this->clean_cache();

            if ($this->exists()) {
                $sql = "UPDATE " . $this->table_name . " SET nombre = " . $this->var2str($this->nombre) .
                        ", apellidos = " . $this->var2str($this->apellidos) .
                        ", dnicif = " . $this->var2str($this->dnicif) .
                        ", telefono = " . $this->var2str($this->telefono) .
                        ", email = " . $this->var2str($this->email) .
                        ", cargo = " . $this->var2str($this->cargo) .
                        ", provincia = " . $this->var2str($this->provincia) .
                        ", ciudad = " . $this->var2str($this->ciudad) .
                        ", direccion = " . $this->var2str($this->direccion) .
                        ", codpostal = " . $this->var2str($this->codpostal) .
                        ", f_nacimiento = " . $this->var2str($this->f_nacimiento) .
                        ", f_alta = " . $this->var2str($this->f_alta) .
                        ", f_baja = " . $this->var2str($this->f_baja) .
                        ", seg_social = " . $this->var2str($this->seg_social) .
                        ", banco = " . $this->var2str($this->banco) .
                        ", porcomision = " . $this->var2str($this->porcomision) .
                        "  WHERE codagente = " . $this->var2str($this->codagente) . ";";
            } else {
                if (is_null($this->codagente)) {
                    $this->codagente = $this->get_new_codigo();
                }

                $sql = "INSERT INTO " . $this->table_name . " (codagente,nombre,apellidos,dnicif,telefono,
               email,cargo,provincia,ciudad,direccion,codpostal,f_nacimiento,f_alta,f_baja,seg_social,
               banco,porcomision) VALUES (" . $this->var2str($this->codagente) .
                        "," . $this->var2str($this->nombre) .
                        "," . $this->var2str($this->apellidos) .
                        "," . $this->var2str($this->dnicif) .
                        "," . $this->var2str($this->telefono) .
                        "," . $this->var2str($this->email) .
                        "," . $this->var2str($this->cargo) .
                        "," . $this->var2str($this->provincia) .
                        "," . $this->var2str($this->ciudad) .
                        "," . $this->var2str($this->direccion) .
                        "," . $this->var2str($this->codpostal) .
                        "," . $this->var2str($this->f_nacimiento) .
                        "," . $this->var2str($this->f_alta) .
                        "," . $this->var2str($this->f_baja) .
                        "," . $this->var2str($this->seg_social) .
                        "," . $this->var2str($this->banco) .
                        "," . $this->var2str($this->porcomision) . ");";
            }

            return $this->db->exec($sql);
        } else
            return FALSE;
    }

    /**
     * Elimina este empleado/agente
     * @return boolean
     */
    public function delete() {
        $this->clean_cache();
        return $this->db->exec("DELETE FROM " . $this->table_name . " WHERE codagente = " . $this->var2str($this->codagente) . ";");
    }

    /**
     * Limpiamos la caché
     */
    private function clean_cache() {
        $this->cache->delete('m_agente_all');
    }

    /**
     * Devuelve un array con todos los agentes/empleados.
     * @return \agente
     */
    public function all($incluir_debaja = FALSE) {

        if ($incluir_debaja) {
            $listagentes = array();
            $data = $this->db->select("SELECT * FROM " . $this->table_name . " ORDER BY nombre ASC, apellidos ASC;");
            if ($data) {
                foreach ($data as $a) {
                    $listagentes[] = new \agente($a);
                }
            }
        } else {
            /// leemos esta lista de la caché
            $listagentes = $this->cache->get_array('m_agente_all');

            if (!$listagentes) {
                /// si no está en caché, leemos de la base de datos
                $data = $this->db->select("SELECT * FROM " . $this->table_name . " WHERE f_baja IS NULL ORDER BY nombre ASC, apellidos ASC;");
                if ($data) {
                    foreach ($data as $a) {
                        $listagentes[] = new \agente($a);
                    }
                }

                /// guardamos la lista en caché
                $this->cache->set('m_agente_all', $listagentes);
            }
        }

        return $listagentes;
    }

}