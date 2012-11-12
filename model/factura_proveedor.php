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
   private $factura_url;
   private $albaran_codigo;
   private $albaran_numero;
   private $albaran_url;
   private $articulo_url;
   
   private static $facturas;
   private static $albaranes;
   private static $articulos;

   public function __construct($l=FALSE)
   {
      parent::__construct('lineasfacturasprov');
      
      if( !isset(self::$facturas) )
         self::$facturas = array();
      
      if( !isset(self::$albaranes) )
         self::$albaranes = array();
      
      if( !isset(self::$articulos) )
         self::$articulos = array();
      
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
            $this->factura_url = $f->url();
            $encontrado = TRUE;
            break;
         }
      }
      if( !$encontrado )
      {
         $fac = new factura_proveedor();
         $fac = $fac->get($this->idfactura);
         if($fac)
         {
            $this->codigo = $fac->codigo;
            $this->fecha = $fac->fecha;
            $this->factura_url = $fac->url();
            self::$facturas[] = $fac;
         }
      }
      
      $encontrado = FALSE;
      foreach(self::$albaranes as $a)
      {
         if($a->idalbaran == $this->idalbaran)
         {
            $this->albaran_codigo = $a->codigo;
            if( is_null($a->numproveedor) OR $a->numproveedor == '')
               $this->albaran_numero = $a->numero;
            else
               $this->albaran_numero = $a->numproveedor;
            $this->albaran_url = $a->url();
            $encontrado = TRUE;
            break;
         }
      }
      if( !$encontrado )
      {
         $alb = new albaran_proveedor();
         $alb = $alb->get($this->idalbaran);
         if($alb)
         {
            $this->albaran_codigo = $alb->codigo;
            if( is_null($alb->numproveedor) OR $alb->numproveedor == '')
               $this->albaran_numero = $alb->numero;
            else
               $this->albaran_numero = $alb->numproveedor;
            $this->albaran_url = $alb->url();
            self::$albaranes[] = $alb;
         }
      }
      
      $encontrado = FALSE;
      foreach(self::$articulos as $a)
      {
         if($a->referencia == $this->referencia)
         {
            $this->articulo_url = $a->url();
            $encontrado = TRUE;
            break;
         }
      }
      if( !$encontrado )
      {
         $art = new articulo();
         $art = $art->get($this->referencia);
         if($art)
         {
            $this->articulo_url = $art->url();
            self::$articulos[] = $art;
         }
      }
   }
   
   public function show_pvp()
   {
      return number_format($this->pvpunitario, 2, '.', ' ');
   }
   
   public function show_total()
   {
      return number_format($this->pvptotal, 2, '.', ' ');
   }
   
   public function show_total_iva()
   {
      return number_format($this->pvptotal*(100+$this->iva)/100, 2, '.', ' ');
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
   
   public function url()
   {
      if( !isset($this->factura_url) )
         $this->fill();
      return $this->factura_url;
   }
   
   public function albaran_codigo()
   {
      if( !isset($this->albaran_codigo) )
         $this->fill();
      return $this->albaran_codigo;
   }
   
   public function albaran_url()
   {
      if( !isset($this->albaran_url) )
         $this->fill();
      return $this->albaran_url;
   }
   
   public function albaran_numero()
   {
      if( !isset($this->albaran_numero) )
         $this->fill();
      return $this->albaran_numero;
   }
   
   public function articulo_url()
   {
      if( !isset($this->articulo_url) )
         $this->fill();
      return $this->articulo_url;
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
      $newid = $this->db->select("SELECT nextval('".$this->table_name."_idlinea_seq');");
      if($newid)
         $this->idlinea = intval($newid[0]['nextval']);
   }
   
   public function save()
   {
      if( $this->test() )
      {
         if( $this->exists() )
         {
            $sql = "UPDATE ".$this->table_name." SET pvptotal = ".$this->var2str($this->pvptotal).",
               dtopor = ".$this->var2str($this->dtopor).", recargo = ".$this->var2str($this->recargo).",
               irpf = ".$this->var2str($this->irpf).", pvpsindto = ".$this->var2str($this->pvpsindto).",
               cantidad = ".$this->var2str($this->cantidad).", codimpuesto = ".$this->var2str($this->codimpuesto).",
               pvpunitario = ".$this->var2str($this->pvpunitario).", idfactura = ".$this->var2str($this->idfactura).",
               idalbaran = ".$this->var2str($this->idalbaran).", descripcion = ".$this->var2str($this->descripcion).",
               dtolineal = ".$this->var2str($this->dtolineal).", referencia = ".$this->var2str($this->referencia).",
               iva = ".$this->var2str($this->iva)." WHERE idlinea = ".$this->var2str($this->idlinea).";";
         }
         else
         {
            $this->new_idlinea();
            $sql = "INSERT INTO ".$this->table_name." (pvptotal,dtopor,recargo,irpf,pvpsindto,cantidad,
               codimpuesto,pvpunitario,idlinea,idfactura,idalbaran,descripcion,dtolineal,referencia,iva)
               VALUES (".$this->var2str($this->pvptotal).",".$this->var2str($this->dtopor).",".$this->var2str($this->recargo).",
               ".$this->var2str($this->irpf).",".$this->var2str($this->pvpsindto).",".$this->var2str($this->cantidad).",
               ".$this->var2str($this->codimpuesto).",".$this->var2str($this->pvpunitario).",".$this->var2str($this->idlinea).",
               ".$this->var2str($this->idfactura).",".$this->var2str($this->idalbaran).",".$this->var2str($this->descripcion).",
               ".$this->var2str($this->dtolineal).",".$this->var2str($this->referencia).",".$this->var2str($this->iva).");";
         }
         return $this->db->exec($sql);
      }
      else
         return FALSE;
   }
   
   public function delete()
   {
      return $this->db->exit("DELETE FROM ".$this->table_name." WHERE idlinea = ".$this->var2str($this->idlinea).";");
   }
   
   public function test()
   {
      $status = TRUE;
      
      $this->descripcion = $this->no_html($this->descripcion);
      $total = $this->pvpunitario * $this->cantidad * (100 - $this->dtopor) / 100;
      $totalsindto = $this->pvpunitario * $this->cantidad;
      
      if( abs($this->pvptotal - $total) > .01 )
      {
         $this->new_error_msg("Error en el valor de pvptotal de la línea ".$this->referencia.
            " de la factura. Valor correcto: ".$total);
         $status = FALSE;
      }
      else if( abs($this->pvpsindto - $totalsindto) > .01 )
      {
         $this->new_error_msg("Error en el valor de pvpsindto de la línea ".$this->referencia.
            " de la factura. Valor correcto: ".$totalsindto);
         $status = FALSE;
      }
      
      return $status;
   }
   
   public function all_from_factura($id)
   {
      $linlist = array();
      $lineas = $this->db->select("SELECT * FROM ".$this->table_name."
         WHERE idfactura = ".$this->var2str($id)." ORDER BY idlinea ASC;");
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
      $lineas = $this->db->select_limit("SELECT * FROM ".$this->table_name."
         WHERE referencia = ".$this->var2str($ref)." ORDER BY idalbaran DESC", FS_ITEM_LIMIT, $offset);
      if( $lineas )
      {
         foreach($lineas as $l)
            $linealist[] = new linea_factura_proveedor($l);
      }
      return $linealist;
   }
   
   public function facturas_from_albaran($id)
   {
      $facturalist = array();
      $lineas = $this->db->select("SELECT DISTINCT idfactura FROM ".$this->table_name."
         WHERE idalbaran = ".$this->var2str($id).";");
      if($lineas)
      {
         $factura = new factura_proveedor();
         foreach($lineas as $l)
            $facturalist[] = $factura->get( $l['idfactura'] );
      }
      return $facturalist;
   }
}


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
   
   public function show_neto()
   {
      return number_format($this->neto, 2, '.', ' ');
   }
   
   public function show_iva()
   {
      return number_format($this->iva, 2, '.', ' ');
   }
   
   public function show_totaliva()
   {
      return number_format($this->totaliva, 2, '.', ' ');
   }
   
   public function show_total()
   {
      return number_format($this->totallinea, 2, '.', ' ');
   }
   
   public function exists()
   {
      if( is_null($this->idlinea) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE idlinea = ".$this->var2str($this->idlinea).";");
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE ".$this->table_name." SET idfactura = ".$this->var2str($this->idfactura).",
            neto = ".$this->var2str($this->neto).", codimpuesto = ".$this->var2str($this->codimpuesto).",
            iva = ".$this->var2str($this->iva).", totaliva = ".$this->var2str($this->totaliva).",
            recargo = ".$this->var2str($this->recargo).", totalrecargo = ".$this->var2str($this->totalrecargo).",
            totallinea = ".$this->var2str($this->totallinea)." WHERE idlinea = ".$this->var2str($this->idlinea).";";
      }
      else
      {
         $sql = "INSERT INTO ".$this->table_name." (idfactura,neto,codimpuesto,iva,totaliva,recargo,totalrecargo,totallinea)
            VALUES (".$this->var2str($this->idfactura).",".$this->var2str($this->neto).",".$this->var2str($this->codimpuesto).",
            ".$this->var2str($this->iva).",".$this->var2str($this->totaliva).",".$this->var2str($this->recargo).",
            ".$this->var2str($this->totalrecargo).",".$this->var2str($this->totallinea).");";
      }
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE idlinea = ".$this->var2str($this->idlinea).";");
   }
   
   public function test()
   {
      $status = TRUE;
      
      $totaliva = $this->neto * $this->iva / 100;
      $total = $this->neto * (100 + $this->iva) / 100;
      if( abs($totaliva - $this->totaliva) > .01 )
      {
         $this->new_error_msg("Error en el valor de totaliva de la línea de iva del impuesto ".$this->codimpuesto."
            de la factura. Valor correcto: ".$totaliva);
         $status = FALSE;
      }
      else if( abs($total - $this->totallinea) > .01 )
      {
         $this->new_error_msg("Error en el valor de totallinea de la línea de iva del impuesto ".$this->codimpuesto."
            de la factura. Valor correcto: ".$total);
         $status = FALSE;
      }
      
      return $status;
   }
   
   public function all_from_factura($id)
   {
      $linealist = array();
      $lineas = $this->db->select("SELECT * FROM ".$this->table_name." WHERE idfactura = ".$this->var2str($id).";");
      if($lineas)
      {
         foreach($lineas as $l)
            $linealist[] = new linea_iva_factura_proveedor($l);
      }
      return $linealist;
   }
}


class factura_proveedor extends fs_model
{
   public $automatica;
   public $cifnif;
   public $codalmacen;
   public $coddivisa;
   public $codejercicio;
   public $codigo;
   public $codigorect;
   public $codpago;
   public $codproveedor;
   public $codserie;
   public $deabono;
   public $fecha;
   public $idasiento;
   public $idfactura;
   public $idfacturarect;
   public $idpagodevol;
   public $irpf;
   public $neto;
   public $nogenerarasiento;
   public $nombre;
   public $numero;
   public $numproveedor;
   public $observaciones;
   public $recfinanciero;
   public $tasaconv;
   public $total;
   public $totaleuros;
   public $totalirpf;
   public $totaliva;
   public $totalrecargo;
   
   public function __construct($f=FALSE)
   {
      parent::__construct('facturasprov');
      if($f)
      {
         $this->automatica = ($f['automatica'] == 't');
         $this->cifnif = $f['cifnif'];
         $this->codalmacen = $f['codalmacen'];
         $this->coddivisa = $f['coddivisa'];
         $this->codejercicio = $f['codejercicio'];
         $this->codigo = $f['codigo'];
         $this->codigorect = $f['codigorect'];
         $this->codpago = $f['codpago'];
         $this->codproveedor = $f['codproveedor'];
         $this->codserie = $f['codserie'];
         $this->deabono = ($f['deabono'] == 't');
         $this->fecha = Date('d-m-Y', strtotime($f['fecha']));
         $this->idasiento = $this->intval($f['idasiento']);
         $this->idfactura = $this->intval($f['idfactura']);
         $this->idfacturarect = $this->intval($f['idfacturarect']);
         $this->idpagodevol = $this->intval($f['idpagodevol']);
         $this->irpf = floatval($f['irpf']);
         $this->neto = floatval($f['neto']);
         $this->nogenerarasiento = ($f['nogenerarasiento'] == 't');
         $this->nombre = $f['nombre'];
         $this->numero = $f['numero'];
         $this->numproveedor = $f['numproveedor'];
         $this->observaciones = $this->no_html($f['observaciones']);
         $this->recfinanciero = floatval($f['recfinanciero']);
         $this->tasaconv = floatval($f['tasaconv']);
         $this->total = floatval($f['total']);
         $this->totaleuros = floatval($f['totaleuros']);
         $this->totalirpf = floatval($f['totalirpf']);
         $this->totaliva = floatval($f['totaliva']);
         $this->totalrecargo = floatval($f['totalrecargo']);
      }
      else
      {
         $this->automatica = FALSE;
         $this->cifnif = NULL;
         $this->codalmacen = NULL;
         $this->coddivisa = NULL;
         $this->codejercicio = NULL;
         $this->codigo = NULL;
         $this->codigorect = NULL;
         $this->codpago = NULL;
         $this->codproveedor = NULL;
         $this->codserie = NULL;
         $this->deabono = FALSE;
         $this->fecha = Date('d-m-Y');
         $this->idasiento = NULL;
         $this->idfactura = NULL;
         $this->idfacturarect = NULL;
         $this->idpagodevol = NULL;
         $this->irpf = 0;
         $this->neto = 0;
         $this->nogenerarasiento = FALSE;
         $this->nombre = NULL;
         $this->numero = NULL;
         $this->numproveedor = NULL;
         $this->observaciones = NULL;
         $this->recfinanciero = 0;
         $this->tasaconv = 1;
         $this->total = 0;
         $this->totaleuros = 0;
         $this->totalirpf = 0;
         $this->totaliva = 0;
         $this->totalrecargo = 0;
      }
   }

   protected function install()
   {
      return '';
   }
   
   public function url()
   {
      if( is_null($this->idfactura) )
         return 'index.php?page=contabilidad_facturas_prov';
      else
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
      return number_format($this->neto, 2, '.', ' ');
   }
   
   public function show_iva()
   {
      return number_format($this->totaliva, 2, '.', ' ');
   }
   
   public function show_total()
   {
      return number_format($this->totaleuros, 2, '.', ' ');
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
      $fact = $this->db->select("SELECT * FROM ".$this->table_name." WHERE idfactura = ".$this->var2str($id).";");
      if($fact)
         return new factura_proveedor($fact[0]);
      else
         return FALSE;
   }
   
   public function get_by_codigo($cod)
   {
      $fact = $this->db->select("SELECT * FROM ".$this->table_name." WHERE codigo = ".$this->var2str($cod).";");
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
   
   public function get_lineas_iva()
   {
      $linea_iva = new linea_iva_factura_proveedor();
      $lineasi = $linea_iva->all_from_factura($this->idfactura);
      /// si no hay lineas de IVA las generamos
      if( !$lineasi )
      {
         $lineas = $this->get_lineas();
         if($lineas)
         {
            foreach($lineas as $l)
            {
               $i = 0;
               $encontrada = FALSE;
               while($i < count($lineasi))
               {
                  if($l->codimpuesto == $lineasi[$i]->codimpuesto)
                  {
                     $encontrada = TRUE;
                     $lineasi[$i]->neto += $l->pvptotal;
                     $lineasi[$i]->totaliva += ($l->pvptotal*$l->iva)/100;
                     $lineasi[$i]->totallinea = $lineasi[$i]->neto + $lineasi[$i]->totaliva;
                  }
                  $i++;
               }
               if( !$encontrada )
               {
                  $lineasi[$i] = new linea_iva_factura_proveedor();
                  $lineasi[$i]->idfactura = $this->idfactura;
                  $lineasi[$i]->codimpuesto = $l->codimpuesto;
                  $lineasi[$i]->iva = $l->iva;
                  $lineasi[$i]->neto = $l->pvptotal;
                  $lineasi[$i]->totaliva = ($l->pvptotal*$l->iva)/100;
                  $lineasi[$i]->totallinea = $lineasi[$i]->neto + $lineasi[$i]->totaliva;
               }
            }
            /// guardamos
            foreach($lineasi as $li)
               $li->save();
         }
      }
      return $lineasi;
   }
   
   public function exists()
   {
      if( is_null($this->idfactura) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name."
            WHERE idfactura = ".$this->var2str($this->idfactura).";");
   }
   
   public function new_idfactura()
   {
      $newid = $this->db->select("SELECT nextval('".$this->table_name."_idfactura_seq');");
      if($newid)
         $this->idfactura = intval($newid[0]['nextval']);
   }
   
   public function new_codigo()
   {
      $sec = new secuencia();
      $sec = $sec->get_by_params2($this->codejercicio, $this->codserie, 'nfacturaprov');
      if($sec)
      {
         $this->numero = $sec->valorout;
         $sec->valorout++;
         $sec->save();
      }
      
      if(!$sec OR $this->numero <= 1)
      {
         $numero = $this->db->select("SELECT MAX(numero::integer) as num FROM ".$this->table_name."
            WHERE codejercicio = ".$this->var2str($this->codejercicio)." AND codserie = ".$this->var2str($this->codserie).";");
         if($numero)
            $this->numero = 1 + intval($numero[0]['num']);
         else
            $this->numero = 1;
         
         if($sec)
         {
            $sec->valorout = 1 + $this->numero;
            $sec->save();
         }
      }
      
      $this->codigo = $this->codejercicio . sprintf('%02s', $this->codserie) . sprintf('%06s', $this->numero);
   }
   
   public function test()
   {
      $this->observaciones = $this->no_html($this->observaciones);
      return TRUE;
   }
   
   public function save()
   {
      if( $this->test() )
      {
         if( $this->exists() )
         {
            $sql = "UPDATE ".$this->table_name." SET deabono = ".$this->var2str($this->deabono).",
               codigo = ".$this->var2str($this->codigo).", automatica = ".$this->var2str($this->automatica).",
               total = ".$this->var2str($this->total).", neto = ".$this->var2str($this->neto).",
               cifnif = ".$this->var2str($this->cifnif).", observaciones = ".$this->var2str($this->observaciones).",
               idpagodevol = ".$this->var2str($this->idpagodevol).", codalmacen = ".$this->var2str($this->codalmacen).",
               irpf = ".$this->var2str($this->irpf).", totaleuros = ".$this->var2str($this->totaleuros).",
               nombre = ".$this->var2str($this->nombre).", codpago = ".$this->var2str($this->codpago).",
               codproveedor = ".$this->var2str($this->codproveedor).", idfacturarect = ".$this->var2str($this->idfacturarect).",
               numproveedor = ".$this->var2str($this->numproveedor).", codigorect = ".$this->var2str($this->codigorect).",
               codserie = ".$this->var2str($this->codserie).", idasiento = ".$this->var2str($this->idasiento).",
               totalirpf = ".$this->var2str($this->totalirpf).", totaliva = ".$this->var2str($this->totaliva).",
               coddivisa = ".$this->var2str($this->coddivisa).", numero = ".$this->var2str($this->numero).",
               codejercicio = ".$this->var2str($this->codejercicio).", tasaconv = ".$this->var2str($this->tasaconv).",
               recfinanciero = ".$this->var2str($this->recfinanciero).", nogenerarasiento = ".$this->var2str($this->nogenerarasiento).",
               totalrecargo = ".$this->var2str($this->totalrecargo).", fecha = ".$this->var2str($this->fecha)."
               WHERE idfactura = ".$this->var2str($this->idfactura).";";
         }
         else
         {
            $this->new_idfactura();
            $this->new_codigo();
            $sql = "INSERT INTO ".$this->table_name." (deabono,codigo,automatica,total,neto,cifnif,observaciones,
               idpagodevol,codalmacen,irpf,totaleuros,nombre,codpago,codproveedor,idfacturarect,numproveedor,
               idfactura,codigorect,codserie,idasiento,totalirpf,totaliva,coddivisa,numero,codejercicio,tasaconv,
               recfinanciero,nogenerarasiento,totalrecargo,fecha) VALUES (".$this->var2str($this->deabono).",
               ".$this->var2str($this->codigo).",".$this->var2str($this->automatica).",".$this->var2str($this->total).",
               ".$this->var2str($this->neto).",".$this->var2str($this->cifnif).",
               ".$this->var2str($this->observaciones).",".$this->var2str($this->idpagodevol).",
               ".$this->var2str($this->codalmacen).",".$this->var2str($this->irpf).",".$this->var2str($this->totaleuros).",
               ".$this->var2str($this->nombre).",".$this->var2str($this->codpago).",".$this->var2str($this->codproveedor).",
               ".$this->var2str($this->idfacturarect).",".$this->var2str($this->numproveedor).",".$this->var2str($this->idfactura).",
               ".$this->var2str($this->codigorect).",".$this->var2str($this->codserie).",".$this->var2str($this->idasiento).",
               ".$this->var2str($this->totalirpf).",".$this->var2str($this->totaliva).",".$this->var2str($this->coddivisa).",
               ".$this->var2str($this->numero).",".$this->var2str($this->codejercicio).",".$this->var2str($this->tasaconv).",
               ".$this->var2str($this->recfinanciero).",".$this->var2str($this->nogenerarasiento).",
               ".$this->var2str($this->totalrecargo).",".$this->var2str($this->fecha).");";
         }
         return $this->db->exec($sql);
      }
      else
         return FALSE;
   }
   
   public function delete()
   {
      if( $this->idasiento )
      {
         $asiento = new asiento();
         $asiento = $asiento->get($this->idasiento);
         if( $asiento )
            $asiento->delete();
      }
      /// desvinculamos el/los albaranes asociados
      $this->db->exec("UPDATE albaranesprov SET idfactura = NULL, ptefactura = TRUE
         WHERE idfactura = ".$this->var2str($this->idfactura).";");
      /// eliminamos
      return $this->db->exec("DELETE FROM ".$this->table_name."
         WHERE idfactura = ".$this->var2str($this->idfactura).";");
   }
   
   public function full_test()
   {
      $status = TRUE;
      $neto = 0;
      $iva = 0;
      $total = 0;
      
      /// comprobamos las líneas
      foreach($this->get_lineas() as $l)
      {
         if( !$l->test() )
            $status = FALSE;
         
         $neto += $l->pvptotal;
         $iva += $l->pvptotal * $l->iva / 100;
         $total += $l->pvptotal * (100 + $l->iva) / 100;
      }
      if( abs($this->neto - $neto) > .01 )
      {
         $this->new_error_msg("Valor neto de la factura incorrecto. Valor correcto: ".$neto);
         $status = FALSE;
      }
      else if( abs($this->totaliva - $iva) > .01 )
      {
         $this->new_error_msg("Valor totaliva de la factura incorrecto. Valor correcto: ".$iva);
         $status = FALSE;
      }
      else if( abs($this->total - $total) > .01 )
      {
         $this->new_error_msg("Valor total de la factura incorrecto. Valor correcto: ".$total);
         $status = FALSE;
      }
      else if( abs($this->totaleuros - $total) > .01 )
      {
         $this->new_error_msg("Valor totaleuros de la factura incorrecto. Valor correcto: ".$total);
         $status = FALSE;
      }
      
      /// comprobamos las líneas de IVA
      $neto = 0;
      $iva = 0;
      $total = 0;
      foreach($this->get_lineas_iva() as $li)
      {
         if( !$li->test() )
            $status = FALSE;
         
         $neto += $li->neto;
         $iva += $li->totaliva;
         $total += $li->totallinea;
      }
      if( abs($this->neto - $neto) > .01 )
      {
         $this->new_error_msg("Valor neto incorrecto en las líneas de IVA. Valor correcto: ".$neto);
         $status = FALSE;
      }
      else if( abs($this->totaliva - $iva) > .01 )
      {
         $this->new_error_msg("Valor totaliva incorrecto en las líneas de IVA. Valor correcto: ".$iva);
         $status = FALSE;
      }
      else if( abs($this->total - $total) > .01 )
      {
         $this->new_error_msg("Valor total incorrecto en las líneas de IVA. Valor correcto: ".$total);
         $status = FALSE;
      }
      
      /// comprobamos el asiento
      if( !is_null($this->idasiento) )
      {
         $asiento = new asiento();
         $asiento = $asiento->get($this->idasiento);
         if( $asiento )
         {
            if($asiento->tipodocumento != 'Factura de proveedor' OR $asiento->documento != $this->codigo)
            {
               $this->new_error_msg("Esta factura apunta a un <a href='".$this->asiento_url()."'>asiento incorrecto</a>.");
               $status = FALSE;
            }
         }
      }
      
      return $status;
   }
   
   public function all($offset=0)
   {
      $faclist = array();
      $facturas = $this->db->select_limit("SELECT * FROM ".$this->table_name."
         ORDER BY fecha DESC, codigo DESC", FS_ITEM_LIMIT, $offset);
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
      $query = strtolower( $this->no_html($query) );
      
      $consulta = "SELECT * FROM ".$this->table_name." WHERE ";
      if( is_numeric($query) )
         $consulta .= "codigo ~~ '%".$query."%' OR numproveedor ~~ '%".$query."%' OR observaciones ~~ '%".$query."%'
            OR total BETWEEN ".($query-.01)." AND ".($query+.01);
      else if( preg_match('/^([0-9]{1,2})-([0-9]{1,2})-([0-9]{4})$/i', $query) )
         $consulta .= "fecha = '".$query."' OR observaciones ~~ '%".$query."%'";
      else
         $consulta .= "lower(codigo) ~~ '%".$query."%' OR lower(observaciones) ~~ '%".str_replace(' ', '%', $query)."%'";
      $consulta .= " ORDER BY fecha DESC, codigo DESC";
      
      $facturas = $this->db->select_limit($consulta, FS_ITEM_LIMIT, $offset);
      if($facturas)
      {
         foreach($facturas as $f)
            $faclist[] = new factura_proveedor($f);
      }
      return $faclist;
   }
}

?>
