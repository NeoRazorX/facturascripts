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
class CuentaBanco
{

    use Base\ModelTrait;

    /**
     * Clave primaria. Varchar (6).
     * @var string 
     */
    public $codcuenta;
    public $descripcion;
    public $iban;
    public $swift;

    /**
     * Código de la subcuenta de contabilidad
     * @var string 
     */
    public $codsubcuenta;

    public function __construct($data = [])
    {
        $this->init(__CLASS__, 'cuentasbanco', 'codcuenta');
        if (empty($data)) {
            $this->clear();
        } else {
            $this->loadFromData($data);
        }
    }

    /**
     * Devuelve el IBAN con o sin espacios.
     * @param boolean $espacios
     * @return string
     */
    public function iban($espacios = FALSE)
    {
        if ($espacios) {
            $txt = '';
            $iban = str_replace(' ', '', $this->iban);
            for ($i = 0; $i < strlen($iban); $i += 4) {
                $txt .= substr($iban, $i, 4) . ' ';
            }
            return $txt;
        }

        return str_replace(' ', '', $this->iban);
    }

    /**
     * Devuelve la URL donde ver/modificar los datos de esta cuenta bancaria
     * @return string
     */
    public function url()
    {
        return 'index.php?page=admin_empresa#cuentasb';
    }

    /**
     * Devuelve un nuevo código para una cuenta bancaria
     * @return int
     */
    private function get_new_codigo()
    {
        $sql = "SELECT MAX(" . $this->db->sql_to_int('codcuenta') . ") as cod FROM " . $this->table_name . ";";
        $data = $this->db->select($sql);
        if ($data) {
            return (string) (1 + (int) $data[0]['cod']);
        }

        return '1';
    }

    /**
     * Calcula el IBAN a partir de la cuenta bancaria del cliente CCC
     * @param string $ccc
     * @return string
     */
    public function calcular_iban($ccc)
    {
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
