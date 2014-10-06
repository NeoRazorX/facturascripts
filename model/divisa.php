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

/**
 * Una divisa (moneda) con su símbolo y su tasa de conversión respecto al euro.
 */
class divisa extends fs_model
{
   public $coddivisa;
   public $descripcion;
   
   /**
    * Tasa de conversión respecto al euro.
    * @var type 
    */
   public $tasaconv;
   
   /**
    * código ISO 4217 en número: http://en.wikipedia.org/wiki/ISO_4217
    * @var type
    */
   public $codiso;
   public $simbolo;

   public function __construct($d=FALSE)
   {
      parent::__construct('divisas');
      if($d)
      {
         $this->coddivisa = $d['coddivisa'];
         $this->descripcion = $d['descripcion'];
         $this->tasaconv = floatval($d['tasaconv']);
         $this->codiso = $d['codiso'];
         $this->simbolo = $d['simbolo'];
         
         if($this->simbolo == '' AND $this->coddivisa == 'EUR')
         {
            $this->simbolo = '€';
            $this->save();
         }
      }
      else
      {
         $this->coddivisa = NULL;
         $this->descripcion = '';
         $this->tasaconv = 1;
         $this->codiso = NULL;
         $this->simbolo = '?';
      }
   }
   
   protected function install()
   {
      $this->clean_cache();
      return "INSERT INTO ".$this->table_name." (coddivisa,descripcion,tasaconv,codiso,simbolo)
         VALUES ('EUR','EUROS','1','978','€');".
         "INSERT INTO ".$this->table_name." (coddivisa,descripcion,tasaconv,codiso,simbolo)
         VALUES ('ARS','PESOS (ARG)','10.83','32','$');".
           "INSERT INTO ".$this->table_name." (coddivisa,descripcion,tasaconv,codiso,simbolo)
         VALUES ('CLP','PESOS (CLP)','755.73','152','$');".
           "INSERT INTO ".$this->table_name." (coddivisa,descripcion,tasaconv,codiso,simbolo)
         VALUES ('COP','PESOS (COP)','2573','170','$');".
           "INSERT INTO ".$this->table_name." (coddivisa,descripcion,tasaconv,codiso,simbolo)
         VALUES ('USD','DÓLARES EE.UU.','1.36','840','$');".
           "INSERT INTO ".$this->table_name." (coddivisa,descripcion,tasaconv,codiso,simbolo)
         VALUES ('MXN','PESOS (MXN)','18.1','484','$');".
           "INSERT INTO ".$this->table_name." (coddivisa,descripcion,tasaconv,codiso,simbolo)
         VALUES ('PAB','BALBOAS','38.17','590','B');".
           "INSERT INTO ".$this->table_name." (coddivisa,descripcion,tasaconv,codiso,simbolo)
         VALUES ('VEF','BOLÍVARES','38.17','937','Bs');";
   }
   
   public function show_tasa()
   {
      return number_format($this->tasaconv, 3);
   }
   
   public function url()
   {
      return 'index.php?page=admin_divisas';
   }
   
   public function is_default()
   {
      return ( $this->coddivisa == $this->default_items->coddivisa() );
   }
   
   public function get($cod)
   {
      $divisa = $this->db->select("SELECT * FROM ".$this->table_name.
              " WHERE coddivisa = ".$this->var2str($cod).";");
      if($divisa)
         return new divisa($divisa[0]);
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->coddivisa) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name.
                 " WHERE coddivisa = ".$this->var2str($this->coddivisa).";");
   }
   
   public function test()
   {
      $status = FALSE;
      $this->descripcion = $this->no_html($this->descripcion);
      
      if( !preg_match("/^[A-Z0-9]{1,3}$/i", $this->coddivisa) )
         $this->new_error_msg("Código de almacén no válido.");
      else if( isset($this->codiso) AND !preg_match("/^[A-Z0-9]{1,3}$/i", $this->codiso) )
         $this->new_error_msg("Código ISO no válido.");
      else
         return TRUE;
      
      return $status;
   }
   
   public function save()
   {
      if( $this->test() )
      {
         $this->clean_cache();
         
         if( $this->exists() )
         {
            $sql = "UPDATE ".$this->table_name." SET descripcion = ".$this->var2str($this->descripcion).",
               tasaconv = ".$this->var2str($this->tasaconv).", codiso = ".$this->var2str($this->codiso).",
               simbolo = ".$this->var2str($this->simbolo)."
               WHERE coddivisa = ".$this->var2str($this->coddivisa).";";
         }
         else
         {
            $sql = "INSERT INTO ".$this->table_name." (coddivisa,descripcion,tasaconv,codiso,simbolo) VALUES
               (".$this->var2str($this->coddivisa).",".$this->var2str($this->descripcion).",
                ".$this->var2str($this->tasaconv).",".$this->var2str($this->codiso).",
                ".$this->var2str($this->simbolo).");";
         }
         
         return $this->db->exec($sql);
      }
      else
         return FALSE;
   }
   
   public function delete()
   {
      $this->clean_cache();
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE coddivisa = ".$this->var2str($this->coddivisa).";");
   }
   
   private function clean_cache()
   {
      $this->cache->delete('m_divisa_all');
   }
   
   public function all()
   {
      $listad = $this->cache->get_array('m_divisa_all');
      if( !$listad )
      {
         $divisas = $this->db->select("SELECT * FROM ".$this->table_name.
                 " ORDER BY coddivisa ASC;");
         if($divisas)
         {
            foreach($divisas as $d)
               $listad[] = new divisa($d);
         }
         $this->cache->set('m_divisa_all', $listad);
      }
      return $listad;
   }
}
