<?php
/*
 * This file is part of FacturaScripts
 * Copyright (C) 2014  Francesc Pineda Segarra  shawe.ewahs@gmail.com
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
 * Control de pagos y devoluciones de clientes.
 */
class pagos_devoluciones_remesas_cliente extends fs_model
{
   public $fecha;
   public $idasiento;
   public $idpagorem;
   public $idremesa;
   public $nogenerarasiento;
   public $tipo;
     
   public $recibo;
   
   public function __construct($f=FALSE)
   {
      parent::__construct('pagosdevolrem', 'plugins/recibos_para_pagos_y_cobros/');
   }

   protected function install()
   {
      return '';
   }
   
   public function url()
   {
      if( is_null($this->idpagorem) )
         return 'index.php?page=pagos_devoluciones_remesas_clientes';
      else
         return 'index.php?page=pagos_devoluciones_remesas_cliente&id='.$this->idpagorem;
   }
   
   public function cliente_url()
   {
      if( is_null($this->codcliente) )
         return "index.php?page=pagos_devoluciones_remesas_clientes";
      else
         return "index.php?page=pagos_devoluciones_remesas_cliente&cliente=".$this->codcliente;
   }
   
   public function get($id)
   {
      $pago_devolucion = $this->db->select("SELECT * FROM ".$this->table_name." WHERE idpagorem = ".$this->var2str($id).";");
      if($pago_devolucion)
         return new pagos_devoluciones_cliente($pago_devolucion[0]);
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->idpagorem) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE idpagorem = ".$this->var2str($this->idpagorem).";");
   }
   
   public function new_idpagorem()
   {
      $newid = $this->db->nextval($this->table_name.'_idpagorem_seq');
      if($newid)
         $this->idpagorem = intval($newid);
   }
   
   public function test()
   {
      /* Todavía no implementado */
   }
   
   public function full_test($duplicados = TRUE)
   {
      /* Todavía no implementado */
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE ".$this->table_name." SET 
            fecha = ".$this->var2str($this->fecha).", 
            idasiento = ".$this->var2str($this->idasiento).", 
            idremesa = ".$this->var2str($this->idremesa).", 
            nogenerarasiento = ".$this->var2str($this->nogenerarasiento).", 
            tipo = ".$this->var2str($this->tipo)."
            WHERE idpagorem = ".$this->var2str($this->idpagorem).";";
         
         return $this->db->exec($sql);
      }
      else
      {
         $sql = "INSERT INTO ".$this->table_name." (fecha, idasiento, idpagorem, 
         idremesa, nogenerarasiento, tipo) VALUES (
            ".$this->var2str($this->fecha).",".$this->var2str($this->idasiento).",
            ".$this->var2str($this->idpagorem).",".$this->var2str($this->idremesa).",
            ".$this->var2str($this->nogenerarasiento).",".$this->var2str($this->tipo).");";
         
         if( $this->db->exec($sql) )
         {
            $this->recibo = $this->db->lastval();
            return TRUE;
         }
         else
            return FALSE;
      }
   }
   
   public function delete()
   {
      /* Todavía no implementado */
   }
   
   private function clean_cache()
   {
      /* Todavía no implementado */
   }
   
   public function all($offset=0, $limit=FS_ITEM_LIMIT)
   {
      /* Falta utilizar offset y limit */
      $reciboslist = array();
      
      $sql = "SELECT * FROM ".$this->table_name." ORDER BY codigo DESC;";
      $data = $this->db->select($sql);
      if($data)
      {
         foreach($data as $d)
            $reciboslist[] = new pagos_devoluciones_cliente($d);
      }
      
      return $reciboslist;
   }
   
   public function all_from_cliente($codcliente, $offset=0)
   {
      /* Hay que filtrar en reciboscli y remesas para obtener las remesas 
       * en las que esta el cliente */
   }
   
   public function search($query, $offset=0)
   {
      /* Todavía no implementado */
   }
}
