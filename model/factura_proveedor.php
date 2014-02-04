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
require_model('agente.php');
require_model('albaran_proveedor.php');
require_model('articulo.php');
require_model('asiento.php');
require_model('ejercicio.php');
require_model('proveedor.php');
require_model('secuencia.php');

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
      return number_format($this->pvpunitario, FS_NF0, FS_NF1, FS_NF2);
   }
   
   public function show_dto()
   {
      return number_format($this->dtopor, FS_NF0, FS_NF1, FS_NF2);
   }
   
   public function show_total()
   {
      return number_format($this->pvptotal, FS_NF0, FS_NF1, FS_NF2);
   }
   
   public function show_total_iva()
   {
      return number_format($this->pvptotal*(100+$this->iva)/100, FS_NF0, FS_NF1, FS_NF2);
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
         return $this->db->select("SELECT * FROM ".$this->table_name.
                 " WHERE idlinea = ".$this->var2str($this->idlinea).";");
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
      
      if( !$this->floatcmp($this->pvptotal, $total, 2, TRUE) )
      {
         $this->new_error_msg("Error en el valor de pvptotal de la línea ".$this->referencia.
            " de la factura. Valor correcto: ".$total);
         return FALSE;
      }
      else if( !$this->floatcmp($this->pvpsindto, $totalsindto, 2, TRUE) )
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
            $sql = "UPDATE ".$this->table_name." SET pvptotal = ".$this->var2str($this->pvptotal).",
               dtopor = ".$this->var2str($this->dtopor).", recargo = ".$this->var2str($this->recargo).",
               irpf = ".$this->var2str($this->irpf).", pvpsindto = ".$this->var2str($this->pvpsindto).",
               cantidad = ".$this->var2str($this->cantidad).",
               codimpuesto = ".$this->var2str($this->codimpuesto).",
               pvpunitario = ".$this->var2str($this->pvpunitario).",
               idfactura = ".$this->var2str($this->idfactura).",
               idalbaran = ".$this->var2str($this->idalbaran).",
               descripcion = ".$this->var2str($this->descripcion).",
               dtolineal = ".$this->var2str($this->dtolineal).",
               referencia = ".$this->var2str($this->referencia).",
               iva = ".$this->var2str($this->iva).
               " WHERE idlinea = ".$this->var2str($this->idlinea).";";
         }
         else
         {
            $this->new_idlinea();
            $sql = "INSERT INTO ".$this->table_name." (pvptotal,dtopor,recargo,irpf,pvpsindto,cantidad,
               codimpuesto,pvpunitario,idlinea,idfactura,idalbaran,descripcion,dtolineal,referencia,iva)
               VALUES (".$this->var2str($this->pvptotal).",".$this->var2str($this->dtopor).",
               ".$this->var2str($this->recargo).",".$this->var2str($this->irpf).",
               ".$this->var2str($this->pvpsindto).",".$this->var2str($this->cantidad).",
               ".$this->var2str($this->codimpuesto).",".$this->var2str($this->pvpunitario).",
               ".$this->var2str($this->idlinea).",".$this->var2str($this->idfactura).",
               ".$this->var2str($this->idalbaran).",".$this->var2str($this->descripcion).",
               ".$this->var2str($this->dtolineal).",".$this->var2str($this->referencia).",
               ".$this->var2str($this->iva).");";
         }
         return $this->db->exec($sql);
      }
      else
         return FALSE;
   }
   
   public function delete()
   {
      return $this->db->exit("DELETE FROM ".$this->table_name.
              " WHERE idlinea = ".$this->var2str($this->idlinea).";");
   }
   
   public function all_from_factura($id)
   {
      $linlist = array();
      $lineas = $this->db->select("SELECT * FROM ".$this->table_name.
              " WHERE idfactura = ".$this->var2str($id)." ORDER BY idlinea ASC;");
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
      $lineas = $this->db->select_limit("SELECT * FROM ".$this->table_name.
              " WHERE referencia = ".$this->var2str($ref).
              " ORDER BY idalbaran DESC", FS_ITEM_LIMIT, $offset);
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
      $lineas = $this->db->select("SELECT DISTINCT idfactura FROM ".$this->table_name.
              " WHERE idalbaran = ".$this->var2str($id).";");
      if($lineas)
      {
         $factura = new factura_proveedor();
         foreach($lineas as $l)
            $facturalist[] = $factura->get( $l['idfactura'] );
      }
      return $facturalist;
   }
}


