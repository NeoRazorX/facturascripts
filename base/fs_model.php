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

require_once 'base/fs_db.php';

abstract class fs_model
{
   protected $db;
   protected $table_name;
   protected static $checked_tables;
   public $error_msg;

   public function __construct($name = '')
   {
      $this->db = new fs_db();
      $this->table_name = $name;
      $this->error_msg = FALSE;
      
      if(!self::$checked_tables)
         self::$checked_tables = array();
      
      if($name != '')
      {
         if( !in_array($name, self::$checked_tables) )
         {
            $this->check_table($name);
            self::$checked_tables[] = $name;
         }
      }
   }
   
   protected function new_error_msg($msg)
   {
      if( !$this->error_msg )
         $this->error_msg = $msg;
      else
         $this->error_msg .= '<br/>' . $msg;
   }


   /*
    * Esta función es llamada al crear una tabla.
    * Permite insertar tuplas o lo que desees.
    */
   abstract protected function install();
   
   /*
    * Esta función devuelve TRUE si los datos del objeto se encuentran
    * en la base de datos.
    */
   abstract public function exists();

   /*
    * Esta función sirve tanto para insertar como para actualizar
    * los datos del objeto en la base de datos.
    */
   abstract public function save();
   
   /// Esta función sirve para eliminar los datos del objeto de la base de datos
   abstract public function delete();
   
   protected function var2str($v)
   {
      if( is_null($v) )
         return 'NULL';
      else if( is_bool($v) )
      {
         if($v)
            return 'TRUE';
         else
            return 'FALSE';
      }
      else
         return "'".$v."'";
   }

   /// obtiene las columnas y restricciones del fichero xml para una tabla
   private function get_xml_table(&$columnas, &$restricciones)
   {
      $retorno = TRUE;
      $xml = simplexml_load_file('table/' . $this->table_name . '.xml');
      if($xml)
      {
         if($xml->columna)
         {
            $i = 0;
            foreach($xml->columna as $col)
            {
               $columnas[$i]['nombre'] = $col->nombre;
               $columnas[$i]['tipo'] = $col->tipo;
               $columnas[$i]['nulo'] = $col->nulo;
               $columnas[$i]['defecto'] = $col->defecto;
               $i++;
            }
         }
         else /// debe de haber columnas, sino es un fallo
            $retorno = FALSE;
         
         if($xml->restriccion)
         {
            $i = 0;
            foreach($xml->restriccion as $col)
            {
               $restricciones[$i]['nombre'] = $col->nombre;
               $restricciones[$i]['consulta'] = $col->consulta;
               $i++;
            }
         }
      }
      else
         $retorno = FALSE;
      return($retorno);
   }
   
   /*
    * Compara las columnas de una tabla data, devuelve una sentencia sql
    * en caso de encontrar diferencias.
    */
   private function compare_columns($col, $columnas)
   {
      $consulta = "";
      $encontrada = FALSE;
      if($columnas)
      {
         foreach($columnas as $col2)
         {
            if($col2['column_name'] == $col['nombre'])
            {
               if($col['defecto'] == "")
                  $col['defecto'] = NULL;
               if($col2['column_default'] != $col['defecto'])
               {
                  if($col['defecto'] != NULL)
                     $consulta .= "ALTER TABLE " . $this->table_name . ' ALTER COLUMN "' . $col['nombre'] . '" SET DEFAULT ' . $col['defecto'] . ";";
               }
               if(($col2['is_nullable'] == "YES" AND $col['nulo'] == "NO") OR $col2['is_nullable'] == "NO" AND $col['nulo'] == "SI")
               {
                  if($col['nulo'] == "SI")
                     $consulta .= "ALTER TABLE " . $this->table_name . ' ALTER COLUMN "' . $col['nombre'] . '" DROP NOT NULL;';
                  else
                     $consulta .= "ALTER TABLE " . $this->table_name . ' ALTER COLUMN "' . $col['nombre'] . '" SET NOT NULL;';
               }
               $encontrada = TRUE;
               break;
            }
         }
      }
      if(!$encontrada)
      {
         $consulta .= "ALTER TABLE " . $this->table_name . ' ADD COLUMN "' . $col['nombre'] . '" ' . $col['tipo'];
         if($col['defecto'] != "")
            $consulta .= " DEFAULT " . $col['defecto'];
         if($col['nulo'] == "NO")
            $consulta .= " NOT NULL";
         $consulta .= ";\n";
      }
      return($consulta);
   }

   /*
    * Compara dos arrays de restricciones, devuelve una sentencia sql
    * en caso de encontrar diferencias.
    */
   private function compare_constraints($c_nuevas, $c_old)
   {
      $consulta = "";
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
                  $consulta .= "ALTER TABLE " . $this->table_name . " DROP CONSTRAINT " . $col['restriccion'] . ";\n";
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
                  $consulta .= "ALTER TABLE " . $this->table_name . " ADD CONSTRAINT " . $col['nombre'] . " " . $col['consulta'] . ";\n";
               }
            }
         }
         else
         {
            /// eliminamos todas las restricciones
            foreach($c_old as $col)
               $consulta .= "ALTER TABLE " . $this->table_name . " DROP CONSTRAINT " . $col['restriccion'] . ";\n";
         }
      }
      else if($c_nuevas)
      {
         /// añadimos todas las restricciones nuevas
         foreach($c_nuevas as $col)
            $consulta .= "ALTER TABLE " . $this->table_name . " ADD CONSTRAINT " . $col['nombre'] . " " . $col['consulta'] . ";\n";
      }
      return($consulta);
   }

   /// devuelve la sentencia sql necesaria para crear una tabla con la estructura proporcionada
   private function generate_table($xml_columnas, $xml_restricciones)
   {
      $consulta = "CREATE TABLE " . $this->table_name . " (\n";
      $i = FALSE;
      foreach($xml_columnas as $col)
      {
         /// añade la coma al final
         if($i)
            $consulta .= ",\n";
         else
            $i = TRUE;
         $consulta .= '"' . $col['nombre'] . '" ' . $col['tipo'];
         if($col['nulo'] == 'NO')
            $consulta .= " NOT NULL";
         if($col['defecto'] != "")
            $consulta .= " DEFAULT " . $col['defecto'];
      }
      $consulta .= " );\n" . $this->compare_constraints($xml_restricciones, FALSE);
      return($consulta);
   }
   
   /// comprueba y actualiza la estructura de la tabla si es necesario
   private function check_table()
   {
      $consulta = "";
      $columnas = FALSE;
      $restricciones = FALSE;
      $xml_columnas = FALSE;
      $xml_restricciones = FALSE;
      if( $this->get_xml_table($xml_columnas, $xml_restricciones) )
      {
         if( $this->db->table_exists($this->table_name) )
         {
            $columnas = $this->db->get_columns($this->table_name);
            $restricciones = $this->db->get_constraints($this->table_name);
            /// comparamos las columnas
            foreach($xml_columnas as $col)
               $consulta .= $this->compare_columns($col, $columnas);
            /// comparamos las restricciones
            $consulta .= $this->compare_constraints($xml_restricciones, $restricciones);
         }
         else
         {
            /// generamos el sql para crear la tabla
            $consulta .= $this->generate_table($xml_columnas, $xml_restricciones);
            $consulta .= $this->install();
         }
         if($consulta != '')
         {
            if( !$this->db->exec($consulta) )
               $this->new_error_msg("Error. " . $consulta);
         }
      }
      else
         $this->new_error_msg("Error con el xml");
   }
}

?>
