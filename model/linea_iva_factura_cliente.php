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

/*
 * Función para comparar dos linea_iva_factura_cliente
 * en función de su totallinea
 */
function cmp_linea_iva_fact_cli($a, $b)
{
   if($a->totallinea == $b->totallinea)
      return 0;
   else
      return ($a->totallinea < $b->totallinea) ? 1 : -1;
}


class linea_iva_factura_cliente extends fs_model
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
   
   public function __construct($l=FALSE)
   {
      parent::__construct('lineasivafactcli');
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
      if( is_null($this->idfactura) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name.
                 " WHERE idlinea = ".$this->var2str($this->idlinea).";");
   }
   
   public function test()
   {
      if( $this->floatcmp($this->totallinea, $this->neto + $this->totaliva, 2, TRUE) )
         return TRUE;
      else
      {
         $this->new_error_msg("Error en el valor de totallinea de la línea de IVA del impuesto ".
                 $this->codimpuesto." de la factura. Valor correcto: ".
                 round($this->neto + $this->totaliva, 2));
         return FALSE;
      }
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE ".$this->table_name." SET idfactura = ".$this->var2str($this->idfactura).",
            neto = ".$this->var2str($this->neto).",
            codimpuesto = ".$this->var2str($this->codimpuesto).",
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
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name.
              " WHERE idlinea = ".$this->var2str($this->idlinea).";");;
   }
   
   public function all_from_factura($id)
   {
      $linealist = array();
      $lineas = $this->db->select("SELECT * FROM ".$this->table_name.
              " WHERE idfactura = ".$this->var2str($id).";");
      if($lineas)
      {
         foreach($lineas as $l)
            $linealist[] = new linea_iva_factura_cliente($l);
      }
      return $linealist;
   }
}

?>