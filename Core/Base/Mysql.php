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

define('FS_FOREIGN_KEYS', '1');

/**
 * Clase para conectar a MySQL.
 * 
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class Mysql {

    /**
     * El enlace con la base de datos.
     * @var \mysqli
     */
    private static $link;

    /**
     * Nº de selects ejecutados.
     * @var integer 
     */
    private static $totalSelects;

    /**
     * Nº de transacciones ejecutadas.
     * @var integer 
     */
    private static $totalTransactions;

    /**
     * Gestiona el log de todos los controladores, modelos y base de datos.
     * @var MiniLog
     */
    private static $miniLog;

    public function __construct() {
        if (!isset(self::$link)) {
            self::$totalSelects = 0;
            self::$totalTransactions = 0;
            self::$miniLog = new MiniLog();
        }
    }

    /**
     * Conecta a la base de datos.
     * @return boolean
     */
    public function connect() {
        $connected = FALSE;

        if (self::$link) {
            $connected = TRUE;
        } else if (class_exists('mysqli')) {
            self::$link = new \mysqli(FS_DB_HOST, FS_DB_USER, FS_DB_PASS, FS_DB_NAME, intval(FS_DB_PORT));

            if (self::$link->connect_error) {
                self::$miniLog->critical(self::$link->connect_error);
                self::$link = NULL;
            } else {
                self::$link->set_charset('utf8');
                $connected = TRUE;

                if (!FS_FOREIGN_KEYS) {
                    /// desactivamos las claves ajenas
                    $this->exec("SET foreign_key_checks = 0;");
                }

                /// desactivamos el autocommit
                self::$link->autocommit(FALSE);
            }
        } else {
            self::$miniLog->critical('No tienes instalada la extensión de PHP para MySQL.');
        }

        return $connected;
    }

    /**
     * Devuelve TRUE si se está conectado a la base de datos.
     * @return boolean
     */
    public function connected() {
        return (bool) self::$link;
    }

    /**
     * Desconecta de la base de datos.
     * @return boolean
     */
    public function close() {
        if (self::$link) {
            $return = self::$link->close();
            self::$link = NULL;
            return $return;
        } else {
            return TRUE;
        }
    }

    /**
     * Devuelve el motor de base de datos y la versión.
     * @return string
     */
    public function version() {
        if (self::$link) {
            return 'MYSQL ' . self::$link->server_version;
        } else {
            return FALSE;
        }
    }

    /**
     * Devuelve el número de selects ejecutados.
     * @return integer
     */
    public function getTotalSelects() {
        return self::$totalSelects;
    }

    /**
     * Devuele le número de transacciones realizadas.
     * @return integer
     */
    public function getTotalTransactions() {
        return self::$totalTransactions;
    }

    /**
     * Devuelve un array con las columnas de una tabla dada.
     * @param string $tableName
     * @return mixed
     */
    public function getColumns($tableName) {
        $columns = array();

        $aux = $this->select("SHOW COLUMNS FROM `" . $tableName . "`;");
        if ($aux) {
            foreach ($aux as $a) {
                $columns[] = array(
                    'name' => $a['Field'],
                    'type' => $a['Type'],
                    'default' => $a['Default'],
                    'is_nullable' => $a['Null'],
                    'extra' => $a['Extra']
                );
            }
        }

        return $columns;
    }

    /**
     * Devuelve una array con las restricciones de una tabla dada:
     * clave primaria, claves ajenas, etc.
     * @param string $tableName
     * @return mixed
     */
    public function getConstraints($tableName) {
        $constraints = array();
        $sql = "SELECT CONSTRAINT_NAME as name, CONSTRAINT_TYPE as type FROM information_schema.table_constraints "
                . "WHERE table_schema = schema() AND table_name = '" . $tableName . "';";

        $aux = $this->select($sql);
        if ($aux) {
            foreach ($aux as $a) {
                $constraints[] = $a;
            }
        }

        return $constraints;
    }

    /**
     * Devuelve una array con las restricciones de una tabla dada, pero aportando muchos más detalles.
     * @param string $tableName
     * @return mixed
     */
    public function getConstraintsExtended($tableName) {
        $constraints = array();
        $sql = "SELECT t1.constraint_name as name,
            t1.constraint_type as type,
            t2.column_name,
            t2.referenced_table_name AS foreign_table_name,
            t2.referenced_column_name AS foreign_column_name,
            t3.update_rule AS on_update,
            t3.delete_rule AS on_delete
         FROM information_schema.table_constraints t1
         LEFT JOIN information_schema.key_column_usage t2
            ON t1.table_schema = t2.table_schema
            AND t1.table_name = t2.table_name
            AND t1.constraint_name = t2.constraint_name
         LEFT JOIN information_schema.referential_constraints t3
            ON t3.constraint_schema = t1.table_schema
            AND t3.constraint_name = t1.constraint_name
         WHERE t1.table_schema = SCHEMA() AND t1.table_name = '" . $tableName . "'
         ORDER BY type DESC, name ASC;";

        $aux = $this->select($sql);
        if ($aux) {
            foreach ($aux as $a) {
                $constraints[] = $a;
            }
        }

        return $constraints;
    }

    /**
     * Devuelve una array con los indices de una tabla dada.
     * @param string $tableName
     * @return mixed
     */
    public function getIndexes($tableName) {
        $indexes = array();

        $aux = $this->select("SHOW INDEXES FROM " . $tableName . ";");
        if ($aux) {
            foreach ($aux as $a) {
                $indexes[] = array('name' => $a['Key_name']);
            }
        }

        return $indexes;
    }

    /**
     * Devuelve un array con los nombres de las tablas de la base de datos.
     * @return mixed
     */
    public function listTables() {
        $tables = array();

        $aux = $this->select("SHOW TABLES;");
        if ($aux) {
            foreach ($aux as $a) {
                if (isset($a['Tables_in_' . FS_DB_NAME])) {
                    $tables[] = array('name' => $a['Tables_in_' . FS_DB_NAME]);
                }
            }
        }

        return $tables;
    }

    /**
     * Ejecuta una sentencia SQL de tipo select, y devuelve un array con los resultados,
     * o false en caso de fallo.
     * @param string $sql
     * @return mixed
     */
    public function select($sql) {
        $result = FALSE;

        if (self::$link) {
            /// añadimos la consulta sql al historial
            self::$miniLog->sql($sql);

            $aux = self::$link->query($sql);
            if ($aux) {
                $result = array();
                while ($row = $aux->fetch_array(MYSQLI_ASSOC)) {
                    $result[] = $row;
                }
                $aux->free();
            } else {
                /// añadimos el error a la lista de errores
                self::$miniLog->error(self::$link->error);
            }

            /// aumentamos el contador de selects realizados
            self::$totalSelects++;
        }

        return $result;
    }

    /**
     * Ejecuta una sentencia SQL de tipo select, pero con paginación,
     * y devuelve un array con los resultados,
     * o false en caso de fallo.
     * Limit es el número de elementos que quieres que devuelve.
     * Offset es el número de resultado desde el que quieres que empiece.
     * @param string $sql
     * @param integer $limit
     * @param integer $offset
     * @return mixed
     */
    public function selectLimit($sql, $limit = FS_ITEM_LIMIT, $offset = 0) {
        $result = FALSE;

        if (self::$link) {
            /// añadimos limit y offset a la consulta sql
            $sql .= ' LIMIT ' . $limit . ' OFFSET ' . $offset . ';';

            /// añadimos la consulta sql al historial
            self::$miniLog->sql($sql);

            $aux = self::$link->query($sql);
            if ($aux) {
                $result = array();
                while ($row = $aux->fetch_array(MYSQLI_ASSOC)) {
                    $result[] = $row;
                }
                $aux->free();
            } else {
                /// añadimos el error a la lista de errores
                self::$miniLog->error(self::$link->error);
            }

            /// aumentamos el contador de selects realizados
            self::$totalSelects++;
        }

        return $result;
    }

    /**
     * Ejecuta sentencias SQL sobre la base de datos (inserts, updates y deletes).
     * Para selects, mejor usar las funciones select() o selectLimit().
     * Por defecto se inicia una transacción, se ejecutan las consultas, y si todo
     * sale bien, se guarda, sino se deshace.
     * Se puede evitar este modo de transacción si se pone false
     * en el parametro transaction.
     * @param string $sql
     * @param boolean $transaction
     * @return boolean
     */
    public function exec($sql, $transaction = TRUE) {
        $result = FALSE;

        if (self::$link) {
            /// añadimos la consulta sql al historial
            self::$miniLog->sql($sql);

            if ($transaction) {
                $this->beginTransaction();
            }

            $num = 0;
            if (self::$link->multi_query($sql)) {
                do {
                    $num++;
                } while (self::$link->more_results() && self::$link->next_result());
            }

            if (self::$link->errno) {
                self::$miniLog->error('Error al ejecutar la consulta ' . $num . ': ' . self::$link->error .
                        '. La secuencia ocupa la posición ' . count(self::$miniLog->read('sql')));
            } else {
                $result = TRUE;
            }

            if ($transaction) {
                if ($result) {
                    $this->commit();
                } else {
                    $this->rollback();
                }
            }
        }

        return $result;
    }

    /**
     * Inicia una transacción SQL.
     * @return boolean
     */
    public function beginTransaction() {
        if (self::$link) {
            /**
             * Ejecutamos START TRANSACTION en lugar de begin_transaction()
             * para mayor compatibilidad.
             */
            return self::$link->query("START TRANSACTION;");
        } else {
            return FALSE;
        }
    }

    /**
     * Guarda los cambios de una transacción SQL.
     * @return boolean
     */
    public function commit() {
        if (self::$link) {
            /// aumentamos el contador de selects realizados
            self::$totalTransactions++;

            return self::$link->commit();
        } else {
            return FALSE;
        }
    }

    /**
     * Deshace los cambios de una transacción SQL.
     * @return boolean
     */
    public function rollback() {
        if (self::$link) {
            return self::$link->rollback();
        } else {
            return FALSE;
        }
    }

    /**
     * Devuleve el último ID asignado al hacer un INSERT en la base de datos.
     * @return integer|false
     */
    public function lastval() {
        $aux = $this->select('SELECT LAST_INSERT_ID() as num;');
        if ($aux) {
            return $aux[0]['num'];
        } else {
            return FALSE;
        }
    }

    /**
     * Escapa las comillas de la cadena de texto.
     * @param string $str
     * @return string
     */
    public function escapeString($str) {
        if (self::$link) {
            return self::$link->escape_string($str);
        } else {
            return $str;
        }
    }

    /**
     * Devuelve el estilo de fecha del motor de base de datos.
     * @return string
     */
    public function dateStyle() {
        return 'Y-m-d';
    }

    /**
     * Devuelve el SQL necesario para convertir la columna a entero.
     * @param string $col_name
     * @return string
     */
    public function sql2int($col_name) {
        return 'CAST(' . $col_name . ' as UNSIGNED)';
    }

    /**
     * Compara dos arrays de columnas, devuelve una sentencia SQL en caso de encontrar diferencias.
     * @param string $tableName
     * @param array $xmlCols
     * @param array $dbCols
     * @return string
     */
    public function compareColumns($tableName, $xmlCols, $dbCols) {
        $sql = '';

        foreach ($xmlCols as $xml_col) {
            $encontrada = FALSE;
            if ($dbCols) {
                if (strtolower($xml_col['tipo']) == 'integer') {
                    /**
                     * Desde la pestaña avanzado el panel de control se puede cambiar
                     * el tipo de entero a usar en las columnas.
                     */
                    $xml_col['tipo'] = FS_DB_INTEGER;
                }

                foreach ($dbCols as $db_col) {
                    if ($db_col['name'] == $xml_col['nombre']) {
                        if (!$this->compareDataTypes($db_col['type'], $xml_col['tipo'])) {
                            $sql .= 'ALTER TABLE ' . $tableName . ' MODIFY `' . $xml_col['nombre'] . '` ' . $xml_col['tipo'] . ';';
                        }

                        if ($db_col['is_nullable'] != $xml_col['nulo']) {
                            if ($xml_col['nulo'] == 'YES') {
                                $sql .= 'ALTER TABLE ' . $tableName . ' MODIFY `' . $xml_col['nombre'] . '` ' . $xml_col['tipo'] . ' NULL;';
                            } else {
                                $sql .= 'ALTER TABLE ' . $tableName . ' MODIFY `' . $xml_col['nombre'] . '` ' . $xml_col['tipo'] . ' NOT NULL;';
                            }
                        }

                        if (!$this->compareDefaults($db_col['default'], $xml_col['defecto'])) {
                            if (is_null($xml_col['defecto'])) {
                                $sql .= 'ALTER TABLE ' . $tableName . ' ALTER `' . $xml_col['nombre'] . '` DROP DEFAULT;';
                            } else {
                                if (strtolower(substr($xml_col['defecto'], 0, 9)) == "nextval('") { /// nextval es para postgresql
                                    if ($db_col['extra'] != 'auto_increment') {
                                        $sql .= 'ALTER TABLE ' . $tableName . ' MODIFY `' . $xml_col['nombre'] . '` ' . $xml_col['tipo'];

                                        if ($xml_col['nulo'] == 'YES') {
                                            $sql .= ' NULL AUTO_INCREMENT;';
                                        } else {
                                            $sql .= ' NOT NULL AUTO_INCREMENT;';
                                        }
                                    }
                                } else {
                                    $sql .= 'ALTER TABLE ' . $tableName . ' ALTER `' . $xml_col['nombre'] . '` SET DEFAULT ' . $xml_col['defecto'] . ";";
                                }
                            }
                        }

                        $encontrada = TRUE;
                        break;
                    }
                }
            }
            if (!$encontrada) {
                $sql .= 'ALTER TABLE ' . $tableName . ' ADD `' . $xml_col['nombre'] . '` ';

                if ($xml_col['tipo'] == 'serial') {
                    $sql .= '`' . $xml_col['nombre'] . '` ' . FS_DB_INTEGER . ' NOT NULL AUTO_INCREMENT;';
                } else {
                    $sql .= $xml_col['tipo'];

                    if ($xml_col['nulo'] == 'NO') {
                        $sql .= " NOT NULL";
                    } else {
                        $sql .= " NULL";
                    }

                    if ($xml_col['defecto'] !== NULL) {
                        $sql .= " DEFAULT " . $xml_col['defecto'] . ";";
                    } else if ($xml_col['nulo'] == 'YES') {
                        $sql .= " DEFAULT NULL;";
                    } else {
                        $sql .= ';';
                    }
                }
            }
        }

        return $this->fixPostgresql($sql);
    }

    /**
     * Compara los tipos de datos de una columna. Devuelve TRUE si son iguales.
     * @param string $dbType
     * @param string $xmlType
     * @return boolean
     */
    private function compareDataTypes($dbType, $xmlType) {
        if (FS_CHECK_DB_TYPES != 1) {
            /// si está desactivada la comprobación de tipos, devolvemos que son iguales.
            return TRUE;
        } else if ($dbType == $xmlType) {
            return TRUE;
        } else if (strtolower($xmlType) == 'serial') {
            return TRUE;
        } else if ($dbType == 'tinyint(1)' && $xmlType == 'boolean') {
            return TRUE;
        } else if (substr($dbType, 0, 4) == 'int(' && $xmlType == 'INTEGER') {
            return TRUE;
        } else if (substr($dbType, 0, 6) == 'double' && $xmlType == 'double precision') {
            return TRUE;
        } else if (substr($dbType, 0, 4) == 'time' && substr($xmlType, 0, 4) == 'time') {
            return TRUE;
        } else if (substr($dbType, 0, 8) == 'varchar(' && substr($xmlType, 0, 18) == 'character varying(') {
            /// comprobamos las longitudes
            return (substr($dbType, 8, -1) == substr($xmlType, 18, -1));
        } else if (substr($dbType, 0, 5) == 'char(' && substr($xmlType, 0, 18) == 'character varying(') {
            /// comprobamos las longitudes
            return (substr($dbType, 5, -1) == substr($xmlType, 18, -1));
        } else {
            return FALSE;
        }
    }

    /**
     * Compara los tipos por defecto. Devuelve TRUE si son equivalentes.
     * @param string $dbDefault
     * @param string $xmlDefault
     * @return boolean
     */
    private function compareDefaults($dbDefault, $xmlDefault) {
        if ($dbDefault == $xmlDefault) {
            return TRUE;
        } else if (in_array($dbDefault, array('0', 'false', 'FALSE'))) {
            return in_array($xmlDefault, array('0', 'false', 'FALSE'));
        } else if (in_array($dbDefault, array('1', 'true', 'TRUE'))) {
            return in_array($xmlDefault, array('1', 'true', 'TRUE'));
        } else if ($dbDefault == '00:00:00' && $xmlDefault == 'now()') {
            return TRUE;
        } else if ($dbDefault == date('Y-m-d') . ' 00:00:00' && $xmlDefault == 'CURRENT_TIMESTAMP') {
            return TRUE;
        } else if ($dbDefault == 'CURRENT_DATE' && $xmlDefault == date("'Y-m-d'")) {
            return TRUE;
        } else if (substr($xmlDefault, 0, 8) == 'nextval(') {
            return TRUE;
        } else {
            $dbDefault = str_replace(array('::character varying', "'"), array('', ''), $dbDefault);
            $xmlDefault = str_replace(array('::character varying', "'"), array('', ''), $xmlDefault);
            return ($dbDefault == $xmlDefault);
        }
    }

    /**
     * Compara dos arrays de restricciones, devuelve una sentencia SQL en caso de encontrar diferencias.
     * @param string $tableName
     * @param array $xmlCons
     * @param array $dbCons
     * @param boolean $deleteOnly
     * @return string
     */
    public function compareConstraints($tableName, $xmlCons, $dbCons, $deleteOnly = FALSE) {
        $sql = '';

        if ($dbCons) {
            /**
             * comprobamos una a una las restricciones de la base de datos, si hay que eliminar una,
             * tendremos que eliminar todas para evitar problemas.
             */
            $delete = FALSE;
            foreach ($dbCons as $db_con) {
                $found = FALSE;
                if ($xmlCons) {
                    foreach ($xmlCons as $xml_con) {
                        if ($db_con['name'] == 'PRIMARY' OR $db_con['name'] == $xml_con['nombre']) {
                            $found = TRUE;
                            break;
                        }
                    }
                }

                if (!$found) {
                    $delete = TRUE;
                    break;
                }
            }

            /// eliminamos todas las restricciones
            if ($delete) {
                /// eliminamos antes las claves ajenas y luego los unique, evita problemas
                foreach ($dbCons as $db_con) {
                    if ($db_con['type'] == 'FOREIGN KEY') {
                        $sql .= 'ALTER TABLE ' . $tableName . ' DROP FOREIGN KEY ' . $db_con['name'] . ';';
                    }
                }

                foreach ($dbCons as $db_con) {
                    if ($db_con['type'] == 'UNIQUE') {
                        $sql .= 'ALTER TABLE ' . $tableName . ' DROP INDEX ' . $db_con['name'] . ';';
                    }
                }

                $dbCons = array();
            }
        }

        if (!empty($xmlCons) && !$deleteOnly && FS_FOREIGN_KEYS) {
            /// comprobamos una a una las nuevas
            foreach ($xmlCons as $xml_con) {
                $found = FALSE;
                if ($dbCons) {
                    foreach ($dbCons as $db_con) {
                        if ($xml_con['nombre'] == $db_con['name']) {
                            $found = TRUE;
                            break;
                        }
                    }
                }

                if (!$found) {
                    /// añadimos la restriccion
                    if (substr($xml_con['consulta'], 0, 11) == 'FOREIGN KEY') {
                        $sql .= 'ALTER TABLE ' . $tableName . ' ADD CONSTRAINT ' . $xml_con['nombre'] . ' ' . $xml_con['consulta'] . ';';
                    } else if (substr($xml_con['consulta'], 0, 6) == 'UNIQUE') {
                        $sql .= 'ALTER TABLE ' . $tableName . ' ADD CONSTRAINT ' . $xml_con['nombre'] . ' ' . $xml_con['consulta'] . ';';
                    }
                }
            }
        }

        return $this->fixPostgresql($sql);
    }

    /**
     * Devuelve la sentencia SQL necesaria para crear una tabla con la estructura proporcionada.
     * @param string $tableName
     * @param array $xmlCols
     * @param array $xmlCons
     * @return string
     */
    public function generateTable($tableName, $xmlCols, $xmlCons) {
        $sql = "CREATE TABLE " . $tableName . " ( ";

        $coma = FALSE;
        foreach ($xmlCols as $col) {
            /// añade la coma al final
            if ($coma) {
                $sql .= ", ";
            } else {
                $coma = TRUE;
            }

            if ($col['tipo'] == 'serial') {
                $sql .= '`' . $col['nombre'] . '` ' . FS_DB_INTEGER . ' NOT NULL AUTO_INCREMENT';
            } else {
                if (strtolower($col['tipo']) == 'integer') {
                    /**
                     * Desde la pestaña avanzado el panel de control se puede cambiar
                     * el tipo de entero a usar en las columnas.
                     */
                    $col['tipo'] = FS_DB_INTEGER;
                }

                $sql .= '`' . $col['nombre'] . '` ' . $col['tipo'];

                if ($col['nulo'] == 'NO') {
                    $sql .= " NOT NULL";
                } else {
                    /// es muy importante especificar que la columna permite NULL
                    $sql .= " NULL";
                }

                if ($col['defecto'] !== NULL) {
                    $sql .= " DEFAULT " . $col['defecto'];
                }
            }
        }

        return $this->fixPostgresql($sql) . ' ' . $this->generateTableConstraints($xmlCons) . ' ) '
                . 'ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;';
    }

    /**
     * Genera el SQL para establecer las restricciones proporcionadas.
     * @param array $xmlCons
     * @return string
     */
    private function generateTableConstraints($xmlCons) {
        $sql = '';

        if ($xmlCons) {
            foreach ($xmlCons as $res) {
                if (strstr(strtolower($res['consulta']), 'primary key')) {
                    $sql .= ', ' . $res['consulta'];
                } else if (FS_FOREIGN_KEYS OR substr($res['consulta'], 0, 11) != 'FOREIGN KEY') {
                    $sql .= ', CONSTRAINT ' . $res['nombre'] . ' ' . $res['consulta'];
                }
            }
        }

        return $this->fixPostgresql($sql);
    }

    /**
     * Realiza comprobaciones extra a la tabla.
     * @param string $tableName
     * @return boolean
     */
    public function checkTableAux($tableName) {
        $return = TRUE;

        /// ¿La tabla no usa InnoDB?
        $data = $this->select("SHOW TABLE STATUS FROM `" . FS_DB_NAME . "` LIKE '" . $tableName . "';");
        if ($data) {
            if ($data[0]['Engine'] != 'InnoDB') {
                if (!$this->exec("ALTER TABLE " . $tableName . " ENGINE=InnoDB;")) {
                    self::$miniLog->critical('Imposible convertir la tabla ' . $tableName . ' a InnoDB.'
                            . ' Imprescindible para FacturaScripts.');
                    $return = FALSE;
                }
            }
        }

        return $return;
    }

    /**
     * Elimina código problemático de postgresql.
     * @param string $sql
     * @return string
     */
    private function fixPostgresql($sql) {
        return str_replace(
                array('::character varying', 'without time zone', 'now()', 'CURRENT_TIMESTAMP', 'CURRENT_DATE'), array('', '', "'00:00'", "'" . date('Y-m-d') . " 00:00:00'", date("'Y-m-d'")), $sql
        );
    }

}
