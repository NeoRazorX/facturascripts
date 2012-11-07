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

require_once 'model/agente.php';
require_once 'model/albaran_cliente.php';
require_once 'model/almacen.php';
require_once 'model/articulo.php';
require_once 'model/caja.php';
require_once 'model/cliente.php';
require_once 'model/divisa.php';
require_once 'model/ejercicio.php';
require_once 'model/familia.php';
require_once 'model/forma_pago.php';
require_once 'model/serie.php';

class tpv_recambios extends fs_controller
{
   public $agente;
   public $almacen;
   public $articulo;
   public $caja;
   public $cliente;
   public $divisa;
   public $ejercicio;
   public $familia;
   public $forma_pago;
   public $impresora;
   public $results;
   public $serie;
   
   public function __construct()
   {
      parent::__construct('tpv_recambios', 'TPV recambios', 'TPV', FALSE, TRUE);
   }
   
   protected function process()
   {
      $this->articulo = new articulo();
      $this->familia = new familia();
      $this->results = array();
      
      if( $this->query != '' )
         $this->new_search();
      else
      {
         $this->agente = $this->user->get_agente();
         $this->almacen = new almacen();
         $this->caja = new caja();
         $this->cliente = new cliente();
         $this->divisa = new divisa();
         $this->ejercicio = new ejercicio();
         $this->forma_pago = new forma_pago();
         $this->serie = new serie();
         
         /// seleccionamos impresora de tickets
         if( isset($_POST['impresora']) )
         {
            $this->impresora = $_POST['impresora'];
            setcookie('impresora', $this->impresora, time()+FS_COOKIES_EXPIRE);
         }
         else if( isset($_COOKIE['impresora']) )
            $this->impresora = $_COOKIE['impresora'];
         
         if( $this->agente )
         {
            /// obtenemos el bloqueo de caja, sin esto no se puede continuar
            $this->caja = $this->caja->get_last_from_this_server();
            if( $this->caja )
            {
               if($this->caja->codagente == $this->user->codagente)
               {
                  $this->buttons[] = new fs_button('b_borrar_ticket', 'borrar ticket', '#', 'remove', 'img/remove.png');
                  $this->buttons[] = new fs_button('b_cerrar_caja', 'cerrar caja', '#', 'remove', 'img/remove.png');
                  
                  if( isset($_GET['cerrar_caja']) )
                     $this->cerrar_caja();
                  else if( isset($_POST['cliente']) )
                     $this->nuevo_albaran_cliente();
                  else if( isset($_GET['delete']) )
                     $this->borrar_ticket();
               }
               else
                  $this->new_error_msg("Esta caja está bloqueada por el agente ".$this->caja->agente->get_fullname());
            }
            else if( isset($_POST['d_inicial']) )
            {
               $this->caja = new caja();
               $this->caja->codagente = $this->user->codagente;
               $this->caja->dinero_inicial = floatval($_POST['d_inicial']);
               $this->caja->dinero_fin = floatval($_POST['d_inicial']);
               if( $this->caja->save() )
               {
                  $this->buttons[] = new fs_button('b_borrar_ticket', 'borrar ticket', '#', 'remove', 'img/remove.png');
                  $this->buttons[] = new fs_button('b_cerrar_caja', 'cerrar caja', '#', 'remove', 'img/remove.png');
                  
                  $this->new_message("Caja iniciada con ".$this->caja->show_dinero_inicial()." Euros.");
               }
               else
                  $this->new_error_msg("¡Imposible guardar los datos de caja!");
            }
            else
            {
               if($this->impresora)
                  $imp = " -d ".$this->impresora;
               else
                  $imp = "";
               shell_exec("echo '".chr(27).chr(112).chr(48)."' | lp".$imp); /// abre el cajón
            }
         }
         else
            $this->new_error_msg("¡No tienes un agente asociado a tu usuario!");
      }
   }
   
   public function version()
   {
      return parent::version().'-5';
   }
   
