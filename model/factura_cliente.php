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
require_model('albaran_cliente.php');
require_model('articulo.php');
require_model('asiento.php');
require_model('cliente.php');
require_model('ejercicio.php');
require_model('linea_iva_factura_cliente.php');
require_model('linea_factura_cliente.php');
require_model('secuencia.php');

class factura_cliente extends fs_model
{
   public $idfactura;
   public $idasiento;
   public $idpagodevol;
   public $idfacturarect;
   public $codigo;
   public $numero;
   public $codigorect;
   public $codejercicio;
   public $codserie;
   public $codalmacen;
   public $codpago;
   public $coddivisa;
   public $fecha;
   public $hora;
   public $codcliente;
   public $nombrecliente;
   public $cifnif;
   public $direccion;
   public $ciudad;
   public $provincia;
   public $apartado;
   public $coddir;
   public $codpostal;
   public $codpais;
   public $codagente;
   public $neto;
   public $totaliva;
   public $total;
   public $totaleuros;
   public $irpf;
   public $totalirpf;
   public $porcomision;
   public $tasaconv;
   public $recfinanciero;
   public $totalrecargo;
   public $observaciones;
   public $deabono;
   public $automatica;
   public $editable;
   public $nogenerarasiento;

   public function __construct($f=FALSE)
   {
      parent::__construct('facturascli');
      if($f)
      {
         $this->idfactura = $this->intval($f['idfactura']);
         $this->idasiento = $this->intval($f['idasiento']);
         $this->idpagodevol = $this->intval($f['idpagodevol']);
         $this->idfacturarect = $this->intval($f['idfacturarect']);
         $this->codigo = $f['codigo'];
         $this->numero = $f['numero'];
         $this->codigorect = $f['codigorect'];
         $this->codejercicio = $f['codejercicio'];
         $this->codserie = $f['codserie'];
         $this->codalmacen = $f['codalmacen'];
         $this->codpago = $f['codpago'];
         $this->coddivisa = $f['coddivisa'];
         $this->fecha = Date('d-m-Y', strtotime($f['fecha']));
         if( is_null($f['hora']) )
            $this->hora = '00:00:00';
         else
            $this->hora = $f['hora'];
         $this->codcliente = $f['codcliente'];
         $this->nombrecliente = $f['nombrecliente'];
         $this->cifnif = $f['cifnif'];
         $this->direccion = $f['direccion'];
         $this->ciudad = $f['ciudad'];
         $this->provincia = $f['provincia'];
         $this->apartado = $f['apartado'];
         $this->coddir = $f['coddir'];
         $this->codpostal = $f['codpostal'];
         $this->codpais = $f['codpais'];
         $this->codagente = $f['codagente'];
         $this->neto = floatval($f['neto']);
         $this->totaliva = floatval($f['totaliva']);
         $this->total = floatval($f['total']);
         $this->totaleuros = floatval($f['totaleuros']);
         $this->irpf = floatval($f['irpf']);
         $this->totalirpf = floatval($f['totalirpf']);
         $this->porcomision = floatval($f['porcomision']);
         $this->tasaconv = floatval($f['tasaconv']);
         $this->recfinanciero = floatval($f['recfinanciero']);
         $this->totalrecargo = floatval($f['totalrecargo']);
         $this->observaciones = $this->no_html($f['observaciones']);
         $this->deabono = $this->str2bool($f['deabono']);
         $this->automatica = $this->str2bool($f['automatica']);
         $this->editable = $this->str2bool($f['editable']);
         $this->nogenerarasiento = $this->str2bool($f['nogenerarasiento']);
      }
      else
      {
         $this->idfactura = NULL;
         $this->idasiento = NULL;
         $this->idpagodevol = NULL;
         $this->idfacturarect = NULL;
         $this->codigo = NULL;
         $this->numero = NULL;
         $this->codigorect = NULL;
         $this->codejercicio = NULL;
         $this->codserie = NULL;
         $this->codalmacen = NULL;
         $this->codpago = NULL;
         $this->coddivisa = NULL;
         $this->fecha = Date('d-m-Y');
         $this->hora = Date('H:i:s');
         $this->codcliente = NULL;
         $this->nombrecliente = NULL;
         $this->cifnif = NULL;
         $this->direccion = NULL;
         $this->provincia = NULL;
         $this->ciudad = NULL;
         $this->apartado = NULL;
         $this->coddir = NULL;
         $this->codpostal = NULL;
         $this->codpais = NULL;
         $this->codagente = NULL;
         $this->neto = 0;
         $this->totaliva = 0;
         $this->total = 0;
         $this->totaleuros = 0;
         $this->irpf = 0;
         $this->totalirpf = 0;
         $this->porcomision = 0;
         $this->tasaconv = 1;
         $this->recfinanciero = 0;
         $this->totalrecargo = 0;
         $this->observaciones = NULL;
         $this->deabono = FALSE;
         $this->automatica = FALSE;
         $this->editable = TRUE;
         $this->nogenerarasiento = FALSE;
      }
   }
   
