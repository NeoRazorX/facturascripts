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
 * Un impuesto (IVA) que puede estar asociado a artículos y línes de albaranes o facturas.
 */
class impuesto extends fs_model
{
   public $codimpuesto; /// pkey
   public $codsubcuentadeu;
   public $codsubcuentaacr;
   public $codsubcuentaivadedadue;
   public $codsubcuentaivadevadue;
   public $codsubcuentaivadeventue;
   public $codsubcuentarep;
   public $codsubcuentasop;
   public $idsubcuentaacr;
   public $idsubcuentadeu;
   public $idsubcuentaivadeventue;
   public $idsubcuentarep;
   public $idsubcuentasop;
   public $idsubcuentaivadevadue;
   public $idsubcuentaivadedadue;
   public $descripcion;
   public $iva;
   public $recargo;

   public function __construct($i=FALSE)
   {
      parent::__construct('impuestos');
      if($i)
      {
         $this->codimpuesto = $i['codimpuesto'];
         $this->codsubcuentadeu = $i['codsubcuentadeu'];
         $this->codsubcuentaacr = $i['codsubcuentaacr'];
         $this->codsubcuentaivadedadue = $i['codsubcuentaivadedadue'];
         $this->codsubcuentaivadevadue = $i['codsubcuentaivadevadue'];
         $this->codsubcuentaivadeventue = $i['codsubcuentaivadeventue'];
         $this->codsubcuentarep = $i['codsubcuentarep'];
         $this->codsubcuentasop = $i['codsubcuentasop'];
         $this->idsubcuentaacr = $this->intval($i['idsubcuentaacr']);
         $this->idsubcuentadeu = $this->intval($i['idsubcuentadeu']);
         $this->idsubcuentaivadeventue = $this->intval($i['idsubcuentaivadeventue']);
         $this->idsubcuentarep = $this->intval($i['idsubcuentarep']);
         $this->idsubcuentasop = $this->intval($i['idsubcuentasop']);
         $this->idsubcuentaivadevadue = $this->intval($i['idsubcuentaivadevadue']);
         $this->idsubcuentaivadedadue = $this->intval($i['idsubcuentaivadedadue']);
         $this->descripcion = $i['descripcion'];
         $this->iva = floatval($i['iva']);
         $this->recargo = floatval($i['recargo']);
      }
      else
      {
         $this->codimpuesto = NULL;
         $this->codsubcuentadeu = NULL;
         $this->codsubcuentaacr = NULL;
         $this->codsubcuentaivadedadue = NULL;
         $this->codsubcuentaivadevadue = NULL;
         $this->codsubcuentaivadeventue = NULL;
         $this->codsubcuentarep = NULL;
         $this->codsubcuentasop = NULL;
         $this->idsubcuentaacr = NULL;
         $this->idsubcuentadeu = NULL;
         $this->idsubcuentaivadeventue = NULL;
         $this->idsubcuentarep = NULL;
         $this->idsubcuentasop = NULL;
         $this->idsubcuentaivadevadue = NULL;
         $this->idsubcuentaivadedadue = NULL;
         $this->descripcion = NULL;
         $this->iva = 0;
         $this->recargo = 0;
      }
   }
   
   protected function install()
   {
      $this->clean_cache();
      return "INSERT INTO ".$this->table_name." (codimpuesto,descripcion,iva,recargo) VALUES "
         . "('IVA0','IVA 0%','0','0'),('IVA21','IVA 21%','21','5.2'),"
         . "('IVA10','IVA 10%','10','1.4'),('IVA4','IVA 4%','4','0.5');";
   }
   
   public function url()
   {
      if( is_null($this->codimpuesto) )
         return 'index.php?page=contabilidad_impuestos';
      else
         return 'index.php?page=contabilidad_impuestos#'.$this->codimpuesto;
   }
   
   public function is_default()
   {
      return ( $this->codimpuesto == $this->default_items->codimpuesto() );
   }
   
   public function get($cod)
   {
      $impuesto = $this->db->select("SELECT * FROM ".$this->table_name.
         " WHERE codimpuesto = ".$this->var2str($cod).";");
      if($impuesto)
         return new impuesto($impuesto[0]);
      else
         return FALSE;
   }
   
