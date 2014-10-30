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
require_model('albaran_cliente.php');
require_model('factura_cliente.php');

/**
 * Línea de una factura de cliente.
 */
class linea_factura_cliente extends fs_model
{
   public $pvptotal;
   public $dtopor;
   public $recargo;
   public $irpf;
   public $pvpsindto;
   public $cantidad;
   public $codimpuesto;
   public $pvpunitario;
   public $idlinea;
   public $idfactura;
   public $idalbaran;
   public $descripcion;
   public $dtolineal;
   public $referencia;
   public $iva;
   
   private $codigo;
   private $fecha;
   private $albaran_codigo;
   private $albaran_numero;
   private $albaran_fecha;
   
   private static $facturas;
   private static $albaranes;
   
   public function __construct($l=FALSE)
   {
      parent::__construct('lineasfacturascli');
      
      if( !isset(self::$facturas) )
         self::$facturas = array();
      
      if( !isset(self::$albaranes) )
         self::$albaranes = array();
      
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
         $this->codimpuesto = $l['codimpuesto'];
         $this->iva = floatval($l['iva']);
         $this->recargo = floatval($l['recargo']);
         $this->irpf = floatval($l['irpf']);
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
         $this->codimpuesto = NULL;
         $this->iva = 0;
         $this->recargo = 0;
         $this->irpf = 0;
      }
   }
   
   protected function install()
   {
      return '';
   }
   
   private function fill()
   {
      $encontrado = FALSE;
      foreach(self::$facturas as $f)
      {
         if($f->idfactura == $this->idfactura)
         {
            $this->codigo = $f->codigo;
            $this->fecha = $f->fecha;
            $encontrado = TRUE;
            break;
         }
      }
      if( !$encontrado )
      {
         $fac = new factura_cliente();
         $fac = $fac->get($this->idfactura);
         if($fac)
         {
            $this->codigo = $fac->codigo;
            $this->fecha = $fac->fecha;
            self::$facturas[] = $fac;
         }
      }
      
      if( !is_null($this->idalbaran) )
      {
         $encontrado = FALSE;
         foreach(self::$albaranes as $a)
         {
            if($a->idalbaran == $this->idalbaran)
            {
               $this->albaran_codigo = $a->codigo;
               if( is_null($a->numero2) OR $a->numero2 == '')
                  $this->albaran_numero = $a->numero;
               else
                  $this->albaran_numero = $a->numero2;
               $this->albaran_fecha = $a->fecha;
               $encontrado = TRUE;
               break;
            }
         }
         if( !$encontrado )
         {
            $alb = new albaran_cliente();
            $alb = $alb->get($this->idalbaran);
            if($alb)
            {
               $this->albaran_codigo = $alb->codigo;
               if( is_null($alb->numero2) OR $alb->numero2 == '')
                  $this->albaran_numero = $alb->numero;
               else
                  $this->albaran_numero = $alb->numero2;
               $this->albaran_fecha = $alb->fecha;
               self::$albaranes[] = $alb;
            }
         }
      }
   }
   
   public function total_iva()
   {
      return $this->pvptotal*(100+$this->iva-$this->irpf+$this->recargo)/100;
   }
   
   public function show_codigo()
   {
      if( !isset($this->codigo) )
         $this->fill();
      return $this->codigo;
   }
   
   public function show_fecha()
   {
      if( !isset($this->fecha) )
         $this->fill();
      return $this->fecha;
   }
   
   public function show_nombrecliente()
   {
      $nombre = 'desconocido';
      
      foreach(self::$facturas as $a)
      {
         if($a->idfactura == $this->idfactura)
         {
            $nombre = $a->nombrecliente;
            break;
         }
      }
      
      return $nombre;
   }
   
   public function url()
   {
      if( is_null($this->idfactura) )
         return 'index.php?page=ventas_facturas';
      else
         return 'index.php?page=ventas_factura&id='.$this->idfactura;
   }
   
   public function albaran_codigo()
   {
      if( !isset($this->albaran_codigo) )
         $this->fill();
      return $this->albaran_codigo;
   }
   
   public function albaran_url()
   {
      if( is_null($this->idalbaran) )
         return 'index.php?page=ventas_albaranes';
      else
         return 'index.php?page=ventas_albaran&id='.$this->idalbaran;
   }
   
   public function albaran_numero()
   {
      if( !isset($this->albaran_numero) )
         $this->fill();
      return $this->albaran_numero;
   }
   
   public function albaran_fecha()
   {
      if( !isset($this->albaran_fecha) )
         $this->fill();
      return $this->albaran_fecha;
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
   
   public function new_idlinea()
   {
      $newid = $this->db->nextval($this->table_name.'_idlinea_seq');
      if($newid)
         $this->idlinea = intval($newid);
   }
   
   public function test()
   {
      $this->descripcion = $this->no_html($this->descripcion);
      $total = $this->pvpunitario * $this->cantidad * (100 - $this->dtopor) / 100;
      $totalsindto = $this->pvpunitario * $this->cantidad;
      
      if( !$this->floatcmp($this->pvptotal, $total, FS_NF0, TRUE) )
      {
         $this->new_error_msg("Error en el valor de pvptotal de la línea ".$this->referencia.
            " de la factura. Valor correcto: ".$total);
         return FALSE;
      }
      else if( !$this->floatcmp($this->pvpsindto, $totalsindto, FS_NF0, TRUE) )
      {
         $this->new_error_msg("Error en el valor de pvpsindto de la línea ".$this->referencia.
            " de la factura. Valor correcto: ".$totalsindto);
         return FALSE;
      }
      else
         return TRUE;
   }
   
   public function save()
   {
      if( $this->test() )
      {
         if( $this->exists() )
         {
            $sql = "UPDATE ".$this->table_name." SET idfactura = ".$this->var2str($this->idfactura).",
               idalbaran = ".$this->var2str($this->idalbaran).",
               referencia = ".$this->var2str($this->referencia).",
               descripcion = ".$this->var2str($this->descripcion).",
               cantidad = ".$this->var2str($this->cantidad).",
               pvpunitario = ".$this->var2str($this->pvpunitario).",
               pvpsindto = ".$this->var2str($this->pvpsindto).",
               dtopor = ".$this->var2str($this->dtopor).",
               dtolineal = ".$this->var2str($this->dtolineal).",
               pvptotal = ".$this->var2str($this->pvptotal).",
               codimpuesto = ".$this->var2str($this->codimpuesto).",
               iva = ".$this->var2str($this->iva).",
               recargo = ".$this->var2str($this->recargo).",
               irpf = ".$this->var2str($this->irpf).
               " WHERE idlinea = ".$this->var2str($this->idlinea).";";
         }
         else
         {
            $this->new_idlinea();
            $sql = "INSERT INTO ".$this->table_name." (idlinea,idfactura,idalbaran,referencia,
               descripcion,cantidad,pvpunitario,pvpsindto,dtopor,dtolineal,pvptotal,codimpuesto,
               iva,recargo,irpf) VALUES (".$this->var2str($this->idlinea).",
               ".$this->var2str($this->idfactura).",".$this->var2str($this->idalbaran).",
               ".$this->var2str($this->referencia).",".$this->var2str($this->descripcion).",
               ".$this->var2str($this->cantidad).",".$this->var2str($this->pvpunitario).",
               ".$this->var2str($this->pvpsindto).",".$this->var2str($this->dtopor).",
               ".$this->var2str($this->dtolineal).",".$this->var2str($this->pvptotal).",
               ".$this->var2str($this->codimpuesto).",".$this->var2str($this->iva).",
               ".$this->var2str($this->recargo).",".$this->var2str($this->irpf).");";
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
      $linlist = array();
      $lineas = $this->db->select("SELECT * FROM ".$this->table_name.
              " WHERE idfactura = ".$this->var2str($id)." ORDER BY idlinea ASC;");
      if($lineas)
      {
         $aux = array();
         foreach($lineas as $l)
            $aux[] = new linea_factura_cliente($l);
         
         /// ordenamos por fecha del albarán
         $lids = array();
         while( count($linlist) != count($aux) )
         {
            $selection = FALSE;
            foreach($aux as $linea)
            {
               if( !in_array($linea->idlinea, $lids) )
               {
                  if( !$selection )
                     $selection = $linea;
                  else if( $linea->albaran_fecha() < $selection->albaran_fecha() )
                     $selection = $linea;
               }
            }
            if($selection)
            {
               $linlist[] = $selection;
               $lids[] = $selection->idlinea;
            }
         }
      }
      return $linlist;
   }
   
   public function all_from_articulo($ref, $offset=0)
   {
      $linealist = array();
      $lineas = $this->db->select_limit("SELECT * FROM ".$this->table_name.
              " WHERE referencia = ".$this->var2str($ref).
              " ORDER BY idalbaran DESC", FS_ITEM_LIMIT, $offset);
      if( $lineas )
      {
         foreach($lineas as $l)
            $linealist[] = new linea_factura_cliente($l);
      }
      return $linealist;
   }
   
   public function search($query='', $offset=0)
   {
      $linealist = array();
      $query = strtolower( $this->no_html($query) );
      
      $sql = "SELECT * FROM ".$this->table_name." WHERE ";
      if( is_numeric($query) )
      {
         $sql .= "referencia LIKE '%".$query."%' OR descripcion LIKE '%".$query."%'";
      }
      else
      {
         $buscar = str_replace(' ', '%', $query);
         $sql .= "lower(referencia) LIKE '%".$buscar."%' OR lower(descripcion) LIKE '%".$buscar."%'";
      }
      $sql .= " ORDER BY idalbaran DESC, idlinea ASC";
      
      $lineas = $this->db->select_limit($sql, FS_ITEM_LIMIT, $offset);
      if( $lineas )
      {
         foreach($lineas as $l)
            $linealist[] = new linea_factura_cliente($l);
      }
      return $linealist;
   }
   
   public function search_from_cliente($codcliente, $query='', $offset=0)
   {
      $linealist = array();
      $query = strtolower( $this->no_html($query) );
      
      $sql = "SELECT * FROM ".$this->table_name." WHERE idfactura IN
         (SELECT idfactura FROM facturascli WHERE codcliente = ".$this->var2str($codcliente).") AND ";
      if( is_numeric($query) )
      {
         $sql .= "(referencia LIKE '%".$query."%' OR descripcion LIKE '%".$query."%')";
      }
      else
      {
         $buscar = str_replace(' ', '%', $query);
         $sql .= "(lower(referencia) LIKE '%".$buscar."%' OR lower(descripcion) LIKE '%".$buscar."%')";
      }
      $sql .= " ORDER BY idfactura DESC, idlinea ASC";
      
      $lineas = $this->db->select_limit($sql, FS_ITEM_LIMIT, $offset);
      if( $lineas )
      {
         foreach($lineas as $l)
            $linealist[] = new linea_factura_cliente($l);
      }
      return $linealist;
   }
   
   public function search_from_cliente2($codcliente, $ref='', $obs='', $offset=0)
   {
      $linealist = array();
      $ref = strtolower( $this->no_html($ref) );
      
      $sql = "SELECT * FROM ".$this->table_name." WHERE idfactura IN
         (SELECT idfactura FROM facturascli WHERE codcliente = ".$this->var2str($codcliente)."
         AND lower(observaciones) LIKE '".strtolower($obs)."%') AND ";
      if( is_numeric($ref) )
      {
         $sql .= "(referencia LIKE '%".$ref."%' OR descripcion LIKE '%".$ref."%')";
      }
      else
      {
         $buscar = str_replace(' ', '%', $ref);
         $sql .= "(lower(referencia) LIKE '%".$ref."%' OR lower(descripcion) LIKE '%".$ref."%')";
      }
      $sql .= " ORDER BY idfactura DESC, idlinea ASC";
      
      $lineas = $this->db->select_limit($sql, FS_ITEM_LIMIT, $offset);
      if( $lineas )
      {
         foreach($lineas as $l)
            $linealist[] = new linea_factura_cliente($l);
      }
      return $linealist;
   }
   
   public function facturas_from_albaran($id)
   {
      $facturalist = array();
      $lineas = $this->db->select("SELECT DISTINCT idfactura FROM ".$this->table_name.
              " WHERE idalbaran = ".$this->var2str($id).";");
      if($lineas)
      {
         $factura = new factura_cliente();
         foreach($lineas as $l)
            $facturalist[] = $factura->get( $l['idfactura'] );
      }
      return $facturalist;
   }
}