   protected function install()
   {
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
         return 'index.php?page=contabilidad_facturas_cli';
      else
         return 'index.php?page=contabilidad_factura_cli&id='.$this->idfactura;
   }
   
   public function asiento_url()
   {
      $asiento = $this->get_asiento();
      if($asiento)
         return $asiento->url();
      else
         return '#';
   }
   
   public function agente_url()
   {
      $agente = $this->get_agente();
      if($agente)
         return $agente->url();
      else
         return '#';
   }
   
   public function cliente_url()
   {
      $cliente = new cliente();
      $cliente = $cliente->get($this->codcliente);
      if($cliente)
         return $cliente->url();
      else
         return '#';
   }
   
   public function get_agente()
   {
      $agente = new agente();
      return $agente->get($this->codagente);
   }
   
   public function get_asiento()
   {
      $asiento = new asiento();
      return $asiento->get($this->idasiento);
   }
   
   public function get_lineas()
   {
      $linea = new linea_factura_cliente();
      return $linea->all_from_factura($this->idfactura);
   }
   
   public function get_lineas_iva()
   {
      $linea_iva = new linea_iva_factura_cliente();
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
                  $lineasi[$i] = new linea_iva_factura_cliente();
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
                  usort($lineasi, 'cmp_linea_iva_fact_cli');
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
                  usort($lineasi, 'cmp_linea_iva_fact_cli');
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
   
   public function get($id)
   {
      $fact = $this->db->select("SELECT * FROM ".$this->table_name.
              " WHERE idfactura = ".$this->var2str($id).";");
      if($fact)
         return new factura_cliente($fact[0]);
      else
         return FALSE;
   }
   
   public function get_by_codigo($cod)
   {
      $fact = $this->db->select("SELECT * FROM ".$this->table_name.
              " WHERE codigo = ".$this->var2str($cod).";");
      if($fact)
         return new factura_cliente($fact[0]);
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
         $sec = $sec->get_by_params2($this->codejercicio, $this->codserie, 'nfacturacli');
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
      $numero0 = intval($this->numero)-1;
      if( $numero0 > 0 )
      {
         $codigo0 = $this->codejercicio . sprintf('%02s', $this->codserie) . sprintf('%06s', $numero0);
         $fac0 = $this->get_by_codigo($codigo0);
         if($fac0)
         {
            if( strtotime($fac0->fecha) > strtotime($this->fecha) )
            {
               $status = FALSE;
               $this->new_error_msg("La fecha de esta factura es anterior a la fecha de <a href='".
                       $fac0->url()."'>la factura anterior</a>.");
            }
         }
      }
      $numero2 = intval($this->numero)+1;
      $codigo2 = $this->codejercicio . sprintf('%02s', $this->codserie) . sprintf('%06s', $numero2);
      $fac2 = $this->get_by_codigo($codigo2);
      if($fac2)
      {
         if( strtotime($fac2->fecha) < strtotime($this->fecha) )
         {
            $status = FALSE;
            $this->new_error_msg("La fecha de esta factura es posterior a la fecha de <a href='".
                    $fac2->url()."'>la factura siguiente</a>.");
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
            if($asiento->tipodocumento != 'Factura de cliente' OR $asiento->documento != $this->codigo)
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
            AND codcliente = ".$this->var2str($this->codcliente)." AND total = ".$this->var2str($this->total)."
            AND observaciones = ".$this->var2str($this->observaciones)." AND idfactura != ".$this->var2str($this->idfactura).";");
         if($facturas)
         {
            foreach($facturas as $fac)
            {
               /// comprobamos las líneas
               $aux = $this->db->select("SELECT referencia FROM lineasfacturascli WHERE
                  idfactura = ".$this->var2str($this->idfactura)."
                  AND referencia NOT IN (SELECT referencia FROM lineasfacturascli
                  WHERE idfactura = ".$this->var2str($fac['idfactura']).");");
               if( !$aux )
               {
                  $this->new_error_msg("Esta factura es un posible duplicado de
                     <a href='index.php?page=contabilidad_factura_cli&id=".$fac['idfactura']."'>esta otra</a>.
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
            $sql = "UPDATE ".$this->table_name." SET idasiento = ".$this->var2str($this->idasiento).",
               idpagodevol = ".$this->var2str($this->idpagodevol).", idfacturarect = ".$this->var2str($this->idfacturarect).",
               codigo = ".$this->var2str($this->codigo).", numero = ".$this->var2str($this->numero).",
               codigorect = ".$this->var2str($this->codigorect).", codejercicio = ".$this->var2str($this->codejercicio).",
               codserie = ".$this->var2str($this->codserie).", codalmacen = ".$this->var2str($this->codalmacen).",
               codpago = ".$this->var2str($this->codpago).", coddivisa = ".$this->var2str($this->coddivisa).",
               fecha = ".$this->var2str($this->fecha).", codcliente = ".$this->var2str($this->codcliente).",
               nombrecliente = ".$this->var2str($this->nombrecliente).", cifnif = ".$this->var2str($this->cifnif).",
               direccion = ".$this->var2str($this->direccion).", ciudad = ".$this->var2str($this->ciudad).",
               provincia = ".$this->var2str($this->provincia).",
               apartado = ".$this->var2str($this->apartado).", coddir = ".$this->var2str($this->coddir).",
               codpostal = ".$this->var2str($this->codpostal).", codpais = ".$this->var2str($this->codpais).",
               codagente = ".$this->var2str($this->codagente).", neto = ".$this->var2str($this->neto).",
               totaliva = ".$this->var2str($this->totaliva).", total = ".$this->var2str($this->total).",
               totaleuros = ".$this->var2str($this->totaleuros).", irpf = ".$this->var2str($this->irpf).",
               totalirpf = ".$this->var2str($this->totalirpf).", porcomision = ".$this->var2str($this->porcomision).",
               tasaconv = ".$this->var2str($this->tasaconv).", recfinanciero = ".$this->var2str($this->recfinanciero).",
               totalrecargo = ".$this->var2str($this->totalrecargo).", observaciones = ".$this->var2str($this->observaciones).",
               deabono = ".$this->var2str($this->deabono).", automatica = ".$this->var2str($this->automatica).",
               editable = ".$this->var2str($this->editable).", nogenerarasiento = ".$this->var2str($this->nogenerarasiento).",
               hora = ".$this->var2str($this->hora)." WHERE idfactura = ".$this->var2str($this->idfactura).";";
         }
         else
         {
            $this->new_idfactura();
            $this->new_codigo();
            $sql = "INSERT INTO ".$this->table_name." (idfactura,idasiento,idpagodevol,idfacturarect,codigo,numero,
               codigorect,codejercicio,codserie,codalmacen,codpago,coddivisa,fecha,codcliente,nombrecliente,
               cifnif,direccion,ciudad,provincia,apartado,coddir,codpostal,codpais,codagente,neto,totaliva,total,totaleuros,
               irpf,totalirpf,porcomision,tasaconv,recfinanciero,totalrecargo,observaciones,deabono,automatica,editable,
               nogenerarasiento,hora) VALUES (".$this->var2str($this->idfactura).",".$this->var2str($this->idasiento).",
               ".$this->var2str($this->idpagodevol).",".$this->var2str($this->idfacturarect).",".$this->var2str($this->codigo).",
               ".$this->var2str($this->numero).",".$this->var2str($this->codigorect).",".$this->var2str($this->codejercicio).",
               ".$this->var2str($this->codserie).",".$this->var2str($this->codalmacen).",".$this->var2str($this->codpago).",
               ".$this->var2str($this->coddivisa).",".$this->var2str($this->fecha).",".$this->var2str($this->codcliente).",
               ".$this->var2str($this->nombrecliente).",".$this->var2str($this->cifnif).",".$this->var2str($this->direccion).",
               ".$this->var2str($this->ciudad).",".$this->var2str($this->provincia).",".$this->var2str($this->apartado).",
               ".$this->var2str($this->coddir).",
               ".$this->var2str($this->codpostal).",".$this->var2str($this->codpais).",
               ".$this->var2str($this->codagente).",".$this->var2str($this->neto).",".$this->var2str($this->totaliva).",
               ".$this->var2str($this->total).",".$this->var2str($this->totaleuros).",".$this->var2str($this->irpf).",
               ".$this->var2str($this->totalirpf).",".$this->var2str($this->porcomision).",".$this->var2str($this->tasaconv).",
               ".$this->var2str($this->recfinanciero).",".$this->var2str($this->totalrecargo).",".$this->var2str($this->observaciones).",
               ".$this->var2str($this->deabono).",".$this->var2str($this->automatica).",".$this->var2str($this->editable).",
               ".$this->var2str($this->nogenerarasiento).",".$this->var2str($this->hora).");";
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
      $this->db->exec("UPDATE albaranescli SET idfactura = NULL, ptefactura = TRUE
         WHERE idfactura = ".$this->var2str($this->idfactura).";");
      
      /// eliminamos
      return $this->db->exec("DELETE FROM ".$this->table_name.
              " WHERE idfactura = ".$this->var2str($this->idfactura).";");
   }
   
   private function clean_cache()
   {
      $this->cache->delete('factura_cliente_huecos');
   }
   
   public function all($offset=0, $limit=FS_ITEM_LIMIT)
   {
      $faclist = array();
      $facturas = $this->db->select_limit("SELECT * FROM ".$this->table_name.
         " ORDER BY fecha DESC, codigo DESC", $limit, $offset);
      if($facturas)
      {
         foreach($facturas as $f)
            $faclist[] = new factura_cliente($f);
      }
      return $faclist;
   }
   
   public function all_from_cliente($codcliente, $offset=0)
   {
      $faclist = array();
      $facturas = $this->db->select_limit("SELECT * FROM ".$this->table_name.
         " WHERE codcliente = ".$this->var2str($codcliente).
         " ORDER BY fecha DESC, codigo DESC", FS_ITEM_LIMIT, $offset);
      if($facturas)
      {
         foreach($facturas as $f)
            $faclist[] = new factura_cliente($f);
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
            $faclist[] = new factura_cliente($f);
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
            $faclist[] = new factura_cliente($f);
      }
      return $faclist;
   }
   
   public function search($query, $offset=0)
   {
      $faclist = array();
      $query = strtolower( $this->no_html($query) );
      
      $consulta = "SELECT * FROM ".$this->table_name." WHERE ";
      if( is_numeric($query) )
         $consulta .= "codigo LIKE '%".$query."%' OR observaciones LIKE '%".$query."%'
            OR total BETWEEN ".($query-.01)." AND ".($query+.01);
      else if( preg_match('/^([0-9]{1,2})-([0-9]{1,2})-([0-9]{4})$/i', $query) )
         $consulta .= "fecha = ".$this->var2str($query)." OR observaciones LIKE '%".$query."%'";
      else
         $consulta .= "lower(codigo) LIKE '%".$query."%' OR lower(observaciones) LIKE '%".str_replace(' ', '%', $query)."%'";
      $consulta .= " ORDER BY fecha DESC, codigo DESC";
      
      $facturas = $this->db->select_limit($consulta, FS_ITEM_LIMIT, $offset);
      if($facturas)
      {
         foreach($facturas as $f)
            $faclist[] = new factura_cliente($f);
      }
      return $faclist;
   }
   
   public function huecos()
   {
      $error = TRUE;
      $huecolist = $this->cache->get_array2('factura_cliente_huecos', $error);
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
         $this->cache->set('factura_cliente_huecos', $huecolist, 86400);
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