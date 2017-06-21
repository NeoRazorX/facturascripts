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
 * Una cuenta bancaria de la propia empresa.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class cuenta_banco extends \FacturaScripts\Core\Base\Model {

    /**
     * Clave primaria. Varchar (6).
     * @var integer 
     */
    public $codcuenta;
    
    /**
     * Descripción de la cuenta bancaria
     * @var string 
     */
    public $descripcion;
    
    /**
     * Código IBAN de la cuenta bancaria
     * @var string 
     */
    public $iban;
    
    /**
     * Código SWIFT de la entidad a la que pertenece la cuenta bancaria
     * @var string 
     */
    public $swift;

    /**
     * Código de la subcuenta de contabilidad
     * @var string 
     */
    public $codsubcuenta;

    /**
     * Constructor por defecto
     * @param array $c Array con los valores para crear una nueva uenta bancaria
     */
    public function __construct($c = FALSE) {
        parent::__construct('cuentasbanco');
        if ($c) {
            $this->codcuenta = $c['codcuenta'];
            $this->descripcion = $c['descripcion'];
            $this->iban = $c['iban'];
            $this->swift = $c['swift'];
            $this->codsubcuenta = $c['codsubcuenta'];
        } else {
            $this->codcuenta = NULL;
            $this->descripcion = NULL;
            $this->iban = NULL;
            $this->swift = NULL;
            $this->codsubcuenta = NULL;
        }
    }

    /**
     * Crea la consulta necesaria para crear una nueva enta bancaria en la base de datos.
     * @return string
     */
    protected function install() {
        return '';
    }

    /**
     * Devuelve el IBAN con o sin espacios.
     * @param boolean $espacios
     * @return string
     */
    public function iban($espacios = FALSE) {
        if ($espacios) {
            $txt = '';
            $iban = str_replace(' ', '', $this->iban);
            for ($i = 0; $i < strlen($iban); $i += 4) {
                $txt .= substr($iban, $i, 4) . ' ';
            }
            return $txt;
        } else {
            return str_replace(' ', '', $this->iban);
        }
    }

    /**
     * Devuelve la URL donde ver/modificar los datos de esta cuenta bancaria
     * @return string
     */
    public function url() {
        return 'index.php?page=admin_empresa#cuentasb';
    }

    /**
     * Devuelve la cuenta bancaria con codcuenta = $cod
     * @param string $cod
     * @return boolean|\cuenta_banco
     */
    public function get($cod) {
        $data = $this->dataBase->select("SELECT * FROM " . $this->tableName . " WHERE codcuenta = " . $this->var2str($cod) . ";");
        if ($data) {
            return new \cuenta_banco($data[0]);
        } else {
            return FALSE;
        }
    }

    /**
     * Devuelve un nuevo código para una cuenta bancaria
     * @return int
     */
    private function get_new_codigo() {
        $sql = "SELECT MAX(" . $this->dataBase->sql_to_int('codcuenta') . ") as cod FROM " . $this->tableName . ";";
        $cod = $this->dataBase->select($sql);
        if ($cod) {
            return 1 + intval($cod[0]['cod']);
        } else {
            return 1;
        }
    }

    /**
     * Devuelve TRUE si la cuenta bancaria existe
     * @return boolean
     */
    public function exists() {
        if (is_null($this->codcuenta)) {
            return FALSE;
        } else {
            return $this->dataBase->select("SELECT * FROM " . $this->tableName . " WHERE codcuenta = " . $this->var2str($this->codcuenta) . ";");
        }
    }

    /**
     * Guarda los datos en la base de datos.
     * @return boolean
     */
    public function save() {
        $this->descripcion = $this->no_html($this->descripcion);

        if ($this->exists()) {
            $sql = "UPDATE " . $this->tableName . " SET descripcion = " . $this->var2str($this->descripcion) .
                    ", iban = " . $this->var2str($this->iban) .
                    ", swift = " . $this->var2str($this->swift) .
                    ", codsubcuenta = " . $this->var2str($this->codsubcuenta) .
                    "  WHERE codcuenta = " . $this->var2str($this->codcuenta) . ";";
        } else {
            $this->codcuenta = $this->get_new_codigo();
            $sql = "INSERT INTO " . $this->tableName . " (codcuenta,descripcion,iban,swift,codsubcuenta)
                 VALUES (" . $this->var2str($this->codcuenta) .
                    "," . $this->var2str($this->descripcion) .
                    "," . $this->var2str($this->iban) .
                    "," . $this->var2str($this->swift) .
                    "," . $this->var2str($this->codsubcuenta) . ");";
        }

        return $this->dataBase->exec($sql);
    }

    /**
     * Elimina esta cuenta bancaria
     * @return boolean
     */
    public function delete() {
        return $this->dataBase->exec("DELETE FROM " . $this->tableName . " WHERE codcuenta = " . $this->var2str($this->codcuenta) . ";");
    }

    /**
     * Devuelve un array con todas las cuentas bancarias de la empresa
     * @return array
     */
    public function all() {
        return $this->all_from_empresa();
    }

    /**
     * Devuelve un array con todas las cuentas bancarias de la empresa
     * @return \cuenta_banco
     */
    public function all_from_empresa() {
        $clist = array();

        $data = $this->dataBase->select("SELECT * FROM " . $this->tableName . " ORDER BY descripcion ASC;");
        if ($data) {
            foreach ($data as $d) {
                $clist[] = new \cuenta_banco($d);
            }
        }

        return $clist;
    }

    /**
     * Calcula el IBAN a partir de la cuenta bancaria del cliente CCC
     * @param string $ccc
     * @return string
     */
    public function calcular_iban($ccc) {
        $codpais = substr($this->default_items->codpais(), 0, 2);

        $pesos = array('A' => '10', 'B' => '11', 'C' => '12', 'D' => '13', 'E' => '14', 'F' => '15',
            'G' => '16', 'H' => '17', 'I' => '18', 'J' => '19', 'K' => '20', 'L' => '21', 'M' => '22',
            'N' => '23', 'O' => '24', 'P' => '25', 'Q' => '26', 'R' => '27', 'S' => '28', 'T' => '29',
            'U' => '30', 'V' => '31', 'W' => '32', 'X' => '33', 'Y' => '34', 'Z' => '35'
        );

        $dividendo = $ccc . $pesos[substr($codpais, 0, 1)] . $pesos[substr($codpais, 1, 1)] . '00';
        $digitoControl = 98 - bcmod($dividendo, '97');

        if (strlen($digitoControl) == 1) {
            $digitoControl = '0' . $digitoControl;
        }

        return $codpais . $digitoControl . $ccc;
    }

}