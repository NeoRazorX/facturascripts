<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2013  Carlos Garcia Gomez  neorazorx@gmail.com
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
require_once 'model/articulo.php';
require_once 'model/factura_proveedor.php';
require_once 'model/proveedor.php';
require_once 'model/secuencia.php';

class linea_albaran_proveedor extends fs_model
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
   public $irpf;
   public $recargo;
   
   private $codigo;
   private $fecha;
   private $albaran_url;
   private $articulo_url;
   
   private static $albaranes;
   private static $articulos;
   
   public function __construct($l=FALSE)
   {
      parent::__construct('lineasalbaranesprov');
      
      if( !isset(self::$albaranes) )
         self::$albaranes = array();
      
      if( !isset(self::$articulos) )
         self::$articulos = array();
      
      if($l)
      {
         $this->idlinea = $this->intval($l['idlinea']);
         $this->idalbaran = $this->intval($l['idalbaran']);
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
         $this->irpf = floatval($l['irpf']);
         $this->recargo = floatval($l['recargo']);
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
         $this->irpf = 0;
         $this->recargo = 0;
      }
   }

   protected function install()
   {
      return '';
   }
   
   private function fill()
   {
      $encontrado = FALSE;
      foreach(self::$albaranes as $a)
      {
         if($a->idalbaran == $this->idalbaran)
         {
            $this->codigo = $a->codigo;
            $this->fecha = $a->fecha;
            $this->albaran_url = $a->url();
            $encontrado = TRUE;
            break;
         }
      }
      if( !$encontrado )
      {
         $alb = new albaran_proveedor();
         $alb = $alb->get($this->idalbaran);
         if( $alb )
         {
            $this->codigo = $alb->codigo;
            $this->fecha = $alb->fecha;
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
   
   public function show_pvp_iva()
   {
      return number_format($this->pvpunitario*(100+$this->iva)/100, 2, '.', ' ');
   }
   
   public function show_dto()
   {
      return number_format($this->dtopor, 2, '.', ' ');
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
      if( !isset($this->albaran_url) )
         $this->fill();
      return $this->albaran_url;
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
         return false;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE idlinea = ".$this->var2str($this->idlinea).";");
   }
   
   public function new_idlinea()
   {
      $newid = $this->db->nextval($this->table_name.'_idlinea_seq');
      if($newid)
         $this->idlinea = intval($newid);
   }
   
   public function save()
   {
      if( $this->test() )
      {
         if( $this->exists() )
         {
            $sql = "UPDATE ".$this->table_name." SET idalbaran = ".$this->var2str($this->idalbaran).",
               referencia = ".$this->var2str($this->referencia).", descripcion = ".$this->var2str($this->descripcion).",
               cantidad = ".$this->var2str($this->cantidad).", dtopor = ".$this->var2str($this->dtopor).",
               dtolineal = ".$this->var2str($this->dtolineal).", codimpuesto = ".$this->var2str($this->codimpuesto).",
               iva = ".$this->var2str($this->iva).", pvptotal = ".$this->var2str($this->pvptotal).",
               pvpsindto = ".$this->var2str($this->pvpsindto).", pvpunitario = ".$this->var2str($this->pvpunitario).",
               irpf = ".$this->var2str($this->irpf).", recargo = ".$this->var2str($this->recargo)."
               WHERE idlinea = ".$this->var2str($this->idlinea).";";
         }
         else
         {
            $this->new_idlinea();
            $sql = "INSERT INTO ".$this->table_name." (idlinea,idalbaran,referencia,descripcion,cantidad,dtopor,
               dtolineal,codimpuesto,iva,pvptotal,pvpsindto,pvpunitario,irpf,recargo) VALUES (".$this->var2str($this->idlinea).",
               ".$this->var2str($this->idalbaran).",".$this->var2str($this->referencia).",
               ".$this->var2str($this->descripcion).",".$this->var2str($this->cantidad).",
               ".$this->var2str($this->dtopor).",".$this->var2str($this->dtolineal).",".$this->var2str($this->codimpuesto).",
               ".$this->var2str($this->iva).",".$this->var2str($this->pvptotal).",".$this->var2str($this->pvpsindto).",
               ".$this->var2str($this->pvpunitario).",".$this->var2str($this->irpf).",".$this->var2str($this->recargo).");";
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
   
   public function test()
   {
      $status = TRUE;
      
      $this->descripcion = $this->no_html($this->descripcion);
      $total = $this->pvpunitario * $this->cantidad * (100 - $this->dtopor) / 100;
      $totalsindto = $this->pvpunitario * $this->cantidad;
      
      if( abs($this->pvptotal - $total) > .01 )
      {
         $this->new_error_msg("Error en el valor de pvptotal de la línea ".$this->referencia.
            " del albarán. Valor correcto: ".$total);
         $status = FALSE;
      }
      else if( abs($this->pvpsindto - $totalsindto) > .01 )
      {
         $this->new_error_msg("Error en el valor de pvpsindto de la línea ".$this->referencia.
            " del albarán. Valor correcto: ".$totalsindto);
         $status = FALSE;
      }
      
      return $status;
   }
   
   public function all_from_albaran($id)
   {
      $linealist = array();
      $lineas = $this->db->select("SELECT * FROM ".$this->table_name."
         WHERE idalbaran = ".$this->var2str($id)." ORDER BY idlinea ASC;");
      if($lineas)
      {
         foreach($lineas as $l)
            $linealist[] = new linea_albaran_proveedor($l);
      }
      return $linealist;
   }
   
   public function all_from_articulo($ref, $offset=0, $limit=FS_ITEM_LIMIT)
   {
      $linealist = array();
      $lineas = $this->db->select_limit("SELECT * FROM ".$this->table_name."
         WHERE referencia = ".$this->var2str($ref)." ORDER BY idalbaran DESC", $limit, $offset);
      if( $lineas )
      {
         foreach($lineas as $l)
            $linealist[] = new linea_albaran_proveedor($l);
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
         $sql .= "referencia ~~ '%".$query."%' OR descripcion ~~ '%".$query."%'";
      }
      else
      {
         $buscar = str_replace(' ', '%', $query);
         $sql .= "lower(referencia) ~~ '%".$buscar."%' OR lower(descripcion) ~~ '%".$buscar."%'";
      }
      $sql .= " ORDER BY idalbaran DESC, idlinea ASC";
      
      $lineas = $this->db->select_limit($sql, FS_ITEM_LIMIT, $offset);
      if( $lineas )
      {
         foreach($lineas as $l)
            $linealist[] = new linea_albaran_proveedor($l);
      }
      return $linealist;
   }
   
   public function count_by_articulo()
   {
      $num = 0;
      $lineas = $this->db->select("SELECT COUNT(DISTINCT referencia) as total FROM ".$this->table_name.";");
      if($lineas)
         $num = intval($lineas[0]['total']);
      return $num;
   }
   
   public function top_by_articulo()
   {
      $toplist = array();
      $articulo = new articulo();
      $lineas = $this->db->select_limit("SELECT referencia, SUM(cantidad) as compras FROM ".$this->table_name."
         GROUP BY referencia ORDER BY compras DESC", FS_ITEM_LIMIT, 0);
      if($lineas)
      {
         foreach($lineas as $l)
            $toplist[] = array($articulo->get($l['referencia']), intval($l['compras']));
      }
      return $toplist;
   }
}

class albaran_proveedor extends fs_model
{
   public $idalbaran;
   public $idfactura;
   public $codigo;
   public $numero;
   public $numproveedor;
   public $codejercicio;
   public $codserie;
   public $coddivisa;
   public $codpago;
   public $codagente;
   public $codalmacen;
   public $fecha;
   public $hora;
   public $codproveedor;
   public $nombre;
   public $cifnif;
   public $neto;
   public $total;
   public $totaliva;
   public $totaleuros;
   public $irpf;
   public $totalirpf;
   public $tasaconv;
   public $recfinanciero;
   public $totalrecargo;
   public $observaciones;
   public $ptefactura;

   public function __construct($a=FALSE)
   {
      parent::__construct('albaranesprov');
      if($a)
      {
         $this->idalbaran = $this->intval($a['idalbaran']);
         if($a['ptefactura'] == 't')
         {
            $this->ptefactura = TRUE;
            $this->idfactura = NULL;
         }
         else
         {
            $this->ptefactura = FALSE;
            $this->idfactura = $this->intval($a['idfactura']);
         }
         $this->codigo = $a['codigo'];
         $this->numero = $a['numero'];
         $this->numproveedor = $a['numproveedor'];
         $this->codejercicio = $a['codejercicio'];
         $this->codserie = $a['codserie'];
         $this->coddivisa = $a['coddivisa'];
         $this->codpago = $a['codpago'];
         $this->codagente = $a['codagente'];
         $this->codalmacen = $a['codalmacen'];
         $this->fecha = Date('d-m-Y', strtotime($a['fecha']));
         if( is_null($a['hora']) )
            $this->hora = '00:00:00';
         else
            $this->hora = $a['hora'];
         $this->codproveedor = $a['codproveedor'];
         $this->nombre = $a['nombre'];
         $this->cifnif = $a['cifnif'];
         $this->neto = floatval($a['neto']);
         $this->total = floatval($a['total']);
         $this->totaliva = floatval($a['totaliva']);
         $this->totaleuros = floatval($a['totaleuros']);
         $this->irpf = floatval($a['irpf']);
         $this->totalirpf = floatval($a['totalirpf']);
         $this->tasaconv = floatval($a['tasaconv']);
         $this->recfinanciero = floatval($a['recfinanciero']);
         $this->totalrecargo = floatval($a['totalrecargo']);
         $this->observaciones = $this->no_html($a['observaciones']);
      }
      else
      {
         $this->idalbaran = NULL;
         $this->idfactura = NULL;
         $this->codigo = '';
         $this->numero = '';
         $this->numproveedor = '';
         $this->codejercicio = NULL;
         $this->codserie = NULL;
         $this->coddivisa = NULL;
         $this->codpago = NULL;
         $this->codagente = NULL;
         $this->codalmacen = NULL;
         $this->fecha = Date('d-m-Y');
         $this->hora = Date('H:i:s');
         $this->codproveedor = NULL;
         $this->nombre = '';
         $this->cifnif = '';
         $this->neto = 0;
         $this->total = 0;
         $this->totaliva = 0;
         $this->totaleuros = 0;
         $this->irpf = 0;
         $this->totalirpf = 0;
         $this->tasaconv = 1;
         $this->recfinanciero = 0;
         $this->totalrecargo = 0;
         $this->observaciones = '';
         $this->ptefactura = TRUE;
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
   
   public function url()
   {
      if( is_null($this->idalbaran) )
         return 'index.php?page=general_albaranes_prov';
      else
         return 'index.php?page=general_albaran_prov&id='.$this->idalbaran;
   }
   
   public function factura_url()
   {
      if( $this->ptefactura )
         return '#';
      else
      {
         $fac = new factura_proveedor();
         $fac = $fac->get($this->idfactura);
         if($fac)
            return $fac->url();
         else
            return '#';
      }
   }
   
   public function agente_url()
   {
      $agente = new agente();
      $agente = $agente->get($this->codagente);
      return $agente->url();
   }
   
   public function proveedor_url()
   {
      $pro = new proveedor();
      $pro = $pro->get($this->codproveedor);
      return $pro->url();
   }
   
   public function get($id)
   {
      $albaran = $this->db->select("SELECT * FROM ".$this->table_name." WHERE idalbaran = ".$this->var2str($id).";");
      if($albaran)
         return new albaran_proveedor($albaran[0]);
      else
         return FALSE;
   }
   
   public function get_lineas()
   {
      $linea = new linea_albaran_proveedor();
      return $linea->all_from_albaran($this->idalbaran);
   }
   
   public function get_agente()
   {
      $agente = new agente();
      return $agente->get($this->codagente);
   }
   
   public function exists()
   {
      if( is_null($this->idalbaran) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE idalbaran = ".$this->var2str($this->idalbaran).";");
   }
   
   public function new_idalbaran()
   {
      $newid = $this->db->nextval($this->table_name.'_idalbaran_seq');
      if($newid)
         $this->idalbaran = intval($newid);
   }
   
   public function new_codigo()
   {
      $sec = new secuencia();
      $sec = $sec->get_by_params2($this->codejercicio, $this->codserie, 'nalbaranprov');
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
            $sql = "UPDATE ".$this->table_name." SET idfactura = ".$this->var2str($this->idfactura).",
               codigo = ".$this->var2str($this->codigo).", numero = ".$this->var2str($this->numero).",
               numproveedor = ".$this->var2str($this->numproveedor).", codejercicio = ".$this->var2str($this->codejercicio).",
               codserie = ".$this->var2str($this->codserie).", coddivisa = ".$this->var2str($this->coddivisa).",
               codpago = ".$this->var2str($this->codpago).", codagente = ".$this->var2str($this->codagente).",
               codalmacen = ".$this->var2str($this->codalmacen).", fecha = ".$this->var2str($this->fecha).",
               codproveedor = ".$this->var2str($this->codproveedor).", nombre = ".$this->var2str($this->nombre).",
               cifnif = ".$this->var2str($this->cifnif).", neto = ".$this->var2str($this->neto).",
               total = ".$this->var2str($this->total).", totaliva = ".$this->var2str($this->totaliva).",
               totaleuros = ".$this->var2str($this->totaleuros).", irpf = ".$this->var2str($this->irpf).",
               totalirpf = ".$this->var2str($this->totalirpf).", tasaconv = ".$this->var2str($this->tasaconv).",
               recfinanciero = ".$this->var2str($this->recfinanciero).", totalrecargo = ".$this->var2str($this->totalrecargo).",
               observaciones = ".$this->var2str($this->observaciones).", hora = ".$this->var2str($this->hora).",
               ptefactura = ".$this->var2str($this->ptefactura)." WHERE idalbaran = ".$this->var2str($this->idalbaran).";";
         }
         else
         {
            $this->new_idalbaran();
            $this->new_codigo();
            $sql = "INSERT INTO ".$this->table_name." (idalbaran,codigo,numero,numproveedor,codejercicio,codserie,coddivisa,
               codpago,codagente,codalmacen,fecha,codproveedor,nombre,cifnif,neto,total,totaliva,totaleuros,irpf,totalirpf,
               tasaconv,recfinanciero,totalrecargo,observaciones,ptefactura,hora) VALUES (".$this->var2str($this->idalbaran).",
               ".$this->var2str($this->codigo).",".$this->var2str($this->numero).",".$this->var2str($this->numproveedor).",
               ".$this->var2str($this->codejercicio).",".$this->var2str($this->codserie).",".$this->var2str($this->coddivisa).",
               ".$this->var2str($this->codpago).",".$this->var2str($this->codagente).",".$this->var2str($this->codalmacen).",
               ".$this->var2str($this->fecha).",".$this->var2str($this->codproveedor).",".$this->var2str($this->nombre).",
               ".$this->var2str($this->cifnif).",".$this->var2str($this->neto).",".$this->var2str($this->total).",
               ".$this->var2str($this->totaliva).",".$this->var2str($this->totaleuros).",".$this->var2str($this->irpf).",
               ".$this->var2str($this->totalirpf).",".$this->var2str($this->tasaconv).",".$this->var2str($this->recfinanciero).",
               ".$this->var2str($this->totalrecargo).",".$this->var2str($this->observaciones).",
               ".$this->var2str($this->ptefactura).",".$this->var2str($this->hora).");";
         }
         return $this->db->exec($sql);
      }
      else
         return FALSE;
   }
   
   public function delete()
   {
      if($this->idfactura)
      {
         $factura = new factura_proveedor();
         $factura = $factura->get($this->idfactura);
         $factura->delete();
      }
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE idalbaran = ".$this->var2str($this->idalbaran).";");
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
      
      /// comprobamos los totales
      if( abs($this->neto - $neto) > .01 )
      {
         $this->new_error_msg("Valor neto del albarán incorrecto. Valor correcto: ".$neto);
         $status = FALSE;
      }
      else if( abs($this->totaliva - $iva) > .01 )
      {
         $this->new_error_msg("Valor totaliva del albarán incorrecto. Valor correcto: ".$iva);
         $status = FALSE;
      }
      else if( abs($this->total - $total) > .01 )
      {
         $this->new_error_msg("Valor total del albarán incorrecto. Valor correcto: ".$total);
         $status = FALSE;
      }
      else if( abs($this->totaleuros - $total) > .01 )
      {
         $this->new_error_msg("Valor totaleuros del albarán incorrecto. Valor correcto: ".$total);
         $status = FALSE;
      }
      
      /// comprobamos las facturas asociadas
      $linea_factura = new linea_factura_proveedor();
      $facturas = $linea_factura->facturas_from_albaran( $this->idalbaran );
      if($facturas)
      {
         if( count($facturas) > 1 )
         {
            $msg = "Este albarán esta asociado a las siguientes facturas (y no debería):";
            foreach($facturas as $f)
               $msg .= " <a href='".$f->url()."'>".$f->codigo."</a>";
            $this->new_error_msg($msg);
            $status = FALSE;
         }
         else if($facturas[0]->idfactura != $this->idfactura)
         {
            $this->new_error_msg("Este albarán esta asociado a una <a href='".$this->factura_url()."'>factura</a> incorrecta.
                                  La correcta es <a href='".$facturas[0]->url()."'>esta</a>.");
            $status = FALSE;
         }
      }
      else if( !is_null($this->idfactura) )
      {
         $this->new_error_msg("Este albarán esta asociado a una <a href='".$this->factura_url()."'>factura</a> incorrecta.");
         $status = FALSE;
      }
      
      return $status;
   }
   
   public function all($offset=0)
   {
      $albalist = array();
      $albaranes = $this->db->select_limit("SELECT * FROM ".$this->table_name."
         ORDER BY fecha DESC, codigo DESC", FS_ITEM_LIMIT, $offset);
      if($albaranes)
      {
         foreach($albaranes as $a)
            $albalist[] = new albaran_proveedor($a);
      }
      return $albalist;
   }
   
   public function all_from_proveedor($codproveedor, $offset=0)
   {
      $alblist = array();
      $albaranes = $this->db->select_limit("SELECT * FROM ".$this->table_name."
         WHERE codproveedor = ".$this->var2str($codproveedor)."
         ORDER BY fecha DESC, codigo DESC", FS_ITEM_LIMIT, $offset);
      if($albaranes)
      {
         foreach($albaranes as $a)
            $alblist[] = new albaran_proveedor($a);
      }
      return $alblist;
   }
   
   public function all_from_agente($codagente, $offset=0)
   {
      $alblist = array();
      $albaranes = $this->db->select_limit("SELECT * FROM ".$this->table_name."
         WHERE codagente = ".$this->var2str($codagente)."
         ORDER BY fecha DESC, codigo DESC", FS_ITEM_LIMIT, $offset);
      if($albaranes)
      {
         foreach($albaranes as $a)
            $alblist[] = new albaran_proveedor($a);
      }
      return $alblist;
   }
   
   public function search($query, $offset=0)
   {
      $alblist = array();
      $query = strtolower( $this->no_html($query) );
      
      $consulta = "SELECT * FROM ".$this->table_name." WHERE ";
      if( is_numeric($query) )
      {
         $consulta .= "codigo ~~ '%".$query."%' OR numproveedor ~~ '%".$query."%' OR observaciones ~~ '%".$query."%'
            OR total BETWEEN '".($query-.01)."' AND '".($query+.01)."'";
      }
      else if( preg_match('/^([0-9]{1,2})-([0-9]{1,2})-([0-9]{4})$/i', $query) ) /// es una fecha
         $consulta .= "fecha = '".$query."' OR observaciones ~~ '%".$query."%'";
      else
         $consulta .= "lower(codigo) ~~ '%".$query."%' OR lower(observaciones) ~~ '%".str_replace(' ', '%', $query)."%'";
      $consulta .= " ORDER BY fecha DESC, codigo DESC";
      
      $albaranes = $this->db->select_limit($consulta, FS_ITEM_LIMIT, $offset);
      if($albaranes)
      {
         foreach($albaranes as $a)
            $alblist[] = new albaran_proveedor($a);
      }
      return $alblist;
   }
   
   public function search_from_proveedor($codproveedor, $desde, $hasta, $serie)
   {
      $albalist = array();
      $albaranes = $this->db->select("SELECT * FROM ".$this->table_name.
         " WHERE codproveedor = ".$this->var2str($codproveedor)."
         AND ptefactura AND fecha BETWEEN ".$this->var2str($desde)." AND ".$this->var2str($hasta)."
         AND codserie = ".$this->var2str($serie)."
         ORDER BY fecha DESC, codigo DESC");
      if($albaranes)
      {
         foreach($albaranes as $a)
            $albalist[] = new albaran_proveedor($a);
      }
      return $albalist;
   }
}

?>
