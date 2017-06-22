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

namespace FacturaScripts\Core\Base;

/**
 * La clase de la que heredan todos los modelos, conecta a la base de datos,
 * comprueba la estructura de la tabla y de ser necesario la crea o adapta.
 * 
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
abstract class Model {

    /**
     * Proporciona acceso directo a la base de datos.
     * @var DataBase
     */
    protected $dataBase;

    /**
     * Nombre de la tabla en la base de datos.
     * @var string 
     */
    protected $tableName;

    /**
     * Directorio donde se encuentra el directorio table con
     * el XML con la estructura de la tabla.
     * @var string 
     */
    private static $baseDir;

    /**
     * Lista de tablas ya comprobadas.
     * @var array 
     */
    private static $checkedTables;

    /**
     * Gestiona el log de todos los controladores, modelos y base de datos.
     * @var MiniLog
     */
    protected static $miniLog;
    
     /**
     * Permite conectar e interactuar con el sistema de caché.
     * @var CacheItemPoll
     */
    protected $cache;
    /**
     * Constructor.
     * @param string $tableName nombre de la tabla de la base de datos.
     */
    public function __construct($tableName = '') {
        $this->cache = new CacheItemPool();
        $this->dataBase = new DataBase();
        $this->tableName = $tableName;

        if (!isset(self::$checkedTables)) {
            self::$miniLog = new MiniLog();
            self::$checkedTables = [];

            $pluginManager = new PluginManager();
            /// directorio donde se encuentra el archivo xml que define la estructura de la tabla
            self::$baseDir = $pluginManager->folder() . '/Dinamic/Table/';
        }

        if ($tableName != '' && !in_array($tableName, self::$checkedTables) && $this->checkTable($tableName)) {
            self::$checkedTables[] = $tableName;
        }
    }

    public function tableName() {
        return $this->tableName;
    }

    /**
     * Esta función es llamada al crear una tabla.
     * Permite insertar valores en la tabla.
     */
    abstract protected function install();

    /**
     * Esta función devuelve TRUE si los datos del objeto se encuentran
     * en la base de datos.
     */
    abstract public function exists();

    /**
     * Esta función sirve tanto para insertar como para actualizar
     * los datos del objeto en la base de datos.
     */
    abstract public function save();

    /**
     * Esta función sirve para eliminar los datos del objeto de la base de datos
     */
    abstract public function delete();

    /**
     * Escapa las comillas de una cadena de texto.
     * @param string $str cadena de texto a escapar
     * @return string cadena de texto resultante
     */
    protected function escapeString($str) {
        return $this->dataBase->escapeString($str);
    }

    /**
     * Transforma una variable en una cadena de texto válida para ser
     * utilizada en una consulta SQL.
     * @param mixed $val
     * @return string
     */
    public function var2str($val) {
        if (is_null($val)) {
            return 'NULL';
        } else if (is_bool($val)) {
            if ($val) {
                return 'TRUE';
            } else {
                return 'FALSE';
            }
        } else if (preg_match('/^([0-9]{1,2})-([0-9]{1,2})-([0-9]{4})$/i', $val)) {
            return "'" . Date($this->dataBase->dateStyle(), strtotime($val)) . "'"; /// es una fecha
        } else if (preg_match('/^([0-9]{1,2})-([0-9]{1,2})-([0-9]{4}) ([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})$/i', $val)) {
            return "'" . Date($this->dataBase->dateStyle() . ' H:i:s', strtotime($val)) . "'"; /// es una fecha+hora
        } else {
            return "'" . $this->dataBase->escapeString($val) . "'";
        }
    }

    /**
     * Convierte una variable con contenido binario a texto.
     * Lo hace en base64.
     * @param mixed $val
     * @return string
     */
    protected function bin2str($val) {
        if (is_null($val)) {
            return 'NULL';
        } else {
            return "'" . base64_encode($val) . "'";
        }
    }

    /**
     * Convierte un texto a binario.
     * Lo hace con base64.
     * @param string $val
     * @return null|string
     */
    protected function str2bin($val) {
        if (is_null($val)) {
            return NULL;
        } else {
            return base64_decode($val);
        }
    }

    /**
     * PostgreSQL guarda los valores TRUE como 't', MySQL como 1.
     * Esta función devuelve TRUE si el valor se corresponde con
     * alguno de los anteriores.
     * @param string $val
     * @return boolean
     */
    public function str2bool($val) {
        return ($val == 't' OR $val == '1');
    }

    /**
     * Devuelve el valor entero de la variable $s,
     * o NULL si es NULL. La función intval() del php devuelve 0 si es NULL.
     * @param string $str
     * @return integer
     */
    public function intval($str) {
        if (is_null($str)) {
            return NULL;
        } else {
            return intval($str);
        }
    }

    /**
     * Compara dos números en coma flotante con una precisión de $precision,
     * devuelve TRUE si son iguales, FALSE en caso contrario.
     * @param double $f1
     * @param double $f2
     * @param integer $precision
     * @param boolean $round
     * @return boolean
     */
    public function floatcmp($f1, $f2, $precision = 10, $round = FALSE) {
        if ($round OR ! function_exists('bccomp')) {
            return( abs($f1 - $f2) < 6 / pow(10, $precision + 1) );
        } else {
            return( bccomp((string) $f1, (string) $f2, $precision) == 0 );
        }
    }

    /**
     * Devuelve un array con todas las fechas entre $first y $last.
     * @param string $first
     * @param string $last
     * @param string $step
     * @param string $format
     * @return mixed
     */
    protected function dateRange($first, $last, $step = '+1 day', $format = 'd-m-Y') {
        $dates = array();
        $current = strtotime($first);
        $last = strtotime($last);

        while ($current <= $last) {
            $dates[] = date($format, $current);
            $current = strtotime($step, $current);
        }

        return $dates;
    }

    /**
     * Esta función convierte:
     * < en &lt;
     * > en &gt;
     * " en &quot;
     * ' en &#39;
     * 
     * No tengas la tentación de sustiturla por htmlentities o htmlspecialshars
     * porque te encontrarás con muchas sorpresas desagradables.
     * @param string $txt
     * @return string
     */
    public function noHtml($txt) {
        $newt = str_replace(
                array('<', '>', '"', "'"), array('&lt;', '&gt;', '&quot;', '&#39;'), $txt
        );

        return trim($newt);
    }

    /**
     * Devuelve una cadena de texto aleatorio de longitud $length
     * @param integer $length
     * @return string
     */
    protected function randomString($length = 10) {
        return mb_substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
    }

    /**
     * Comprueba y actualiza la estructura de la tabla si es necesario
     * @param string $tableName
     * @return boolean
     */
    protected function checkTable($tableName) {
        $done = TRUE;
        $sql = '';
        $xmlCols = [];
        $xmlCons = [];

        if ($this->getXmlTable($tableName, $xmlCols, $xmlCons)) {
            if ($this->dataBase->tableExists($tableName)) {
                if (!$this->dataBase->checkTableAux($tableName)) {
                    self::$miniLog->critical('Error al convertir la tabla a InnoDB.');
                }

                /**
                 * Si hay que hacer cambios en las restricciones, eliminamos todas las restricciones,
                 * luego añadiremos las correctas. Lo hacemos así porque evita problemas en MySQL.
                 */
                $dbCons = $this->dataBase->getConstraints($tableName);
                $sql2 = $this->dataBase->compareConstraints($tableName, $xmlCons, $dbCons, TRUE);
                if ($sql2 != '') {
                    if (!$this->dataBase->exec($sql2)) {
                        self::$miniLog->critical('Error al comprobar la tabla ' . $tableName);
                    }

                    /// leemos de nuevo las restricciones
                    $dbCons = $this->dataBase->getConstraints($tableName);
                }

                /// comparamos las columnas
                $dbCols = $this->dataBase->getColumns($tableName);
                $sql .= $this->dataBase->compareColumns($tableName, $xmlCols, $dbCols);

                /// comparamos las restricciones
                $sql .= $this->dataBase->compareConstraints($tableName, $xmlCons, $dbCons);
            } else {
                /// generamos el sql para crear la tabla
                $sql .= $this->dataBase->generateTable($tableName, $xmlCols, $xmlCons);
                $sql .= $this->install();
            }

            if ($sql != '') {
                if (!$this->dataBase->exec($sql)) {
                    self::$miniLog->critical('Error al comprobar la tabla ' . $tableName);
                    $done = FALSE;
                }
            }
        } else {
            self::$miniLog->critical('Error con el xml.');
            $done = FALSE;
        }

        return $done;
    }

    /**
     * Obtiene las columnas y restricciones del fichero xml para una tabla
     * @param string $tableName
     * @param array $columns
     * @param array $constraints
     * @return boolean
     */
    protected function getXmlTable($tableName, &$columns, &$constraints) {
        $return = FALSE;
        $filename = self::$baseDir . $tableName . '.xml';

        if (file_exists($filename)) {
            $xml = simplexml_load_string(file_get_contents($filename, FILE_USE_INCLUDE_PATH));
            if ($xml) {
                if ($xml->columna) {
                    $key = 0;
                    foreach ($xml->columna as $col) {
                        $columns[$key]['nombre'] = (string) $col->nombre;
                        $columns[$key]['tipo'] = (string) $col->tipo;

                        $columns[$key]['nulo'] = 'YES';
                        if ($col->nulo) {
                            if (strtolower($col->nulo) == 'no') {
                                $columns[$key]['nulo'] = 'NO';
                            }
                        }

                        if ($col->defecto == '') {
                            $columns[$key]['defecto'] = NULL;
                        } else {
                            $columns[$key]['defecto'] = (string) $col->defecto;
                        }

                        $key++;
                    }

                    /// debe de haber columnas, sino es un fallo
                    $return = TRUE;
                }

                if ($xml->restriccion) {
                    $key = 0;
                    foreach ($xml->restriccion as $col) {
                        $constraints[$key]['nombre'] = (string) $col->nombre;
                        $constraints[$key]['consulta'] = (string) $col->consulta;
                        $key++;
                    }
                }
            } else {
                self::$miniLog->critical('Error al leer el archivo ' . $filename);
            }
        } else {
            self::$miniLog->critical('Archivo ' . $filename . ' no encontrado.');
        }

        return $return;
    }

}
