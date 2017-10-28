<?php

/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Model\Base;
use FacturaScripts\Core\Base;
use FacturaScripts\Core\Base\Cache;
use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\DefaultItems;
use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Base\Translator;
 


class checkTable
{
    /**
     * Proporciona acceso directo a la base de datos.
     *
     * @var DataBase
     */
    protected $dataBase;

    /**
     * Permite conectar e interactuar con el sistema de caché.
     *
     * @var Cache
     */
    protected $cache;

    /**
     * Clase que se utiliza para definir algunos valores por defecto:
     * codejercicio, codserie, coddivisa, etc...
     *
     * @var DefaultItems
     */
    protected $defaultItems;

    /**
     * Traductor multi-idioma.
     *
     * @var Translator
     */
    protected $i18n;

    /**
     * Gestiona el log de todos los controladores, modelos y base de datos.
     *
     * @var MiniLog
     */
    protected $miniLog;
    
     
     public function __construct()
     {
          $this->init();
         
     }
     /**
     * Inicializa lo necesario.
     */
    public function init()
    {
      $this->cache = new Cache();
        $this->dataBase = new DataBase();
        $this->defaultItems = new DefaultItems();
        $this->i18n = new Translator();
        $this->miniLog = new MiniLog();
    }
     /**
     * Comprueba y actualiza la estructura de la tabla si es necesario
     *
     * @param string $tableName
     *
     * @return bool
     */
    public function checkTable($tableName)
    {
        $done = true;
        $sql = '';
        $xmlCols = [];
        $xmlCons = [];

        if ($this->getXmlTable($tableName, $xmlCols, $xmlCons)) {
            if ($this->dataBase->tableExists($tableName)) {
                if (!$this->dataBase->checkTableAux($tableName)) {
                    $this->miniLog->critical($this->i18n->trans('error-to-innodb'));
                }

                /**
                 * Si hay que hacer cambios en las restricciones, eliminamos todas las restricciones,
                 * luego añadiremos las correctas. Lo hacemos así porque evita problemas en MySQL.
                 */
                $dbCons = $this->dataBase->getConstraints($tableName);
                $sql2 = $this->dataBase->compareConstraints($tableName, $xmlCons, $dbCons, true);
                if ($sql2 !== '') {
                    if (!$this->dataBase->exec($sql2)) {
                        $this->miniLog->critical($this->i18n->trans('check-table', [$tableName]));
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
            if ($sql !== '' && !$this->dataBase->exec($sql)) {
                $this->miniLog->critical($this->i18n->trans('check-table', [$tableName]));
                $this->cache->clear();
                $done = false;
            }
        } else {
            $this->miniLog->critical($this->i18n->trans('error-on-xml-file'));
            $done = false;
        }

        return $done;
    }
    /**
     * Obtiene las columnas y restricciones del fichero xml para una tabla
     *
     * @param string $tableName
     * @param array  $columns
     * @param array  $constraints
     *
     * @return bool
     */
    protected function getXmlTable($tableName, &$columns, &$constraints)
    {
        $return = false;

        $filename = FS_FOLDER . '/Dinamic/Table/' . $tableName . '.xml';
        if (file_exists($filename)) {
            $xml = simplexml_load_string(file_get_contents($filename, FILE_USE_INCLUDE_PATH));
            if ($xml) {
                if ($xml->columna) {
                    $key = 0;
                    foreach ($xml->columna as $col) {
                        $columns[$key]['nombre'] = (string) $col->nombre;
                        $columns[$key]['tipo'] = (string) $col->tipo;

                        $columns[$key]['nulo'] = 'YES';
                        if ($col->nulo && strtolower($col->nulo) === 'no') {
                            $columns[$key]['nulo'] = 'NO';
                        }

                        if ($col->defecto === '') {
                            $columns[$key]['defecto'] = null;
                        } else {
                            $columns[$key]['defecto'] = (string) $col->defecto;
                        }

                        ++$key;
                    }

                    /// debe de haber columnas, sino es un fallo
                    $return = true;
                }

                if ($xml->restriccion) {
                    $key = 0;
                    foreach ($xml->restriccion as $col) {
                        $constraints[$key]['nombre'] = (string) $col->nombre;
                        $constraints[$key]['consulta'] = (string) $col->consulta;
                        ++$key;
                    }
                }
            } else {
                $this->miniLog->critical($this->i18n->trans('error-reading-file', [$filename]));
            }
        } else {
            $this->miniLog->critical($this->i18n->trans('file-not-found', [$filename]));
        }

        return $return;
    }
    /**
     * Esta función es llamada al crear la tabla del modelo. Devuelve el SQL
     * que se ejecutará tras la creación de la tabla. útil para insertar valores
     * por defecto.
     *
     * @return string
     */
    public function install()
    {
        if (method_exists(__CLASS__, 'cleanCache')) {
            $this->cleanCache();
        }

        return '';
    }

    
   
}

