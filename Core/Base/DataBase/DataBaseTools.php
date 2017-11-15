<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2015-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Base\DataBase;

use FacturaScripts\Core\Base\DataBase as db;
use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Base\Translator;

/**
 * Description of DataBaseTools
 *
 * @author Carlos García Gómez
 */
class DataBaseTools
{

    private static $dataBase;
    private static $i18n;
    private static $miniLog;

    public function __construct()
    {
        if (!isset(self::$dataBase)) {
            self::$dataBase = new db();
            self::$i18n = new Translator();
            self::$miniLog = new MiniLog();
        }
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
    public function getXmlTable($tableName, &$columns, &$constraints)
    {
        $return = false;

        $filename = FS_FOLDER . '/Dinamic/Table/' . $tableName . '.xml';
        if (!file_exists($filename)) {
            $filename = FS_FOLDER . '/Core/Table/' . $tableName . '.xml';
        }

        if (file_exists($filename)) {
            $xml = simplexml_load_string(file_get_contents($filename, FILE_USE_INCLUDE_PATH));
            if ($xml) {
                if ($xml->column) {
                    $key = 0;
                    foreach ($xml->column as $col) {
                        $columns[$key]['name'] = (string) $col->name;
                        $columns[$key]['type'] = (string) $col->type;

                        $columns[$key]['null'] = 'YES';
                        if ($col->null && strtolower($col->null) === 'no') {
                            $columns[$key]['null'] = 'NO';
                        }

                        if ($col->default === '') {
                            $columns[$key]['default'] = null;
                        } else {
                            $columns[$key]['default'] = (string) $col->default;
                        }

                        ++$key;
                    }

                    /// debe de haber columnas, sino es un fallo
                    $return = true;
                }

                if ($xml->constraint) {
                    $key = 0;
                    foreach ($xml->constraint as $col) {
                        $constraints[$key]['name'] = (string) $col->name;
                        $constraints[$key]['constraint'] = (string) $col->type;
                        ++$key;
                    }
                }
            } else {
                self::$miniLog->critical(self::$i18n->trans('error-reading-file', [$filename]));
            }
        } else {
            self::$miniLog->critical(self::$i18n->i18n->trans('file-not-found', [$filename]));
        }

        return $return;
    }
}
