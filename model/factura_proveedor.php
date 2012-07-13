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
require_once 'model/agente.php';
require_once 'model/albaran_proveedor.php';
require_once 'model/articulo.php';
require_once 'model/asiento.php';
require_once 'model/proveedor.php';
require_once 'model/secuencia.php';

class linea_factura_proveedor extends fs_model
{
   public $idlinea;
   public $idfactura;
   public $idalbaran;
   public $referencia;
   public $descripcion;
   public $cantidad;
   public $pvpunitario;
   public $pvpsindto;
   public $dtopor;
   public $dtolineal;
   public $pvptotal;
   
   public function __construct($l=FALSE)
   {
      parent::__construct('lineasfacturasprov');
      if($l)
      {
         $this->idlinea = $this->intval($l['idlinea']);
         $this->idfactura = $this->intval($l['idfactura']);
         $this->idalbaran = $this->intval($l['idalbaran']);
         $this->referencia = $l['referencia'];
         $this->descripcion = $l['descripcion'];
         $this->cantidad = floatval($l['cantidad']);
         $this->pvpunitario = floatval($l['pvpunitario']);
         $this->pvpsindto = floatval($l['pvpsindto']);
         $this->dtopor = floatval($l['dtopor']);
         $this->dtolineal = floatval($l['dtolineal']);
         $this->pvptotal = floatval($l['pvptotal']);
      }
      else
      {
         $this->idlinea = NULL;
         $this->idfactura = NULL;
         $this->idalbaran = NULL;
         $this->referencia = '';
         $this->descripcion = '';
         $this->cantidad = 0;
         $this->pvpunitario = 0;
         $this->pvpsindto = 0;
         $this->dtopor = 0;
         $this->dtolineal = 0;
         $this->pvptotal = 0;
      }
   }
   
   public function show_pvp()
   {
      return number_format($this->pvpunitario, 2, ',', ' ');
   }
   
   public function show_total()
   {
      return number_format($this->pvptotal, 2, ',', ' ');
   }
   
   public function url()
   {
      $fac = new factura_proveedor();
      $fac = $fac->get($this->idfactura);
      return $fac->url();
   }
   
   public function albaran_url()
   {
      $alb = new albaran_proveedor();
      $alb = $alb->get($this->idalbaran);
      return $alb->url();
   }
   
   public function articulo_url()
   {
      $art = new articulo();
      $art = $art->get($this->referencia);
      return $art->url();
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
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE idlinea = '".$this->idlinea."';");
   }
   
   public function save()
   {
      ;
   }
   
   public function delete()
   {
      return $this->db->exit("DELETE FROM ".$this->table_name." WHERE idlinea = '".$this->idlinea."';");
   }
   
   public function all_from_factura($id)
   {
      $linlist = array();
      $lineas = $this->db->select("SELECT * FROM ".$this->table_name." WHERE idfactura = '".$id."';");
      if($lineas)
      {
         foreach($lineas as $l)
            $linlist[] = new linea_factura_proveedor($l);
      }
      return $linlist;
   }
   
   public function all_from_articulo($ref, $offset=0)
   {
      $linealist = array();
      $lineas = $this->db->select_limit("SELECT * FROM ".$this->table_name." WHERE referencia = '".$ref."' ORDER BY idalbaran DESC",
              FS_ITEM_LIMIT, $offset);
      if( $lineas )
      {
         foreach($lineas as $l)
            $linealist[] = new linea_factura_proveedor($l);
      }
      return $linealist;
   }
}


class factura_proveedor extends fs_model
{
   public $idfactura;
   public $idasiento;
   public $codigo;
   public $numero;
   public $numproveedor;
   public $codejercicio;
   public $codserie;
   public $fecha;
   public $codproveedor;
   public $nombre;
   public $cifnif;
   public $neto;
   public $totaliva;
   public $total;
   public $totaleuros;
   public $observaciones;
   
   public function __construct($f=FALSE)
   {
      parent::__construct('facturasprov');
      if($f)
      {
         $this->idfactura = $this->intval($f['idfactura']);
         $this->idasiento = $this->intval($f['idasiento']);
         $this->codigo = $f['codigo'];
         $this->numero = $f['numero'];
         $this->numproveedor = $f['numproveedor'];
         $this->codejercicio = $f['codejercicio'];
         $this->codserie = $f['codserie'];
         $this->fecha = Date('d-m-Y', strtotime($f['fecha']));
         $this->codproveedor = $f['codproveedor'];
         $this->nombre = $f['nombre'];
         $this->cifnif = $f['cifnif'];
         $this->neto = floatval($f['neto']);
         $this->totaliva = floatval($f['totaliva']);
         $this->total = floatval($f['total']);
         $this->totaleuros = floatval($f['totaleuros']);
         $this->observaciones = $f['observaciones'];
      }
      else
      {
         $this->idfactura = NULL;
         $this->idasiento = NULL;
         $this->codigo = '';
         $this->numero = '';
         $this->numproveedor = '';
         $this->codejercicio = NULL;
         $this->codserie = NULL;
         $this->fecha = Date('d-m-Y');
         $this->codproveedor = NULL;
         $this->nombre = '';
         $this->cifnif = '';
         $this->neto = 0;
         $this->totaliva = 0;
         $this->total = 0;
         $this->totaleuros = 0;
         $this->observaciones = '';
      }
   }
   
