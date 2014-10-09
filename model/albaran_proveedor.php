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
require_model('ejercicio.php');
require_model('factura_proveedor.php');
require_model('linea_albaran_proveedor.php');
require_model('secuencia.php');

/**
 * Albarán de proveedor (boceto de factura o factura preliminar)
 */
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
         if( $this->str2bool($a['ptefactura']) )
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
         
         $this->hora = '00:00:00';
         if( !is_null($a['hora']) )
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
      new serie();
      
      return '';
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
         return 'index.php?page=compras_albaranes';
      else
         return 'index.php?page=compras_albaran&id='.$this->idalbaran;
   }
   
   public function factura_url()
   {
      if( $this->ptefactura )
         return '#';
      else
      {
         if( is_null($this->idfactura) )
            return 'index.php?page=compras_facturas';
         else
            return 'index.php?page=compras_factura&id='.$this->idfactura;
      }
   }
   
   public function agente_url()
   {
      if( is_null($this->codagente) )
         return "index.php?page=admin_agentes";
      else
         return "index.php?page=admin_agente&cod=".$this->codagente;
   }
   
   public function proveedor_url()
   {
      if( is_null($this->codproveedor) )
         return "index.php?page=compras_proveedores";
      else
         return "index.php?page=compras_proveedor&cod=".$this->codproveedor;
   }
   
   public function get_lineas()
   {
      $linea = new linea_albaran_proveedor();
      return $linea->all_from_albaran($this->idalbaran);
   }
   
   public function get($id)
   {
      $albaran = $this->db->select("SELECT * FROM ".$this->table_name." WHERE idalbaran = ".$this->var2str($id).";");
      if($albaran)
         return new albaran_proveedor($albaran[0]);
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->idalbaran) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE idalbaran = ".$this->var2str($this->idalbaran).";");
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
         $numero = $this->db->select("SELECT MAX(".$this->db->sql_to_int('numero').") as num
            FROM ".$this->table_name." WHERE codejercicio = ".$this->var2str($this->codejercicio).
            " AND codserie = ".$this->var2str($this->codserie).";");
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
      
      $this->codigo = $this->codejercicio.sprintf('%02s', $this->codserie).sprintf('%06s', $this->numero);
   }
   
   public function test()
   {
      $this->observaciones = $this->no_html($this->observaciones);
      $this->totaleuros = $this->total * $this->tasaconv;
      
      if( $this->floatcmp($this->total, $this->neto+$this->totaliva-$this->totalirpf+$this->totalrecargo, FS_NF0, TRUE) )
      {
         return TRUE;
      }
      else
      {
         $this->new_error_msg("Error grave: El total está mal calculado. ¡Avisa al informático!");
         return FALSE;
      }
   }
   
   public function full_test($duplicados = TRUE)
   {
      $status = TRUE;
      
      /// comprobamos las líneas
      $neto = 0;
      $iva = 0;
      $irpf = 0;
      $recargo = 0;
      foreach($this->get_lineas() as $l)
      {
         if( !$l->test() )
            $status = FALSE;
         
         $neto += $l->pvptotal;
         $iva += $l->pvptotal * $l->iva / 100;
         $irpf += $l->pvptotal * $l->irpf/ 100;
         $recargo += $l->pvptotal * $l->recargo/ 100;
      }
      
      $neto = round($neto, FS_NF0);
      $iva = round($iva, FS_NF0);
      $irpf = round($irpf, FS_NF0);
      $recargo = round($recargo, FS_NF0);
      $total = $neto + $iva - $irpf + $recargo;
      
      if( !$this->floatcmp($this->neto, $neto, FS_NF0, TRUE) )
      {
         $this->new_error_msg("Valor neto de ".FS_ALBARAN." incorrecto. Valor correcto: ".$neto);
         $status = FALSE;
      }
      else if( !$this->floatcmp($this->totaliva, $iva, FS_NF0, TRUE) )
      {
         $this->new_error_msg("Valor totaliva de ".FS_ALBARAN." incorrecto. Valor correcto: ".$iva);
         $status = FALSE;
      }
      else if( !$this->floatcmp($this->totalirpf, $irpf, FS_NF0, TRUE) )
      {
         $this->new_error_msg("Valor totalirpf de ".FS_ALBARAN." incorrecto. Valor correcto: ".$irpf);
         $status = FALSE;
      }
      else if( !$this->floatcmp($this->totalrecargo, $recargo, FS_NF0, TRUE) )
      {
         $this->new_error_msg("Valor totalrecargo de ".FS_ALBARAN." incorrecto. Valor correcto: ".$recargo);
         $status = FALSE;
      }
      else if( !$this->floatcmp($this->total, $total, FS_NF0, TRUE) )
      {
         $this->new_error_msg("Valor total de ".FS_ALBARAN." incorrecto. Valor correcto: ".$total);
         $status = FALSE;
      }
      else if( !$this->floatcmp($this->totaleuros, $this->total * $this->tasaconv, FS_NF0, TRUE) )
      {
         $this->new_error_msg("Valor totaleuros de ".FS_ALBARAN." incorrecto.
            Valor correcto: ".round($this->total * $this->tasaconv, FS_NF0));
         $status = FALSE;
      }
      
      /// comprobamos las facturas asociadas
      $linea_factura = new linea_factura_proveedor();
      $facturas = $linea_factura->facturas_from_albaran( $this->idalbaran );
      if($facturas)
      {
         if( count($facturas) > 1 )
         {
            $msg = "Este ".FS_ALBARAN." esta asociado a las siguientes facturas (y no debería):";
            foreach($facturas as $f)
               $msg .= " <a href='".$f->url()."'>".$f->codigo."</a>";
            $this->new_error_msg($msg);
            $status = FALSE;
         }
         else if($facturas[0]->idfactura != $this->idfactura)
         {
            $this->new_error_msg("Este ".FS_ALBARAN." esta asociado a una <a href='".$this->factura_url().
                    "'>factura</a> incorrecta. La correcta es <a href='".$facturas[0]->url()."'>esta</a>.");
            $status = FALSE;
         }
      }
      else if( isset($this->idfactura) )
      {
         $this->new_error_msg("Este ".FS_ALBARAN." esta asociado a una <a href='".$this->factura_url()."'>factura</a> incorrecta.");
         $status = FALSE;
      }
      
      if($status AND $duplicados)
      {
         /// comprobamos si es un duplicado
         $albaranes = $this->db->select("SELECT * FROM ".$this->table_name." WHERE fecha = ".$this->var2str($this->fecha)."
            AND codproveedor = ".$this->var2str($this->codproveedor)." AND total = ".$this->var2str($this->total)."
            AND codagente = ".$this->var2str($this->codagente)." AND numproveedor = ".$this->var2str($this->numproveedor)."
            AND observaciones = ".$this->var2str($this->observaciones)." AND idalbaran != ".$this->var2str($this->idalbaran).";");
         if($albaranes)
         {
            foreach($albaranes as $alb)
            {
               /// comprobamos las líneas
               $aux = $this->db->select("SELECT referencia FROM lineasalbaranesprov WHERE
                  idalbaran = ".$this->var2str($this->idalbaran)."
                  AND referencia NOT IN (SELECT referencia FROM lineasalbaranesprov
                  WHERE idalbaran = ".$this->var2str($alb['idalbaran']).");");
               if( !$aux )
               {
                  $this->new_error_msg("Este ".FS_ALBARAN." es un posible duplicado de
                     <a href='index.php?page=compras_albaran&id=".$alb['idalbaran']."'>este otro</a>.
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
         if( $this->exists() )
         {
            $sql = "UPDATE ".$this->table_name." SET idfactura = ".$this->var2str($this->idfactura).",
               codigo = ".$this->var2str($this->codigo).", numero = ".$this->var2str($this->numero).",
               numproveedor = ".$this->var2str($this->numproveedor).",
               codejercicio = ".$this->var2str($this->codejercicio).",
               codserie = ".$this->var2str($this->codserie).", coddivisa = ".$this->var2str($this->coddivisa).",
               codpago = ".$this->var2str($this->codpago).", codagente = ".$this->var2str($this->codagente).",
               codalmacen = ".$this->var2str($this->codalmacen).", fecha = ".$this->var2str($this->fecha).",
               codproveedor = ".$this->var2str($this->codproveedor).", nombre = ".$this->var2str($this->nombre).",
               cifnif = ".$this->var2str($this->cifnif).", neto = ".$this->var2str($this->neto).",
               total = ".$this->var2str($this->total).", totaliva = ".$this->var2str($this->totaliva).",
               totaleuros = ".$this->var2str($this->totaleuros).", irpf = ".$this->var2str($this->irpf).",
               totalirpf = ".$this->var2str($this->totalirpf).", tasaconv = ".$this->var2str($this->tasaconv).",
               recfinanciero = ".$this->var2str($this->recfinanciero).",
               totalrecargo = ".$this->var2str($this->totalrecargo).",
               observaciones = ".$this->var2str($this->observaciones).", hora = ".$this->var2str($this->hora).",
               ptefactura = ".$this->var2str($this->ptefactura).
               " WHERE idalbaran = ".$this->var2str($this->idalbaran).";";
            
            return $this->db->exec($sql);
         }
         else
         {
            $this->new_codigo();
            $sql = "INSERT INTO ".$this->table_name." (codigo,numero,numproveedor,
               codejercicio,codserie,coddivisa,codpago,codagente,codalmacen,fecha,codproveedor,
               nombre,cifnif,neto,total,totaliva,totaleuros,irpf,totalirpf,tasaconv,
               recfinanciero,totalrecargo,observaciones,ptefactura,hora) VALUES
               (".$this->var2str($this->codigo).",
               ".$this->var2str($this->numero).",".$this->var2str($this->numproveedor).",
               ".$this->var2str($this->codejercicio).",".$this->var2str($this->codserie).",
               ".$this->var2str($this->coddivisa).",".$this->var2str($this->codpago).",
               ".$this->var2str($this->codagente).",".$this->var2str($this->codalmacen).",
               ".$this->var2str($this->fecha).",".$this->var2str($this->codproveedor).",
               ".$this->var2str($this->nombre).",".$this->var2str($this->cifnif).",
               ".$this->var2str($this->neto).",".$this->var2str($this->total).",
               ".$this->var2str($this->totaliva).",".$this->var2str($this->totaleuros).",
               ".$this->var2str($this->irpf).",".$this->var2str($this->totalirpf).",
               ".$this->var2str($this->tasaconv).",".$this->var2str($this->recfinanciero).",
               ".$this->var2str($this->totalrecargo).",".$this->var2str($this->observaciones).",
               ".$this->var2str($this->ptefactura).",".$this->var2str($this->hora).");";
            
            if( $this->db->exec($sql) )
            {
               $this->idalbaran = $this->db->lastval();
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
      if( $this->db->exec("DELETE FROM ".$this->table_name." WHERE idalbaran = ".$this->var2str($this->idalbaran).";") )
      {
         if($this->idfactura)
         {
            /**
             * Delegamos la eliminación de la factura en la clase correspondiente,
             * que tendrá que hacer más cosas.
             */
            $factura = new factura_proveedor();
            $factura0 = $factura->get($this->idfactura);
            if($factura0)
            {
               $factura0->delete();
            }
         }
         
         return TRUE;
      }
      else
         return FALSE;
   }
   
   public function all($offset=0)
   {
      $albalist = array();
      $albaranes = $this->db->select_limit("SELECT * FROM ".$this->table_name." ORDER BY fecha DESC, codigo DESC", FS_ITEM_LIMIT, $offset);
      if($albaranes)
      {
         foreach($albaranes as $a)
            $albalist[] = new albaran_proveedor($a);
      }
      return $albalist;
   }
   
   public function all_ptefactura($offset=0, $order='DESC')
   {
      $albalist = array();
      $albaranes = $this->db->select_limit("SELECT * FROM ".$this->table_name.
              " WHERE ptefactura = true ORDER BY fecha ".$order.", codigo ".$order, FS_ITEM_LIMIT, $offset);
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
      $albaranes = $this->db->select_limit("SELECT * FROM ".$this->table_name.
              " WHERE codproveedor = ".$this->var2str($codproveedor).
              " ORDER BY fecha DESC, codigo DESC", FS_ITEM_LIMIT, $offset);
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
      $albaranes = $this->db->select_limit("SELECT * FROM ".$this->table_name.
              " WHERE codagente = ".$this->var2str($codagente).
              " ORDER BY fecha DESC, codigo DESC", FS_ITEM_LIMIT, $offset);
      if($albaranes)
      {
         foreach($albaranes as $a)
            $alblist[] = new albaran_proveedor($a);
      }
      return $alblist;
   }
   
   public function all_desde($desde, $hasta)
   {
      $alblist = array();
      $albaranes = $this->db->select("SELECT * FROM ".$this->table_name.
         " WHERE fecha >= ".$this->var2str($desde)." AND fecha <= ".$this->var2str($hasta).
         " ORDER BY codigo ASC;");
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
         $consulta .= "codigo LIKE '%".$query."%' OR numproveedor LIKE '%".$query."%' OR observaciones LIKE '%".$query."%'
            OR total BETWEEN '".($query-.01)."' AND '".($query+.01)."'";
      }
      else if( preg_match('/^([0-9]{1,2})-([0-9]{1,2})-([0-9]{4})$/i', $query) ) /// es una fecha
      {
         $consulta .= "fecha = ".$this->var2str($query)." OR observaciones LIKE '%".$query."%'";
      }
      else
      {
         $consulta .= "lower(codigo) LIKE '%".$query."%' OR lower(numproveedor) LIKE '%".$query."%' "
                 . "OR lower(observaciones) LIKE '%".str_replace(' ', '%', $query)."%'";
      }
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
         " WHERE codproveedor = ".$this->var2str($codproveedor).
         " AND ptefactura AND fecha BETWEEN ".$this->var2str($desde)." AND ".$this->var2str($hasta).
         " AND codserie = ".$this->var2str($serie)." ORDER BY fecha DESC, codigo DESC");
      if($albaranes)
      {
         foreach($albaranes as $a)
            $albalist[] = new albaran_proveedor($a);
      }
      return $albalist;
   }
   
   public function cron_job()
   {
      /*
       * Marcamos como ptefactura = TRUE todos los albaranes de ejercicios
       * ya cerrados. Así no se podrán modificar ni facturar.
       */
      $ejercicio = new ejercicio();
      foreach($ejercicio->all() as $eje)
      {
         if( !$eje->abierto() )
         {
            $this->db->exec("UPDATE ".$this->table_name." SET ptefactura = FALSE
               WHERE codejercicio = ".$this->var2str($eje->codejercicio).";");
         }
      }
   }
}
