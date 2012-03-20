<?php

require_once 'model/articulo.php';
require_once 'model/familia.php';
require_once 'model/cliente.php';
require_once 'model/ejercicio.php';
require_once 'model/serie.php';
require_once 'model/forma_pago.php';
require_once 'model/divisa.php';
require_once 'model/albaran_cliente.php';

class tpv_yamyam extends fs_controller
{
   public $familia;
   public $cliente;
   public $ejercicio;
   public $serie;
   public $impuesto;
   public $agente;
   public $forma_pago;
   public $divisa;
   public $albaran;
   public $impresora;
   
   public function __construct()
   {
      parent::__construct('tpv_yamyam', 'TPV yamyam', 'TPV', FALSE, TRUE);
   }
   
   protected function process()
   {
      $this->css_file = 'touch.css';
      $this->familia = new familia();
      $this->cliente = new cliente();
      $this->ejercicio = new ejercicio();
      $this->serie = new serie();
      $this->agente = $this->user->get_agente();
      $this->forma_pago = new forma_pago();
      $this->divisa = new divisa();
      
      if( isset($_POST['impresora']) )
      {
         $this->impresora = $_POST['impresora'];
         setcookie('impresora', $this->impresora, time()+FS_COOKIES_EXPIRE);
      }
      else if( isset($_COOKIE['impresora']) )
         $this->impresora = $_COOKIE['impresora'];
      
      if( isset($_POST['numlineas']) )
      {
         $this->cliente = $this->cliente->get($_POST['cliente']);
         $this->ejercicio = $this->ejercicio->get($_POST['ejercicio']);
         $this->serie = $this->serie->get($_POST['serie']);
         $this->forma_pago = $this->forma_pago->get($_POST['forma_pago']);
         $this->divisa = $this->divisa->get($_POST['divisa']);
         $this->albaran = new albaran_cliente();
         $this->albaran->codcliente = $this->cliente->codcliente;
         $this->albaran->cifnif = $this->cliente->cifnif;
         $this->albaran->nombrecliente = $this->cliente->nombre;
         $this->albaran->codejercicio = $this->ejercicio->codejercicio;
         $this->albaran->codserie = $this->serie->codserie;
         $this->albaran->codpago = $this->forma_pago->codpago;
         $this->albaran->coddivisa = $this->divisa->coddivisa;
         $this->albaran->codagente = $this->agente->codagente;
         $this->albaran->observaciones = $_POST['observaciones'];
         if( $this->albaran->save() )
         {
            $n = floatval($_POST['numlineas']);
            for($i = 1; $i <= $n; $i++)
            {
               if( isset($_POST['referencia_'.$i]) )
               {
                  $articulo = new articulo();
                  $articulo = $articulo->get($_POST['referencia_'.$i]);
                  if($articulo)
                  {
                     $linea = new linea_albaran_cliente();
                     $linea->idalbaran = $this->albaran->idalbaran;
                     $linea->referencia = $articulo->referencia;
                     $linea->descripcion = $articulo->descripcion;
                     $linea->codimpuesto = $articulo->codimpuesto;
                     $linea->iva = floatval($_POST['iva_'.$i]);
                     $linea->pvpunitario = floatval($_POST['pvp_'.$i]);
                     $linea->cantidad = floatval($_POST['cantidad_'.$i]);
                     $linea->pvpsindto = ($linea->pvpunitario * $linea->cantidad);
                     $linea->pvptotal = ($linea->pvpunitario * $linea->cantidad);
                     
                     if( $linea->save() )
                     {
                        $this->albaran->neto += $linea->pvptotal;
                        $this->albaran->totaliva += ($linea->iva * $linea->pvptotal / 100);
                        $this->albaran->total = ($this->albaran->neto + $this->albaran->totaliva);
                        $this->albaran->totaleuros = ($this->albaran->neto + $this->albaran->totaliva);
                     }
                     else
                        $this->new_error_msg("¡Imposible guardar la linea con referencia: ".$linea->referencia);
                  }
               }
            }
            if( $this->albaran->save() )
            {
               $this->new_message("Albaran guardado correctamente");
               $this->imprimir_ticket();
            }
            else
               $this->new_error_msg("¡Imposible actualizar el albaran!");
         }
         else
            $this->new_error_msg("¡Imposible guardar el albaran!");
      }
   }
   
   private function imprimir_ticket()
   {
      /// abrimos el archivo temporal
      $file = fopen("/tmp/ticket.txt", "w");
      if($file)
      {
         $empresa = new empresa();
         $linea = "\nTicket: " . $this->albaran->codigo;
         $linea .= " " . $this->albaran->show_fecha() . "\n";
         $linea .= " " . $this->albaran->show_hora(FALSE) . "\n";
         fwrite($file, $linea);
         $linea = "Cliente: " . $this->albaran->nombrecliente . "\n";
         fwrite($file, $linea);
         $linea = "Agente: " . $this->albaran->codagente . "\n\n";
         fwrite($file, $linea);
         
         $linea = sprintf("%3s", "Ud.") . " " . sprintf("%-25s", "Articulo") . " " . sprintf("%10s", "P.U.") . "\n";
         fwrite($file, $linea);
         $linea = sprintf("%3s", "---") . " " . sprintf("%-25s", "-------------------------") . " ".
            sprintf("%10s", "----------") . "\n";
         fwrite($file, $linea);
         
         foreach($this->albaran->get_lineas() as $col)
         {
            $linea = sprintf("%3s", $col->cantidad) . " " . sprintf("%-25s", $col->referencia) . " ".
               sprintf("%10s", $col->show_pvp_iva()) . "\n";
            fwrite($file, $linea);
         }
         
         $linea = "----------------------------------------\n".
            $this->center_text("IVA: " . number_format($this->albaran->totaliva, 2) . " Eur.  ".
            "Total: " . $this->albaran->show_total() . " Eur.") . "\n\n\n\n";
         fwrite($file, $linea);
         $linea = $this->center_text($empresa->nombre) . "\n";
         fwrite($file, $linea);
         $linea = $this->center_text($empresa->direccion . " - " . $empresa->ciudad) . "\n";
         fwrite($file, $linea);
         $linea = $this->center_text("CIF: " . $empresa->cifnif) . "\n\n";
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
         shell_exec("echo '\x1D\x56\x1' | lp".$imp); /// corta
         shell_exec("cat /tmp/ticket.txt | lp".$imp); /// imprime
         shell_exec("echo '\x1D\x56\x1' | lp".$imp); /// corta
         shell_exec("echo '".chr(27).chr(112).chr(48)."' | lp".$imp); /// abre el cajón
         unlink("/tmp/ticket.txt"); /// borra el ticket
      }
   }
   
   private function center_text($word)
   {
      $tot_width = 40;
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

?>
