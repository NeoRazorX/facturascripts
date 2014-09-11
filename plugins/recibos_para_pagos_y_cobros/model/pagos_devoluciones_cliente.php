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
class pagos_devoluciones_cliente extends fs_model
{
   public $codcuenta;
   public $codsubcuenta;
   public $ctaagencia;
   public $ctaentidad;
   public $cuenta;
   public $dc;
   public $descripcion;
   public $editable;
   public $fecha;
   public $idasiento;
   public $idpagodevol;
   public $idrecibo;
   public $idremesa;
   public $idsubcuenta;
   public $nogenerarasiento;
   public $tasaconv;
   public $tipo;
   
   public $recibo;
   
   public function __construct($f=FALSE)
   {
      parent::__construct('pagosdevolcli', 'plugins/recibos_para_pagos_y_cobros/');
   }

   protected function install()
   {
      return '';
   }
   
   public function url()
   {
      if( is_null($this->idpagodevol) )
         return 'index.php?page=pagos_devoluciones_clientes';
      else
         return 'index.php?page=pagos_devoluciones_cliente&id='.$this->idpagodevol;
   }
   
   public function cliente_url()
   {
      if( is_null($this->codcliente) )
         return "index.php?page=pagos_devoluciones_clientes";
      else
         return "index.php?page=pagos_devoluciones_cliente&cliente=".$this->codcliente;
   }
   
   public function get($id)
   {
      $pago_devolucion = $this->db->select("SELECT * FROM ".$this->table_name." WHERE idpagodevol = ".$this->var2str($id).";");
      if($pago_devolucion)
         return new pagos_devoluciones_cliente($pago_devolucion[0]);
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->idpagodevol) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE idpagodevol = ".$this->var2str($this->idpagodevol).";");
   }
   
   public function new_idpagodevol()
   {
      $newid = $this->db->nextval($this->table_name.'_idpagodevol_seq');
      if($newid)
         $this->idpagodevol = intval($newid);
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
            codcuenta = ".$this->var2str($this->codcuenta).", 
            codsubcuenta = ".$this->var2str($this->codsubcuenta).", 
            ctaagencia = ".$this->var2str($this->ctaagencia).", 
            ctaentidad = ".$this->var2str($this->ctaentidad).", 
            cuenta = ".$this->var2str($this->cuenta).", 
            dc = ".$this->var2str($this->dc).", 
            descripcion = ".$this->var2str($this->descripcion).", 
            editable = ".$this->var2str($this->editable).", 
            fecha = ".$this->var2str($this->fecha).", 
            idasiento = ".$this->var2str($this->idasiento).", 
            idrecibo = ".$this->var2str($this->idrecibo).", 
            idremesa = ".$this->var2str($this->idremesa).", 
            idsubcuenta = ".$this->var2str($this->idsubcuenta).", 
            nogenerarasiento = ".$this->var2str($this->nogenerarasiento).", 
            tasaconv = ".$this->var2str($this->tasaconv).", 
            tipo = ".$this->var2str($this->tipo)."
            WHERE idpagodevol = ".$this->var2str($this->idpagodevol).";";
         
         return $this->db->exec($sql);
      }
      else
      {
         $sql = "INSERT INTO ".$this->table_name." (codcuenta, codsubcuenta, 
         ctaagencia, ctaentidad, cuenta, dc, descripcion, editable, fecha, 
         idasiento, idpagodevol, idrecibo, idremesa, idsubcuenta, nogenerarasiento, 
         tasaconv, tipo) VALUES (
            ".$this->var2str($this->codcuenta).",".$this->var2str($this->codsubcuenta).",
            ".$this->var2str($this->ctaagencia).",".$this->var2str($this->ctaentidad).",
            ".$this->var2str($this->cuenta).",".$this->var2str($this->dc).",
            ".$this->var2str($this->descripcion).",".$this->var2str($this->editable).",
            ".$this->var2str($this->fecha).",".$this->var2str($this->idasiento).",
            ".$this->var2str($this->idpagodevol).",".$this->var2str($this->idrecibo).",
            ".$this->var2str($this->idremesa).",".$this->var2str($this->idsubcuenta).",
            ".$this->var2str($this->nogenerarasiento).",".$this->var2str($this->tasaconv).",
            ".$this->var2str($this->tipo).");";
         
         if( $this->db->exec($sql) )
         {
            $this->idpagodevol = $this->db->lastval();
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
