<?php
/*
   Plugin Fabricantes para FacturaSctipts
   (c) 2014 JHircano@gmail.com
   -----------------------------------------------------------------------------------------

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

require_once 'validar.php';

class fabricante extends fs_model
{
   public $codfabricante;
   public $nombre;
   public $descripcion;
   public $valoracion;
   public $fecha_alta;
   public $activo;
   
   public function __construct($g = FALSE)
   {
      parent::__construct('fabricantes', 'plugins/fabricantes/');
      
      if($g)
      {
         $this->codfabricante = $g['codfabricante'];
         $this->nombre = $g['nombre'];
         $this->descripcion = $g['descripcion'];
         $this->valoracion = $g['valoracion'];
         
         $this->fecha_alta = NULL;
         if( !is_null($g['fecha_alta']) )
            $this->fecha_alta = date('d-m-Y', strtotime($g['fecha_alta']));

         $this->activo = ( intval($g['activo']) > 0 ? 1 : 0 );
      }
      else
      {
         $this->codfabricante = NULL;
         $this->nombre = '';
         $this->descripcion = '';
         $this->valoracion = 0;
         $this->fecha_alta = NULL;
         $this->activo = 0;
      }
   }
   
   public static function loadObject( $cod = 0) {

      $md = new fabricante();
      $f = $md->db->select("SELECT * FROM ".$md->table_name." WHERE codfabricante = ".$md->var2str($cod).";");
      if($f)
         return new fabricante($f[0]);
      else
         return new fabricante();
   }
   
   protected function install() {
      ;
   }
   
   public function exists()
   {
      if( is_null($this->codfabricante) )
      {
         return FALSE;
      }
      else
      {
         return $this->db->select("select * from fabricantes where codfabricante = ".$this->var2str($this->codfabricante).";");
      }
   }
   
   public function test() {
      $status = FALSE;

         /*
         $this->codfabricante = NULL; int(10)
         $this->nombre = ''; varchar(64)
         $this->descripcion = ''; text
         $this->valoracion = 0; int(1)
         $this->fecha_alta = NULL; datetime
         $this->activo = 0; int(1)
         */
      
      if( !(validar::isUnsignedInt( $this->codfabricante ) ) )
         $this->new_error_msg("Código de fabricante no válido. Deben ser un número entero mayor que cero.");

      else if( !(validar::isCatalogName( $this->nombre ) AND ( strlen( $this->nombre ) <= 64 ) ) )
         $this->new_error_msg("El nombre es demasiado corto, demasiado largo, o contiene caracteres no válidos: <>;=#{} .");

      else if( !validar::isCleanHtml( strtolower( $this->descripcion ) ) )
         $this->new_error_msg("La descripción no es válida. No se permite HTML / JavaScript.");

      else if( !(validar::isUnsignedInt( $this->valoracion ) AND ( $this->valoracion >= 0 ) AND ( $this->valoracion <= 5 ) ) )
         $this->new_error_msg("La valoración del fabricante debe ser un entero entre 0 y 5.");

      else if( !(validar::isDate( $this->fecha_alta ) OR is_null( $this->fecha_alta ) ) )
         $this->new_error_msg("El formato de fecha es incorrecto.");

      else if( !validar::isBool( $this->activo ) )
         $this->new_error_msg("El estado debe ser activo / no activo.");

      else
         $status = TRUE;
      
      return $status;
   }
   
   public function nuevo_numero()
   {
      $data = $this->db->select("select max(codfabricante) as num from fabricantes;");
      if($data)
         return intval($data[0]['num']) + 1;
      else
         return 1;
   }
   
   public function save()
   {
      if( $this->test() )
      {
            if( $this->exists() )
            {
               $sql = "UPDATE fabricantes set 
                        nombre = ".$this->var2str($this->nombre).", 
                        descripcion = ".$this->var2str($this->descripcion).", 
                        valoracion = ".$this->var2str($this->valoracion).", 
                        fecha_alta = ".$this->var2str($this->fecha_alta).", 
                        activo = ".$this->var2str($this->activo)." 
                        where codfabricante = ".$this->var2str($this->codfabricante).";";
            }
            else
            {
               $sql = "INSERT into fabricantes (codfabricante,nombre,descripcion,valoracion,fecha_alta, activo) VALUES (".
                        $this->var2str($this->codfabricante).",".
                        $this->var2str($this->nombre).",".
                        $this->var2str($this->descripcion).",".
                        $this->var2str($this->valoracion).",".
                        $this->var2str($this->fecha_alta).",".
                        $this->var2str($this->activo).");";
            }
            
            return $this->db->exec($sql);
      }
      else
         return FALSE;
   }
   
   public function delete()
   {
      return $this->db->exec("delete from fabricantes where codfabricante = ".$this->var2str($this->codfabricante).";");
   }
   
   public function listar()
   {
      $listag = array();
      
      $data = $this->db->select("select * from fabricantes;");
      if($data)
      {
         foreach($data as $d)
         {
            $listag[] = new fabricante($d);
         }
      }
      
      return $listag;
   }
   
   public function buscar($query = '')
   {
      $listag = array();
      $query = strtolower($query);
      if( !validar::isValidSearch( $query ) ) 
      {
         $this->new_error_msg("Los términos de búsqueda son incorrectos.");
         return $listag;
      }
      
      $data = $this->db->select("select * from fabricantes 
                  where lower(nombre) like ".$this->var2str('%'.$query.'%')." 
                  or lower(descripcion) like ".$this->var2str('%'.$query.'%').";");
      if($data)
      {
         foreach($data as $d)
         {
            $listag[] = new fabricante($d);
         }
      }
      
      return $listag;
   }
   
   
   public function url()
   {
      if( is_null($this->codfabricante) )
         return "index.php?page=fabricantes";
      else
         return "index.php?page=fabricante&cod=".$this->codfabricante;
   }
}
