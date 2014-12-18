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

require_once 'base/fs_printer.php';
require_model('agente.php');
require_model('albaran_cliente.php');
require_model('almacen.php');
require_model('articulo.php');
require_model('caja.php');
require_model('cliente.php');
require_model('divisa.php');
require_model('ejercicio.php');
require_model('familia.php');
require_model('forma_pago.php');
require_model('serie.php');

class tpv_recambios extends fs_controller
{
   public $agente;
   public $almacen;
   public $articulo;
   public $caja;
   public $cliente;
   public $divisa;
   public $ejercicio;
   public $equivalentes;
   public $familia;
   public $forma_pago;
   public $imprimir_descripciones;
   public $imprimir_observaciones;
   public $results;
   public $serie;
   public $tarifas;
   public $ultimas_compras;
   public $ultimas_ventas;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'TPV Genérico', 'TPV', FALSE, TRUE);
   }
   
   protected function process()
   {
      header('Access-Control-Allow-Origin: *');
      
      $this->show_fs_toolbar = FALSE;
      
      $this->articulo = new articulo();
      $this->cliente = new cliente();
      $this->familia = new familia();
      $this->results = array();
      
      if( isset($_POST['saldo']) )
      {
         $this->template = FALSE;
         
         if(FS_LCD != '')
         {
            $fpt = new fs_printer(FS_LCD);
            $fpt->add( chr(12).'TOTAL               ' );
            $fpt->add( substr(sprintf('%20s', $this->show_precio($_POST['saldo'], FALSE, FALSE)), 0, 20) );
            $fpt->imprimir();
         }
      }
      else if( $this->query != '' )
      {
         $this->new_search();
      }
      else if( isset($_REQUEST['referencia4precios']) )
      {
         $this->get_precios_articulo();
      }
      else
      {
         $this->agente = $this->user->get_agente();
         $this->almacen = new almacen();
         $this->caja = new caja();
         $this->divisa = new divisa();
         $this->ejercicio = new ejercicio();
         $this->forma_pago = new forma_pago();
         $this->serie = new serie();
         
         $this->imprimir_descripciones = FALSE;
         if( isset($_POST['imprimir_desc']) )
            $this->imprimir_descripciones = TRUE;
         else if( isset($_COOKIE['imprimir_desc']) )
            $this->imprimir_descripciones = TRUE;
         
         $this->imprimir_observaciones = FALSE;
         if( isset($_POST['imprimir_obs']) )
            $this->imprimir_observaciones = TRUE;
         else if( isset($_COOKIE['imprimir_obs']) )
            $this->imprimir_observaciones = TRUE;
         
         if( $this->agente )
         {
            /// obtenemos el bloqueo de caja, sin esto no se puede continuar
            $this->caja = $this->caja->get_last_from_this_server();
            if( $this->caja )
            {
               if($this->caja->codagente == $this->user->codagente)
               {
                  if( isset($_GET['abrir_caja']) )
                     $this->abrir_caja();
                  else if( isset($_GET['cerrar_caja']) )
                     $this->cerrar_caja();
                  else if( isset($_POST['cliente']) )
                     $this->nuevo_albaran_cliente();
                  else if( isset($_GET['reticket']) )
                     $this->reimprimir_ticket();
                  else if( isset($_GET['delete']) )
                     $this->borrar_ticket();
               }
               else
               {
                  $this->new_error_msg("Esta caja está bloqueada por el agente ".
                     $this->caja->agente->get_fullname().". Puedes cerrarla desde TPV &gt; Caja."
                  );
               }
            }
            else if( isset($_POST['d_inicial']) )
            {
               $this->caja = new caja();
               $this->caja->codagente = $this->user->codagente;
               $this->caja->dinero_inicial = floatval($_POST['d_inicial']);
               $this->caja->dinero_fin = floatval($_POST['d_inicial']);
               if( $this->caja->save() )
               {
                  $this->new_message("Caja iniciada con ".$this->show_precio($this->caja->dinero_inicial) );
               }
               else
                  $this->new_error_msg("¡Imposible guardar los datos de caja!");
            }
            else
            {
               $fpt = new fs_printer();
               $fpt->abrir_cajon();
            }
         }
         else
         {
            $this->new_error_msg('No tienes un <a href="'.$this->user->url().'">agente asociado</a>
               a tu usuario, y por tanto no puedes hacer tickets.');
         }
      }
   }
   
   private function new_search()
   {
      /// cambiamos la plantilla HTML
      $this->template = 'ajax/tpv_recambios';
      
      $codfamilia = '';
      if( isset($_POST['codfamilia']) )
         $codfamilia = $_POST['codfamilia'];
      
      $con_stock = isset($_POST['con_stock']);
      $this->results = $this->articulo->search($this->query, 0, $codfamilia, $con_stock);
      
      $cliente = $this->cliente->get($_POST['codcliente']);
      if($cliente)
      {
         if($cliente->regimeniva == 'Exento')
         {
            foreach($this->results as $i => $value)
               $this->results[$i]->iva = 0;
         }
      }
   }
   
   private function get_precios_articulo()
   {
      /// cambiamos la plantilla HTML
      $this->template = 'ajax/tpv_recambios_precios';
      $this->articulo = $this->articulo->get($_REQUEST['referencia4precios']);
   }
   
   private function nuevo_albaran_cliente()
   {
      $continuar = TRUE;
      
      $cliente = $this->cliente->get($_POST['cliente']);
      if( $cliente )
         $this->save_codcliente( $cliente->codcliente );
      else
      {
         $this->new_error_msg('Cliente no encontrado.');
         $continuar = FALSE;
      }
      
      $almacen = $this->almacen->get($_POST['almacen']);
      if( $almacen )
         $this->save_codalmacen( $almacen->codalmacen );
      else
      {
         $this->new_error_msg('Almacén no encontrado.');
         $continuar = FALSE;
      }
      
      $ejercicio = $this->ejercicio->get_by_fecha($_POST['fecha']);
      if( $ejercicio )
         $this->save_codejercicio( $ejercicio->codejercicio );
      else
      {
         $this->new_error_msg('Ejercicio no encontrado.');
         $continuar = FALSE;
      }
      
      $serie = $this->serie->get($_POST['serie']);
      if( $serie )
         $this->save_codserie( $serie->codserie );
      else
      {
         $this->new_error_msg('Serie no encontrada.');
         $continuar = FALSE;
      }
      
      $forma_pago = $this->forma_pago->get($_POST['forma_pago']);
      if( $forma_pago )
         $this->save_codpago( $forma_pago->codpago );
      else
      {
         $this->new_error_msg('Forma de pago no encontrada.');
         $continuar = FALSE;
      }
      
      $divisa = $this->divisa->get($_POST['divisa']);
      if( $divisa )
         $this->save_coddivisa( $divisa->coddivisa );
      else
      {
         $this->new_error_msg('Divisa no encontrada.');
         $continuar = FALSE;
      }
      
      if( isset($_POST['imprimir_desc']) )
      {
         $this->imprimir_descripciones = TRUE;
         setcookie('imprimir_desc', TRUE, time()+FS_COOKIES_EXPIRE);
      }
      else
      {
         $this->imprimir_descripciones = FALSE;
         setcookie('imprimir_desc', FALSE, time()-FS_COOKIES_EXPIRE);
      }
      
      if( isset($_POST['imprimir_obs']) )
      {
         $this->imprimir_observaciones = TRUE;
         setcookie('imprimir_obs', TRUE, time()+FS_COOKIES_EXPIRE);
      }
      else
      {
         $this->imprimir_observaciones = FALSE;
         setcookie('imprimir_obs', FALSE, time()-FS_COOKIES_EXPIRE);
      }
      
      $albaran = new albaran_cliente();
      
      if( $this->duplicated_petition($_POST['petition_id']) )
      {
         $this->new_error_msg('Petición duplicada. Has hecho doble clic sobre el botón Guardar
               y se han enviado dos peticiones. Mira en <a href="'.$albaran->url().'">'.FS_ALBARANES.'</a>
               para ver si el '.FS_ALBARAN.' se ha guardado correctamente.');
         $continuar = FALSE;
      }
      
      if( $continuar )
      {
         $albaran->fecha = $_POST['fecha'];
         $albaran->codalmacen = $almacen->codalmacen;
         $albaran->codejercicio = $ejercicio->codejercicio;
         $albaran->codserie = $serie->codserie;
         $albaran->codpago = $forma_pago->codpago;
         $albaran->coddivisa = $divisa->coddivisa;
         $albaran->tasaconv = $divisa->tasaconv;
         $albaran->codagente = $this->agente->codagente;
         $albaran->observaciones = $_POST['observaciones'];
         $albaran->numero2 = $_POST['numero2'];
         $albaran->irpf = $serie->irpf;
         $albaran->porcomision = $this->agente->porcomision;
         
         foreach($cliente->get_direcciones() as $d)
         {
            if($d->domfacturacion)
            {
               $albaran->codcliente = $cliente->codcliente;
               $albaran->cifnif = $cliente->cifnif;
               $albaran->nombrecliente = $cliente->nombrecomercial;
               $albaran->apartado = $d->apartado;
               $albaran->ciudad = $d->ciudad;
               $albaran->coddir = $d->id;
               $albaran->codpais = $d->codpais;
               $albaran->codpostal = $d->codpostal;
               $albaran->direccion = $d->direccion;
               $albaran->provincia = $d->provincia;
               break;
            }
         }
         
         if( is_null($albaran->codcliente) )
         {
            $this->new_error_msg("No hay ninguna dirección asociada al cliente.");
         }
         else if( $albaran->save() )
         {
            $n = floatval($_POST['numlineas']);
            for($i = 1; $i <= $n; $i++)
            {
               if( isset($_POST['referencia_'.$i]) )
               {
                  $articulo = $this->articulo->get($_POST['referencia_'.$i]);
                  if($articulo)
                  {
                     $linea = new linea_albaran_cliente();
                     $linea->idalbaran = $albaran->idalbaran;
                     $linea->referencia = $articulo->referencia;
                     $linea->descripcion = $_POST['desc_'.$i];
                     
                     if( !$serie->siniva OR $cliente->regimeniva != 'Exento' )
                     {
                        $linea->codimpuesto = $articulo->codimpuesto;
                        $linea->iva = floatval($_POST['iva_'.$i]);
                        $linea->recargo = floatval($_POST['recargo_'.$i]);
                     }
                     
                     if($linea->iva > 0)
                        $linea->irpf = $albaran->irpf;
                     
                     $linea->pvpunitario = floatval($_POST['pvp_'.$i]);
                     $linea->cantidad = floatval($_POST['cantidad_'.$i]);
                     $linea->dtopor = floatval($_POST['dto_'.$i]);
                     $linea->pvpsindto = ($linea->pvpunitario * $linea->cantidad);
                     $linea->pvptotal = floatval($_POST['total_'.$i]);
                     
                     if( $linea->save() )
                     {
                        /// descontamos del stock
                        $articulo->sum_stock($albaran->codalmacen, 0 - $linea->cantidad);
                        
                        $albaran->neto += $linea->pvptotal;
                        $albaran->totaliva += ($linea->pvptotal * $linea->iva/100);
                        $albaran->totalirpf += ($linea->pvptotal * $linea->irpf/100);
                        $albaran->totalrecargo += ($linea->pvptotal * $linea->recargo/100);
                     }
                     else
                     {
                        $this->new_error_msg("¡Imposible guardar la linea con referencia: ".$linea->referencia);
                        $continuar = FALSE;
                     }
                  }
                  else
                  {
                     $this->new_error_msg("Artículo no encontrado: ".$_POST['referencia_'.$i]);
                     $continuar = FALSE;
                  }
               }
            }
            
            if($continuar)
            {
               /// redondeamos
               $albaran->neto = round($albaran->neto, FS_NF0);
               $albaran->totaliva = round($albaran->totaliva, FS_NF0);
               $albaran->totalirpf = round($albaran->totalirpf, FS_NF0);
               $albaran->totalrecargo = round($albaran->totalrecargo, FS_NF0);
               $albaran->total = $albaran->neto + $albaran->totaliva - $albaran->totalirpf + $albaran->totalrecargo;
               
               if( $albaran->save() )
               {
                  $this->new_message("<a href='".$albaran->url()."'>".FS_ALBARAN."</a> guardado correctamente.");
                  
                  $this->imprimir_ticket( $albaran, floatval($_POST['num_tickets']) );
                  
                  /// actualizamos la caja
                  $this->caja->dinero_fin += $albaran->total;
                  $this->caja->tickets += 1;
                  $this->caja->ip = $_SERVER['REMOTE_ADDR'];
                  if( !$this->caja->save() )
                     $this->new_error_msg("¡Imposible actualizar la caja!");
               }
               else
                  $this->new_error_msg("¡Imposible actualizar el <a href='".$albaran->url()."'>".FS_ALBARAN."</a>!");
            }
            else if( $albaran->delete() )
            {
               $this->new_message(FS_ALBARAN." eliminado correctamente.");
            }
            else
               $this->new_error_msg("¡Imposible eliminar el <a href='".$albaran->url()."'>".FS_ALBARAN."</a>!");
         }
         else
            $this->new_error_msg("¡Imposible guardar el ".FS_ALBARAN."!");
      }
   }
   
   private function abrir_caja()
   {
      if( $this->user->admin )
      {
         $fpt = new fs_printer();
         $fpt->abrir_cajon();
      }
      else
         $this->new_error_msg('Sólo un administrador puede abrir la caja.');
   }
   
   private function cerrar_caja()
   {
      $this->caja->fecha_fin = Date('d-m-Y H:i:s');
      if( $this->caja->save() )
      {
         $fpt = new fs_printer();
         $fpt->add_big("\nCIERRE DE CAJA:\n");
         $fpt->add("Empleado: ".$this->user->codagente." ".$this->agente->get_fullname()."\n");
         $fpt->add("Caja: ".$this->caja->fs_id."\n");
         $fpt->add("Fecha inicial: ".$this->caja->fecha_inicial."\n");
         $fpt->add("Dinero inicial: ".$this->show_precio($this->caja->dinero_inicial, FALSE, FALSE)."\n");
         $fpt->add("Fecha fin: ".$this->caja->show_fecha_fin()."\n");
         $fpt->add("Dinero fin: ".$this->show_precio($this->caja->dinero_fin, FALSE, FALSE)."\n");
         $fpt->add("Diferencia: ".$this->show_precio($this->caja->diferencia(), FALSE, FALSE)."\n");
         $fpt->add("Tickets: ".$this->caja->tickets."\n\n");
         $fpt->add("Dinero pesado:\n\n\n");
         $fpt->add("Observaciones:\n\n\n\n");
         $fpt->add("Firma:\n\n\n\n\n\n\n");
         
         /// encabezado común para los tickets
         $fpt->add_big( $fpt->center_text($this->empresa->nombre, 16)."\n");
         $fpt->add( $fpt->center_text($this->empresa->lema) . "\n\n");
         $fpt->add( $fpt->center_text($this->empresa->direccion . " - " . $this->empresa->ciudad) . "\n");
         $fpt->add( $fpt->center_text("CIF: " . $this->empresa->cifnif) . chr(27).chr(105) . "\n\n"); /// corta el papel
         $fpt->add( $fpt->center_text($this->empresa->horario) . "\n");
         
         $fpt->imprimir();
         $fpt->abrir_cajon();
         
         /// recargamos la página
         header('location: '.$this->url());
      }
      else
         $this->new_error_msg("¡Imposible cerrar la caja!");
   }
   
   private function reimprimir_ticket()
   {
      $albaran = new albaran_cliente();
      
      if($_GET['reticket'] == '')
      {
         foreach($albaran->all() as $alb)
         {
            $alb0 = $alb;
            break;
         }
      }
      else
         $alb0 = $albaran->get_by_codigo($_GET['reticket']);
      
      if($alb0)
         $this->imprimir_ticket($alb0, 1, FALSE);
      else
         $this->new_error_msg("Ticket no encontrado.");
   }
   
   private function borrar_ticket()
   {
      $albaran = new albaran_cliente();
      $alb = $albaran->get_by_codigo($_GET['delete']);
      if($alb)
      {
         if($alb->ptefactura)
         {
            $articulo = new articulo();
            foreach($alb->get_lineas() as $linea)
            {
               $art0 = $articulo->get($linea->referencia);
               if($art0)
               {
                  $art0->sum_stock($alb->codalmacen, $linea->cantidad);
                  $art0->save();
               }
            }
            
            if( $alb->delete() )
            {
               $this->new_message("Ticket ".$_GET['delete']." borrado correctamente.");
               
               /// actualizamos la caja
               $this->caja->dinero_fin -= $alb->total;
               $this->caja->tickets -= 1;
               if( !$this->caja->save() )
                  $this->new_error_msg("¡Imposible actualizar la caja!");
            }
            else
               $this->new_error_msg("¡Imposible borrar el ticket ".$_GET['delete']."!");
         }
         else
            $this->new_error_msg('No se ha podido borrar este '.FS_ALBARAN.' porque ya está facturado.');
      }
      else
         $this->new_error_msg("Ticket no encontrado.");
   }
   
   private function imprimir_ticket($albaran, $num_tickets=1, $cajon=TRUE)
   {
      $fpt = new fs_printer();
      
      if($cajon)
         $fpt->abrir_cajon();
      
      while($num_tickets > 0)
      {
         $linea = "\nTicket: " . $albaran->codigo;
         $linea .= " " . $albaran->fecha;
         $linea .= " " . $albaran->show_hora(FALSE) . "\n";
         $fpt->add($linea);
         $fpt->add("Cliente: " . $albaran->nombrecliente . "\n");
         $fpt->add("Empleado: " . $albaran->codagente . "\n\n");
         
         if($this->imprimir_observaciones)
         {
            $fpt->add('Observaciones: '.$albaran->observaciones."\n\n");
         }
         
         $fpt->add(sprintf("%3s", "Ud.")." ".sprintf("%-25s", "Articulo")." ".sprintf("%10s", "TOTAL")."\n");
         $fpt->add(sprintf("%3s", "---")." ".sprintf("%-25s", "-------------------------")." ".sprintf("%10s", "----------")."\n");
         foreach($albaran->get_lineas() as $col)
         {
            if($this->imprimir_descripciones)
            {
               $linea = sprintf("%3s", $col->cantidad)." ".sprintf("%-25s", substr($col->descripcion, 0, 24))." "
                  .sprintf("%10s", $this->show_numero($col->total_iva()))."\n";
            }
            else
            {
               $linea = sprintf("%3s", $col->cantidad)." ".sprintf("%-25s", $col->referencia)." "
                  .sprintf("%10s", $this->show_numero($col->total_iva()))."\n";
            }
            
            $fpt->add($linea);
         }
         
         $linea = "----------------------------------------\n"
            .$fpt->center_text(
               "IVA: ".$this->show_precio($albaran->totaliva, $albaran->coddivisa, FALSE).'   '.
               "Total: ".$this->show_precio($albaran->total, $albaran->coddivisa, FALSE)
            )."\n\n\n\n";
         $fpt->add($linea);
         
         $fpt->add_big( $fpt->center_text($this->empresa->nombre, 16)."\n");
         
         if($this->empresa->lema != '')
            $fpt->add( $fpt->center_text($this->empresa->lema) . "\n\n");
         else
            $fpt->add("\n");
         
         $fpt->add( $fpt->center_text($this->empresa->direccion . " - " . $this->empresa->ciudad) . "\n");
         $fpt->add( $fpt->center_text("CIF: " . $this->empresa->cifnif) . chr(27).chr(105) . "\n\n"); /// corta el papel
         
         if($this->empresa->horario != '')
            $fpt->add( $fpt->center_text($this->empresa->horario) . "\n");
         
         $fpt->imprimir();
         $num_tickets--;
      }
   }
}
