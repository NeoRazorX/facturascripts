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

class fbm_pago_socio extends fs_model
{
   public $id;
   public $idsocio;
   public $fecha;
   public $cuota;
   public $codpago;
   public $pagado;
   
   public function __construct($p = FALSE)
   {
      parent::__construct('fbm_pago_socios', 'plugins/veterinaria/');
      if($p)
      {
         $this->id = $this->intval($p['id']);
         $this->idsocio = $this->intval($p['idsocio']);
         $this->fecha = Date('d-m-Y', strtotime($p['fecha']));
         $this->cuota = floatval($p['cuota']);
         $this->codpago = $p['codpago'];
         $this->pagado = $this->str2bool($p['pagado']);
      }
      else
      {
         $this->id = NULL;
         $this->idsocio = NULL;
         $this->fecha = NULL;
         $this->cuota = 0;
         $this->codpago = NULL;
         $this->pagado = FALSE;
      }
   }
   
   protected function install()
   {
      return '';
   }
   
   public function exists()
   {
      if( is_null($this->id) )
      {
         return FALSE;
      }
      else
      {
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE id = ".$this->var2str($this->id).";");
      }
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE ".$this->table_name." SET cuota = ".$this->var2str($this->cuota).",
            codpago = ".$this->var2str($this->codpago).", pagado = ".$this->var2str($this->pagado).",
            idsocio = ".$this->var2str($this->idsocio).", fecha = ".$this->var2str($this->fecha).
            " WHERE id = ".$this->var2str($this->id).";";
         
         return $this->db->exec($sql);
      }
      else
      {
         $sql = "INSERT INTO ".$this->table_name." (idsocio,fecha,cuota,codpago,pagado) VALUES
            (".$this->var2str($this->idsocio).",".$this->var2str($this->fecha).",".$this->var2str($this->cuota).",
            ".$this->var2str($this->codpago).",".$this->var2str($this->pagado).");";
         
         if( $this->db->exec($sql) )
         {
            $this->id = $this->db->lastval();
            return TRUE;
         }
         else
            return FALSE;
      }
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE id = ".$this->var2str($this->id).";");
   }
   
   public function all_from_socio($ids)
   {
      $pagos = array();
      
      $data = $this->db->select("SELECT * FROM ".$this->table_name." WHERE idsocio = ".$this->var2str($ids)." ORDER BY fecha DESC;");
      if($data)
      {
         foreach($data as $d)
            $pagos[] = new fbm_pago_socio($d);
      }
      
      return $pagos;
   }
}