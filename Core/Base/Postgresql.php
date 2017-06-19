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
 * Clase para conectar a PostgreSQL.
 * 
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class Postgresql {

    /**
     * El enlace con la base de datos.
     * @var resource
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
        } else if (function_exists('pg_connect')) {
            self::$link = pg_connect('host=' . FS_DB_HOST . ' dbname=' . FS_DB_NAME . ' port=' . FS_DB_PORT . ' user=' . FS_DB_USER . ' password=' . FS_DB_PASS);
            if (self::$link) {
                $connected = TRUE;

                /// establecemos el formato de fecha para la conexión
                pg_query(self::$link, "SET DATESTYLE TO ISO, DMY;");
            }
        } else {
            self::$miniLog->critical('No tienes instalada la extensión de PHP para PostgreSQL.');
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
            $return = pg_close(self::$link);
            self::$link = NULL;
            return $return;
        } else {
            return TRUE;
        }
    }

    /**
     * Devuelve el motor de base de datos y la versión.
     * @return boolean
     */
    public function version() {
        if (self::$link) {
            $aux = pg_version(self::$link);
            return 'POSTGRESQL ' . $aux['server'];
        } else {
            return FALSE;
        }
    }

    /**
     * Devuelve el número de selects ejecutados
     * @return integer
     */
    public function getTotalSelects() {
        return self::$totalSelects;
    }

    /**
     * Devuele le número de transacciones realizadas
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
        $sql = "SELECT column_name as name, data_type as type, character_maximum_length, column_default as default, is_nullable"
                . " FROM information_schema.columns WHERE table_catalog = '" . FS_DB_NAME
                . "' AND table_name = '" . $tableName . "' ORDER BY name ASC;";

        $aux = $this->select($sql);
        if ($aux) {
            foreach ($aux as $d) {
                $d['extra'] = NULL;

                /// añadimos la longitud, si tiene
                if ($d['character_maximum_length']) {
                    $d['type'] .= '(' . $d['character_maximum_length'] . ')';
                    unset($d['character_maximum_length']);
                }

                $columns[] = $d;
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
        $sql = "SELECT tc.constraint_name as name, tc.constraint_type as type"
                . " FROM information_schema.table_constraints AS tc"
                . " WHERE tc.table_name = '" . $tableName . "' AND tc.constraint_type IN"
                . " ('PRIMARY KEY','FOREIGN KEY','UNIQUE') ORDER BY type DESC, name ASC;";

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
        $sql = "SELECT tc.constraint_name as name,
            tc.constraint_type as type,
            kcu.column_name,
            ccu.table_name AS foreign_table_name,
            ccu.column_name AS foreign_column_name,
            rc.update_rule AS on_update,
            rc.delete_rule AS on_delete
         FROM information_schema.table_constraints AS tc
         LEFT JOIN information_schema.key_column_usage AS kcu
            ON kcu.constraint_schema = tc.constraint_schema
            AND kcu.constraint_catalog = tc.constraint_catalog
            AND kcu.constraint_name = tc.constraint_name
         LEFT JOIN information_schema.constraint_column_usage AS ccu
            ON ccu.constraint_schema = tc.constraint_schema
            AND ccu.constraint_catalog = tc.constraint_catalog
            AND ccu.constraint_name = tc.constraint_name
            AND ccu.column_name = kcu.column_name
         LEFT JOIN information_schema.referential_constraints rc
            ON rc.constraint_schema = tc.constraint_schema
            AND rc.constraint_catalog = tc.constraint_catalog
            AND rc.constraint_name = tc.constraint_name
         WHERE tc.table_name = '" . $tableName . "' AND tc.constraint_type IN ('PRIMARY KEY','FOREIGN KEY','UNIQUE')
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

        $aux = $this->select("SELECT indexname FROM pg_indexes WHERE tablename = '" . $tableName . "';");
        if ($aux) {
            foreach ($aux as $a) {
                $indexes[] = array('name' => $a['indexname']);
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
        $sql = "SELECT * FROM pg_catalog.pg_tables WHERE schemaname NOT IN "
                . "('pg_catalog','information_schema') ORDER BY tablename ASC;";

        $aux = $this->select($sql);
        if ($aux) {
            foreach ($aux as $a) {
                $tables[] = array('name' => $a['tablename']);
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

            $aux = pg_query(self::$link, $sql);
            if ($aux) {
                $result = pg_fetch_all($aux);
                pg_free_result($aux);
            } else {
                /// añadimos el error a la lista de errores
                self::$miniLog->error(pg_last_error(self::$link));
            }

            /// aumentamos el contador de selects realizados
            self::$totalSelects++;
        }

        return $result;
    }

    /**
     * Ejecuta una sentencia SQL de tipo select, pero con paginación,
     * y devuelve un array con los resultados o false en caso de fallo.
     * Limit es el número de elementos que quieres que devuelva.
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

            $aux = pg_query(self::$link, $sql);
            if ($aux) {
                $result = pg_fetch_all($aux);
                pg_free_result($aux);
            } else {
                /// añadimos el error a la lista de errores
                self::$miniLog->error(pg_last_error(self::$link));
            }

            /// aumentamos el contador de selects realizados
            self::$totalSelects++;
        }

        return $result;
    }

    /**
     * Ejecuta sentencias SQL sobre la base de datos (inserts, updates o deletes).
     * Para hacer selects, mejor usar select() o selecLimit().
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
                $this->begin_transaction();
            }

            $aux = pg_query(self::$link, $sql);
            if ($aux) {
                pg_free_result($aux);
                $result = TRUE;
            } else {
                self::$miniLog->error(pg_last_error(self::$link) . '. La secuencia ocupa la posición ' . count(self::$miniLog->read('sql')));
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
            pg_query(self::$link, 'BEGIN TRANSACTION;');
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

            return pg_query(self::$link, 'COMMIT;');
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
            pg_query(self::$link, 'ROLLBACK;');
        } else {
            return FALSE;
        }
    }

    /**
     * Devuelve TRUE si la secuancia solicitada existe.
     * @param string $seqName
     * @return boolean
     */
    private function sequenceExists($seqName) {
        return (bool) $this->select("SELECT * FROM pg_class where relname = '" . $seqName . "';");
    }

    /**
     * Devuleve el último ID asignado al hacer un INSERT en la base de datos.
     * @return integer|false
     */
    public function lastval() {
        $aux = $this->select('SELECT lastval() as num;');
        if ($aux) {
            return $aux[0]['num'];
        } else {
            return FALSE;
        }
    }

    /**
     * Escapa las comillas de la cadena de texto.
     * @param string $s
     * @return string
     */
    public function escapeString($s) {
        if (self::$link) {
            return pg_escape_string(self::$link, $s);
        } else {
            return $s;
        }
    }

    /**
     * Devuelve el estilo de fecha del motor de base de datos.
     * @return string
     */
    public function dateStyle() {
        return 'd-m-Y';
    }

    /**
     * Devuelve el SQL necesario para convertir la columna a entero.
     * @param string $colName
     * @return string
     */
    public function sql2int($colName) {
        return $colName . '::integer';
    }

    /**
     * Compara dos arrays de columnas, devuelve una sentencia SQL en caso de encontrar diferencias.
     * @param string $tableName
     * @param array $xml_cols
     * @param array $db_cols
     * @return string
     */
    public function compareColumns($tableName, $xml_cols, $db_cols) {
        $sql = '';

        foreach ($xml_cols as $xml_col) {
            $encontrada = FALSE;
            if ($db_cols) {
                foreach ($db_cols as $db_col) {
                    if ($db_col['name'] == $xml_col['nombre']) {
                        if (!$this->compare_data_types($db_col['type'], $xml_col['tipo'])) {
                            $sql .= 'ALTER TABLE ' . $tableName . ' ALTER COLUMN "' . $xml_col['nombre'] . '" TYPE ' . $xml_col['tipo'] . ';';
                        }

                        if ($db_col['default'] != $xml_col['defecto']) {
                            if (is_null($xml_col['defecto'])) {
                                $sql .= 'ALTER TABLE ' . $tableName . ' ALTER COLUMN "' . $xml_col['nombre'] . '" DROP DEFAULT;';
                            } else {
                                $this->default2check_sequence($tableName, $xml_col['defecto'], $xml_col['nombre']);
                                $sql .= 'ALTER TABLE ' . $tableName . ' ALTER COLUMN "' . $xml_col['nombre'] . '" SET DEFAULT ' . $xml_col['defecto'] . ';';
                            }
                        }

                        if ($db_col['is_nullable'] != $xml_col['nulo']) {
                            if ($xml_col['nulo'] == 'YES') {
                                $sql .= 'ALTER TABLE ' . $tableName . ' ALTER COLUMN "' . $xml_col['nombre'] . '" DROP NOT NULL;';
                            } else {
                                $sql .= 'ALTER TABLE ' . $tableName . ' ALTER COLUMN "' . $xml_col['nombre'] . '" SET NOT NULL;';
                            }
                        }

                        $encontrada = TRUE;
                        break;
                    }
                }
            }
            if (!$encontrada) {
                $sql .= 'ALTER TABLE ' . $tableName . ' ADD COLUMN "' . $xml_col['nombre'] . '" ' . $xml_col['tipo'];

                if ($xml_col['defecto'] !== NULL) {
                    $sql .= ' DEFAULT ' . $xml_col['defecto'];
                }

                if ($xml_col['nulo'] == 'NO') {
                    $sql .= ' NOT NULL';
                }

                $sql .= ';';
            }
        }

        return $sql;
    }

    /**
     * Compara los tipos de datos de una columna. Devuelve TRUE si son iguales.
     * @param string $db_type
     * @param string $xml_type
     * @return boolean
     */
    private function compareDataTypes($db_type, $xml_type) {
        if (FS_CHECK_DB_TYPES != 1) {
            /// si está desactivada la comprobación de tipos, devolvemos que son iguales.
            return TRUE;
        } else if ($db_type == $xml_type) {
            return TRUE;
        } else if (strtolower($xml_type) == 'serial') {
            return TRUE;
        } else if (substr($db_type, 0, 4) == 'time' AND substr($xml_type, 0, 4) == 'time') {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * A partir del campo default del xml de una tabla
     * comprueba si se refiere a una secuencia, y si es así
     * comprueba la existencia de la secuencia. Si no la encuentra
     * la crea.
     * @param string $tableName
     * @param string $default
     * @param string $colname
     */
    private function default2checkSequence($tableName, $default, $colname) {
        /// ¿Se refiere a una secuencia?
        if (strtolower(substr($default, 0, 9)) == "nextval('") {
            $aux = explode("'", $default);
            if (count($aux) == 3) {
                /// ¿Existe esa secuencia?
                if (!$this->sequence_exists($aux[1])) {
                    /// ¿En qué número debería empezar esta secuencia?
                    $num = 1;
                    $aux_num = $this->select("SELECT MAX(" . $colname . "::integer) as num FROM " . $tableName . ";");
                    if ($aux_num) {
                        $num += intval($aux_num[0]['num']);
                    }

                    $this->exec("CREATE SEQUENCE " . $aux[1] . " START " . $num . ";");
                }
            }
        }
    }

    /**
     * Compara dos arrays de restricciones, devuelve una sentencia SQL en caso de encontrar diferencias.
     * @param string $tableName
     * @param array $xml_cons
     * @param array $db_cons
     * @param boolean $delete_only
     * @return string
     */
    public function compareConstraints($tableName, $xml_cons, $db_cons, $delete_only = FALSE) {
        $sql = '';

        if ($db_cons) {
            /// comprobamos una a una las viejas
            foreach ($db_cons as $db_con) {
                $found = FALSE;
                if ($xml_cons) {
                    foreach ($xml_cons as $xml_con) {
                        if ($db_con['name'] == $xml_con['nombre']) {
                            $found = TRUE;
                            break;
                        }
                    }
                }

                if (!$found) {
                    /// eliminamos la restriccion
                    $sql .= "ALTER TABLE " . $tableName . " DROP CONSTRAINT " . $db_con['name'] . ";";
                }
            }
        }

        if ($xml_cons AND ! $delete_only) {
            /// comprobamos una a una las nuevas
            foreach ($xml_cons as $xml_con) {
                $found = FALSE;
                if ($db_cons) {
                    foreach ($db_cons as $db_con) {
                        if ($xml_con['nombre'] == $db_con['name']) {
                            $found = TRUE;
                            break;
                        }
                    }
                }

                if (!$found) {
                    /// añadimos la restriccion
                    $sql .= "ALTER TABLE " . $tableName . " ADD CONSTRAINT " . $xml_con['nombre'] . " " . $xml_con['consulta'] . ";";
                }
            }
        }

        return $sql;
    }

    /**
     * Devuelve la sentencia SQL necesaria para crear una tabla con la estructura proporcionada.
     * @param string $tableName
     * @param array $xml_cols
     * @param array $xml_cons
     * @return string
     */
    public function generateTable($tableName, $xml_cols, $xml_cons) {
        $sql = 'CREATE TABLE ' . $tableName . ' (';

        $i = FALSE;
        foreach ($xml_cols as $col) {
            /// añade la coma al final
            if ($i) {
                $sql .= ', ';
            } else {
                $i = TRUE;
            }

            $sql .= '"' . $col['nombre'] . '" ' . $col['tipo'];

            if ($col['nulo'] == 'NO') {
                $sql .= ' NOT NULL';
            }

            if ($col['defecto'] !== NULL AND ! in_array($col['tipo'], array('serial', 'bigserial'))) {
                $sql .= ' DEFAULT ' . $col['defecto'];
            }
        }

        return $sql . ' ); ' . $this->compare_constraints($tableName, $xml_cons, FALSE);
    }

    /**
     * Debería realizar comprobaciones extra, pero en PostgreSQL no es necesario.
     * @param string $tableName
     * @return boolean
     */
    public function checkTableAux($tableName) {
        return TRUE;
    }

}
