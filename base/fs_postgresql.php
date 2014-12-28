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

require_once 'base/fs_db.php';

/**
 * Clase para conectar a PostgreSQL
 */
class fs_postgresql extends fs_db
{
   /// conecta con la base de datos
   public function connect()
   {
      $connected = FALSE;
      
      if(self::$link)
      {
         $connected = TRUE;
      }
      else if( function_exists('pg_connect') )
      {
         self::$link = pg_connect('host='.FS_DB_HOST.' dbname='.FS_DB_NAME.
                 ' port='.FS_DB_PORT.' user='.FS_DB_USER.' password='.FS_DB_PASS);
         if(self::$link)
         {
            $connected = TRUE;
            
            /// establecemos el formato de fecha para la conexión
            pg_query(self::$link, "SET DATESTYLE TO ISO, DMY;");
         }
      }
      else
         self::$errors[] = 'No tienes instalada la extensión de PHP para PostgreSQL.';
      
      return $connected;
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
      else
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
      return $this->select("SELECT indexname as name FROM pg_indexes
         WHERE tablename = '".$table."';");
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
      {
         $aux = pg_version(self::$link);
         return 'POSTGRESQL '.$aux['server'];
      }
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
         else
            self::$errors[] = pg_last_error(self::$link);
         
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
         else
            self::$errors[] = pg_last_error(self::$link);
         
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
         {
            self::$errors[] = pg_last_error(self::$link);
            pg_query(self::$link, 'ROLLBACK;');
         }
         
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
   
   public function date_style()
   {
      return 'd-m-Y';
   }
   
   public function sql_to_int($col)
   {
      return $col.'::integer';
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
                  if( $this->compare_data_types($col2['data_type'], $col['tipo']) )
                  {
                     $consulta .= 'ALTER TABLE '.$table_name.' ALTER COLUMN "'.$col['nombre'].'" TYPE '.$col['tipo'].';';
                  }
                  
                  if($col2['column_default'] != $col['defecto'])
                  {
                     if( is_null($col['defecto']) )
                     {
                        $consulta .= 'ALTER TABLE '.$table_name.' ALTER COLUMN "'.$col['nombre'].'" DROP DEFAULT;';
                     }
                     else
                     {
                        $this->default2check_sequence($table_name, $col['defecto'], $col['nombre']);
                        $consulta .= 'ALTER TABLE '.$table_name.' ALTER COLUMN "'.$col['nombre'].'" SET DEFAULT '.$col['defecto'].';';
                     }
                  }
                  
                  if($col2['is_nullable'] != $col['nulo'])
                  {
                     if($col['nulo'] == 'YES')
                     {
                        $consulta .= 'ALTER TABLE '.$table_name.' ALTER COLUMN "'.$col['nombre'].'" DROP NOT NULL;';
                     }
                     else
                        $consulta .= 'ALTER TABLE '.$table_name.' ALTER COLUMN "'.$col['nombre'].'" SET NOT NULL;';
                  }
                  
                  $encontrada = TRUE;
                  break;
               }
            }
         }
         if(!$encontrada)
         {
            $consulta .= 'ALTER TABLE '.$table_name.' ADD COLUMN "'.$col['nombre'].'" '.$col['tipo'];
            
            if($col['defecto'])
            {
               $consulta .= ' DEFAULT '.$col['defecto'];
            }
            
            if($col['nulo'] == 'NO')
            {
               $consulta .= ' NOT NULL';
            }
            
            $consulta .= ';';
         }
      }
      
      return $consulta;
   }
   
   private function compare_data_types($v1, $v2)
   {
      if( strtolower($v2) == 'serial')
      {
         return FALSE;
      }
      else if( $v1 == substr($v2, 0, strlen($v1)) )
      {
         return FALSE;
      }
      else if( substr($v1, 0, 4) == 'time' AND substr($v2, 0, 4) == 'time' )
      {
         return FALSE;
      }
      else if($v1 != $v2)
      {
         return TRUE;
      }
   }
   
   /*
    * A partir del campo default del xml de una tabla
    * comprueba si se refiere a una secuencia, y si es así
    * comprueba la existencia de la secuencia. Si no la encuentra
    * la crea.
    */
   private function default2check_sequence($table_name, $default, $colname)
   {
      /// ¿Se refiere a una secuencia?
      if( strtolower(substr($default, 0, 9)) == "nextval('" )
      {
         $aux = explode("'", $default);
         if( count($aux) == 3 )
         {
            /// ¿Existe esa secuencia?
            if( !$this->sequence_exists($aux[1]) )
            {
               /// ¿En qué número debería empezar esta secuencia?
               $num = 1;
               $aux_num = $this->select("SELECT MAX(".$colname."::integer) as num FROM ".$table_name.";");
               if($aux_num)
                  $num += intval($aux_num[0]['num']);
               $this->exec("CREATE SEQUENCE ".$aux[1]." START ".$num.";");
            }
         }
      }
   }
   
   /*
    * Compara dos arrays de restricciones, devuelve una sentencia sql
    * en caso de encontrar diferencias.
    */
   public function compare_constraints($table_name, $c_nuevas, $c_old)
   {
      $consulta = '';
      
      if($c_old)
      {
         if($c_nuevas)
         {
            /// comprobamos una a una las viejas
            foreach($c_old as $col)
            {
               $encontrado = FALSE;
               foreach($c_nuevas as $col2)
               {
                  if($col['restriccion'] == $col2['nombre'])
                  {
                     $encontrado = TRUE;
                     break;
                  }
               }
               if(!$encontrado)
               {
                  /// eliminamos la restriccion
                  $consulta .= "ALTER TABLE ".$table_name." DROP CONSTRAINT ".$col['restriccion'].";";
               }
            }
            
            /// comprobamos una a una las nuevas
            foreach($c_nuevas as $col)
            {
               $encontrado = FALSE;
               foreach($c_old as $col2)
               {
                  if($col['nombre'] == $col2['restriccion'])
                  {
                     $encontrado = TRUE;
                     break;
                  }
               }
               if(!$encontrado)
               {
                  /// añadimos la restriccion
                  $consulta .= "ALTER TABLE ".$table_name." ADD CONSTRAINT ".$col['nombre']." ".$col['consulta'].";";
               }
            }
         }
         else
         {
            /// eliminamos todas las restricciones
            foreach($c_old as $col)
               $consulta .= "ALTER TABLE ".$table_name." DROP CONSTRAINT ".$col['restriccion'].";";
         }
      }
      else if($c_nuevas)
      {
         /// añadimos todas las restricciones nuevas
         foreach($c_nuevas as $col)
            $consulta .= "ALTER TABLE ".$table_name." ADD CONSTRAINT ".$col['nombre']." ".$col['consulta'].";";
      }
      
      return $consulta;
   }
   
   /// devuelve la sentencia sql necesaria para crear una tabla con la estructura proporcionada
   public function generate_table($table_name, $xml_columnas, $xml_restricciones)
   {
      $consulta = 'CREATE TABLE '.$table_name.' (';
      
      $i = FALSE;
      foreach($xml_columnas as $col)
      {
         /// añade la coma al final
         if($i)
            $consulta .= ', ';
         else
            $i = TRUE;
         
         $consulta .= '"'.$col['nombre'].'" '.$col['tipo'];
         
         if($col['nulo'] == 'NO')
            $consulta .= ' NOT NULL';
         
         if($col['defecto'] AND !in_array($col['tipo'], array('serial', 'bigserial')))
            $consulta .= ' DEFAULT '.$col['defecto'];
      }
      
      return $consulta.' ); '.$this->compare_constraints($table_name, $xml_restricciones, FALSE);
   }
}

?>