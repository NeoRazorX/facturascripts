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

class linea_presupuesto_cliente extends fs_model
{
   public $cantidad;
   public $codimpuesto;
   public $descripcion;
   public $dtopor;
   public $idlinea;
   public $idpresupuesto;
   public $irpf;
   public $iva;
   public $pvpsindto;
   public $pvptotal;
   public $pvpunitario;
   public $recargo;
   public $referencia;
   
   public function __construct($l = FALSE)
   {
      parent::__construct('lineaspresupuestoscli', 'plugins/presupuestos_y_pedidos/');
      
      if($l)
      {
         $this->cantidad = floatval($l['cantidad']);
         $this->codimpuesto = $l['codimpuesto'];
         $this->descripcion = $l['descripcion'];
         $this->dtopor = floatval($l['dtopor']);
         $this->idlinea = intval($l['idlinea']);
         $this->idpresupuesto = intval($l['idpresupuesto']);
         $this->irpf = floatval($l['irpf']);
         $this->iva = floatval($l['iva']);
         $this->pvpsindto = floatval($l['pvpsindto']);
         $this->pvptotal = floatval($l['pvptotal']);
         $this->pvpunitario = floatval($l['pvpunitario']);
         $this->recargo = floatval($l['recargo']);
         $this->referencia = $l['referencia'];
      }
      else
      {
         $this->cantidad = 0;
         $this->codimpuesto = NULL;
         $this->descripcion = NULL;
         $this->dtopor = 0;
         $this->idlinea = NULL;
         $this->idpresupuesto = NULL;
         $this->irpf = 0;
         $this->iva = 0;
         $this->pvpsindto = 0;
         $this->pvptotal = 0;
         $this->pvpunitario = 0;
         $this->recargo = 0;
         $this->referencia = NULL;
      }
   }
   
   protected function install()
   {
      return '';
   }
      
   public function pvp_iva()
   {
      return $this->pvpunitario*(100+$this->iva)/100;
   }
   
   public function total_iva()
   {
      return $this->pvptotal*(100+$this->iva)/100;
   }
   
   public function url()
   {
      if( is_null($this->idpresupuesto) )
         return 'index.php?page=ventas_presupuestos';
      else
         return 'index.php?page=ventas_presupuesto&id='.$this->idpresupuesto;
   }
   
   public function articulo_url()
   {
      if( is_null($this->referencia) OR $this->referencia == ' ')
         return "index.php?page=ventas_articulos";
      else
         return "index.php?page=ventas_articulo&ref=".urlencode($this->referencia);
   }
   
   public function exists()
   {
      if( is_null($this->idlinea) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE idlinea = ".$this->var2str($this->idlinea).";");
   }
   
   public function test()
   {
      return TRUE;
   }
   
   public function save()
   {
      if( $this->test() )
      {
         if( $this->exists() )
         {
            $sql = "UPDATE ".$this->table_name." SET cantidad = ".$this->var2str($this->idlinea).",
               codimpuesto = ".$this->var2str($this->codimpuesto).", descripcion = ".$this->var2str($this->descripcion).",
               dtopor = ".$this->var2str($this->dtopor).", idpresupuesto = ".$this->var2str($this->idpresupuesto).",
               irpf = ".$this->var2str($this->irpf).", iva = ".$this->var2str($this->iva).",
               pvpsindto = ".$this->var2str($this->pvpsindto).", pvptotal = ".$this->var2str($this->pvptotal).",
               pvpunitario = ".$this->var2str($this->pvpunitario).", recargo = ".$this->var2str($this->recargo).",
               referencia = ".$this->var2str($this->referencia)." WHERE idlinea = ".$this->var2str($this->idlinea).";";
            return $this->db->exec($sql);
         }
         else
         {
            $sql = "INSERT INTO ".$this->table_name." (cantidad,codimpuesto,descripcion,dtopor,
               idpresupuesto,irpf,iva,pvpsindto,pvptotal,pvpunitario,recargo,referencia)
               VALUES (".$this->var2str($this->cantidad).",".$this->var2str($this->codimpuesto).",
               ".$this->var2str($this->descripcion).",".$this->var2str($this->dtopor).",
               ".$this->var2str($this->idpresupuesto).",".$this->var2str($this->irpf).",".$this->var2str($this->iva).",
               ".$this->var2str($this->pvpsindto).",".$this->var2str($this->pvptotal).",".$this->var2str($this->pvpunitario).",
               ".$this->var2str($this->recargo).",".$this->var2str($this->referencia).");";
            if( $this->db->exec($sql) )
            {
               $this->idlinea = $this->db->lastval();
               return TRUE;
            }
            else
               return FALSE;
         }
      }
      else
         return FALSE;
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE idlinea = ".$this->var2str($this->idlinea).";");
   }
   
   public function all_from_presupuesto($idp)
   {
      $plist = array();
      
      $data = $this->db->select("SELECT * FROM ".$this->table_name." WHERE idpresupuesto = ".$this->var2str($idp)." ORDER BY referencia ASC;");
      if($data)
      {
         foreach($data as $d)
            $plist[] = new linea_presupuesto_cliente($d);
      }
      
      return $plist;
   }
}