   public function get_by_iva($iva)
   {
      $impuesto = $this->db->select("SELECT * FROM ".$this->table_name.
         " WHERE iva = ".$this->var2str( floatval($iva) ).";");
      if($impuesto)
         return new impuesto($impuesto[0]);
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->codimpuesto) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name."
            WHERE codimpuesto = ".$this->var2str($this->codimpuesto).";");
   }
   
   public function test()
   {
      $status = FALSE;
      
      $this->codimpuesto = trim($this->codimpuesto);
      $this->descripcion = $this->no_html($this->descripcion);
      
      if( !preg_match("/^[A-Z0-9]{1,10}$/i", $this->codimpuesto) )
      {
         $this->new_error_msg("Código del impuesto no válido. Debe tener entre 1 y 10 caracteres alfanuméricos.");
      }
      else if( strlen($this->descripcion) < 1 OR strlen($this->descripcion) > 50 )
      {
         $this->new_error_msg("Descripción del impuesto no válida.");
      }
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
         {
            $sql = "UPDATE ".$this->table_name." SET codsubcuentadeu = ".$this->var2str($this->codsubcuentadeu).",
               codsubcuentaacr = ".$this->var2str($this->codsubcuentaacr).",
                  codsubcuentaivadedadue = ".$this->var2str($this->codsubcuentaivadedadue).",
               codsubcuentaivadevadue = ".$this->var2str($this->codsubcuentaivadevadue).",
               codsubcuentaivadeventue = ".$this->var2str($this->codsubcuentaivadeventue).",
               codsubcuentarep = ".$this->var2str($this->codsubcuentarep).", codsubcuentasop = ".$this->var2str($this->codsubcuentasop).",
               idsubcuentaacr = ".$this->var2str($this->idsubcuentaacr).", idsubcuentadeu = ".$this->var2str($this->idsubcuentadeu).",
               idsubcuentaivadeventue = ".$this->var2str($this->idsubcuentaivadeventue).",
               idsubcuentarep = ".$this->var2str($this->idsubcuentarep).",
               idsubcuentasop = ".$this->var2str($this->idsubcuentasop).",
                  idsubcuentaivadevadue = ".$this->var2str($this->idsubcuentaivadevadue).",
               idsubcuentaivadedadue = ".$this->var2str($this->idsubcuentaivadedadue).", descripcion = ".$this->var2str($this->descripcion).",
               iva = ".$this->var2str($this->iva).", recargo = ".$this->var2str($this->recargo)."
               WHERE codimpuesto = ".$this->var2str($this->codimpuesto).";";
         }
         else
         {
            $sql = "INSERT INTO ".$this->table_name." (codimpuesto,codsubcuentadeu,codsubcuentaacr,codsubcuentaivadedadue,
               codsubcuentaivadevadue,codsubcuentaivadeventue,codsubcuentarep,codsubcuentasop,idsubcuentaacr,
               idsubcuentadeu,idsubcuentaivadeventue,idsubcuentarep,idsubcuentasop,idsubcuentaivadevadue,idsubcuentaivadedadue,
               descripcion,iva,recargo) VALUES (".$this->var2str($this->codimpuesto).",".$this->var2str($this->codsubcuentadeu).",
               ".$this->var2str($this->codsubcuentaacr).",".$this->var2str($this->codsubcuentaivadedadue).",
               ".$this->var2str($this->codsubcuentaivadevadue).",".$this->var2str($this->codsubcuentaivadeventue).",
               ".$this->var2str($this->codsubcuentarep).",".$this->var2str($this->codsubcuentasop).",
               ".$this->var2str($this->idsubcuentaacr).",".$this->var2str($this->idsubcuentadeu).",
               ".$this->var2str($this->idsubcuentaivadeventue).",".$this->var2str($this->idsubcuentarep).",
               ".$this->var2str($this->idsubcuentasop).",".$this->var2str($this->idsubcuentaivadevadue).",
               ".$this->var2str($this->idsubcuentaivadedadue).",".$this->var2str($this->descripcion).",
               ".$this->var2str($this->iva).",".$this->var2str($this->recargo).");";
         }
         return $this->db->exec($sql);
      }
      else
         return FALSE;
   }
   
   public function delete()
   {
      $this->clean_cache();
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE codimpuesto = ".$this->var2str($this->codimpuesto).";");
   }
   
   private function clean_cache()
   {
      $this->cache->delete('m_impuesto_all');
   }
   
   public function all()
   {
      $impuestolist = $this->cache->get_array('m_impuesto_all');
      if( !$impuestolist )
      {
         $impuestos = $this->db->select("SELECT * FROM ".$this->table_name." ORDER BY iva ASC;");
         if($impuestos)
         {
            foreach($impuestos as $i)
               $impuestolist[] = new impuesto($i);
         }
         $this->cache->set('m_impuesto_all', $impuestolist);
      }
      return $impuestolist;
   }
}
