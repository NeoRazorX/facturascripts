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
   public $pvptotal;
   public $idpresupuesto;
   public $cantidad;
   public $descripcion;
   public $idlinea;
   public $codimpuesto;
   public $iva;
   public $dtopor;
   public $pvpsindto;
   public $pvpunitario;
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
         $this->idlinea = $l['idlinea'];
         $this->idpresupuesto = $l['idpresupuesto'];
         $this->iva = floatval($l['iva']);
         $this->pvpsindto = floatval($l['pvpsindto']);
         $this->pvptotal = floatval($l['pvptotal']);
         $this->pvpunitario = floatval($l['pvpunitario']);
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
         $this->iva = 0;
         $this->pvpsindto = 0;
         $this->pvptotal = 0;
         $this->pvpunitario = 0;
         $this->referencia = NULL;
      }
   }
   
   protected function install()
   {
      return '';
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
      if( is_null($this->referencia) AND $this->referencia == ' ')
         return "index.php?page=general_articulos";
      else
         return "index.php?page=general_articulo&ref=".urlencode($this->referencia);
   }
   
   public function exists()
   {
      
   }
   
   public function test()
   {
      
   }
   
   public function save()
   {
      
   }
   
   public function delete()
   {
      
   }
   
   public function all_from_presupuesto($idp)
   {
      $plist = array();
      
      $data = $this->db->select("SELECT * FROM ".$this->table_name.
              " WHERE idpresupuesto = ".$this->var2str($idp)." ORDER BY referencia ASC;");
      if($data)
      {
         foreach($data as $d)
            $plist[] = new linea_presupuesto_cliente($d);
      }
      
      return $plist;
   }
}
