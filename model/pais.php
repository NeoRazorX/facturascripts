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

class pais extends fs_model
{
   public $codiso;
   public $bandera;
   public $nombre;
   public $codpais; /// pkey
   
   public function __construct($p=FALSE)
   {
      parent::__construct('paises');
      if($p)
      {
         $this->codpais = $p['codpais'];
         $this->nombre = $p['nombre'];
         $this->bandera = $p['bandera'];
         $this->codiso = $p['codiso'];
      }
      else
      {
         $this->codpais = '';
         $this->nombre = '';
         $this->bandera = NULL;
         $this->codiso = NULL;
      }
   }

   protected function install()
   {
      $this->clean_cache();
      return "INSERT INTO ".$this->table_name." (codpais,nombre,bandera,codiso) VALUES ('ESP','España',NULL,'');".
           "INSERT INTO ".$this->table_name." (codpais,nombre,bandera,codiso) VALUES ('ARG','Argentina',NULL,'');".
           "INSERT INTO ".$this->table_name." (codpais,nombre,bandera,codiso) VALUES ('CHL','Chile',NULL,'');".
           "INSERT INTO ".$this->table_name." (codpais,nombre,bandera,codiso) VALUES ('ECU','Ecuador',NULL,'');".
           "INSERT INTO ".$this->table_name." (codpais,nombre,bandera,codiso) VALUES ('MEX','México',NULL,'');".
           "INSERT INTO ".$this->table_name." (codpais,nombre,bandera,codiso) VALUES ('PAN','Panamá',NULL,'');".
           "INSERT INTO ".$this->table_name." (codpais,nombre,bandera,codiso) VALUES ('VEN','Venezuela',NULL,'');";
   }
   
   public function url()
   {
      if( is_null($this->codpais) )
         return 'index.php?page=admin_paises';
      else
         return 'index.php?page=admin_paises#'.$this->codpais;
   }
   
   public function is_default()
   {
      return ( $this->codpais == $this->default_items->codpais() );
   }
   
   public function get($cod)
   {
      $pais = $this->db->select("SELECT * FROM ".$this->table_name." WHERE codpais = ".$this->var2str($cod).";");
      if($pais)
         return new pais($pais[0]);
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->codpais) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name."
            WHERE codpais = ".$this->var2str($this->codpais).";");
   }
   
   public function test()
   {
      $status = FALSE;
      
      $this->codpais = trim($this->codpais);
      $this->nombre = $this->no_html($this->nombre);
      
      if( !preg_match("/^[A-Z0-9]{1,20}$/i", $this->codpais) )
         $this->new_error_msg("Código del país no válido.");
      else if( strlen($this->nombre) < 1 OR strlen($this->nombre) > 100 )
         $this->new_error_msg("Nombre del país no válido.");
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
            $sql = "UPDATE ".$this->table_name." SET nombre = ".$this->var2str($this->nombre)."
               WHERE codpais = ".$this->var2str($this->codpais).";";
         else
            $sql = "INSERT INTO ".$this->table_name." (codpais,nombre) VALUES
               (".$this->var2str($this->codpais).",".$this->var2str($this->nombre).");";
         return $this->db->exec($sql);
      }
      else
         return FALSE;
   }
   
   public function delete()
   {
      $this->clean_cache();
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE codpais = ".$this->var2str($this->codpais).";");
   }
   
   private function clean_cache()
   {
      $this->cache->delete('m_pais_all');
   }
   
   public function all()
   {
      $listap = $this->cache->get_array('m_pais_all');
      if( !$listap )
      {
         $paises = $this->db->select("SELECT * FROM ".$this->table_name." ORDER BY codpais ASC;");
         if($paises)
         {
            foreach($paises as $p)
               $listap[] = new pais($p);
         }
         $this->cache->set('m_pais_all', $listap);
      }
      return $listap;
   }
}

?>