   public function url()
   {
      return 'index.php?page=contabilidad_factura_prov&id='.$this->idfactura;
   }
   
   public function asiento_url()
   {
      $asiento = new asiento();
      $asiento = $asiento->get($this->idasiento);
      return $asiento->url();
   }
   
   public function proveedor_url()
   {
      $pro = new proveedor();
      $pro = $pro->get($this->codproveedor);
      return $pro->url();
   }
   
   public function show_neto()
   {
      return number_format($this->neto, 2, ',', ' ');
   }
   
   public function show_iva()
   {
      return number_format($this->totaliva, 2, ',', ' ');
   }
   
   public function show_total()
   {
      return number_format($this->totaleuros, 2, ',', ' ');
   }
   
   public function observaciones_resume()
   {
      if($this->observaciones == '')
         return '-';
      else if( strlen($this->observaciones) < 60 )
         return $this->observaciones;
      else
         return substr($this->observaciones, 0, 50).'...';
   }
   
   public function get($id)
   {
      $fact = $this->db->select("SELECT * FROM ".$this->table_name." WHERE idfactura = '".$id."';");
      if($fact)
         return new factura_proveedor($fact[0]);
      else
         return FALSE;
   }
   
   public function get_by_codigo($cod)
   {
      $fact = $this->db->select("SELECT * FROM ".$this->table_name." WHERE codigo = '".$cod."';");
      if($fact)
         return new factura_proveedor($fact[0]);
      else
         return FALSE;
   }
   
   public function get_lineas()
   {
      $linea = new linea_factura_proveedor();
      return $linea->all_from_factura($this->idfactura);
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
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE idfactura = '".$this->idfactura."';");
   }
      
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE ".$this->table_name." SET idasiento = ".$this->var2str($this->idasiento).",
            codigo = ".$this->var2str($this->codigo).", numero = ".$this->var2str($this->numero).",
            numproveedor = ".$this->var2str($this->numproveedor).", codejercicio = ".$this->var2str($this->codejercicio).",
            codserie = ".$this->var2str($this->codserie).", fecha = ".$this->var2str($this->fecha).",
            codproveedor = ".$this->var2str($this->codproveedor).", nombre = ".$this->var2str($this->nombre).",
            cifnif = ".$this->var2str($this->cifnif).", neto = ".$this->var2str($this->neto).",
            totaliva = ".$this->var2str($this->totaliva).", total = ".$this->var2str($this->total).",
            totaleuros = ".$this->var2str($this->totaleuros).", observaciones = ".$this->var2str($this->observaciones)."
            WHERE idfactura = '".$this->idfactura."';";
      }
      else
      {
         $sql = "INSERT INTO ".$this->table_name." (idfactura,idasiento,codigo,numero,numproveedor,codejercicio,
            codserie,fecha,codproveedor,nombre,cifnif,neto,totaliva,total,totaleuros,observaciones) VALUES
            (".$this->var2str($this->idfactura).",".$this->var2str($this->idasiento).",".$this->var2str($this->codigo).",
            ".$this->var2str($this->numero).",".$this->var2str($this->numproveedor).",".$this->var2str($this->codejercicio).",
            ".$this->var2str($this->codserie).",".$this->var2str($this->fecha).",".$this->var2str($this->codproveedor).",
            ".$this->var2str($this->nombre).",".$this->var2str($this->cifnif).",".$this->var2str($this->neto).",
            ".$this->var2str($this->totaliva).",".$this->var2str($this->total).",".$this->var2str($this->totaleuros).",
            ".$this->var2str($this->observaciones).");";
      }
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE idfactura = '".$this->idfactura."';");
   }
   
   public function all($offset=0)
   {
      $faclist = array();
      $facturas = $this->db->select_limit("SELECT * FROM ".$this->table_name." ORDER BY fecha DESC",
                                          FS_ITEM_LIMIT, $offset);
      if($facturas)
      {
         foreach($facturas as $f)
            $faclist[] = new factura_proveedor($f);
      }
      return $faclist;
   }
   
   public function search($query, $offset=0)
   {
      $faclist = array();
      $query = strtolower($query);
      $facturas = $this->db->select_limit("SELECT * FROM ".$this->table_name." WHERE codigo ~~ '%".$query."%'
         OR lower(observaciones) ~~ '%".$query."%' ORDER BY fecha DESC", FS_ITEM_LIMIT, $offset);
      if($facturas)
      {
         foreach($facturas as $f)
            $faclist[] = new factura_proveedor($f);
      }
      return $faclist;
   }
}

?>
