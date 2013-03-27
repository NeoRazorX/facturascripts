<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2013  Carlos Garcia Gomez  neorazorx@gmail.com
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

class fs_db
{
   private static $link;
   private static $t_selects;
   private static $t_transactions;
   private static $history;
   
   public function __construct()
   {
      if( !isset(self::$link) )
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
      if(self::$link)
         $connected = TRUE;
      else
      {
         self::$link = pg_connect('host='.FS_DB_HOST.' dbname='.FS_DB_NAME.
                 ' port='.FS_DB_PORT.' user='.FS_DB_USER.' password='.FS_DB_PASS);
         if(self::$link)
         {
            $connected = TRUE;
            
            /// establecemos el formato de fecha para la conexión
            pg_query(self::$link, "SET DATESTYLE TO ISO, DMY;");
         }
         else
            $connected = FALSE;
      }
      return $connected;
   }
   
   public function connected()
   {
      if(self::$link)
         return TRUE;
      else
         return FALSE;
   }
   
   /// desconecta de la base de datos
   public function close()
   {
      if(self::$link)
      {
         $retorno = pg_close(self::$link);
         self::$link = NULL;
         return $retorno;
      }
      return TRUE;
   }
   
   /// devuelve un array con los nombres de las tablas de la base de datos
   public function list_tables()
   {
      $sql = "SELECT a.relname AS Name FROM pg_class a, pg_user b
         WHERE ( relkind = 'r') and relname !~ '^pg_' AND relname !~ '^sql_'
          AND relname !~ '^xin[vx][0-9]+' AND b.usesysid = a.relowner
          AND NOT (EXISTS (SELECT viewname FROM pg_views WHERE viewname=a.relname))
         ORDER BY a.relname ASC;";
      $resultado = $this->select($sql);
      if($resultado)
         return $resultado;
      else
         return array();
   }
   
   /// devuelve TRUE si la tabla existe
   public function table_exists($name)
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
      return $resultado;
   }
   
   /// devuelve un array con las columnas de una tabla dada
   public function get_columns($table)
   {
      $sql = "SELECT column_name, data_type, character_maximum_length, column_default, is_nullable
         FROM information_schema.columns
         WHERE table_catalog = '".FS_DB_NAME."' AND table_name = '".$table."'
         ORDER BY column_name ASC;";
      return $this->select($sql);
   }
   
   /// devuelve una array con las restricciones de una tabla dada
   public function get_constraints($table)
   {
      $sql = "SELECT c.conname as \"restriccion\", c.contype as \"tipo\"
         FROM pg_class r, pg_constraint c
         WHERE r.oid = c.conrelid AND relname = '".$table."'
         ORDER BY restriccion ASC;";
      return $this->select($sql);
   }
   
   /// devuelve una array con los indices de una tabla dada
   public function get_indexes($table)
   {
      return $this->select("SELECT * FROM pg_indexes WHERE tablename = '".$table."';");
   }
   
   /// devuelve un array con los datos de bloqueos
   public function get_locks()
   {
      return $this->select("SELECT relname,pg_locks.* FROM pg_class,pg_locks
         WHERE relfilenode=relation AND NOT granted;");
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
      return $resultado;
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
      return $resultado;
   }
   
   /// ejecuta una consulta sobre la base de datos
   public function exec($sql)
   {
      $resultado = FALSE;
      if(self::$link)
      {
         self::$history[] = $sql;
         pg_query(self::$link, 'BEGIN TRANSACTION;');
         $aux = pg_query(self::$link, $sql);
         if($aux)
         {
            pg_free_result($aux);
            pg_query(self::$link, 'COMMIT;');
            $resultado = TRUE;
         }
         else
            pg_query(self::$link, 'ROLLBACK;');
         self::$t_transactions++;
      }
      return $resultado;
   }
   
   public function sequence_exists($seq)
   {
      return $this->select("SELECT * FROM pg_class where relname = '".$seq."';");
   }
   
   /// devuleve el siguiente valor de una secuencia
   public function nextval($seq)
   {
      $aux = $this->select("SELECT nextval('".$seq."') as num;");
      if($aux)
         return $aux[0]['num'];
      else
         return FALSE;
   }
   
   /// devuleve el último ID asignado
   public function lastval()
   {
      $aux = $this->select('SELECT lastval() as num;');
      if($aux)
         return $aux[0]['num'];
      else
         return FALSE;
   }
   
   public function escape_string($s)
   {
      return pg_escape_string(self::$link, $s);
   }
}

?>
