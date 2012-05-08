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

require_once 'base/fs_model.php';

class serie extends fs_model
{
   public $codserie;
   public $descripcion;
   public $siniva;
   public $irpf;
   public $idcuenta;
   
   public function __construct($s=FALSE)
   {
      parent::__construct('series');
      if($s)
      {
         $this->codserie = $s['codserie'];
         $this->descripcion = $s['descripcion'];
         $this->siniva = ($s['siniva'] == 't');
         $this->irpf = floatval($s['irpf']);
         $this->idcuenta = floatval($s['idcuenta']);
      }
      else
      {
         $this->codserie = '';
         $this->descripcion = '';
         $this->siniva = FALSE;
         $this->irpf = 0;
         $this->idcuenta = NULL;
      }
   }
   
   public function url()
   {
      return 'index.php?page=contabilidad_series#'.$this->codserie;
   }

   protected function install()
   {
      return "INSERT INTO ".$this->table_name." (codserie,descripcion,siniva,irpf,idcuenta)
            VALUES ('A','SERIE A',FALSE,'0',NULL);";
   }
   
   public function get($cod)
   {
      $serie = $this->db->select("SELECT * FROM ".$this->table_name." WHERE codserie = '".$cod."';");
      if($serie)
         return new serie($serie[0]);
      else
         return FALSE;
   }
   
   public function exists()
   {
      return $this->db->select("SELECT * FROM ".$this->table_name." WHERE codserie = '".$this->codserie."';");
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE ".$this->table_name." SET descripcion = ".$this->var2str($this->descripcion).",
            siniva = ".$this->var2str($this->siniva).", irpf = ".$this->var2str($this->irpf).",
            idcuenta = ".$this->var2str($this->idcuenta)." WHERE codserie = '".$this->codserie."';";
      }
      else
      {
         $sql = "INSERT INTO ".$this->table_name." (codserie,descripcion,siniva,irpf,idcuenta)
            VALUES (".$this->var2str($this->codserie).",".$this->var2str($this->descripcion).",".$this->var2str($this->siniva).",
            ".$this->var2str($this->irpf).",".$this->var2str($this->idcuenta).");";
      }
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE codserie = '".$this->codserie."';");
   }
   
   public function all()
   {
      $serielist = array();
      $series = $this->db->select("SELECT * FROM ".$this->table_name." ORDER BY codserie ASC;");
      if($series)
      {
         foreach($series as $s)
            $serielist[] = new serie($s);
      }
      return $serielist;
   }
}

?>
