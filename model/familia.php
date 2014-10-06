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

require_once 'base/fs_model.php';
require_model('articulo.php');
require_model('proveedor.php');

/**
 * Una familia de artículos (el equivalente a la marca del artículo).
 */
class familia extends fs_model
{
   public $codfamilia;
   public $descripcion;
   
   public $stats;
   
   public function __construct($f=FALSE)
   {
      parent::__construct('familias');
      if($f)
      {
         $this->codfamilia = $f['codfamilia'];
         $this->descripcion = $f['descripcion'];
      }
      else
      {
         $this->codfamilia = NULL;
         $this->descripcion = '';
      }
      
      $this->stats = array(
          'articulos' => 0,
          'con_stock' => 0,
          'bloqueados' => 0,
          'publicos' => 0,
          'factualizado' => Date('d-m-Y', strtotime(0) )
      );
   }
   
   protected function install()
   {
      $this->clean_cache();
      return "INSERT INTO ".$this->table_name." (codfamilia,descripcion) VALUES ('VARI','VARIOS');";
   }
   
   public function url()
   {
      if( is_null($this->codfamilia) )
         return "index.php?page=ventas_familias";
      else
         return "index.php?page=ventas_familia&cod=".$this->codfamilia;
   }
   
   public function is_default()
   {
      return ( $this->codfamilia == $this->default_items->codfamilia() );
   }
   
   public function get($cod)
   {
      $f = $this->db->select("SELECT * FROM ".$this->table_name." WHERE codfamilia = ".$this->var2str($cod).";");
      if($f)
         return new familia($f[0]);
      else
         return FALSE;
   }

   public function get_articulos($offset=0, $limit=FS_ITEM_LIMIT)
   {
      $articulo = new articulo();
      return $articulo->all_from_familia($this->codfamilia, $offset, $limit);
   }
   
   public function exists()
   {
      if( is_null($this->codfamilia) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name."
            WHERE codfamilia = ".$this->var2str($this->codfamilia).";");
   }
   
   public function test()
   {
      $status = FALSE;
      
      $this->codfamilia = trim($this->codfamilia);
      $this->descripcion = $this->no_html($this->descripcion);
      
      if( !preg_match("/^[A-Z0-9_]{1,4}$/i", $this->codfamilia) )
         $this->new_error_msg("Código de familia no válido. Deben ser entre 1 y 4 caracteres alfanuméricos.");
      else if( strlen($this->descripcion) < 1 OR strlen($this->descripcion) > 100 )
         $this->new_error_msg("Descripción de familia no válida.");
      else
         $status = TRUE;
      
      return $status;
   }
   
   public function save()
   {
      if( $this->test() )
      {
         $this->clean_cache();
         if( $this->exists() )
            $sql = "UPDATE ".$this->table_name." SET descripcion = ".$this->var2str($this->descripcion)."
               WHERE codfamilia = ".$this->var2str($this->codfamilia).";";
         else
            $sql = "INSERT INTO ".$this->table_name." (codfamilia,descripcion) VALUES
               (".$this->var2str($this->codfamilia).",".$this->var2str($this->descripcion).");";
         return $this->db->exec($sql);
      }
      else
         return FALSE;
   }
   
   public function delete()
   {
      $this->clean_cache();
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE codfamilia = ".$this->var2str($this->codfamilia).";");
   }
   
   private function clean_cache()
   {
      $this->cache->delete('m_familia_all');
   }
   
   public function all()
   {
      $famlist = $this->cache->get_array('m_familia_all');
      if( !$famlist )
      {
         $familias = $this->db->select("SELECT * FROM ".$this->table_name." ORDER BY descripcion ASC;");
         if($familias)
         {
            foreach($familias as $f)
               $famlist[] = new familia($f);
         }
         $this->cache->set('m_familia_all', $famlist);
      }
      return $famlist;
   }
   
   public function search($query)
   {
      $famlist = array();
      $query = $this->no_html( strtolower($query) );
      
      $familias = $this->db->select("SELECT * FROM ".$this->table_name.
              " WHERE lower(descripcion) LIKE '%".$query."%' ORDER BY descripcion ASC;");
      if($familias)
      {
         foreach($familias as $f)
            $famlist[] = new familia($f);
      }
      
      return $famlist;
   }
   
   public function stats()
   {
      $aux = $this->db->select("SELECT GREATEST( COUNT(referencia), 0) as art,
         GREATEST( SUM(case when stockfis > 0 then 1 else 0 end), 0) as stock,
         GREATEST( SUM(".$this->db->sql_to_int('bloqueado')."), 0) as bloq,
         GREATEST( SUM(".$this->db->sql_to_int('publico')."), 0) as publi,
         MAX(factualizado) as factualizado
         FROM articulos WHERE codfamilia = ".$this->var2str($this->codfamilia).";");
      if($aux)
      {
         $this->stats['articulos'] = intval($aux[0]['art']);
         $this->stats['con_stock'] = intval($aux[0]['stock']);
         $this->stats['bloqueados'] = intval($aux[0]['bloq']);
         $this->stats['publicos'] = intval($aux[0]['publi']);
         $this->stats['factualizado'] = Date('d-m-Y', strtotime($aux[0]['factualizado']) );
      }
   }
   
   public function proveedores()
   {
      $provelist = array();
      
      $data = $this->db->select("SELECT DISTINCT codproveedor FROM albaranesprov
         WHERE idalbaran IN (SELECT DISTINCT idalbaran FROM lineasalbaranesprov WHERE referencia IN
         (SELECT referencia FROM articulos WHERE codfamilia = ".$this->var2str($this->codfamilia)."));");
      if($data)
      {
         $pro0 = new proveedor();
         foreach($data as $d)
            $provelist[] = $pro0->get($d['codproveedor']);
      }
      
      return $provelist;
   }
}
