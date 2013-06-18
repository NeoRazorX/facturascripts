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

require_once 'base/fs_db.php';

class fs_mysql extends fs_db
{
   private $last_error;
   
   public function __construct()
   {
      parent::__construct();
      $this->last_error = FALSE;
   }
   
   public function php_support(&$msg)
   {
      if( function_exists('mysqli_connect') )
         return TRUE;
      else
      {
         $msg = "No tienes instala la extensi&oacute;n de PHP para MySQL.";
         return FALSE;
      }
   }
   
   /// conecta con la base de datos
   public function connect()
   {
      if(self::$link)
         $connected = TRUE;
      else if( function_exists('mysqli_connect') )
      {
         self::$link = mysqli_connect(FS_DB_HOST, FS_DB_USER, FS_DB_PASS, FS_DB_NAME, FS_DB_PORT);
         if(self::$link)
            $connected = TRUE;
         else
            $connected = FALSE;
      }
      else
         $connected = FALSE;
      
      return $connected;
   }
   
   /// desconecta de la base de datos
   public function close()
   {
      if(self::$link)
      {
         $retorno = mysqli_close(self::$link);
         self::$link = NULL;
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
      if(self::$link)
         return 'MYSQL '.mysqli_get_server_version(self::$link);
      else
         return FALSE;
   }
   
   /// ejecuta un select
   public function select($sql)
   {
      $resultado = FALSE;
      
      if(self::$link)
      {
         $sql = str_replace('::character varying', '', $sql);
         $sql = str_replace('::integer', '', $sql);
         
         self::$history[] = $sql;
         $filas = mysqli_query(self::$link, $sql);
         if($filas)
         {
            $resultado = array();
            while($row = mysqli_fetch_array($filas))
               $resultado[] = $row;
            mysqli_free_result($filas);
         }
         self::$t_selects++;
      }
      
      return $resultado;
   }
   
   public function select_limit($sql, $limit, $offset)
   {
      $resultado = FALSE;
      
      if(self::$link)
      {
         $sql = str_replace('::character varying', '', $sql);
         $sql = str_replace('::integer', '', $sql);
         
         $sql .= ' LIMIT ' . $limit . ' OFFSET ' . $offset . ';';
         self::$history[] = $sql;
         $filas = mysqli_query(self::$link, $sql);
         if($filas)
         {
            $resultado = array();
            while($row = mysqli_fetch_array($filas))
               $resultado[] = $row;
            mysqli_free_result($filas);
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
         /*
          * MySQL no soporta time without time zone.
          * now() no funciona con time.
          * ::character varying es para PostgreSQL
          */
         $sql = str_replace('without time zone', '', $sql);
         $sql = str_replace('now()', "'00:00:00'", $sql);
         $sql = str_replace('CURRENT_TIMESTAMP', "'00:00:00'", $sql);
         $sql = str_replace('CURRENT_DATE', "'2013-01-01'", $sql);
         $sql = str_replace('::character varying', '', $sql);
         $sql = str_replace('::integer', '', $sql);
         
         self::$history[] = $sql;
         self::$t_transactions++;
         
         /// desactivamos el autocommit
         mysqli_autocommit(self::$link, FALSE);
         
         /// ejecutar multi consulta
         if( mysqli_multi_query(self::$link, $sql) )
         {
            $resultado = TRUE;
            
            do {
               
               $aux = mysqli_store_result(self::$link);
               if($aux)
                  mysqli_free_result($aux);
               
            } while ( mysqli_next_result(self::$link) );
         }
         
         if( mysqli_errno(self::$link) )
         {
            $this->last_error = mysqli_error(self::$link);
            $resultado = FALSE;
         }
         
         if($resultado)
            mysqli_commit(self::$link);
         else
            mysqli_rollback(self::$link);
         
         /// reactivamos el autocommit
         mysqli_autocommit(self::$link, TRUE);
      }
      
      return $resultado;
   }
   
   public function last_error()
   {
      $error = '';
      if($this->last_error)
      {
         $error = $this->last_error;
         $this->last_error = FALSE;
      }
      return $error."\n".mysqli_error(self::$link);
   }
   
   public function sequence_exists($seq)
   {
      return TRUE;
   }
   
   public function nextval($seq)
   {
      /*
       * Todo este código es para emular las secuencias de PostgreSQL.
       * El formato de $seq es tabla_columna_seq
       */
      $aux = explode('_', $seq);
      $tabla = '';
      $columna = '';
      
      /*
       * Pero el nombre de la tabla o el de la columna puede contener '_',
       * así que hay que comprobar directamente en el listado de tablas.
       */
      foreach($this->list_tables() as $t)
      {
         $encontrada = FALSE;
         for($i=0; $i<count($aux); $i++)
         {
            if($i == 0)
               $tabla = $aux[0];
            else
               $tabla .= '_' . $aux[$i];
            
            if($t['name'] == $tabla)
            {
               $columna = substr($seq, 1+strlen($tabla), -4);
               $encontrada = TRUE;
               break;
            }
         }
         if($encontrada)
            break;
      }
      
      $result = $this->select('SELECT COALESCE(1+MAX('.$columna.'),1) as num FROM '.$tabla.';');
      if($result)
         return $result[0]['num'];
      else
         return FALSE;
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
   
   /*
    * Compara dos arrays de columnas, devuelve una sentencia sql
    * en caso de encontrar diferencias.
    */
   public function compare_columns($table_name, $xml_cols, $columnas)
   {
      $consulta = '';
      
      foreach($xml_cols as $col)
      {
         $encontrada = FALSE;
         if($columnas)
         {
            foreach($columnas as $col2)
            {
               if($col2['column_name'] == $col['nombre'])
               {
                  if( !$this->compare_defaults($col2['column_default'], $col['defecto']) )
                  {
                     if( is_null($col['defecto']) )
                        $consulta .= 'ALTER TABLE '.$table_name.' ALTER `'.$col['nombre'].'` DROP DEFAULT;';
                     else
                     {
                        if( strtolower(substr($col['defecto'], 0, 9)) != "nextval('" ) /// nextval es para postgresql
                           $consulta .= 'ALTER TABLE '.$table_name.' ALTER `'.$col['nombre'].'` SET DEFAULT '.$col['defecto'].";";
                     }
                  }
                  
                  if($col2['is_nullable'] != $col['nulo'])
                  {
                     if($col['nulo'] == 'YES')
                        $consulta .= 'ALTER TABLE '.$table_name.' MODIFY `'.$col['nombre'].'` '.$col['tipo'].' NULL;';
                     else
                        $consulta .= 'ALTER TABLE '.$table_name.' MODIFY `'.$col['nombre'].'` '.$col['tipo'].' NOT NULL;';
                  }
                  
                  $encontrada = TRUE;
                  break;
               }
            }
         }
         if(!$encontrada)
         {
            $consulta .= 'ALTER TABLE '.$table_name.' ADD `'.$col['nombre'].'` ';
            
            if($col['tipo'] == 'serial')
               $consulta .= '`'.$col['nombre'].'` INT NOT NULL AUTO_INCREMENT;';
            else
            {
               $consulta .= $col['tipo'];
               
               if($col['nulo'] == 'NO')
                  $consulta .= " NOT NULL";
               else
                  $consulta .= " NULL";
               
               if($col['defecto'])
                  $consulta .= " DEFAULT ".$col['defecto'].";";
               else if($col['nulo'] == 'YES')
                  $consulta .= " DEFAULT NULL;";
            }
         }
      }
      
      return $consulta;
   }
   
   private function compare_defaults($v1, $v2)
   {
      if( in_array($v1, array('0', 'false', 'FALSE')) )
      {
         return in_array($v2, array('0', 'false', 'FALSE'));
      }
      else if( in_array($v1, array('1', 'true', 'true')) )
      {
         return in_array($v2, array('1', 'true', 'true'));
      }
      else
      {
         $v1 = str_replace('now()', "'00:00:00'", $v1);
         $v2 = str_replace('now()', "'00:00:00'", $v2);
         $v1 = str_replace('CURRENT_TIMESTAMP', "'00:00:00'", $v1);
         $v2 = str_replace('CURRENT_TIMESTAMP', "'00:00:00'", $v2);
         $v1 = str_replace('CURRENT_DATE', "'2013-01-01'", $v1);
         $v2 = str_replace('CURRENT_DATE', "'2013-01-01'", $v2);
         $v1 = str_replace('::character varying', '', $v1);
         $v2 = str_replace('::character varying', '', $v2);
         $v1 = str_replace("'", '', $v1);
         $v2 = str_replace("'", '', $v2);
         
         return($v1 == $v2);
      }
   }
   
   /*
    * Compara dos arrays de restricciones, devuelve una sentencia sql
    * en caso de encontrar diferencias.
    */
   public function compare_constraints($table_name, $c_nuevas, $c_old)
   {
      /*
       * He acabado hasta los cojones de MySQL.
       */
   }
   
   /// devuelve la sentencia sql necesaria para crear una tabla con la estructura proporcionada
   public function generate_table($table_name, $xml_columnas, $xml_restricciones)
   {
      $consulta = "CREATE TABLE ".$table_name." ( ";
      
      $i = FALSE;
      foreach($xml_columnas as $col)
      {
         /// añade la coma al final
         if($i)
            $consulta .= ", ";
         else
            $i = TRUE;
         
         if($col['tipo'] == 'serial')
            $consulta .= '`'.$col['nombre'].'` INT NOT NULL AUTO_INCREMENT';
         else
         {
            $consulta .= '`'.$col['nombre'].'` '.$col['tipo'];
            
            if($col['nulo'] == 'NO')
               $consulta .= " NOT NULL";
            else
               $consulta .= " NULL";
            
            if($col['defecto'])
               $consulta .= " DEFAULT ".$col['defecto'];
            else if($col['nulo'] == 'YES')
               $consulta .= " DEFAULT NULL";
         }
      }
      
      return $consulta.' '.$this->generate_table_constraints($xml_restricciones).' );';
   }
   
   private function generate_table_constraints($xml_restricciones)
   {
      $consulta = '';
      
      if($xml_restricciones)
      {
         foreach($xml_restricciones as $res)
         {
            if( strstr(strtolower($res['consulta']), 'primary key') )
               $consulta .= ', '.$res['consulta'];
            else if( strstr(strtolower($res['consulta']), 'unique') )
               $consulta .= ', CONSTRAINT '.$res['nombre'].' '.$res['consulta'];
            else if( strstr(strtolower($res['consulta']), 'foreign key') )
            {
               $consulta .= ', CONSTRAINT '.$res['nombre'].' '.
                    str_replace (' MATCH SIMPLE', '', $res['consulta']);
            }
         }
      }
      
      return $consulta;
   }
}

?>