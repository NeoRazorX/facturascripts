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
require_model('asiento.php');
require_model('ejercicio.php');
require_model('linea_iva_factura_proveedor.php');
require_model('linea_factura_proveedor.php');
require_model('secuencia.php');
require_model('serie.php');

/**
 * Factura de un proveedor.
 */
class factura_proveedor extends fs_model
{
   public $automatica;
   public $cifnif;
   public $codagente;
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
   public $pagada;
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
         $this->codagente = $f['codagente'];
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
         
         $this->hora = '00:00:00';
         if( !is_null($f['hora']) )
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
         $this->pagada = $this->str2bool($f['pagada']);
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
         $this->codagente = NULL;
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
         $this->pagada = FALSE;
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
      new serie();
      new asiento();
      
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
      if( is_null($this->idfactura) )
         return 'index.php?page=compras_facturas';
      else
         return 'index.php?page=compras_factura&id='.$this->idfactura;
   }
   
   public function asiento_url()
   {
      if( is_null($this->idasiento) )
         return 'index.php?page=contabilidad_asientos';
      else
         return 'index.php?page=contabilidad_asiento&id='.$this->idasiento;
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
                     $lineasi[$i]->totalrecargo += ($l->pvptotal*$l->recargo)/100;
                  }
                  $i++;
               }
               if( !$encontrada )
               {
                  $lineasi[$i] = new linea_iva_factura_proveedor();
                  $lineasi[$i]->idfactura = $this->idfactura;
                  $lineasi[$i]->codimpuesto = $l->codimpuesto;
                  $lineasi[$i]->iva = $l->iva;
                  $lineasi[$i]->recargo = $l->recargo;
                  $lineasi[$i]->neto = $l->pvptotal;
                  $lineasi[$i]->totaliva = ($l->pvptotal*$l->iva)/100;
                  $lineasi[$i]->totalrecargo = ($l->pvptotal*$l->recargo)/100;
               }
            }
            
            /// redondeamos y guardamos
            if( count($lineasi) == 1 )
            {
               $lineasi[0]->neto = round($lineasi[0]->neto, FS_NF0);
               $lineasi[0]->totaliva = round($lineasi[0]->totaliva, FS_NF0);
               $lineasi[0]->totalrecargo = round($lineasi[0]->totalrecargo, FS_NF0);
               $lineasi[0]->totallinea = $lineasi[0]->neto + $lineasi[0]->totaliva + $lineasi[0]->totalrecargo;
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
                  $li->neto = bround($li->neto, FS_NF0);
                  $li->totaliva = bround($li->totaliva, FS_NF0);
                  $li->totallinea = $li->neto + $li->totaliva + $li->totalrecargo;
                  
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
                  usort($lineasi, function($a, $b) {
                     if($a->totallinea == $b->totallinea)
                        return 0;
                     else
                        return ($a->totallinea < $b->totallinea) ? 1 : -1;
                  });
                  
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
                  usort($lineasi, function($a, $b) {
                     if($a->totallinea == $b->totallinea)
                        return 0;
                     else
                        return ($a->totallinea < $b->totallinea) ? 1 : -1;
                  });
                  
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
                  $lineasi[$i]->totallinea = $value->neto + $value->totaliva + $value->totalrecargo;
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
   
   public function exists()
   {
      if( is_null($this->idfactura) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE idfactura = ".$this->var2str($this->idfactura).";");
   }
   
   private function new_codigo()
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
      
      if( $this->floatcmp($this->total, $this->neto+$this->totaliva-$this->totalirpf+$this->totalrecargo, FS_NF0, TRUE) )
      {
         return TRUE;
      }
      else
      {
         $this->new_error_msg("Error grave: El total está mal calculado. ¡Informa del error!");
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
      $irpf = 0;
      $recargo = 0;
      foreach($this->get_lineas() as $l)
      {
         if( !$l->test() )
            $status = FALSE;
         
         $neto += $l->pvptotal;
         $iva += $l->pvptotal * $l->iva / 100;
         $irpf += $l->pvptotal * $l->irpf / 100;
         $recargo += $l->pvptotal * $l->recargo / 100;
      }
      
      $neto = round($neto, FS_NF0);
      $iva = round($iva, FS_NF0);
      $irpf = round($irpf, FS_NF0);
      $recargo = round($recargo, FS_NF0);
      $total = $neto + $iva - $irpf + $recargo;
      
      if( !$this->floatcmp($this->neto, $neto, FS_NF0, TRUE) )
      {
         $this->new_error_msg("Valor neto de la factura incorrecto. Valor correcto: ".$neto);
         $status = FALSE;
      }
      else if( !$this->floatcmp($this->totaliva, $iva, FS_NF0, TRUE) )
      {
         $this->new_error_msg("Valor totaliva de la factura incorrecto. Valor correcto: ".$iva);
         $status = FALSE;
      }
      else if( !$this->floatcmp($this->totalirpf, $irpf, FS_NF0, TRUE) )
      {
         $this->new_error_msg("Valor totalirpf de la factura incorrecto. Valor correcto: ".$irpf);
         $status = FALSE;
      }
      else if( !$this->floatcmp($this->totalrecargo, $recargo, FS_NF0, TRUE) )
      {
         $this->new_error_msg("Valor totalrecargo de la factura incorrecto. Valor correcto: ".$recargo);
         $status = FALSE;
      }
      else if( !$this->floatcmp($this->total, $total, FS_NF0, TRUE) )
      {
         $this->new_error_msg("Valor total de la factura incorrecto. Valor correcto: ".$total);
         $status = FALSE;
      }
      else if( !$this->floatcmp($this->totaleuros, $this->total * $this->tasaconv, FS_NF0, TRUE) )
      {
         $this->new_error_msg("Valor totaleuros de la factura incorrecto.
            Valor correcto: ".round($this->total * $this->tasaconv, FS_NF0));
         $status = FALSE;
      }
      
      /// comprobamos las líneas de IVA
      $this->get_lineas_iva();
      $linea_iva = new linea_iva_factura_proveedor();
      $status = $linea_iva->factura_test($this->idfactura, $neto, $iva, $recargo);
      
      /// comprobamos el asiento
      if( isset($this->idasiento) )
      {
         $asiento = $this->get_asiento();
         if($asiento)
         {
            if($asiento->tipodocumento != 'Factura de proveedor' OR $asiento->documento != $this->codigo)
            {
               $this->new_error_msg("Esta factura apunta a un <a href='".$this->asiento_url()."'>asiento incorrecto</a>.");
               $status = FALSE;
            }
            else
            {
               /// comprobamos las partidas del asiento
               $neto_encontrado = FALSE;
               foreach($asiento->get_partidas() as $p)
               {
                  if( $this->floatcmp3($this->neto, $p->debe, $p->haber, FS_NF0, TRUE) )
                     $neto_encontrado = TRUE;
               }
               
               if( !$neto_encontrado )
               {
                  $this->new_error_msg("No se ha encontrado la partida de neto en el asiento.");
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
                     <a href='index.php?page=compras_factura&id=".$fac['idfactura']."'>esta otra</a>.
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
               cifnif = ".$this->var2str($this->cifnif).", pagada = ".$this->var2str($this->pagada).",
               observaciones = ".$this->var2str($this->observaciones).",
               idpagodevol = ".$this->var2str($this->idpagodevol).", codagente = ".$this->var2str($this->codagente).",
               codalmacen = ".$this->var2str($this->codalmacen).",
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
            
            return $this->db->exec($sql);
         }
         else
         {
            $this->new_codigo();
            $sql = "INSERT INTO ".$this->table_name." (deabono,codigo,automatica,total,neto,cifnif,pagada,observaciones,
               idpagodevol,codagente,codalmacen,irpf,totaleuros,nombre,codpago,codproveedor,idfacturarect,numproveedor,
               codigorect,codserie,idasiento,totalirpf,totaliva,coddivisa,numero,codejercicio,tasaconv,
               recfinanciero,nogenerarasiento,totalrecargo,fecha,hora,editable) VALUES (".$this->var2str($this->deabono).",
               ".$this->var2str($this->codigo).",".$this->var2str($this->automatica).",".$this->var2str($this->total).",
               ".$this->var2str($this->neto).",".$this->var2str($this->cifnif).",".$this->var2str($this->pagada).",
               ".$this->var2str($this->observaciones).",".$this->var2str($this->idpagodevol).",
               ".$this->var2str($this->codagente).",
               ".$this->var2str($this->codalmacen).",".$this->var2str($this->irpf).",".$this->var2str($this->totaleuros).",
               ".$this->var2str($this->nombre).",".$this->var2str($this->codpago).",".$this->var2str($this->codproveedor).",
               ".$this->var2str($this->idfacturarect).",".$this->var2str($this->numproveedor).",
               ".$this->var2str($this->codigorect).",
               ".$this->var2str($this->codserie).",".$this->var2str($this->idasiento).",
               ".$this->var2str($this->totalirpf).",".$this->var2str($this->totaliva).",".$this->var2str($this->coddivisa).",
               ".$this->var2str($this->numero).",".$this->var2str($this->codejercicio).",".$this->var2str($this->tasaconv).",
               ".$this->var2str($this->recfinanciero).",".$this->var2str($this->nogenerarasiento).",
               ".$this->var2str($this->totalrecargo).",".$this->var2str($this->fecha).",
               ".$this->var2str($this->hora).",".$this->var2str($this->editable).");";
            
            if( $this->db->exec($sql) )
            {
               $this->idfactura = $this->db->lastval();
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
      $this->clean_cache();
      
      if( $this->db->exec("DELETE FROM ".$this->table_name." WHERE idfactura = ".$this->var2str($this->idfactura).";") )
      {
         if($this->idasiento)
         {
            /**
             * Delegamos la eliminación del asiento en la clase correspondiente.
             */
            $asiento = new asiento();
            $asi0 = $asiento->get($this->idasiento);
            if($asi0)
            {
               $asi0->delete();
            }
         }
         
         /// desvinculamos el/los albaranes asociados
         $this->db->exec("UPDATE albaranesprov SET idfactura = NULL, ptefactura = TRUE WHERE idfactura = ".$this->var2str($this->idfactura).";");
         
         return TRUE;
      }
      else
         return FALSE;
   }
   
   private function clean_cache()
   {
      $this->cache->delete('factura_proveedor_huecos');
   }
   
   public function all($offset=0, $limit=FS_ITEM_LIMIT)
   {
      $faclist = array();
      $facturas = $this->db->select_limit("SELECT * FROM ".$this->table_name." ORDER BY fecha DESC, codigo DESC", $limit, $offset);
      if($facturas)
      {
         foreach($facturas as $f)
            $faclist[] = new factura_proveedor($f);
      }
      return $faclist;
   }
   
   public function all_sin_pagar($offset=0, $limit=FS_ITEM_LIMIT)
   {
      $faclist = array();
      $facturas = $this->db->select_limit("SELECT * FROM ".$this->table_name.
         " WHERE pagada = false ORDER BY fecha DESC, codigo DESC", $limit, $offset);
      if($facturas)
      {
         foreach($facturas as $f)
            $faclist[] = new factura_proveedor($f);
      }
      return $faclist;
   }
   
   public function all_from_agente($codagente, $offset=0)
   {
      $faclist = array();
      $facturas = $this->db->select_limit("SELECT * FROM ".$this->table_name.
         " WHERE codagente = ".$this->var2str($codagente).
         " ORDER BY fecha DESC, codigo DESC", FS_ITEM_LIMIT, $offset);
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
   
   public function all_desde($desde, $hasta, $serie=FALSE)
   {
      $faclist = array();
      $sql = "SELECT * FROM ".$this->table_name." WHERE fecha >= ".$this->var2str($desde)." AND fecha <= ".$this->var2str($hasta);
      if($serie)
      {
         $sql .= " AND codserie = ".$this->var2str($serie);
      }
      $sql .= " ORDER BY codigo ASC;";
      
      $facturas = $this->db->select($sql);
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
      {
         $consulta .= "codigo LIKE '%".$query."%' OR numproveedor LIKE '%".$query."%' OR observaciones LIKE '%".
            $query."%' OR total BETWEEN ".($query-.01)." AND ".($query+.01);
      }
      else if( preg_match('/^([0-9]{1,2})-([0-9]{1,2})-([0-9]{4})$/i', $query) )
      {
         $consulta .= "fecha = ".$this->var2str($query)." OR observaciones LIKE '%".$query."%'";
      }
      else
      {
         $consulta .= "lower(codigo) LIKE '%".$query."%' OR lower(numproveedor) LIKE '%".$query."%' "
                 . "OR lower(observaciones) LIKE '%".str_replace(' ', '%', $query)."%'";
      }
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
