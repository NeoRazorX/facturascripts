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
class Agente {

    use \FacturaScripts\Core\Base\Model;

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

    public function __construct($data = FALSE) {
        $this->init('agentes', 'codagente');
        if ($data) {
            $this->loadFromData($data);
        } else {
            $this->clear();
        }
    }

    public function clear() {
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

    /**
     * Crea la consulta necesaria para crear un nuevo agente en la base de datos.
     * @return string
     */
    protected function install() {
        return "INSERT INTO " . $this->tableName() . " (codagente,nombre,apellidos,dnicif)"
                . " VALUES ('1','Paco','Pepe','00000014Z');";
    }

    /**
     * Devuelve nombre + apellidos del agente.
     * @return string
     */
    public function fullName() {
        return $this->nombre . " " . $this->apellidos;
    }

    /**
     * Genera un nuevo código de agente
     * @return int
     */
    public function newCodigo() {
        $sql = "SELECT MAX(" . $this->dataBase->sql2int('codagente') . ") as cod FROM " . $this->tableName() . ";";
        $cod = $this->dataBase->select($sql);
        if ($cod) {
            return 1 + intval($cod[0]['cod']);
        }

        return 1;
    }

    /**
     * Devuelve la url donde se pueden ver/modificar estos datos
     * @return string
     */
    public function url() {
        if ($this->codagente === NULL) {
            return "index.php?page=admin_agentes";
        }

        return "index.php?page=admin_agente&cod=" . $this->codagente;
    }

    /**
     * Devuelve el empleado/agente con codagente = $cod
     * @param string $cod
     * @return Agente|boolean
     */
    public function get($cod) {
        $agente = $this->dataBase->select("SELECT * FROM " . $this->tableName() . " WHERE codagente = " . $this->var2str($cod) . ";");
        if ($agente) {
            return new Agente($agente[0]);
        }

        return FALSE;
    }

    /**
     * Comprueba los datos del empleado/agente, devuelve TRUE si son correctos
     * @return boolean
     */
    public function test() {
        $this->apellidos = $this->noHtml($this->apellidos);
        $this->banco = $this->noHtml($this->banco);
        $this->cargo = $this->noHtml($this->cargo);
        $this->ciudad = $this->noHtml($this->ciudad);
        $this->codpostal = $this->noHtml($this->codpostal);
        $this->direccion = $this->noHtml($this->direccion);
        $this->dnicif = $this->noHtml($this->dnicif);
        $this->email = $this->noHtml($this->email);
        $this->nombre = $this->noHtml($this->nombre);
        $this->provincia = $this->noHtml($this->provincia);
        $this->seg_social = $this->noHtml($this->seg_social);
        $this->telefono = $this->noHtml($this->telefono);

        if (strlen($this->nombre) < 1 || strlen($this->nombre) > 50) {
            $this->miniLog->alert($this->i18n->trans('agent-name-between-1-50'));
            return FALSE;
        }

        if ($this->codagente === NULL) {
            $this->codagente = $this->newCodigo();
        }

        return TRUE;
    }

    /**
     * Devuelve un array con todos los agentes/empleados.
     * @return Agente
     */
    public function all() {
        $listagentes = array();

        $data = $this->dataBase->select("SELECT * FROM " . $this->tableName() . " ORDER BY nombre ASC, apellidos ASC;");
        if ($data) {
            foreach ($data as $a) {
                $listagentes[] = new Agente($a);
            }
        }

        return $listagentes;
    }

}
