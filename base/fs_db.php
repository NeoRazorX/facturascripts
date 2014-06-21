<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2014  Carlos Garcia Gomez  neorazorx@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 * 
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Esta es la clase de la que heredan las clases fs_mysql y fs_postgresql.
 */
abstract class fs_db
{
   /**
    * El enlace con la base de datos.
    * @var type 
    */
   protected static $link;
   protected static $t_selects;
   protected static $t_transactions;
   
   /**
    * Historial de consultas SQL.
    * @var type 
    */
   protected static $history;
   
   /**
    * Lista de errores.
    * @var type 
    */
   protected static $errors;
   
   public function __construct()
   {
      if( !isset(self::$link) )
      {
         self::$t_selects = 0;
         self::$t_transactions = 0;
         self::$history = array();
         self::$errors = array();
      }
   }
   
   /**
    * Devuelve el número de selects ejecutados
    * @return type
    */
   public function get_selects()
   {
      return self::$t_selects;
   }
   
   /**
    * Devuele le número de transacciones realizadas
    * @return type
    */
   public function get_transactions()
   {
      return self::$t_transactions;
   }
   
   public function get_history()
   {
      return self::$history;
   }
   
   public function get_errors()
   {
      return self::$errors;
   }
   
   /**
    * Conecta con la base de datos
    */
   abstract public function connect();
   
   public function connected()
   {
      if(self::$link)
         return TRUE;
      else
         return FALSE;
   }
   
   /**
    * Desconecta de la base de datos
    */
   abstract public function close();
   
   /**
    * Devuelve un array con los nombres de las tablas de la base de datos
    */
   abstract public function list_tables();
   
   /**
    * Devuelve TRUE si la tabla existe
    * @param type $name
    * @param type $list
    * @return boolean
    */
   public function table_exists($name, $list=FALSE)
   {
      $resultado = FALSE;
      
      if($list === FALSE)
         $list = $this->list_tables();
      
      foreach($list as $tabla)
      {
         if($tabla['name'] == $name)
         {
            $resultado = TRUE;
            break;
         }
      }
      
      return $resultado;
   }
   
   /**
    * Devuelve un array con las columnas de una tabla dada
    */
   abstract public function get_columns($table);
   
   /**
    * Devuelve una array con las restricciones de una tabla dada
    */
   abstract public function get_constraints($table);
   
   /**
    * Devuelve una array con los indices de una tabla dada
    */
   abstract public function get_indexes($table);
   
   /**
    * Devuelve un array con los datos de bloqueos
    */
   abstract public function get_locks();
   
   abstract public function version();
   
   /**
    * Ejecuta un select
    */
   abstract public function select($sql);
   
   /**
    * Ejecuta un select parcial
    */
   abstract public function select_limit($sql, $limit, $offset);
   
   /**
    * Ejecuta una consulta sobre la base de datos
    */
   abstract public function exec($sql);
   
   /**
    * Devuelve TRUE si existe la secuencia
    */
   abstract public function sequence_exists($seq);
   
   /**
    * Devuleve el siguiente valor de una secuencia
    */
   abstract public function nextval($seq);
   
   /**
    * Devuleve el último ID asignado
    */
   abstract public function lastval();
   
   abstract public function escape_string($s);
   
   abstract public function date_style();
   
   abstract public function sql_to_int($col);
   
   /**
    * Compara dos arrays de columnas, devuelve una sentencia sql
    * en caso de encontrar diferencias.
    */
   abstract public function compare_columns($table_name, $xml_cols, $columnas);
   
   /**
    * Compara dos arrays de restricciones, devuelve una sentencia sql
    * en caso de encontrar diferencias.
    */
   abstract public function compare_constraints($table_name, $c_nuevas, $c_old);
   
   /**
    * Devuelve la sentencia sql necesaria para crear una tabla con la estructura proporcionada
    */
   abstract public function generate_table($table_name, $xml_columnas, $xml_restricciones);
}
