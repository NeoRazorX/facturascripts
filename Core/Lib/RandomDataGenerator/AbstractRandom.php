<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2016-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Lib\RandomDataGenerator;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Model;

/**
 * Abstract class that contains the basic methods to populate a table with 
 * random data 
 *
 * @author Rafael San José <info@rsanjoseo.com>
 */
abstract class AbstractRandom
{

    /**
     * Link with the active database
     *
     * @var DataBase
     */
    private $dataBase;

    /**
     * Contains the model to generate random data.
     *
     * @var mixed
     */
    protected $model;

    /**
     * Generate random data.
     *
     * @param int $num
     *
     * @return mixed
     */
    abstract public function generate($num = 50);

    /**
     * AbstractRandom constructor.
     *
     * @param $model
     */
    public function __construct($model)
    {
        $this->dataBase = new DataBase();
        $this->model = $model;
    }

    /**
     * Returns a random number between $min and $max1
     * 1 out of 10 times returns a value between $min and $max2
     * 1 out of 5 times it returns a value with decimal points
     *
     * @param int $min
     * @param int $max1
     * @param int $max2
     *
     * @return float
     */
    public function cantidad($min, $max1, $max2)
    {
        $cantidad = mt_rand($min, $max1);

        if (mt_rand(0, 9) == 0) {
            $cantidad = mt_rand($min, $max2);
        } elseif ($cantidad < $max1 && mt_rand(0, 4) == 0) {
            $cantidad += round(mt_rand(1, 5) / mt_rand(1, 10), mt_rand(0, 3));
            $cantidad = min([$max1, $cantidad]);
        }

        return $cantidad;
    }

    /**
     * Returns a random product description
     *
     * @return string
     */
    public function descripcion()
    {
        $sufijos = [
            'II', '3', 'XL', 'XXL', 'SE', 'GT', 'GTX', 'Pro', 'NX', 'XP', 'OS', 'Nitro',
        ];
        $texto = $this->familia() . $this->getOneItem($sufijos);

        $descripciones1 = [
            'Una alcachofa', 'Un motor', 'Una targeta gráfica (GPU)', 'Un procesador',
            'Un coche', 'Un dispositivo tecnológico', 'Un magnetofón', 'Un palo',
            'un cubo de basura', "Un objeto pequeño d'or", '"La hostia"',
        ];

        $descripciones = [
            '64 núcleos', 'chasis de fibra de carbono', '8 cilindros en V', 'frenos de berilio',
            '16 ejes', 'pantalla Super AMOLED', '1024 stream processors', 'un núcleo híbrido',
            '32 pistones digitales', 'tecnología digitrónica 4.1', 'cuernos metálicos', 'un palo',
            'memoria HBM', 'taladro matricial', 'Wifi 4G', 'faros de xenon', 'un ambientador de pino',
            'un posavasos', 'malignas intenciones', 'la virginidad intacta', 'malware', 'linux',
            'Windows Vista', 'propiedades psicotrópicas', 'spyware', 'reproductor 4k',
        ];

        $descripcion = $this->getOneItem($descripciones1);
        switch (mt_rand(0, 4)) {
            case 0:
                break;

            case 1:
                $texto .= ": $descripcion con {$this->getOneItem($descripciones)}.";
                break;

            case 2:
                $texto .= ": $descripcion con {$this->getOneItem($descripciones)}, {$this->getOneItem($descripciones)}, {$this->getOneItem($descripciones)} y {$this->getOneItem($descripciones)}.";
                break;

            case 3:
                $texto .= ": $descripcion con:\n- {$this->getOneItem($descripciones)}\n- {$this->getOneItem($descripciones)}\n- {$this->getOneItem($descripciones)}\n- {$this->getOneItem($descripciones)}.";
                break;

            default:
                $texto .= ": $descripcion con {$this->getOneItem($descripciones)}, {$this->getOneItem($descripciones)} y {$this->getOneItem($descripciones)}.";
                break;
        }

        return $texto;
    }

    /**
     * Returns a random category description
     *
     * @return string
     */
    public function familia()
    {
        $prefijos = [
            'Jet', 'Jex', 'Max', 'Pro', 'FX', 'Neo', 'Maxi', 'Extreme', 'Sub',
            'Ultra', 'Minga', 'Hiper', 'Giga', 'Mega', 'Super', 'Fusion', 'Broken',
        ];

        $nombres = [
            'Motor', 'Engine', 'Generator', 'Tool', 'Oviode', 'Box', 'Proton', 'Neutro',
            'Radeon', 'GeForce', 'nForce', 'Labtech', 'Station', 'Arco', 'Arkam',
        ];

        return (mt_rand(0, 4) ? $this->getOneItem($prefijos) . ' ' : '') . $this->getOneItem($nombres);
    }

    /**
     * Return a date between $start and $end.
     *
     * @param int $start
     * @param int $end
     *
     * @return false|string
     */
    protected function fecha($start = 2013, $end = 2018)
    {
        return date(mt_rand(1, 28) . '-' . mt_rand(1, 12) . '-' . mt_rand($start, $end));
    }

    /**
     * Return one random item from given array.
     *
     * @param $array
     *
     * @return mixed
     */
    public function getOneItem($array)
    {
        return $array[mt_rand(0, count($array) - 1)];
    }

