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

namespace FacturaScripts\Core\Lib;

use FacturaScripts\Core\Model\Base\ModelTrait;

/**
 * Description of DataGeneratorTools
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class DataGeneratorTools
{

    /**
     * Metodo de apoyo para el constructor de modelos e inicializacion de datos
     * @param array $variable    -> destino de los datos
     * @param ModelTrait $modelo   -> modelo de cada uno de los items del array
     * @param bool $shuffle   -> ordenar aleatoriamente la lista
     */
    public function loadData(&$variable, $modelo, $shuffle)
    {
        $variable = $modelo->all();
        if ($shuffle) {
            shuffle($variable);
        }
    }

    /**
     * Acorta un string hasta $len y sustituye caracteres especiales.
     * Devuelve el string acortado.
     * @param string $txt
     * @param int $len
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
     * Devuelve una descripción de producto aleatoria.
     * @return string
     */
    public function descripcion()
    {
        $prefijos = [
            'Jet', 'Jex', 'Max', 'Pro', 'FX', 'Neo', 'Maxi', 'Extreme', 'Sub',
            'Ultra', 'Minga', 'Hiper', 'Giga', 'Mega', 'Super', 'Fusion', 'Broken'
        ];
        shuffle($prefijos);

        $nombres = [
            'Motor', 'Engine', 'Generator', 'Tool', 'Oviode', 'Box', 'Proton', 'Neutro',
            'Radeon', 'GeForce', 'nForce', 'Labtech', 'Station', 'Arco', 'Arkam'
        ];
        shuffle($nombres);

        $sufijos = [
            'II', '3', 'XL', 'XXL', 'SE', 'GT', 'GTX', 'Pro', 'NX', 'XP', 'OS', 'Nitro'
        ];
        shuffle($sufijos);

        $descripciones1 = [
            'Una alcachofa', 'Un motor', 'Una targeta gráfica (GPU)', 'Un procesador',
            'Un coche', 'Un dispositivo tecnológico', 'Un magnetofón', 'Un palo',
            'un cubo de basura', "Un objeto pequeño d'or", '"La hostia"'
        ];
        shuffle($descripciones1);

        $descripciones2 = [
            '64 núcleos', 'chasis de fibra de carbono', '8 cilindros en V', 'frenos de berilio',
            '16 ejes', 'pantalla Super AMOLED', '1024 stream processors', 'un núcleo híbrido',
            '32 pistones digitales', 'tecnología digitrónica 4.1', 'cuernos metálicos', 'un palo',
            'memoria HBM', 'taladro matricial', 'Wifi 4G', 'faros de xenon', 'un ambientador de pino',
            'un posavasos', 'malignas intenciones', 'la virginidad intacta', 'malware', 'linux',
            'Windows Vista', 'propiedades psicotrópicas', 'spyware', 'reproductor 4k'
        ];
        shuffle($descripciones2);

        $texto = $prefijos[0] . ' ' . $nombres[0] . ' ' . $sufijos[0];

        switch (mt_rand(0, 4)) {
            case 0:
                break;

            case 1:
                $texto .= ': ' . $descripciones1[0] . ' con ' . $descripciones2[0] . '.';
                break;

            case 2:
                $texto .= ': ' . $descripciones1[0] . ' con ' . $descripciones2[0] . ', ' . $descripciones2[1] . ', ' . $descripciones2[2] . ' y ' . $descripciones2[3] . '.';
                break;

            case 3:
                $texto .= ': ' . $descripciones1[0] . " con:\n- " . $descripciones2[0] . "\n- " . $descripciones2[1] . "\n- " . $descripciones2[2] . "\n- " . $descripciones2[3] . '.';
                break;

            default:
                $texto .= ': ' . $descripciones1[0] . ' con ' . $descripciones2[0] . ', ' . $descripciones2[1] . ' y ' . $descripciones2[2] . '.';
                break;
        }

        return $texto;
    }

    /**
     * Devuelve un número aleatorio entre $min y $max1.
     * 1 de cada 10 veces lo devuelve entre $min y $max2.
     * 1 de cada 5 veces lo devuelve con decimales.
     * @param int $min
     * @param int $max1
     * @param int $max2
     * @return float
     */
    public function cantidad($min, $max1, $max2)
    {
        $cantidad = mt_rand($min, $max1);

        if (mt_rand(0, 9) === 0) {
            $cantidad = mt_rand($min, $max2);
        } elseif ($cantidad < $max1 && mt_rand(0, 4) === 0) {
            $cantidad += round(mt_rand(1, 5) / mt_rand(1, 10), mt_rand(0, 3));
            $cantidad = min([$max1, $cantidad]);
        }

        return $cantidad;
    }

    /**
     * Devuelve un número aleatorio entre $min y $max1.
     * 1 de cada 10 veces lo devuelve entre $min y $max2.
     * 1 de cada 3 veces lo devuelve con decimales.
     * @param int $min
     * @param int $max1
     * @param int $max2
     * @return float
     */
    public function precio($min, $max1, $max2)
    {
        $precio = mt_rand($min, $max1);

        if (mt_rand(0, 9) === 0) {
            $precio = mt_rand($min, $max2);
        } elseif ($precio < $max1 && mt_rand(0, 2) === 0) {
            $precio += round(mt_rand(1, 5) / mt_rand(1, 10), FS_NF0_ART);
            $precio = min([$max1, $precio]);
        }

        return $precio;
    }

    /**
     * Devuelve un nombre aleatorio.
     * @return string
     */
    public function nombre()
    {
        $nombres = [
            'Carlos', 'Pepe', 'Wilson', 'Petra', 'Madonna', 'Justin',
            'Emiliana', 'Jo', 'Penélope', 'Mia', 'Wynona', 'Antonio',
            'Joe', 'Cristiano', 'Mohamed', 'John', 'Ali', 'Pastor',
            'Barak', 'Sadam', 'Donald', 'Jorge', 'Joel', 'Pedro', 'Mariano',
            'Albert', 'Alberto', 'Gorka', 'Cecilia', 'Carmena', 'Pichita',
            'Alicia', 'Laura', 'Riola', 'Wilson', 'Jaume', 'David',
            "D'Ambrosio", '"El nota"', '"El master"'
        ];

        shuffle($nombres);
        return $nombres[0];
    }

    /**
     * Devuelve dos apellidos aleatorios.
     * @return string
     */
    public function apellidos()
    {
        $apellidos = [
            'García', 'Gómez', 'Ronaldo', 'Suarez', 'Wilson', 'Pacheco',
            'Escobar', 'Mendoza', 'Pérez', 'Cruz', 'Lee', 'Smith', 'Humilde',
            'Hijo de Dios', 'Petrov', 'Maximiliano', 'Nieve', 'Snow', 'Trump',
            'Obama', 'Ali', 'Stark', 'Sanz', 'Rajoy', 'Sánchez', 'Iglesias',
            'Rivera', 'Tumor', 'Lanister', 'Suarez', 'Aznar', 'Botella',
            'Errejón', "D'Ambrosio", 'Peña'
        ];

        shuffle($apellidos);
        return $apellidos[0] . ' ' . $apellidos[1];
    }

    /**
     * Devuelve un nombre comercial aleatorio.
     * @return string
     */
    public function empresa()
    {
        $nombres = [
            'Tech', 'Motor', 'Pasión', 'Future', 'Max', 'Massive', 'Industrial',
            'Plastic', 'Pro', 'Micro', 'System', 'Light', 'Magic', 'Fake', 'Techno',
            'Miracle', 'NX', 'Smoke', 'Steam', 'Power', 'FX', 'Fusion', 'Bastion',
            'Investments', 'Solutions', 'Neo', 'Ming', 'Tube', 'Pear', 'Apple',
            'Dolphin', 'Chrome', 'Cat', 'Hat', 'Linux', 'Soft', 'Mobile', 'Phone',
            'XL', 'Open', 'Thunder', 'Zero', 'Scorpio', 'Zelda', '10', 'V', 'Q',
            'X', 'Arch', 'Arco', 'Broken', 'Arkam', 'RX', "d'Art", 'Peña', '"La cosa"'
        ];

        $separador = ['-', ' & ', ' ', '_', '', '/', '*'];
        $tipo = ['S.L.', 'S.A.', 'Inc.', 'LTD', 'Corp.'];

        shuffle($nombres);
        shuffle($separador);
        shuffle($tipo);
        return $nombres[0] . $separador[0] . $nombres[1] . ' ' . $tipo[0];
    }

    /**
     * Devuelve un email aleatorio.
     * @return string
     */
    public function email()
    {
        $nicks = [
            'neo', 'carlos', 'moko', 'snake', 'pikachu', 'pliskin', 'ocelot', 'samurai',
            'ninja', 'penetrator', 'info', 'compras', 'ventas', 'administracion', 'contacto',
            'contact', 'invoices', 'mail'
        ];

        shuffle($nicks);
        return $nicks[0] . '.' . mt_rand(2, 9999) . '@facturascripts.com';
    }

    /**
     * Devuelve una provincia aleatoria.
     * @return string
     */
    public function provincia()
    {
        $nombres = [
            'A Coruña', 'Alava', 'Albacete', 'Alicante', 'Almería', 'Asturias', 'Ávila', 'Badajoz', 'Barcelona',
            'Burgos', 'Cáceres', 'Cádiz', 'Cantabria', 'Castellón', 'Ceuta', 'Ciudad Real', 'Córdoba', 'Cuenca',
            'Girona', 'Granada', 'Guadalajara', 'Guipuzcoa', 'Huelva', 'Huesca', 'Jaen', 'León', 'Lleida', 'La Rioja',
            'Lugo', 'Madrid', 'Málaga', 'Melilla', 'Murcia', 'Navarra', 'Ourense', 'Palencia', 'Las Palmas', 'Pontevedra',
            'Salamanca', 'Segovia', 'Sevilla', 'Soria', 'Tarragona', 'Tenerife', 'Teruel', 'Toledo', 'Valencia',
            'Valladolid', 'Vizcaya', 'Zamora', 'Zaragoza'
        ];

        shuffle($nombres);
        return $nombres[0];
    }

    /**
     * Devuelve una ciudad aleatoria.
     * @return string
     */
    public function ciudad()
    {
        $nombres = [
            'A Coruña', 'Alava', 'Albacete', 'Alicante', 'Almería', 'Asturias', 'Ávila', 'Badajoz', 'Barcelona',
            'Burgos', 'Cáceres', 'Cádiz', 'Cantabria', 'Castellón', 'Ceuta', 'Ciudad Real', 'Córdoba', 'Cuenca',
            'Girona', 'Granada', 'Guadalajara', 'Guipuzcoa', 'Huelva', 'Huesca', 'Jaen', 'León', 'Lleida', 'La Rioja',
            'Lugo', 'Madrid', 'Málaga', 'Melilla', 'Murcia', 'Navarra', 'Ourense', 'Palencia', 'Las Palmas', 'Pontevedra',
            'Salamanca', 'Segovia', 'Sevilla', 'Soria', 'Tarragona', 'Tenerife', 'Teruel', 'Toledo', 'Valencia',
            'Valladolid', 'Vizcaya', 'Zamora', 'Zaragoza', 'Torrevieja', 'Elche'
        ];

        shuffle($nombres);
        return $nombres[0];
    }

    /**
     * Devuelve una dirección aleatoria.
     * @return string
     */
    public function direccion()
    {
        $tipos = ['Calle', 'Avenida', 'Polígono', 'Carretera'];
        $nombres = [
            'Infante', 'Principal', 'Falsa', '58', '74', 'Pacheco', 'Baleares',
            'Del Pacífico', 'Rue', "d'Ambrosio", 'Bañez', '"La calle"'
        ];

        shuffle($tipos);
        shuffle($nombres);

        if (mt_rand(0, 2) === 0) {
            return $tipos[0] . ' ' . $nombres[0] . ', nº' . mt_rand(1, 199) . ', puerta ' . mt_rand(1, 99);
        }

        return $tipos[0] . ' ' . $nombres[0] . ', ' . mt_rand(1, 99);
    }

    /**
     * Devuelve unas observaciones aleatorias.
     * @param string|bool $fecha
     * @return string
     */
    public function observaciones($fecha = false)
    {
        $observaciones = [
            'Pagado', 'Faltan piezas', 'No se corresponde con lo solicitado.',
            'Muy caro', 'Muy barato', 'Mala calidad',
            'La parte contratante de la primera parte será la parte contratante de la primera parte.'
        ];

        /// añadimos muchos blas como otra opción
        $bla = 'Bla';
        while (mt_rand(0, 29) > 0) {
            $bla .= ', bla';
        }
        $observaciones[] = $bla . '.';

        /// randomizamos (es posible que me haya inventado esta palabra)
        shuffle($observaciones);

        if ($fecha && mt_rand(0, 2) === 0) {
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

            # Hacemos la peticion al servidor
            $array__ = unserialize(stream_get_contents($sock));
            $texto_final = strip_tags($array__['parse']['text']['*']);
            $texto_final = str_replace("\n\n\n\n", "\n", $texto_final);

            return $texto_final;
        }

        return $observaciones[0];
    }

    /**
     * Devuelve un string aleatorio de longitud $length
     * @param string $length la longitud del string
     * @return string la cadena aleatoria
     */
    public function randomString($length = 30)
    {
        return mb_substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, $length);
    }
}
