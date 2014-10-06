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
 * La línea de IVA de una factura de proveedor.
 * Indica el neto, iva y total para un determinado IVA y una factura.
 */
class linea_iva_factura_proveedor extends fs_model
{
   public $totallinea;
   public $totalrecargo;
   public $recargo;
   public $totaliva;
   public $iva;
   public $codimpuesto;
   public $neto;
   public $idfactura;
   public $idlinea;
   
   public function __construct($l = FALSE)
   {
      parent::__construct('lineasivafactprov');
      if($l)
      {
         $this->idlinea = $this->intval($l['idlinea']);
         $this->idfactura = $this->intval($l['idfactura']);
         $this->neto = floatval($l['neto']);
         $this->codimpuesto = $l['codimpuesto'];
         $this->iva = floatval($l['iva']);
         $this->totaliva = floatval($l['totaliva']);
         $this->recargo = floatval($l['recargo']);
         $this->totalrecargo = floatval($l['totalrecargo']);
         $this->totallinea = floatval($l['totallinea']);
      }
      else
      {
         $this->idlinea = NULL;
         $this->idfactura = NULL;
         $this->neto = 0;
         $this->codimpuesto = NULL;
         $this->iva = 0;
         $this->totaliva = 0;
         $this->recargo = 0;
         $this->totalrecargo = 0;
         $this->totallinea = 0;
      }
   }
   
   protected function install()
   {
      return '';
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
      if( $this->floatcmp($this->totallinea, $this->neto + $this->totaliva + $this->totalrecargo, FS_NF0, TRUE) )
      {
         return TRUE;
      }
      else
      {
         $this->new_error_msg("Error en el valor de totallinea de la línea de IVA del impuesto ".
                 $this->codimpuesto." de la factura. Valor correcto: ".
                 round($this->neto + $this->totaliva + $this->totalrecargo, FS_NF0));
         return FALSE;
      }
   }
   
   public function factura_test($idfactura, $neto, $totaliva, $totalrecargo)
   {
      $status = TRUE;
      
      $li_neto = 0;
      $li_iva = 0;
      $li_recargo = 0;
      foreach($this->all_from_factura($idfactura) as $li)
      {
         if( !$li->test() )
            $status = FALSE;
         
         $li_neto += $li->neto;
         $li_iva += $li->totaliva;
         $li_recargo += $li->totalrecargo;
      }
      
      $li_neto = round($li_neto, FS_NF0);
      $li_iva = round($li_iva, FS_NF0);
      $li_recargo = round($li_recargo, FS_NF0);
      
      if( !$this->floatcmp($neto, $li_neto, FS_NF0, TRUE) )
      {
         $this->new_error_msg("La suma de los netos de las líneas de IVA debería ser: ".$neto);
         $status = FALSE;
      }
      else if( !$this->floatcmp($totaliva, $li_iva, FS_NF0, TRUE) )
      {
         $this->new_error_msg("La suma de los totales de iva de las líneas de IVA debería ser: ".$totaliva);
         $status = FALSE;
      }
      else if( !$this->floatcmp($totalrecargo, $li_recargo, FS_NF0, TRUE) )
      {
         $this->new_error_msg("La suma de los totalrecargo de las líneas de IVA debería ser: ".$totalrecargo);
         $status = FALSE;
      }
      
      return $status;
   }
   
   public function save()
   {
      if( $this->test() )
      {
         if( $this->exists() )
         {
            $sql = "UPDATE ".$this->table_name." SET idfactura = ".$this->var2str($this->idfactura).",
               neto = ".$this->var2str($this->neto).", codimpuesto = ".$this->var2str($this->codimpuesto).",
               iva = ".$this->var2str($this->iva).", totaliva = ".$this->var2str($this->totaliva).",
               recargo = ".$this->var2str($this->recargo).",
               totalrecargo = ".$this->var2str($this->totalrecargo).",
               totallinea = ".$this->var2str($this->totallinea).
               " WHERE idlinea = ".$this->var2str($this->idlinea).";";
         }
         else
         {
            $sql = "INSERT INTO ".$this->table_name." (idfactura,neto,codimpuesto,iva,totaliva,
               recargo,totalrecargo,totallinea) VALUES (".$this->var2str($this->idfactura).",
               ".$this->var2str($this->neto).",".$this->var2str($this->codimpuesto).",
               ".$this->var2str($this->iva).",".$this->var2str($this->totaliva).",
               ".$this->var2str($this->recargo).",".$this->var2str($this->totalrecargo).",
               ".$this->var2str($this->totallinea).");";
         }
         return $this->db->exec($sql);
      }
      else
         return FALSE;
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE idlinea = ".$this->var2str($this->idlinea).";");
   }
   
   public function all_from_factura($id)
   {
      $linealist = array();
      $lineas = $this->db->select("SELECT * FROM ".$this->table_name.
              " WHERE idfactura = ".$this->var2str($id).";");
      if($lineas)
      {
         foreach($lineas as $l)
            $linealist[] = new linea_iva_factura_proveedor($l);
      }
      return $linealist;
   }
}
