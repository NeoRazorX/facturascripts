<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2016-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base;

/**
 * Abstract class that contains the basic methods to populate a table with 
 * random data 
 *
 * @author Rafael San José <info@rsanjoseo.com>
 */
abstract class AbstractRandom
{
    protected $model;
    protected $db;
    
    public function __construct($model)
    {
        $this->model=$model;
        $this->db = new Base\DataBase();
    }
    
    abstract public function generate($num=50);
    
    public function shuffle(&$variable, $modelo)
    {
        $variable=$modelo->all();
        shuffle($variable);
    }
    
    public function getOneItem($array)
    {
        return $array[mt_rand(0,count($array)-1)];
    }

    protected function fecha($desde, $hasta) {
        return date(mt_rand(1, 28) . '-' . mt_rand(1, 12) . '-' . mt_rand($desde, $hasta));
    }
    
    /**
     * Support method for the model constructor and data initialisation
     *
     * @param array $variable -> data destination
     * @param $modelo   -> model for each array item
     * @param bool $shuffle -> Randomly sort the list
     */
    public function _loadData_borrame_(&$variable, $modelo, $shuffle)
    {
        $variable = $modelo->all();
        if ($shuffle) {
            shuffle($variable);
        }
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
            $result = substr($result, 0, $len - 1) . mt_rand(0, 9);
        }

        return $result;
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
            
        return (mt_rand(0, 4)?$this->getOneItem($prefijos).' ':'').$this->getOneItem($nombres);
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
        $texto = $this->familia().$this->getOneItem($sufijos);

        $descripciones1 = [
            'Una alcachofa', 'Un motor', 'Una targeta gráfica (GPU)', 'Un procesador',
            'Un coche', 'Un dispositivo tecnológico', 'Un magnetofón', 'Un palo',
            'un cubo de basura', "Un objeto pequeño d'or", '"La hostia"',
        ];
        $descripcion=$this->getOneItem($descripciones1);
        shuffle($descripciones1);

        $descripciones = [
            '64 núcleos', 'chasis de fibra de carbono', '8 cilindros en V', 'frenos de berilio',
            '16 ejes', 'pantalla Super AMOLED', '1024 stream processors', 'un núcleo híbrido',
            '32 pistones digitales', 'tecnología digitrónica 4.1', 'cuernos metálicos', 'un palo',
            'memoria HBM', 'taladro matricial', 'Wifi 4G', 'faros de xenon', 'un ambientador de pino',
            'un posavasos', 'malignas intenciones', 'la virginidad intacta', 'malware', 'linux',
            'Windows Vista', 'propiedades psicotrópicas', 'spyware', 'reproductor 4k',
        ];
        shuffle($descripciones);


        switch (mt_rand(0, 4)) {
            case 0:
                break;

            case 1:
                $texto .= ": $descripcion con {$descripciones[0]}.";
                break;

            case 2:
                $texto .= ": $descripcion con {$descripciones[0]}, {$descripciones[1]}, {$descripciones[2]} y {$descripciones[3]}.";
                break;

            case 3:
                $texto .= ": $descripcion con:\n- {$descripciones[0]}\n- {$descripciones[1]}\n- {$descripciones[2]}\n- {$descripciones[3]}.";
                break;

            default:
                $texto .= ": $descripcion con {$descripciones[0]}, {$descripciones[1]} y {$descripciones[2]}.";
                break;
        }

        return $texto;
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
            $precio += round(mt_rand(1, 5) / mt_rand(1, 10), FS_NF0_ART);
            $precio = min([$max1, $precio]);
        }

        return $precio;
    }

    /**
     * Returns random comments
     *
     * @param string|bool $fecha
     *
     * @return string
     */
    public function observaciones($fecha = false)
    {
        $observaciones = [
            'Pagado', 'Faltan piezas', 'No se corresponde con lo solicitado.',
            'Muy caro', 'Muy barato', 'Mala calidad',
            'La parte contratante de la primera parte será la parte contratante de la primera parte.',
        ];

        /// Add a lot of Blas as an option
        $bla = 'Bla';
        while (mt_rand(0, 29) > 0) {
            $bla .= ', bla';
        }
        $observaciones[] = $bla . '.';

        /// Randomize
        shuffle($observaciones);

        if ($fecha && mt_rand(0, 2) == 0) {
            $semana = date('D', strtotime($fecha));
            $semanaArray = [
                'Mon' => 'lunes', 'Tue' => 'martes', 'Wed' => 'miércoles', 'Thu' => 'jueves',
                'Fri' => 'viernes', 'Sat' => 'sábado', 'Sun' => 'domingo',
            ];
            $title = urlencode(sprintf('{{Plantilla:Frase-%s}}', $semanaArray[$semana]));
            $sock = @fopen("http://es.wikiquote.org/w/api.php?action=parse&format=php&text=$title", 'rb');
            if (!$sock) {
                return $observaciones[0];
            }

            /// Perform the request to the server
            $array__ = unserialize(stream_get_contents($sock));
            $texto_final = strip_tags($array__['parse']['text']['*']);
            $texto_final = str_replace("\n\n\n\n", "\n", $texto_final);

            return $texto_final;
        }

        return $observaciones[0];
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
    

    /// Creo que a partir de aquí será para otro tipo de documento...
        
    
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

        $data = $this->db->selectLimit($sql, 100, 0);
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
