<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2012  Carlos Garcia Gomez  neorazorx@gmail.com
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

require_once 'config.php';

class fs_db
{
   private static $link;
   private static $t_selects;
   private static $t_transactions;
   private static $history;
   
   public function __construct()
   {
      if(!self::$link)
      {
         self::$t_selects = 0;
         self::$t_transactions = 0;
         self::$history = array();
      }
   }
   
   /// devuelve el número de selects ejecutados
   public function get_selects()
   {
      return self::$t_selects;
   }
   
   /// devuele le número de transacciones realizadas
   public function get_transactions()
   {
      return self::$t_transactions;
   }
   
   public function get_history()
   {
      return self::$history;
   }

   /// conecta con la base de datos
   public function connect()
   {
      $connected = FALSE;
      if(!self::$link)
      {
         self::$link = pg_pconnect('host='.FS_DB_HOST.' dbname='.FS_DB_NAME.' port='.FS_DB_PORT.' user='.FS_DB_USER.' password='.FS_DB_PASS);
         if(self::$link)
            $connected = TRUE;
      }
      return $connected;
   }
   
   /// desconecta de la base de datos
   public function close()
   {
      $retorno = FALSE;
      if(self::$link)
      {
         $retorno = pg_close(self::$link);
         self::$link = NULL;
      }
      return $retorno;
   }
   
   /// devuelve un array con los nombres de las tablas de la base de datos
   public function list_tables()
   {
      $resultado = array();
      if(self::$link)
      {
         $sql = "SELECT a.relname AS Name FROM pg_class a, pg_user b WHERE ( relkind = 'r') and relname !~ '^pg_' AND relname !~ '^sql_'
                 AND relname !~ '^xin[vx][0-9]+' AND b.usesysid = a.relowner AND NOT (EXISTS (SELECT viewname FROM pg_views WHERE viewname=a.relname))
                 ORDER BY a.relname ASC;";
         self::$history[] = $sql;
         $filas = pg_query(self::$link, $sql);
         if($filas)
         {
            $resultado = pg_fetch_all($filas);
            pg_free_result($filas);
         }
         self::$t_selects++;
      }
      return($resultado);
   }
   
   /// devuelve TRUE si la tabla existe
   public function table_exists($name='')
   {
      $resultado = FALSE;
      foreach($this->list_tables() as $tabla)
      {
         if($tabla['name'] == $name)
         {
            $resultado = TRUE;
            break;
         }
      }
      return($resultado);
   }
   
   /// devuelve un array con las columnas de una tabla dada
   public function get_columns($table='')
   {
      if($table != '')
      {
         $sql = "SELECT column_name, data_type, character_maximum_length, column_default, is_nullable FROM information_schema.columns
                 WHERE table_catalog = '".FS_DB_NAME."' AND table_name = '".$table."';";
         return $this->select($sql);
      }
      else
         return FALSE;
   }
   
   /// devuelve una array con las restricciones de una tabla dada
   public function get_constraints($table='')
   {
      if($table != '')
      {
         $sql = "SELECT c.conname as \"restriccion\" FROM pg_class r, pg_constraint c
                 WHERE r.oid = c.conrelid AND relname = '".$table."';";
         return $this->select($sql);
      }
      else
         return FALSE;
   }
   
   /// devuelve una array con los indices de una tabla dada
   public function get_indexes($table='')
   {
      if($table != '')
      {
         $sql = "SELECT * FROM pg_indexes WHERE tablename = '".$tabla."';";
         return $this->select($sql);
      }
      else
         return FALSE;
   }
   
   public function version()
   {
      if(self::$link)
         return pg_version(self::$link);
      else
         return FALSE;
   }
   
   /// ejecuta un select
   public function select($sql)
   {
      $resultado = FALSE;
      if(self::$link)
      {
         self::$history[] = $sql;
         $filas = pg_query(self::$link, $sql);
         if($filas)
         {
            $resultado = pg_fetch_all($filas);
            pg_free_result($filas);
         }
         self::$t_selects++;
      }
      return($resultado);
   }
   
   /// ejecuta un select parcial
   public function select_limit($sql, $limit, $offset)
   {
      $resultado = FALSE;
      if(self::$link)
      {
         $sql .= ' LIMIT ' . $limit . ' OFFSET ' . $offset . ';';
         self::$history[] = $sql;
         $filas = pg_query(self::$link, $sql);
         if($filas)
         {
            $resultado = pg_fetch_all($filas);
            pg_free_result($filas);
         }
         self::$t_selects++;
      }
      return($resultado);
   }
   
   /// devuelve el número de tuplas de una consulta
   function num_rows($sql)
   {
      $total = 0;
      if(self::$link)
      {
         self::$history[] = $sql;
         $filas = pg_query(self::$link, $sql);
         if($filas)
         {
            $total = pg_num_rows($filas);
            pg_free_result($filas);
         }
         self::$t_selects++;
      }
      return($total);
   }

   /// ejecuta una consulta sobre la base de datos
   public function exec($sql)
   {
      $resultado = FALSE;
      if(self::$link)
      {
         self::$history[] = $sql;
         pg_query(self::$link, 'BEGIN TRANSACTION;');
         $resultado = pg_query(self::$link, $sql);
         if($resultado)
            pg_query(self::$link, 'COMMIT;');
         else
            pg_query(self::$link, 'ROLLBACK;');
         self::$t_transactions++;
      }
      return($resultado);
   }
}

?>
