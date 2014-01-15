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

class fs_mysql_x
{
   private $link;
   public $connected;
   public $history;
   public $errors;
   
   public function __construct()
   {
      $this->link = NULL;
      $this->connected = FALSE;
      $this->history = array();
      $this->errors = array();
   }
   
   public function __destruct()
   {
      $this->close();
   }
   
   /// conecta con la base de datos
   public function connect($server, $port, $user, $password, $dbname)
   {
      if($this->link)
      {
         $this->connected = TRUE;
      }
      else if( !function_exists('mysqli_connect') )
      {
         $this->errors[] = "No tienes instala la extensi&oacute;n de PHP para MySQL.";
         $this->connected = FALSE;
      }
      else
      {
         $this->link = mysqli_connect($server, $user, $password, $dbname, $port);
         
         if( mysqli_connect_error($this->link) )
         {
            $this->link = NULL;
            $this->connected = FALSE;
         }
         else
         {
            $this->connected = TRUE;
            mysqli_set_charset($this->link, 'utf8');
         }
      }
      
      return $this->connected;
   }
   
   /// desconecta de la base de datos
   public function close()
   {
      if($this->link)
      {
         $retorno = mysqli_close($this->link);
         $this->link = NULL;
         $this->connected = FALSE;
         return $retorno;
      }
      else
         return TRUE;
   }
   
   public function list_tables()
   {
      $aux = $this->select("SHOW TABLES;");
      if($aux)
      {
         $tables = array();
         foreach($aux as $a)
            $tables[] = array('name' => $a[0]);
         return $tables;
      }
      else
         return array();
   }
   
   public function get_columns($table)
   {
      $aux = $this->select("SHOW COLUMNS FROM ".$table.";");
      if($aux)
      {
         $columnas = array();
         foreach($aux as $a)
         {
            $columnas[] = array(
                'column_name' => $a['Field'],
                'data_type' => $a['Type'],
                'column_default' => $a['Default'],
                'is_nullable' => $a['Null']
            );
         }
         return $columnas;
      }
      else
         return array();
   }
   
   public function get_constraints($table)
   {
      $aux = $this->select("SELECT * FROM information_schema.table_constraints
         WHERE table_schema = schema() AND table_name = '".$table."';");
      if($aux)
      {
         $constraints = array();
         foreach($aux as $a)
         {
            $constraints[] = array(
                'restriccion' => $a['CONSTRAINT_NAME'],
                'tipo' => $a['CONSTRAINT_TYPE']
            );
         }
         return $constraints;
      }
      else
         return array();
   }
   
   public function get_indexes($table)
   {
      $aux = $this->select("SHOW INDEXES FROM ".$table.";");
      if($aux)
      {
         $indices = array();
         foreach($aux as $a)
            $indices[] = array('name' => $a['Key_name']);
         return $indices;
      }
      else
         return array();
   }
   
   public function get_locks()
   {
      return array();
   }
   
   public function version()
   {
      if($this->link)
         return 'MYSQL '.mysqli_get_server_version($this->link);
      else
         return FALSE;
   }
   
   /// ejecuta un select
   public function select($sql)
   {
      $resultado = FALSE;
      
      if($this->link)
      {
         $this->history[] = $sql;
         
         $filas = mysqli_query($this->link, $sql);
         if($filas)
         {
            $resultado = array();
            while($row = mysqli_fetch_array($filas))
               $resultado[] = $row;
            mysqli_free_result($filas);
         }
      }
      
      return $resultado;
   }
   
   public function select_limit($sql, $limit, $offset)
   {
      $resultado = FALSE;
      
      if($this->link)
      {
         $sql .= ' LIMIT ' . $limit . ' OFFSET ' . $offset . ';';
         $this->history[] = $sql;
         
         $filas = mysqli_query($this->link, $sql);
         if($filas)
         {
            $resultado = array();
            while($row = mysqli_fetch_array($filas))
               $resultado[] = $row;
            mysqli_free_result($filas);
         }
      }
      
      return $resultado;
   }
   
   /// ejecuta una consulta sobre la base de datos
   public function exec($sql)
   {
      $resultado = FALSE;
      
      if($this->link)
      {
         $this->history[] = $sql;
         
         /// desactivamos el autocommit
         mysqli_autocommit($this->link, FALSE);
         
         /// ejecutar multi-consulta
         $i = 0;
         if( mysqli_multi_query($this->link, $sql) )
         {
            do { $i++; } while ( mysqli_more_results($this->link) AND mysqli_next_result($this->link) );
         }
         
         if( mysqli_errno($this->link) )
            self::$errors[] =  'Error al ejecutar la consulta '.$i.': '.mysqli_error($this->link);
         else
            $resultado = TRUE;
         
         if($resultado)
            mysqli_commit($this->link);
         else
            mysqli_rollback($this->link);
         
         /// reactivamos el autocommit
         mysqli_autocommit($this->link, TRUE);
      }
      
      return $resultado;
   }
   
   public function last_error()
   {
      return mysqli_error($this->link);
   }
   
   public function lastval()
   {
      $aux = $this->select('SELECT LAST_INSERT_ID() as num;');
      if($aux)
         return $aux[0]['num'];
      else
         return FALSE;
   }
   
   public function escape_string($s)
   {
      return mysqli_escape_string(self::$link, $s);
   }
   
   public function date_style()
   {
      return 'Y-m-d';
   }
}

?>