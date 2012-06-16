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

require_once 'base/fs_cache.php';
require_once 'model/agente.php';
require_once 'model/albaran_cliente.php';
require_once 'model/almacen.php';
require_once 'model/articulo.php';
require_once 'model/cliente.php';
require_once 'model/divisa.php';
require_once 'model/ejercicio.php';
require_once 'model/empresa.php';
require_once 'model/forma_pago.php';
require_once 'model/proveedor.php';
require_once 'model/serie.php';

class tpv_recambios extends fs_controller
{
   public $agente;
   public $almacen;
   public $articulo;
   public $cliente;
   public $divisa;
   public $ejercicio;
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
      $this->results = array();
      
      if( $this->query != '' )
         $this->new_search();
      else
      {
         $this->buttons[] = new fs_button('b_new_line', 'añadir artículo');
         
         $this->agente = $this->user->get_agente();
         $this->almacen = new almacen();
         $this->cliente = new cliente();
         $this->divisa = new divisa();
         $this->ejercicio = new ejercicio();
         $this->forma_pago = new forma_pago();
         $this->serie = new serie();
         
         /// seleccionamos impresora de tickets
         if( isset($_POST['impresora']) )
         {
            $this->impresora = $_POST['impresora'];
            setcookie('impresora', $this->impresora, time()+315360000);
         }
         else if( isset($_COOKIE['impresora']) )
            $this->impresora = $_COOKIE['impresora'];
         
         if( isset($_POST['cliente']) )
            $this->nuevo_albaran_cliente();
      }
   }
   
   private function new_search()
   {
      $cache = new fs_cache();
      $this->results = $cache->get_array('search_'.$this->query);
      if( count($this->results) < 1 )
      {
         $this->results = $this->articulo->search($this->query);
         $cache->set('search_'.$this->query, $this->results);
      }
   }
   
   private function nuevo_albaran_cliente()
   {
      $cliente = $this->cliente->get($_POST['cliente']);
      if( !$cliente->is_default() )
         $cliente->set_default();
      $dirscliente = $cliente->get_direcciones();
      
      $almacen = $this->almacen->get($_POST['almacen']);
      
      $ejercicio = $this->ejercicio->get($_POST['ejercicio']);
      if( !$ejercicio->is_default() )
         $ejercicio->set_default();
      
      $serie = $this->serie->get($_POST['serie']);
      if( !$serie->is_default() )
         $serie->set_default();
      
      $forma_pago = $this->forma_pago->get($_POST['forma_pago']);
      if( !$forma_pago->is_default() )
         $forma_pago->set_default();
      
      $divisa = $this->divisa->get($_POST['divisa']);
      if( !$divisa->is_default() )
         $divisa->set_default();
      
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
         }
         else
            $this->new_error_msg("¡Imposible actualizar el <a href='".$albaran->url()."'>albaran</a>!");
      }
      else
         $this->new_error_msg("¡Imposible guardar el albaran!");
   }
   
   private function imprimir_ticket($albaran)
   {
      /// abrimos el archivo temporal
      $file = fopen("/tmp/ticket.txt", "w");
      if($file)
      {
         $empresa = new empresa();
         $linea = "\nTicket: " . $albaran->codigo;
         $linea .= " " . $albaran->show_fecha();
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
         
         $linea = chr(27).chr(33).chr(56).$this->center_text($empresa->nombre,16).chr(27).chr(33).chr(1)."\n"; /// letras grandes
         fwrite($file, $linea);
         $linea = $this->center_text($empresa->lema) . "\n\n";
         fwrite($file, $linea);
         $linea = $this->center_text($empresa->direccion . " - " . $empresa->ciudad) . "\n";
         fwrite($file, $linea);
         $linea = $this->center_text("CIF: " . $empresa->cifnif) . chr(27).chr(105) . "\n\n"; /// corta el papel
         fwrite($file, $linea);
         $linea = $this->center_text($empresa->horario) . "\n";
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
