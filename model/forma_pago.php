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
 * Forma de pago de una factura.
 */
class forma_pago extends fs_model
{
   public $codpago;
   public $descripcion;
   public $genrecibos;
   public $codcuenta;
   public $domiciliado;

   public function __construct($f=FALSE)
   {
      parent::__construct('formaspago');
      if( $f )
      {
         $this->codpago = $f['codpago'];
         $this->descripcion = $f['descripcion'];
         $this->genrecibos = $f['genrecibos'];
         $this->codcuenta = $f['codcuenta'];
         $this->domiciliado = $this->str2bool($f['domiciliado']);
      }
      else
      {
         $this->codpago = NULL;
         $this->descripcion = '';
         $this->genrecibos = 'Emitidos';
         $this->codcuenta = '';
         $this->domiciliado = FALSE;
      }
   }
   
   protected function install()
   {
      $this->clean_cache();
      return "INSERT INTO ".$this->table_name." (codpago,descripcion,genrecibos,codcuenta,domiciliado) VALUES ('CONT','CONTADO','Emitidos',NULL,FALSE);";
   }
   
   public function url()
   {
      return 'index.php?page=contabilidad_formas_pago';
   }
   
   public function is_default()
   {
      return ( $this->codpago == $this->default_items->codpago() );
   }
   
   public function get($cod)
   {
      $pago = $this->db->select("SELECT * FROM ".$this->table_name." WHERE codpago = ".$this->var2str($cod).";");
      if($pago)
         return new forma_pago($pago[0]);
      else
         return FALSE;
   }

   public function exists()
   {
      if( is_null($this->codpago) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE codpago = ".$this->var2str($this->codpago).";");
   }
   
   public function test()
   {
      $this->descripcion = $this->no_html($this->descripcion);
      return TRUE;
   }
   
   public function save()
   {
      if( $this->test() )
      {
         $this->clean_cache();
         
         if( $this->exists() )
         {
            $sql = "UPDATE ".$this->table_name." SET descripcion = ".$this->var2str($this->descripcion).",
               genrecibos = ".$this->var2str($this->genrecibos).", codcuenta = ".$this->var2str($this->codcuenta).",
               domiciliado = ".$this->var2str($this->domiciliado)." WHERE codpago = ".$this->var2str($this->codpago).";";
         }
         else
         {
            $sql = "INSERT INTO ".$this->table_name." (codpago,descripcion,genrecibos,codcuenta,domiciliado) VALUES
               (".$this->var2str($this->codpago).",".$this->var2str($this->descripcion).",
               ".$this->var2str($this->genrecibos).",".$this->var2str($this->codcuenta).",
               ".$this->var2str($this->domiciliado).");";
         }
         
         return $this->db->exec($sql);
      }
      else
         return FALSE;
   }
   
   public function delete()
   {
      $this->clean_cache();
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE codpago = ".$this->var2str($this->codpago).";");
   }
   
   private function clean_cache()
   {
      $this->cache->delete('m_forma_pago_all');
   }
   
   public function all()
   {
      $listaformas = $this->cache->get_array('m_forma_pago_all');
      if( !$listaformas )
      {
         $formas = $this->db->select("SELECT * FROM ".$this->table_name." ORDER BY codpago ASC;");
         if($formas)
         {
            foreach($formas as $f)
               $listaformas[] = new forma_pago($f);
         }
         $this->cache->set('m_forma_pago_all', $listaformas);
      }
      return $listaformas;
   }
}
