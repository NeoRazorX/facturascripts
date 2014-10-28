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
require_model('paquete.php');
require_model('serie.php');

class tpv_yamyam extends fs_controller
{
   public $agente;
   public $albaran;
   public $almacen;
   public $articulo;
   public $articulos;
   public $caja;
   public $cliente;
   public $divisa;
   public $ejercicio;
   public $familia;
   public $familias;
   public $forma_pago;
   public $impuesto;
   public $num_tickets;
   public $paquete;
   public $paquetes;
   public $serie;
   
   public function __construct()
   {
      parent::__construct('tpv_yamyam', 'Restaurante', 'TPV', FALSE, TRUE);
   }
   
   protected function process()
   {
      header('Access-Control-Allow-Origin: *');
      
      $this->css_file = 'touch.css';
      $this->agente = $this->user->get_agente();
      $this->almacen = new almacen();
      $this->articulo = new articulo();
      $this->caja = new caja();
      $this->cliente = new cliente();
      $this->divisa = new divisa();
      $this->ejercicio = new ejercicio();
      $this->familia = new familia();
      $this->forma_pago = new forma_pago();
      $this->paquete = new paquete();
      $this->serie = new serie();
      
      if( isset($_POST['saldo']) )
      {
         $this->template = FALSE;
         
         if(FS_LCD != '')
         {
            $fpt = new fs_printer(FS_LCD);
            $fpt->add( chr(12).'TOTAL               ');
            $fpt->add( substr(sprintf('%20s', $this->show_precio($_POST['saldo'], FALSE, FALSE)), 0, 20) );
            $fpt->imprimir();
         }
      }
      else if($this->agente)
      {
         /// obtenemos el bloqueo de caja, sin esto no se puede continuar
         $this->caja = $this->caja->get_last_from_this_server();
         if( $this->caja )
         {
            if($this->caja->codagente == $this->user->codagente)
            {
               $this->buttons[] = new fs_button('b_opciones_tpv', 'Opciones');
               $this->buttons[] = new fs_button_img('b_cancelar_ticket', 'Borrar ticket', 'trash.png', '#', TRUE);
               $this->buttons[] = new fs_button_img('b_cerrar_caja', 'Cerrar caja', 'remove.png', '#', TRUE);
               
               if( isset($_GET['cerrar_caja']) )
                  $this->cerrar_caja();
               else if( isset($_POST['numlineas']) )
                  $this->guardar_ticket();
               else if( isset($_GET['delete']) )
                  $this->borrar_ticket();
            }
            else
            {
               $this->new_error_msg("Esta caja está bloqueada por el agente ".
                  $this->caja->agente->get_fullname().". Puedes cerrarla
                  desde Contabilidad &gt; Caja."
               );
            }
            
            $this->cargar_datos_tpv();
         }
         else if( isset($_POST['d_inicial']) )
         {
            $this->buttons[] = new fs_button('b_opciones_tpv', 'Opciones');
            $this->buttons[] = new fs_button_img('b_cancelar_ticket', 'Borrar ticket', 'trash.png', '#', TRUE);
            $this->buttons[] = new fs_button_img('b_cerrar_caja', 'Cerrar caja', 'remove.png', '#', TRUE);
            
            $this->caja = new caja();
            $this->caja->codagente = $this->user->codagente;
            $this->caja->dinero_inicial = floatval($_POST['d_inicial']);
            $this->caja->dinero_fin = floatval($_POST['d_inicial']);
            if( $this->caja->save() )
               $this->new_message( "Caja iniciada con ".$this->show_precio($this->caja->dinero_inicial) );
            else
               $this->new_error_msg("¡Imposible guardar los datos de caja!");
            $this->cargar_datos_tpv();
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
   
   private function cargar_datos_tpv()
   {
      /// cargamos todos los paquetes
      $this->paquetes = $this->paquete->all();
      /// cargamos los articulos que no sean paquetes
      $this->articulos = array();
      foreach($this->get_first_articulos() as $a)
      {
         $encontrado = FALSE;
         foreach($this->paquetes as $p)
         {
            if($a->referencia == $p->referencia)
            {
               $encontrado = TRUE;
               break;
            }
         }
         if(!$encontrado AND $a->pvp > 0 AND !$a->bloqueado)
            $this->articulos[] = $a;
      }
      /// cargamos las familias de los articulos cargados
      $this->familias = array();
      foreach($this->familia->all() as $f)
      {
         $encontrado = FALSE;
         foreach($this->articulos as $a)
         {
            if($a->codfamilia == $f->codfamilia)
            {
               $encontrado = TRUE;
               break;
            }
         }
         if( $encontrado )
            $this->familias[] = $f;
      }
   }
   
   private function get_first_articulos()
   {
      $articulos = $this->cache->get_array('tpv_yamyam_articulos');
      if( !$articulos )
      {
         $articulos = $this->articulo->all(0, 150);
         $this->cache->set('tpv_yamyam_articulos', $articulos, 1800);
      }
      return $articulos;
   }
   
   private function guardar_ticket()
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
         $this->new_error_msg('Forma de pago no encontrado.');
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
      
      $this->albaran = new albaran_cliente();
      
      if( $this->duplicated_petition($_POST['petition_id']) )
      {
         $this->new_error_msg('Petición duplicada. Has hecho doble clic sobre el botón Guardar
               y se han enviado dos peticiones. Mira en <a href="'.$this->albaran->url().'">'.FS_ALBARANES.'</a>
               para ver si el '.FS_ALBARAN.' se ha guardado correctamente.');
         $continuar = FALSE;
      }
      
      if( $continuar )
      {
         $this->albaran->fecha = $_POST['fecha'];
         $this->albaran->codalmacen = $almacen->codalmacen;
         $this->albaran->codejercicio = $ejercicio->codejercicio;
         $this->albaran->codserie = $serie->codserie;
         $this->albaran->codpago = $forma_pago->codpago;
         $this->albaran->coddivisa = $divisa->coddivisa;
         $this->albaran->tasaconv = $divisa->tasaconv;
         $this->albaran->codagente = $this->agente->codagente;
         $this->albaran->observaciones = $_POST['observaciones'];
         
         foreach($cliente->get_direcciones() as $d)
         {
            if($d->domfacturacion)
            {
               $this->albaran->codcliente = $cliente->codcliente;
               $this->albaran->cifnif = $cliente->cifnif;
               $this->albaran->nombrecliente = $cliente->nombrecomercial;
               $this->albaran->apartado = $d->apartado;
               $this->albaran->ciudad = $d->ciudad;
               $this->albaran->coddir = $d->id;
               $this->albaran->codpais = $d->codpais;
               $this->albaran->codpostal = $d->codpostal;
               $this->albaran->direccion = $d->direccion;
               $this->albaran->provincia = $d->provincia;
               break;
            }
         }
         
         if( is_null($this->albaran->codcliente) )
            $this->new_error_msg("No hay ninguna dirección asociada al cliente.");
         else if( $this->albaran->save() )
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
                     $linea->idalbaran = $this->albaran->idalbaran;
                     $linea->referencia = $articulo->referencia;
                     $linea->descripcion = $articulo->descripcion;
                     
                     if( $serie->siniva )
                     {
                        $linea->codimpuesto = NULL;
                        $linea->iva = 0;
                     }
                     else
                     {
                        $linea->codimpuesto = $articulo->codimpuesto;
                        $linea->iva = floatval($_POST['iva_'.$i]);
                     }
                     
                     $linea->pvpunitario = floatval($_POST['pvp_'.$i]);
                     $linea->cantidad = floatval($_POST['cantidad_'.$i]);
                     $linea->pvpsindto = ($linea->pvpunitario * $linea->cantidad);
                     $linea->pvptotal = ($linea->pvpunitario * $linea->cantidad);
                     
                     if( $linea->save() )
                     {
                        /// descontamos del stock
                        $articulo->sum_stock($this->albaran->codalmacen, 0 - $linea->cantidad);
                        
                        $this->albaran->neto += $linea->pvptotal;
                        $this->albaran->totaliva += ($linea->pvptotal * $linea->iva/100);
                     }
                     else
                     {
                        $this->new_error_msg("¡Imposible guardar la línea con referencia: ".$linea->referencia);
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
               $this->albaran->neto = round($this->albaran->neto, 2);
               $this->albaran->totaliva = round($this->albaran->totaliva, 2);
               $this->albaran->total = $this->albaran->neto + $this->albaran->totaliva;
               
               if( $this->albaran->save() )
               {
                  $this->new_message("<a href='".$this->albaran->url()."'>".FS_ALBARAN."</a> guardado correctamente.");
                  
                  if( isset($_POST['num_tickets']) )
                     $this->imprimir_ticket( floatval($_POST['num_tickets']) );
                  
                  /// actualizamos la caja
                  $this->caja->dinero_fin += $this->albaran->total;
                  $this->caja->tickets += 1;
                  if( !$this->caja->save() )
                     $this->new_error_msg("¡Imposible actualizar la caja!");
               }
               else
                  $this->new_error_msg("¡Imposible actualizar el ".FS_ALBARAN."!");
            }
            else if( $this->albaran->delete() )
               $this->new_message(FS_ALBARAN." eliminado correctamente.");
            else
               $this->new_error_msg("¡Imposible eliminar el ".FS_ALBARAN."!");
         }
         else
            $this->new_error_msg("¡Imposible guardar el ".FS_ALBARAN."!");
      }
   }
   
   private function borrar_ticket()
   {
      $this->albaran = new albaran_cliente();
      $alb = $this->albaran->get_by_codigo($_GET['delete']);
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
         $fpt->add_big( $fpt->center_text($this->empresa->nombre, 16)."\n" );
         $fpt->add($fpt->center_text($this->empresa->lema) . "\n\n");
         $fpt->add($fpt->center_text($this->empresa->direccion . " - " . $this->empresa->ciudad) . "\n");
         /// cif de empresa + cortar el papel
         $fpt->add($fpt->center_text("CIF: " . $this->empresa->cifnif) . chr(27).chr(105) . "\n\n");
         $fpt->add($fpt->center_text($this->empresa->horario) . "\n");
         
         $fpt->imprimir();
         $fpt->abrir_cajon();
         
         /// recargamos la página
         header('location: '.$this->url());
      }
      else
         $this->new_error_msg("¡Imposible cerrar la caja!");
   }
   
   private function imprimir_ticket($num_tickets=2)
   {
      $fpt = new fs_printer();
      $fpt->abrir_cajon();
      
      while($num_tickets > 0)
      {
         $linea = "\nTicket: " . $this->albaran->codigo;
         $linea .= " " . $this->albaran->fecha;
         $linea .= " " . $this->albaran->show_hora(FALSE) . "\n";
         $fpt->add($linea);
         $fpt->add("Cliente: " . $this->albaran->nombrecliente . "\n");
         $fpt->add("Agente: " . $this->albaran->codagente . "\n\n");
         
         $fpt->add(sprintf("%3s", "Ud.")." ".sprintf("%-25s", "Articulo")." ".sprintf("%10s", "TOTAL")."\n");
         $fpt->add(sprintf("%3s", "---")." ".sprintf("%-25s", "-------------------------")." ".sprintf("%10s", "----------")."\n");
         foreach($this->albaran->get_lineas() as $col)
         {
            $linea = sprintf("%3s", $col->cantidad)." ".sprintf("%-25s", $col->referencia)." ".sprintf("%10s", $this->show_numero($col->total_iva()))."\n";
            $fpt->add($linea);
         }
         
         $linea = "----------------------------------------\n"
            .$fpt->center_text(
               "IVA: ".$this->show_precio($this->albaran->totaliva, $this->albaran->coddivisa, FALSE)."   ".
               "Total: ".$this->show_precio($this->albaran->total, $this->albaran->coddivisa, FALSE)
            )."\n\n";
         
         if( isset($_POST['efectivo']) )
         {
            $linea .= $fpt->center_text("Efectivo..........: ".sprintf("%12s",$this->show_precio($_POST['efectivo'], $this->albaran->coddivisa, FALSE)))."\n";
         }
         
         if( isset($_POST['cambio']) )
         {
            $linea .= $fpt->center_text("Cambio............: ".sprintf("%12s",$this->show_precio($_POST['cambio'], $this->albaran->coddivisa, FALSE)))."\n";
         }
         
         $linea .= "\n\n\n";
         $fpt->add($linea);
         
         $fpt->add_big( $fpt->center_text($this->empresa->nombre, 16)."\n" );
         $fpt->add($fpt->center_text($this->empresa->lema) . "\n\n");
         $fpt->add($fpt->center_text($this->empresa->direccion . " - " . $this->empresa->ciudad) . "\n");
         $fpt->add($fpt->center_text("CIF: " . $this->empresa->cifnif) . chr(27).chr(105) . "\n\n"); /// corta el papel
         $fpt->add($fpt->center_text($this->empresa->horario) . "\n");
         
         $fpt->imprimir();
         $num_tickets--;
      }
   }
}

?>