    /**
     * Return an IBAN number.
     *
     * @return string
     */
    public function iban()
    {
        $pais = $this->getOneItem(['ES', 'FR']);
        $pesos = ['A' => '10', 'B' => '11', 'C' => '12', 'D' => '13', 'E' => '14', 'F' => '15',
            'G' => '16', 'H' => '17', 'I' => '18', 'J' => '19', 'K' => '20', 'L' => '21', 'M' => '22',
            'N' => '23', 'O' => '24', 'P' => '25', 'Q' => '26', 'R' => '27', 'S' => '28', 'T' => '29',
            'U' => '30', 'V' => '31', 'W' => '32', 'X' => '33', 'Y' => '34', 'Z' => '35',
        ];

        $ccc = mt_rand(1000, 9999) . mt_rand(1000, 9999) . mt_rand(1000, 9999) . mt_rand(1000, 9999) . mt_rand(1000, 9999);
        $dividendo = $ccc . $pesos[$pais[0]] . $pesos[$pais[1]] . '00';
        $digitoControl = 98 - \bcmod($dividendo, '97');

        if (strlen($digitoControl) === 1) {
            $digitoControl = '0' . $digitoControl;
        }

        return $pais . $digitoControl . $ccc;
    }

    /**
     * Returns random comments
     *
     * @return string
     */
    public function observaciones()
    {
        $observaciones = [
            'Pagado', 'Faltan piezas', 'No se corresponde con lo solicitado.',
            'Muy caro', 'Muy barato', 'Mala calidad',
            'La parte contratante de la primera parte será la parte contratante de la primera parte.',
            'Esto "no funcion"', 'Tacaño', "Marina D'or"
        ];

        /// Add a lot of Blas as an option
        $bla = 'Bla';
        while (mt_rand(0, 29) > 0) {
            $bla .= ', bla';
        }
        $observaciones[] = $bla . '.';

        return $this->getOneItem($observaciones);
    }

    /**
     * Returns a random number between $min and $max1
     * 1 out of 10 times returns a value between $min and $max2
     * 1 out of 3 times it returns a value with decimal points
     *
     * @param int $min
     * @param int $max1
     * @param int $max2
     *
     * @return float
     */
    public function precio($min, $max1, $max2)
    {
        $precio = mt_rand($min, $max1);

        if (mt_rand(0, 9) == 0) {
            $precio = mt_rand($min, $max2);
        } elseif ($precio < $max1 && mt_rand(0, 2) == 0) {
            $precio += round(mt_rand(1, 5) / mt_rand(1, 10), (int) FS_NF0);
            $precio = min([$max1, $precio]);
        }

        return $precio;
    }

    /**
     * Suffle all items from $model and put it to $variable.
     *
     * @param $variable
     * @param $model
     */
    public function shuffle(&$variable, $model)
    {
        $variable = $model->all();
        shuffle($variable);
    }

    /**
     * Shortens a string to $len and replaces special characters
     *
     * Devuelve el string acortado.
     *
     * @param string $txt
     * @param int    $len
     *
     * @return string
     */
    public function txt2codigo($txt, $len = 8)
    {
        $result = str_replace([' ', '-', '_', '&', 'ó', ':', 'ñ', '"', "'", '*'], ['', '', '', '', 'O', '', 'N', '', '', '-'], strtoupper($txt));
        if (strlen($result) > $len) {
            return substr($result, 0, $len - 1) . mt_rand(0, 9);
        }

        return $result;
    }

    /**
     * Returns a random string of $length length
     *
     * @param string $length la longitud del string
     *
     * @return string la cadena aleatoria
     */
    public function randomString($length = 30)
    {
        return mb_substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, $length);
    }

    /**
     * Devuelve listados de datos del model indicado.
     *
     * @param string $modelName
     * @param string $tableName
     * @param string $functionName
     * @param bool   $recursivo
     *
     * @return array
     */
    protected function randomModel($modelName, $tableName, $functionName, $recursivo = true)
    {
        $lista = [];

        $sql = 'SELECT * FROM ' . $tableName . ' ORDER BY ';
        $sql .= strtolower(FS_DB_TYPE) === 'mysql' ? 'RAND()' : 'random()';

        $data = $this->dataBase->selectLimit($sql, 100, 0);
        if (!empty($data)) {
            foreach ($data as $d) {
                $lista[] = new $modelName($d);
            }
        } elseif ($recursivo) {
            $this->{$functionName}();
            $lista = $this->randomModel($modelName, $tableName, $functionName, false);
        }

        return $lista;
    }

    /**
     * Returns an array with random clientes.
     *
     * @param bool $recursivo
     *
     * @return Model\Cliente[]
     */
    protected function randomClientes($recursivo = true)
    {
        return $this->randomModel('\FacturaScripts\Dinamic\Model\Cliente', 'clientes', 'clientes', $recursivo);
    }

    /**
     * Returns an array with random proveedores.
     *
     * @param bool $recursivo
     *
     * @return Model\Proveedor[]
     */
    protected function randomProveedores($recursivo = true)
    {
        return $this->randomModel('\FacturaScripts\Dinamic\Model\Proveedor', 'proveedores', 'proveedores', $recursivo);
    }

    /**
     * Returns an array with random empleados.
     *
     * @param bool $recursivo
     *
     * @return Model\Agente[]
     */
    protected function randomAgentes($recursivo = true)
    {
        return $this->randomModel('\FacturaScripts\Dinamic\Model\Agente', 'agentes', 'agentes', $recursivo);
    }

    /**
     * Returns an array with random artículos.
     *
     * @param bool $recursivo
     *
     * @return Model\Articulo[]
     */
    protected function randomArticulos($recursivo = true)
    {
        return $this->randomModel('\FacturaScripts\Dinamic\Model\Articulo', 'articulos', 'articulos', $recursivo);
    }
}
