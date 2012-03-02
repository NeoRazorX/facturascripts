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

class linea_albaran_cliente extends fs_model
{
   public $idlinea;
   public $idalbaran;
   public $referencia;
   public $descripcion;
   public $cantidad;
   public $dtopor;
   public $dtolineal;
   public $codimpuesto;
   public $iva;
   public $pvptotal;
   public $pvpsindto;
   public $pvpunitario;

   public function __construct($l=FALSE)
   {
      parent::__construct('lineasalbaranescli');
      if($l)
      {
         $this->idlinea = $l['idlinea'];
         $this->idalbaran = $l['idalbaran'];
         $this->referencia = $l['referencia'];
         $this->descripcion = $l['descripcion'];
         $this->cantidad = floatval($l['cantidad']);
         $this->dtopor = floatval($l['dtopor']);
         $this->dtolineal = floatval($l['dtolineal']);
         $this->codimpuesto = $l['codimpuesto'];
         $this->iva = floatval($l['iva']);
         $this->pvptotal = floatval($l['pvptotal']);
         $this->pvpsindto = floatval($l['pvpsindto']);
         $this->pvpunitario = floatval($l['pvpunitario']);
      }
      else
      {
         $this->idlinea = NULL;
         $this->idalbaran = NULL;
         $this->referencia = '';
         $this->descripcion = '';
         $this->cantidad = 0;
         $this->dtopor = 0;
         $this->dtolineal = 0;
         $this->codimpuesto = NULL;
         $this->iva = 0;
         $this->pvptotal = 0;
         $this->pvpsindto = 0;
         $this->pvpunitario = 0;
      }
   }
   
   public function show_pvp()
   {
      return number_format($this->pvpunitario, 2, ',', '.');
   }
   
   public function show_total()
   {
      return number_format($this->pvptotal, 2, ',', '.');
   }

   protected function install()
   {
      return '';
   }
   
   public function exists()
   {
      return $this->db->select("SELECT * FROM ".$this->table_name." WHERE idlinea = '".$this->idlinea."';");
   }
   
   public function save()
   {
      ;
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE idlinea = '".$this->idlinea."';");
   }
   
   public function all_from_albaran($id)
   {
      $linealist = array();
      $lineas = $this->db->select("SELECT * FROM ".$this->table_name." WHERE idalbaran = '".$id."';");
      if($lineas)
      {
         foreach($lineas as $l)
         {
            $lo = new linea_albaran_cliente($l);
            $linealist[] = $lo;
         }
      }
      return $linealist;
   }
}

class albaran_cliente extends fs_model
{
   public $idalbaran;
   public $idfactura;
   public $codigo;
   public $numero;
   public $numero2;
   public $codserie;
   public $codejercicio;
   public $codcliente;
   public $nombrecliente;
   public $cifnif;
   public $fecha;
   public $codagente;
   public $neto;
   public $total;
   public $totaliva;
   public $totaleuros;
   public $observaciones;
   
   public function __construct($a=FALSE)
   {
      parent::__construct('albaranescli');
      if($a)
      {
         $this->idalbaran = intval($a['idalbaran']);
         $this->idfactura = intval($a['idfactura']);
         $this->codigo = $a['codigo'];
         $this->numero = $a['numero'];
         $this->numero2 = $a['numero2'];
         $this->codserie = $a['codserie'];
         $this->codejercicio = $a['codejercicio'];
         $this->codcliente = $a['codcliente'];
         $this->nombrecliente = $a['nombrecliente'];
         $this->cifnif = $a['cifnif'];
         $this->fecha = $a['fecha'];
         $this->codagente = $a['codagente'];
         $this->neto = floatval($a['neto']);
         $this->total = floatval($a['total']);
         $this->totaliva = floatval($a['totaliva']);
         $this->totaleuros = floatval($a['totaleuros']);
         $this->observaciones = $a['observaciones'];
      }
      else
      {
         $this->idalbaran = NULL;
         $this->idfactura = NULL;
         $this->codigo = '';
         $this->numero = '';
         $this->numero2 = '';
         $this->codserie = NULL;
         $this->codejercicio = NULL;
         $this->codcliente = NULL;
         $this->nombrecliente = '';
         $this->cifnif = '';
         $this->fecha = Date('j-n-Y');
         $this->codagente = NULL;
         $this->neto = 0;
         $this->total = 0;
         $this->totaliva = 0;
         $this->totaleuros = 0;
         $this->observaciones = '';
      }
   }
   
   public function show_total()
   {
      return number_format($this->totaleuros, 2, ',', '.');
   }
   
   public function show_fecha()
   {
      return Date('j-n-Y', strtotime($this->fecha));
   }
   
   public function url()
   {
      return 'index.php?page=general_albaran_cli&id='.$this->idalbaran;
   }
   
   protected function install()
   {
      return '';
   }
   
   public function exists()
   {
      return $this->db->select("SELECT * FROM ".$this->table_name." WHERE idalbaran = '".$this->idalbaran."';");
   }
   
   public function save()
   {
      
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE idalbaran = '".$this->idalbaran."';");
   }
   
   public function get($id)
   {
      $albaran = $this->db->select("SELECT * FROM ".$this->table_name." WHERE idalbaran = '".$id."';");
      if($albaran)
         return new albaran_cliente($albaran[0]);
      else
         return FALSE;
   }
   
   public function get_lineas()
   {
      $linea = new linea_albaran_cliente();
      return $linea->all_from_albaran($this->idalbaran);
   }

   public function all($offset=0)
   {
      $albalist = array();
      $albaranes = $this->db->select_limit("SELECT * FROM ".$this->table_name." ORDER BY idalbaran DESC",
                                           FS_ITEM_LIMIT, $offset);
      if($albaranes)
      {
         foreach($albaranes as $a)
         {
            $ao = new albaran_cliente($a);
            $albalist[] = $ao;
         }
      }
      return $albalist;
   }
}

?>