/*
 * Función para comparar dos linea_iva_factura_proveedor
 * en función de su totallinea
 */
function cmp_linea_iva_fact_pro($a, $b)
{
   if($a->totallinea == $b->totallinea)
      return 0;
   else
      return ($a->totallinea < $b->totallinea) ? 1 : -1;
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
      return number_format($this->neto, FS_NF0, FS_NF1, FS_NF2);
   }
   
   public function show_iva()
   {
      return number_format($this->iva, FS_NF0, FS_NF1, FS_NF2);
   }
   
   public function show_totaliva()
   {
      return number_format($this->totaliva, FS_NF0, FS_NF1, FS_NF2);
   }
   
   public function show_total()
   {
      return number_format($this->totallinea, FS_NF0, FS_NF1, FS_NF2);
   }
   
   public function exists()
   {
      if( is_null($this->idlinea) )
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
      return $this->db->exec("DELETE FROM ".$this->table_name.
              " WHERE idlinea = ".$this->var2str($this->idlinea).";");
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
   public $editable;
   public $fecha;
   public $hora;
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
         $this->editable = $this->str2bool($f['editable']);
         $this->automatica = $this->str2bool($f['automatica']);
         $this->cifnif = $f['cifnif'];
         $this->codalmacen = $f['codalmacen'];
         $this->coddivisa = $f['coddivisa'];
         $this->codejercicio = $f['codejercicio'];
         $this->codigo = $f['codigo'];
         $this->codigorect = $f['codigorect'];
         $this->codpago = $f['codpago'];
         $this->codproveedor = $f['codproveedor'];
         $this->codserie = $f['codserie'];
         $this->deabono = $this->str2bool($f['deabono']);
         $this->fecha = Date('d-m-Y', strtotime($f['fecha']));
         if( is_null($f['hora']) )
            $this->hora = '00:00:00';
         else
            $this->hora = $f['hora'];
         $this->idasiento = $this->intval($f['idasiento']);
         $this->idfactura = $this->intval($f['idfactura']);
         $this->idfacturarect = $this->intval($f['idfacturarect']);
         $this->idpagodevol = $this->intval($f['idpagodevol']);
         $this->irpf = floatval($f['irpf']);
         $this->neto = floatval($f['neto']);
         $this->nogenerarasiento = $this->str2bool($f['nogenerarasiento']);
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
         $this->editable = TRUE;
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
         $this->hora = Date('H:i:s');
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
   
   public function show_neto()
   {
      return number_format($this->neto, FS_NF0, FS_NF1, FS_NF2);
   }
   
   public function show_iva()
   {
      return number_format($this->totaliva, FS_NF0, FS_NF1, FS_NF2);
   }
   
   public function show_total()
   {
      return number_format($this->total, FS_NF0, FS_NF1, FS_NF2);
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
   
   public function url()
   {
      if( is_null($this->idfactura) )
         return 'index.php?page=contabilidad_facturas_prov';
      else
         return 'index.php?page=contabilidad_factura_prov&id='.$this->idfactura;
   }
   
   public function asiento_url()
   {
      $asiento = $this->get_asiento();
      if($asiento)
         return $asiento->url();
      else
         return '#';
   }
   
   public function proveedor_url()
   {
      $pro = new proveedor();
      $pro0 = $pro->get($this->codproveedor);
      if($pro0)
         return $pro0->url();
      else
         return '#';
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
               }
            }
            
            /// redondeamos y guardamos
            if( count($lineasi) == 1 )
            {
               $lineasi[0]->neto = round($lineasi[0]->neto, 2);
               $lineasi[0]->totaliva = round($lineasi[0]->totaliva, 2);
               $lineasi[0]->totallinea = $lineasi[0]->neto + $lineasi[0]->totaliva;
               $lineasi[0]->save();
            }
            else
            {
               /*
                * Como el neto y el iva se redondean en la factura, al dividirlo
                * en líneas de iva podemos encontrarnos con un descuadre que
                * hay que calcular y solucionar.
                */
               $t_neto = 0;
               $t_iva = 0;
               foreach($lineasi as $li)
               {
                  $li->neto = bround($li->neto, 2);
                  $li->totaliva = bround($li->totaliva, 2);
                  $li->totallinea = $li->neto + $li->totaliva;
                  
                  $t_neto += $li->neto;
                  $t_iva += $li->totaliva;
               }
               
               if( !$this->floatcmp($this->neto, $t_neto) )
               {
                  /*
                   * Sumamos o restamos un céntimo a los netos más altos
                   * hasta que desaparezca el descuadre
                   */
                  $diferencia = round( ($this->neto-$t_neto) * 100 );
                  usort($lineasi, 'cmp_linea_iva_fact_pro');
                  foreach($lineasi as $i => $value)
                  {
                     if($diferencia > 0)
                     {
                        $lineasi[$i]->neto += .01;
                        $diferencia--;
                     }
                     else if($diferencia < 0)
                     {
                        $lineasi[$i]->neto -= .01;
                        $diferencia++;
                     }
                     else
                        break;
                  }
               }
               
               if( !$this->floatcmp($this->totaliva, $t_iva) )
               {
                  /*
                   * Sumamos o restamos un céntimo a los netos más altos
                   * hasta que desaparezca el descuadre
                   */
                  $diferencia = round( ($this->totaliva-$t_iva) * 100 );
                  usort($lineasi, 'cmp_linea_iva_fact_pro');
                  foreach($lineasi as $i => $value)
                  {
                     if($diferencia > 0)
                     {
                        $lineasi[$i]->totaliva += .01;
                        $diferencia--;
                     }
                     else if($diferencia < 0)
                     {
                        $lineasi[$i]->totaliva -= .01;
                        $diferencia++;
                     }
                     else
                        break;
                  }
               }
               
               foreach($lineasi as $i => $value)
               {
                  $lineasi[$i]->totallinea = $value->neto + $value->totaliva;
                  $lineasi[$i]->save();
               }
            }
         }
      }
      return $lineasi;
   }
   
   public function get_asiento()
   {
      $asiento = new asiento();
      return $asiento->get($this->idasiento);
   }
   
   public function get($id)
   {
      $fact = $this->db->select("SELECT * FROM ".$this->table_name.
              " WHERE idfactura = ".$this->var2str($id).";");
      if($fact)
         return new factura_proveedor($fact[0]);
      else
         return FALSE;
   }
   
   public function get_by_codigo($cod)
   {
      $fact = $this->db->select("SELECT * FROM ".$this->table_name.
              " WHERE codigo = ".$this->var2str($cod).";");
      if($fact)
         return new factura_proveedor($fact[0]);
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->idfactura) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name.
                 " WHERE idfactura = ".$this->var2str($this->idfactura).";");
   }
   
   public function new_idfactura()
   {
      $newid = $this->db->nextval($this->table_name.'_idfactura_seq');
      if($newid)
         $this->idfactura = intval($newid);
   }
   
   public function new_codigo()
   {
      /// buscamos un hueco
      $encontrado = FALSE;
      $num = 1;
      $fecha = $this->fecha;
      $numeros = $this->db->select("SELECT ".$this->db->sql_to_int('numero')." as numero,fecha
         FROM ".$this->table_name." WHERE codejercicio = ".$this->var2str($this->codejercicio).
         " AND codserie = ".$this->var2str($this->codserie)." ORDER BY numero ASC;");
      if( $numeros )
      {
         foreach($numeros as $n)
         {
            if( intval($n['numero']) != $num )
            {
               $encontrado = TRUE;
               $fecha = Date('d-m-Y', strtotime($n['fecha']));
               break;
            }
            else
               $num++;
         }
      }
      
      if( $encontrado )
      {
         $this->numero = $num;
         $this->fecha = $fecha;
      }
      else
      {
         $this->numero = $num;
         
         /// nos guardamos la secuencia para abanq/eneboo
         $sec = new secuencia();
         $sec = $sec->get_by_params2($this->codejercicio, $this->codserie, 'nfacturaprov');
         if($sec)
         {
            if($sec->valorout <= $this->numero)
            {
               $sec->valorout = 1 + $this->numero;
               $sec->save();
            }
         }
      }
      
      $this->codigo = $this->codejercicio . sprintf('%02s', $this->codserie) . sprintf('%06s', $this->numero);
   }
   
   public function test()
   {
      $this->observaciones = $this->no_html($this->observaciones);
      $this->totaleuros = $this->total * $this->tasaconv;
      
      if( $this->floatcmp($this->total, $this->neto + $this->totaliva, 2, TRUE) )
         return TRUE;
      else
      {
         $this->new_error_msg("Error grave: El total no es la suma del neto y el iva.
            ¡Avisa al informático!");
         return FALSE;
      }
   }
   
   public function full_test($duplicados = TRUE)
   {
      $status = TRUE;
      
      /// comprobamos la fecha de la factura
      $ejercicio = new ejercicio();
      $eje0 = $ejercicio->get($this->codejercicio);
      if($eje0)
      {
         if( strtotime($this->fecha) < strtotime($eje0->fechainicio) OR strtotime($this->fecha) > strtotime($eje0->fechafin) )
         {
            $status = FALSE;
            $this->new_error_msg("La fecha de esta factura está fuera del rango del <a target='_blank' href='".$eje0->url()."'>ejercicio</a>.");
         }
      }
      
      /// comprobamos las líneas
      $neto = 0;
      $iva = 0;
      foreach($this->get_lineas() as $l)
      {
         if( !$l->test() )
            $status = FALSE;
         
         $neto += $l->pvptotal;
         $iva += $l->pvptotal * $l->iva / 100;
      }
      
      if( !$this->floatcmp($this->neto, $neto, 2, TRUE) )
      {
         $this->new_error_msg("Valor neto de la factura incorrecto. Valor correcto: ".$neto);
         $status = FALSE;
      }
      else if( !$this->floatcmp($this->totaliva, $iva, 2, TRUE) )
      {
         $this->new_error_msg("Valor totaliva de la factura incorrecto. Valor correcto: ".$iva);
         $status = FALSE;
      }
      else if( !$this->floatcmp($this->total, $this->neto + $this->totaliva, 2, TRUE) )
      {
         $this->new_error_msg("Valor total de la factura incorrecto. Valor correcto: ".
                 round($this->neto + $this->totaliva, 2));
         $status = FALSE;
      }
      else if( !$this->floatcmp($this->totaleuros, $this->total * $this->tasaconv, 2, TRUE) )
      {
         $this->new_error_msg("Valor totaleuros de la factura incorrecto.
            Valor correcto: ".round($this->total * $this->tasaconv, 2));
         $status = FALSE;
      }
      
      /// comprobamos las líneas de IVA
      $li_neto = 0;
      $li_iva = 0;
      $li_total = 0;
      foreach($this->get_lineas_iva() as $li)
      {
         if( !$li->test() )
            $status = FALSE;
         
         $li_neto += $li->neto;
         $li_iva += $li->totaliva;
         $li_total += $li->totallinea;
      }
      
      if( !$this->floatcmp($this->neto, $li_neto, 2, TRUE) )
      {
         $this->new_error_msg("La suma de los netos de las líneas de IVA debería ser: ".$this->neto);
         $status = FALSE;
      }
      else if( !$this->floatcmp($this->totaliva, $li_iva, 2, TRUE) )
      {
         $this->new_error_msg("La suma de los totales de iva de las líneas de IVA debería ser: ".
                 $this->totaliva);
         $status = FALSE;
      }
      else if( !$this->floatcmp($li_total, $li_neto + $li_iva, 2, TRUE) )
      {
         $this->new_error_msg("La suma de los totales de las líneas de IVA debería ser: ".$li_total);
         $status = FALSE;
      }
      
      /// comprobamos el asiento
      if( isset($this->idasiento) )
      {
         $asiento = $this->get_asiento();
         if( $asiento )
         {
            if($asiento->tipodocumento != 'Factura de proveedor' OR $asiento->documento != $this->codigo)
            {
               $this->new_error_msg("Esta factura apunta a un <a href='".$this->asiento_url().
                       "'>asiento incorrecto</a>.");
               $status = FALSE;
            }
            else
            {
               /// comprobamos las partidas del asiento
               $neto_encontrado = FALSE;
               $a_debe = 0;
               $a_haber = 0;
               foreach($asiento->get_partidas() as $p)
               {
                  if( $this->floatcmp3($this->neto, $p->debe, $p->haber, 2, TRUE) )
                     $neto_encontrado = TRUE;
                  
                  $a_debe += $p->debe;
                  $a_haber += $p->haber;
               }
               $importe = max( array($a_debe, $a_haber) );
               
               if( !$neto_encontrado )
               {
                  $this->new_error_msg("No se ha encontrado la partida de neto en el asiento.");
                  $status = FALSE;
               }
               else if( !$this->floatcmp($this->total, $importe, 2, TRUE) )
               {
                  $this->new_error_msg("El importe del asiento debería ser: ".$this->total);
                  $status = FALSE;
               }
            }
         }
         else
         {
            $this->new_error_msg("Asiento no encontrado.");
            $status = FALSE;
         }
      }
      
      if($status AND $duplicados)
      {
         /// comprobamos si es un duplicado
         $facturas = $this->db->select("SELECT * FROM ".$this->table_name." WHERE fecha = ".$this->var2str($this->fecha)."
            AND codproveedor = ".$this->var2str($this->codproveedor)." AND total = ".$this->var2str($this->total)."
            AND observaciones = ".$this->var2str($this->observaciones)." AND idfactura != ".$this->var2str($this->idfactura).";");
         if($facturas)
         {
            foreach($facturas as $fac)
            {
               /// comprobamos las líneas
               $aux = $this->db->select("SELECT referencia FROM lineasfacturasprov WHERE
                  idfactura = ".$this->var2str($this->idfactura)."
                  AND referencia NOT IN (SELECT referencia FROM lineasfacturasprov
                  WHERE idfactura = ".$this->var2str($fac['idfactura']).");");
               if( !$aux )
               {
                  $this->new_error_msg("Esta factura es un posible duplicado de
                     <a href='index.php?page=contabilidad_factura_pro&id=".$fac['idfactura']."'>esta otra</a>.
                     Si no lo es, para evitar este mensaje, simplemente modifica las observaciones.");
                  $status = FALSE;
               }
            }
         }
      }
      
      return $status;
   }
   
   public function save()
   {
      if( $this->test() )
      {
         $this->clean_cache();
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
               totalrecargo = ".$this->var2str($this->totalrecargo).", fecha = ".$this->var2str($this->fecha).",
               hora = ".$this->var2str($this->hora).", editable = ".$this->var2str($this->editable)."
               WHERE idfactura = ".$this->var2str($this->idfactura).";";
         }
         else
         {
            $this->new_idfactura();
            $this->new_codigo();
            $sql = "INSERT INTO ".$this->table_name." (deabono,codigo,automatica,total,neto,cifnif,observaciones,
               idpagodevol,codalmacen,irpf,totaleuros,nombre,codpago,codproveedor,idfacturarect,numproveedor,
               idfactura,codigorect,codserie,idasiento,totalirpf,totaliva,coddivisa,numero,codejercicio,tasaconv,
               recfinanciero,nogenerarasiento,totalrecargo,fecha,hora,editable) VALUES (".$this->var2str($this->deabono).",
               ".$this->var2str($this->codigo).",".$this->var2str($this->automatica).",".$this->var2str($this->total).",
               ".$this->var2str($this->neto).",".$this->var2str($this->cifnif).",
               ".$this->var2str($this->observaciones).",".$this->var2str($this->idpagodevol).",
               ".$this->var2str($this->codalmacen).",".$this->var2str($this->irpf).",".$this->var2str($this->totaleuros).",
               ".$this->var2str($this->nombre).",".$this->var2str($this->codpago).",".$this->var2str($this->codproveedor).",
               ".$this->var2str($this->idfacturarect).",".$this->var2str($this->numproveedor).",
               ".$this->var2str($this->idfactura).",".$this->var2str($this->codigorect).",
               ".$this->var2str($this->codserie).",".$this->var2str($this->idasiento).",
               ".$this->var2str($this->totalirpf).",".$this->var2str($this->totaliva).",".$this->var2str($this->coddivisa).",
               ".$this->var2str($this->numero).",".$this->var2str($this->codejercicio).",".$this->var2str($this->tasaconv).",
               ".$this->var2str($this->recfinanciero).",".$this->var2str($this->nogenerarasiento).",
               ".$this->var2str($this->totalrecargo).",".$this->var2str($this->fecha).",
               ".$this->var2str($this->hora).",".$this->var2str($this->editable).");";
         }
         return $this->db->exec($sql);
      }
      else
         return FALSE;
   }
   
   public function delete()
   {
      $this->clean_cache();
      
      /// eliminamos el asiento asociado
      $asiento = $this->get_asiento();
      if($asiento)
         $asiento->delete();
      
      /// desvinculamos el/los albaranes asociados
      $this->db->exec("UPDATE albaranesprov SET idfactura = NULL, ptefactura = TRUE
         WHERE idfactura = ".$this->var2str($this->idfactura).";");
      
      /// eliminamos
      return $this->db->exec("DELETE FROM ".$this->table_name.
              " WHERE idfactura = ".$this->var2str($this->idfactura).";");
   }
   
   private function clean_cache()
   {
      $this->cache->delete('factura_proveedor_huecos');
   }
   
   public function all($offset=0, $limit=FS_ITEM_LIMIT)
   {
      $faclist = array();
      $facturas = $this->db->select_limit("SELECT * FROM ".$this->table_name.
         " ORDER BY fecha DESC, codigo DESC", $limit, $offset);
      if($facturas)
      {
         foreach($facturas as $f)
            $faclist[] = new factura_proveedor($f);
      }
      return $faclist;
   }
   
   public function all_from_proveedor($codproveedor, $offset=0)
   {
      $faclist = array();
      $facturas = $this->db->select_limit("SELECT * FROM ".$this->table_name.
         " WHERE codproveedor = ".$this->var2str($codproveedor).
         " ORDER BY fecha DESC, codigo DESC", FS_ITEM_LIMIT, $offset);
      if($facturas)
      {
         foreach($facturas as $f)
            $faclist[] = new factura_proveedor($f);
      }
      return $faclist;
   }
   
   public function all_from_mes($mes)
   {
      $faclist = array();
      $facturas = $this->db->select("SELECT * FROM ".$this->table_name.
         " WHERE to_char(fecha,'yyyy-mm') = ".$this->var2str($mes).
         " ORDER BY codigo ASC;");
      if($facturas)
      {
         foreach($facturas as $f)
            $faclist[] = new factura_proveedor($f);
      }
      return $faclist;
   }
   
   public function all_desde($desde, $hasta)
   {
      $faclist = array();
      $facturas = $this->db->select("SELECT * FROM ".$this->table_name.
         " WHERE fecha >= ".$this->var2str($desde)." AND fecha <= ".$this->var2str($hasta).
         " ORDER BY codigo ASC;");
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
         $consulta .= "codigo LIKE '%".$query."%' OR numproveedor LIKE '%".$query."%' OR observaciones LIKE '%".
            $query."%' OR total BETWEEN ".($query-.01)." AND ".($query+.01);
      else if( preg_match('/^([0-9]{1,2})-([0-9]{1,2})-([0-9]{4})$/i', $query) )
         $consulta .= "fecha = ".$this->var2str($query)." OR observaciones LIKE '%".$query."%'";
      else
         $consulta .= "lower(codigo) LIKE '%".$query."%' OR lower(observaciones) LIKE '%".str_replace(' ', '%', $query)."%'";
      $consulta .= " ORDER BY fecha DESC, codigo DESC";
      
      $facturas = $this->db->select_limit($consulta, FS_ITEM_LIMIT, $offset);
      if($facturas)
      {
         foreach($facturas as $f)
            $faclist[] = new factura_proveedor($f);
      }
      return $faclist;
   }
   
   public function huecos()
   {
      $error = TRUE;
      $huecolist = $this->cache->get_array2('factura_proveedor_huecos', $error);
      if( $error )
      {
         $ejercicio = new ejercicio();
         foreach($ejercicio->all_abiertos() as $eje)
         {
            $codserie = '';
            $num = 1;
            $numeros = $this->db->select("SELECT codserie,".$this->db->sql_to_int('numero')." as numero,fecha
               FROM ".$this->table_name." WHERE codejercicio = ".$this->var2str($eje->codejercicio).
               " ORDER BY codserie ASC, numero ASC;");
            if( $numeros )
            {
               foreach($numeros as $n)
               {
                  if( $n['codserie'] != $codserie )
                  {
                     $codserie = $n['codserie'];
                     $num = 1;
                  }
                  
                  if( intval($n['numero']) != $num )
                  {
                     while($num < intval($n['numero']))
                     {
                        $huecolist[] = array(
                            'codigo' => $eje->codejercicio . sprintf('%02s', $codserie) . sprintf('%06s', $num),
                            'fecha' => Date('d-m-Y', strtotime($n['fecha']))
                        );
                        $num++;
                     }
                  }
                  
                  $num++;
               }
            }
         }
         $this->cache->set('factura_proveedor_huecos', $huecolist, 86400);
      }
      return $huecolist;
   }
   
   public function meses()
   {
      $listam = array();
      $meses = $this->db->select("SELECT DISTINCT to_char(fecha,'yyyy-mm') as mes
         FROM ".$this->table_name." ORDER BY mes DESC;");
      if($meses)
      {
         foreach($meses as $m)
            $listam[] = $m['mes'];
      }
      return $listam;
   }
   
   public function stats_last_days($numdays = 25)
   {
      $stats = array();
      $desde = Date('d-m-Y', strtotime( Date('d-m-Y').'-'.$numdays.' day'));
      
      foreach($this->date_range($desde, Date('d-m-Y'), '+1 day', 'd') as $date)
      {
         $i = intval($date);
         $stats[$i] = array('day' => $i, 'total' => 0);
      }
      
      if( strtolower(FS_DB_TYPE) == 'postgresql')
         $sql_aux = "to_char(fecha,'FMDD')";
      else
         $sql_aux = "DATE_FORMAT(fecha, '%d')";
      
      $data = $this->db->select("SELECT ".$sql_aux." as dia, sum(total) as total
         FROM ".$this->table_name." WHERE fecha >= ".$this->var2str($desde)."
         AND fecha <= ".$this->var2str(Date('d-m-Y'))."
         GROUP BY ".$sql_aux." ORDER BY dia ASC;");
      if($data)
      {
         foreach($data as $d)
         {
            $i = intval($d['dia']);
            $stats[$i] = array(
                'day' => $i,
                'total' => floatval($d['total'])
            );
         }
      }
      return $stats;
   }
   
   public function stats_last_months($num = 11)
   {
      $stats = array();
      $desde = Date('d-m-Y', strtotime( Date('01-m-Y').'-'.$num.' month'));
      
      foreach($this->date_range($desde, Date('d-m-Y'), '+1 month', 'm') as $date)
      {
         $i = intval($date);
         $stats[$i] = array('month' => $i, 'total' => 0);
      }
      
      if( strtolower(FS_DB_TYPE) == 'postgresql')
         $sql_aux = "to_char(fecha,'FMMM')";
      else
         $sql_aux = "DATE_FORMAT(fecha, '%m')";
      
      $data = $this->db->select("SELECT ".$sql_aux." as mes, sum(total) as total
         FROM ".$this->table_name." WHERE fecha >= ".$this->var2str($desde)."
         AND fecha <= ".$this->var2str(Date('d-m-Y'))."
         GROUP BY ".$sql_aux." ORDER BY mes ASC;");
      if($data)
      {
         foreach($data as $d)
         {
            $i = intval($d['mes']);
            $stats[$i] = array(
                'month' => $i,
                'total' => floatval($d['total'])
            );
         }
      }
      return $stats;
   }
   
   public function stats_last_years($num = 4)
   {
      $stats = array();
      $desde = Date('d-m-Y', strtotime( Date('d-m-Y').'-'.$num.' year'));
      
      foreach($this->date_range($desde, Date('d-m-Y'), '+1 year', 'Y') as $date)
      {
         $i = intval($date);
         $stats[$i] = array('year' => $i, 'total' => 0);
      }
      
      if( strtolower(FS_DB_TYPE) == 'postgresql')
         $sql_aux = "to_char(fecha,'FMYYYY')";
      else
         $sql_aux = "DATE_FORMAT(fecha, '%Y')";
      
      $data = $this->db->select("SELECT ".$sql_aux." as ano, sum(total) as total
         FROM ".$this->table_name." WHERE fecha >= ".$this->var2str($desde)."
         AND fecha <= ".$this->var2str(Date('d-m-Y'))."
         GROUP BY ".$sql_aux." ORDER BY ano ASC;");
      if($data)
      {
         foreach($data as $d)
         {
            $i = intval($d['ano']);
            $stats[$i] = array(
                'year' => $i,
                'total' => floatval($d['total'])
            );
         }
      }
      return $stats;
   }
}

?>