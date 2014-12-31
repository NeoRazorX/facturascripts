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
 * Clase para conectar a MySQL
 */
class fs_mysql extends fs_db
{
   /// conecta con la base de datos
   public function connect()
   {
      $connected = FALSE;
      
      if(self::$link)
      {
         $connected = TRUE;
      }
      else if( class_exists('mysqli') )
      {
         self::$link = new mysqli(FS_DB_HOST, FS_DB_USER, FS_DB_PASS, FS_DB_NAME, intval(FS_DB_PORT) );
         
         if(self::$link->connect_error)
         {
            self::$errors[] = self::$link->connect_error;
            self::$link = NULL;
         }
         else
         {
            self::$link->set_charset('utf8');
            $connected = TRUE;
            
            /// comprobamos el soporte para InnoDB
            $data = $this->select("SHOW TABLE STATUS WHERE Name = 'fs_pages';");
            if($data)
            {
               if($data[0]['Engine'] != 'InnoDB')
                  self::$errors[] = 'FacturaScripts necesita usar el motor InnoDB en MySQL, y tú estás usando el motor '.$data[0]['Engine'].'.';
            }
         }
      }
      else
      {
         self::$errors[] = 'No tienes instalada la extensión de PHP para MySQL.';
      }
      
      return $connected;
   }
   
   /// desconecta de la base de datos
   public function close()
   {
      if(self::$link)
      {
         $retorno = self::$link->close();
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
            $tables[] = array('name' => $a['Tables_in_'.FS_DB_NAME]);
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
               'is_nullable' => $a['Null'],
               'extra' => $a['Extra']
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
         return 'MYSQL '.self::$link->server_version;
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
         self::$history[] = $sql;
         
         $filas = self::$link->query($sql);
         if($filas)
         {
            $resultado = array();
            while( $row = $filas->fetch_array(MYSQLI_ASSOC) )
               $resultado[] = $row;
            $filas->free();;
         }
         else
            self::$errors[] = self::$link->error;
         
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
         $sql .= ' LIMIT ' . $limit . ' OFFSET ' . $offset . ';';
         self::$history[] = $sql;
         
         $filas = self::$link->query($sql);
         if($filas)
         {
            $resultado = array();
            while($row = $filas->fetch_array(MYSQLI_ASSOC) )
               $resultado[] = $row;
            $filas->free();
         }
         else
            self::$errors[] = self::$link->error;
         
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
         $sql = str_replace('CURRENT_DATE', "'".Date('Y-m-d')."'", $sql);
         $sql = str_replace('::character varying', '', $sql);
         self::$history[] = $sql;
         self::$t_transactions++;
         
         /// desactivamos el autocommit
         self::$link->autocommit(FALSE);
         
         $i = 0;
         if( self::$link->multi_query($sql) )
         {
            do { $i++; } while ( self::$link->more_results() AND self::$link->next_result() );
         }
         
         if( self::$link->errno )
            self::$errors[] =  'Error al ejecutar la consulta '.$i.': '.self::$link->error;
         else
            $resultado = TRUE;
         
         if($resultado)
            self::$link->commit();
         else
            self::$link->rollback();
         
         /// reactivamos el autocommit
         self::$link->autocommit(TRUE);
      }
      
      return $resultado;
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
      return self::$link->escape_string($s);
   }
   
   public function date_style()
   {
      return 'Y-m-d';
   }
   
   public function sql_to_int($col)
   {
      return 'CAST('.$col.' as UNSIGNED)';
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
                     $consulta .= 'ALTER TABLE '.$table_name.' MODIFY `'.$col['nombre'].'` '.$col['tipo'].';';
                  }
                  
                  if( !$this->compare_defaults($col2['column_default'], $col['defecto']) )
                  {
                     if( is_null($col['defecto']) )
                     {
                        $consulta .= 'ALTER TABLE '.$table_name.' ALTER `'.$col['nombre'].'` DROP DEFAULT;';
                     }
                     else
                     {
                        if( strtolower(substr($col['defecto'], 0, 9)) == "nextval('" ) /// nextval es para postgresql
                        {
                           if($col2['extra'] != 'auto_increment')
                           {
                              $consulta .= 'ALTER TABLE '.$table_name.' MODIFY `'.$col2['column_name'].'` '.$col2['data_type'];
                              
                              if($col2['is_nullable'] == 'YES')
                              {
                                 $consulta .= ' NULL AUTO_INCREMENT;';
                              }
                              else
                                 $consulta .= ' NOT NULL AUTO_INCREMENT;';
                           }
                        }
                        else
                           $consulta .= 'ALTER TABLE '.$table_name.' ALTER `'.$col['nombre'].'` SET DEFAULT '.$col['defecto'].";";
                     }
                  }
                  
                  if($col2['is_nullable'] != $col['nulo'])
                  {
                     if($col['nulo'] == 'YES')
                     {
                        $consulta .= 'ALTER TABLE '.$table_name.' MODIFY `'.$col['nombre'].'` '.$col['tipo'].' NULL;';
                     }
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
            {
               $consulta .= '`'.$col['nombre'].'` INT NOT NULL AUTO_INCREMENT;';
            }
            else
            {
               $consulta .= $col['tipo'];
               
               if($col['nulo'] == 'NO')
               {
                  $consulta .= " NOT NULL";
               }
               else
                  $consulta .= " NULL";
               
               if($col['defecto'])
               {
                  $consulta .= " DEFAULT ".$col['defecto'].";";
               }
               else if($col['nulo'] == 'YES')
               {
                  $consulta .= " DEFAULT NULL;";
               }
               else
                  $consulta .= ';';
            }
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
      else if( substr($v1, 0, 7) == 'varchar' AND substr($v2, 0, 17) == 'character varying' )
      {
         return FALSE;
      }
      else if($v1 == 'tinyint(1)' AND $v2 == 'boolean')
      {
         return FALSE;
      }
      else if( substr($v1, 0, 3) == 'int' AND $v2 == 'integer')
      {
         return FALSE;
      }
      else if( substr($v1, 0, 6) == 'double' AND $v2 == 'double precision')
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
         $v1 = str_replace('CURRENT_DATE', "'".Date('Y-m-d')."'", $v1);
         $v2 = str_replace('CURRENT_DATE', "'".Date('Y-m-d')."'", $v2);
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
                  if($col['tipo'] == 'FOREIGN KEY')
                     $consulta .= 'ALTER TABLE '.$table_name.' DROP FOREIGN KEY '.$col['restriccion'].';';
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
               
               /// añadimos la restriccion
               if( !$encontrado AND substr($col['consulta'], 0, 11) == 'FOREIGN KEY' )
                  $consulta .= 'ALTER TABLE '.$table_name.' ADD CONSTRAINT '.$col['nombre'].' '.$col['consulta'].';';
            }
         }
      }
      else if($c_nuevas)
      {
         /// añadimos todas las restricciones nuevas
         foreach($c_nuevas as $col)
         {
            if( substr($col['consulta'], 0, 11) == 'FOREIGN KEY' )
               $consulta .= 'ALTER TABLE '.$table_name.' ADD CONSTRAINT '.$col['nombre'].' '.$col['consulta'].';';
         }
      }
      
      return $consulta;
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
      
      return $consulta.' '.$this->generate_table_constraints($xml_restricciones).' )
         ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;';
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
            else
               $consulta .= ', CONSTRAINT '.$res['nombre'].' '.$res['consulta'];
         }
      }
      
      return $consulta;
   }
}