   private function new_search()
   {
      /// cambiamos la plantilla HTML
      $this->template = 'ajax/tpv_recambios';
      
      if( isset($_POST['codfamilia']) )
         $codfamilia = $_POST['codfamilia'];
      else
         $codfamilia = '';
      $con_stock = isset($_POST['con_stock']);
      $this->results = $this->articulo->search($this->query, 0, $codfamilia, $con_stock);
   }
   
   private function nuevo_albaran_cliente()
   {
      $continuar = TRUE;
      
      $cliente = $this->cliente->get($_POST['cliente']);
      if( $cliente )
      {
         $cliente->set_default();
         $dirscliente = $cliente->get_direcciones();
      }
      else
         $continuar = FALSE;
      
      $almacen = $this->almacen->get($_POST['almacen']);
      if( $almacen )
         $almacen->set_default();
      else
         $continuar = FALSE;
      
      $ejercicio = $this->ejercicio->get($_POST['ejercicio']);
      if( $ejercicio )
         $ejercicio->set_default();
      else
         $continuar = FALSE;
      
      $serie = $this->serie->get($_POST['serie']);
      if( $serie )
         $serie->set_default();
      else
         $continuar = FALSE;
      
      $forma_pago = $this->forma_pago->get($_POST['forma_pago']);
      if( $forma_pago )
         $forma_pago->set_default();
      else
         $continuar = FALSE;
      
      $divisa = $this->divisa->get($_POST['divisa']);
      if( $divisa )
         $divisa->set_default();
      else
         $continuar = FALSE;
      
      if( $continuar )
      {
         $albaran = new albaran_cliente();
         $albaran->codcliente = $cliente->codcliente;
         $albaran->cifnif = $cliente->cifnif;
         $albaran->nombrecliente = $cliente->nombre;
         if($dirscliente)
         {
            foreach($dirscliente as $d)
            {
               if($d->domfacturacion)
               {
                  $albaran->apartado = $d->apartado;
                  $albaran->ciudad = $d->ciudad;
                  $albaran->coddir = $d->id;
                  $albaran->codpais = $d->codpais;
                  $albaran->codpostal = $d->codpostal;
                  $albaran->direccion = $d->direccion;
                  $albaran->provincia = $d->provincia;
               }
            }
         }
         
         if( is_null($albaran->coddir) )
            $this->new_error_msg("No hay ninguna dirección asociada al cliente.");
         else
         {
            $albaran->codalmacen = $almacen->codalmacen;
            $albaran->codejercicio = $ejercicio->codejercicio;
            $albaran->codserie = $serie->codserie;
            $albaran->codpago = $forma_pago->codpago;
            $albaran->coddivisa = $divisa->coddivisa;
            $albaran->codagente = $this->agente->codagente;
            $albaran->observaciones = $_POST['observaciones'];
            if( $albaran->save() )
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
                        $linea->descripcion = $articulo->descripcion;
                        $linea->codimpuesto = $articulo->codimpuesto;
                        $linea->iva = floatval($_POST['iva_'.$i]);
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
                           $albaran->totaliva += ($linea->iva * $linea->pvptotal / 100);
                           $albaran->total = ($albaran->neto + $albaran->totaliva);
                           $albaran->totaleuros = ($albaran->neto + $albaran->totaliva);
                        }
                        else
                           $this->new_error_msg("¡Imposible guardar la linea con referencia: ".$linea->referencia);
                     }
                  }
               }
               if( $albaran->save() )
               {
                  $this->new_message("<a href='".$albaran->url()."'>Albarán</a> guardado correctamente.");
                  $this->imprimir_ticket( $albaran );
                  
                  /// actualizamos la caja
                  $this->caja->dinero_fin += $albaran->totaleuros;
                  $this->caja->tickets += 1;
                  if( !$this->caja->save() )
                     $this->new_error_msg("¡Imposible actualizar la caja!");
               }
               else
                  $this->new_error_msg("¡Imposible actualizar el <a href='".$albaran->url()."'>albaran</a>!");
            }
            else
               $this->new_error_msg("¡Imposible guardar el albarán!");
         }
      }
      else
         $this->new_error_msg("¡Faltan datos!");
   }
   
   private function cerrar_caja()
   {
      $this->caja->fecha_fin = Date('Y-n-j H:i:s');
      if( $this->caja->save() )
      {
         /// abrimos el archivo temporal
         $file = fopen("/tmp/ticket.txt", "w");
         if($file)
         {
            $linea = "\n".chr(27).chr(33).chr(56)."CIERRE DE CAJA:".chr(27).chr(33).chr(1)."\n"; /// letras grandes
            fwrite($file, $linea);
            $linea = "Agente: ".$this->user->codagente." ".$this->agente->get_fullname()."\n";
            fwrite($file, $linea);
            $linea = "Caja: ".$this->caja->fs_id."\n";
            fwrite($file, $linea);
            $linea = "Fecha inicial: ".$this->caja->fecha_inicial."\n";
            fwrite($file, $linea);
            $linea = "Dinero inicial: ".$this->caja->show_dinero_inicial()." Eur.\n";
            fwrite($file, $linea);
            $linea = "Fecha fin: ".$this->caja->show_fecha_fin()."\n";
            fwrite($file, $linea);
            $linea = "Dinero fin: ".$this->caja->show_dinero_fin()." Eur.\n";
            fwrite($file, $linea);
            $linea = "Diferencia: ".$this->caja->show_diferencia()." Eur.\n";
            fwrite($file, $linea);
            $linea = "Tickets: ".$this->caja->tickets."\n\n";
            fwrite($file, $linea);
            $linea = "Dinero pesado:\n\n\n";
            fwrite($file, $linea);
            $linea = "Observaciones:\n\n\n\n";
            fwrite($file, $linea);
            $linea = "Firma:\n\n\n\n\n\n\n";
            fwrite($file, $linea);
            
            /// encabezado común para los tickets
            $linea = chr(27).chr(33).chr(56).$this->center_text($this->empresa->nombre,16).chr(27).chr(33).chr(1)."\n"; /// letras grandes
            fwrite($file, $linea);
            $linea = $this->center_text($this->empresa->lema) . "\n\n";
            fwrite($file, $linea);
            $linea = $this->center_text($this->empresa->direccion . " - " . $this->empresa->ciudad) . "\n";
            fwrite($file, $linea);
            $linea = $this->center_text("CIF: " . $this->empresa->cifnif) . chr(27).chr(105) . "\n\n"; /// corta el papel
            fwrite($file, $linea);
            $linea = $this->center_text($this->empresa->horario) . "\n";
            fwrite($file, $linea);
            fclose($file);
         }
         
         if( file_exists("/tmp/ticket.txt") )
         {
            if($this->impresora)
               $imp = " -d ".$this->impresora;
            else
               $imp = "";
            
            shell_exec("cat /tmp/ticket.txt | lp".$imp); /// imprime
            shell_exec("echo '".chr(27).chr(112).chr(48)."' | lp".$imp); /// abre el cajón
            unlink("/tmp/ticket.txt"); /// borra el ticket
         }
         
         /// recargamos la página
         header('location: '.$this->url());
      }
      else
         $this->new_error_msg("¡Imposible cerrar la caja!");
   }
   
   private function borrar_ticket()
   {
      $albaran = new albaran_cliente();
      $alb = $albaran->get_by_codigo($_GET['delete']);
      if($alb)
      {
         if( $alb->delete() )
         {
            $this->new_message("Ticket ".$_GET['delete']." borrado correctamente.");
            
            /// actualizamos la caja
            $this->caja->dinero_fin -= $alb->totaleuros;
            $this->caja->tickets -= 1;
            if( !$this->caja->save() )
               $this->new_error_msg("¡Imposible actualizar la caja!");
         }
         else
            $this->new_error_msg("¡Imposible borrar el ticket ".$_GET['delete']."!");
      }
      else
         $this->new_error_msg("Ticket no encontrado.");
   }

   private function imprimir_ticket($albaran)
   {
      /// abrimos el archivo temporal
      $file = fopen("/tmp/ticket.txt", "w");
      if($file)
      {
         $linea = "\nTicket: " . $albaran->codigo;
         $linea .= " " . $albaran->fecha;
         $linea .= " " . $albaran->show_hora(FALSE) . "\n";
         fwrite($file, $linea);
         $linea = "Cliente: " . $albaran->nombrecliente . "\n";
         fwrite($file, $linea);
         $linea = "Agente: " . $albaran->codagente . "\n\n";
         fwrite($file, $linea);
         
         $linea = sprintf("%3s", "Ud.") . " " . sprintf("%-25s", "Articulo") . " " . sprintf("%10s", "P.U.") . "\n";
         fwrite($file, $linea);
         $linea = sprintf("%3s", "---") . " " . sprintf("%-25s", "-------------------------") . " ".
            sprintf("%10s", "----------") . "\n";
         fwrite($file, $linea);
         
         foreach($albaran->get_lineas() as $col)
         {
            $linea = sprintf("%3s", $col->cantidad) . " " . sprintf("%-25s", $col->referencia) . " ".
               sprintf("%10s", $col->show_pvp_iva()) . "\n";
            fwrite($file, $linea);
         }
         
         $linea = "----------------------------------------\n".
            $this->center_text("IVA: " . number_format($albaran->totaliva,2,',','.') . " Eur.  ".
            "Total: " . $albaran->show_total() . " Eur.") . "\n\n";
         if( isset($_POST['efectivo']) )
            $linea .= $this->center_text("Efectivo..........: ".
                    sprintf("%12s",number_format($_POST['efectivo'],2,',','.')." Eur."))."\n";
         if( isset($_POST['cambio']) )
            $linea .= $this->center_text("Cambio............: ".
                    sprintf("%12s",number_format($_POST['cambio'],2,',','.')." Eur."))."\n";
         $linea .= "\n\n\n";
         fwrite($file, $linea);
         
         $linea = chr(27).chr(33).chr(56).$this->center_text($this->empresa->nombre,16).chr(27).chr(33).chr(1)."\n"; /// letras grandes
         fwrite($file, $linea);
         $linea = $this->center_text($this->empresa->lema) . "\n\n";
         fwrite($file, $linea);
         $linea = $this->center_text($this->empresa->direccion . " - " . $this->empresa->ciudad) . "\n";
         fwrite($file, $linea);
         $linea = $this->center_text("CIF: " . $this->empresa->cifnif) . chr(27).chr(105) . "\n\n"; /// corta el papel
         fwrite($file, $linea);
         $linea = $this->center_text($this->empresa->horario) . "\n";
         fwrite($file, $linea);
         fclose($file);
      }
      
      if( file_exists("/tmp/ticket.txt") )
      {
         if($this->impresora)
            $imp = " -d ".$this->impresora;
         else
            $imp = "";
         
         shell_exec("cat /tmp/ticket.txt | lp".$imp); /// imprime
         shell_exec("cat /tmp/ticket.txt | lp".$imp); /// imprime
         shell_exec("echo '".chr(27).chr(112).chr(48)."' | lp".$imp); /// abre el cajón
         unlink("/tmp/ticket.txt"); /// borra el ticket
      }
   }
   
   private function center_text($word='', $tot_width=40)
   {
      if(strlen($word) >= $tot_width)
         return $word;
      else
      {
         $symbol = " ";
         $middle = round($tot_width / 2);
         $length_word = strlen($word);
         $middle_word = round($length_word / 2);
         $last_position = $middle + $middle_word;
         $number_of_spaces = $middle - $middle_word;
         $result = sprintf("%'{$symbol}{$last_position}s", $word);
         for ($i = 0; $i < $number_of_spaces; $i++)
            $result .= "$symbol";
         return $result;
      }
   }
}

?>
