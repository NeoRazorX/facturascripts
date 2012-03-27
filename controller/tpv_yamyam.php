<?php

require_once 'model/caja.php';
require_once 'model/articulo.php';
require_once 'model/familia.php';
require_once 'model/cliente.php';
require_once 'model/ejercicio.php';
require_once 'model/serie.php';
require_once 'model/forma_pago.php';
require_once 'model/divisa.php';
require_once 'model/albaran_cliente.php';
require_once 'model/paquete.php';

class tpv_yamyam extends fs_controller
{
   public $agente;
   public $albaran;
   public $articulo;
   public $articulos;
   public $caja;
   public $cliente;
   public $divisa;
   public $ejercicio;
   public $familia;
   public $familias;
   public $forma_pago;
   public $impresora;
   public $impuesto;
   public $paquete;
   public $paquetes;
   public $serie;
   
   public function __construct()
   {
      parent::__construct('tpv_yamyam', 'TPV yamyam', 'TPV', FALSE, TRUE);
   }
   
   protected function process()
   {
      $this->css_file = 'touch.css';
      $this->agente = $this->user->get_agente();
      $this->articulo = new articulo();
      $this->caja = new caja();
      $this->cliente = new cliente();
      $this->divisa = new divisa();
      $this->ejercicio = new ejercicio();
      $this->familia = new familia();
      $this->forma_pago = new forma_pago();
      $this->paquete = new paquete();
      $this->serie = new serie();
      
      /// seleccionamos impresora de tickets
      if( isset($_POST['impresora']) )
      {
         $this->impresora = $_POST['impresora'];
         setcookie('impresora', $this->impresora, time()+315360000);
      }
      else if( isset($_COOKIE['impresora']) )
         $this->impresora = $_COOKIE['impresora'];
      
      if($this->agente)
      {
         /// obtenemos el bloqueo de caja, sin esto no se puede continuar
         $this->caja = $this->caja->get_last_from_this_server();
         if( $this->caja )
         {
            if($this->caja->codagente == $this->user->codagente)
            {
               if( isset($_GET['cerrar_caja']) )
                  $this->cerrar_caja();
               else if( isset($_POST['numlineas']) )
                  $this->guardar_ticket();
               else if( isset($_GET['delete']) )
                  $this->borrar_ticket();
            }
            else
               $this->new_error_msg("Esta caja está bloqueada por el agente ".$this->caja->agente->get_fullname());
            $this->cargar_datos_tpv();
         }
         else if( isset($_POST['d_inicial']) )
         {
            $this->caja = new caja();
            $this->caja->codagente = $this->user->codagente;
            $this->caja->dinero_inicial = floatval($_POST['d_inicial']);
            $this->caja->dinero_fin = floatval($_POST['d_inicial']);
            if( $this->caja->save() )
               $this->new_message("Caja iniciada con ".$this->caja->show_dinero_inicial()." Euros.");
            else
               $this->new_error_msg("¡Imposible guardar los datos de caja!");
            $this->cargar_datos_tpv();
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
   
   private function cargar_datos_tpv()
   {
      /// cargamos todos los paquetes
      $this->paquetes = $this->paquete->all();
      /// cargamos los articulos que no sean paquetes
      $this->articulos = array();
      foreach($this->articulo->all(0, 100) as $a)
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
         if(!$encontrado AND $a->pvp > 0)
         {
            $this->articulos[] = $a;
         }
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
         {
            $this->familias[] = $f;
         }
      }
   }
   
   private function guardar_ticket()
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
               $articulo = $this->articulo->get($_POST['referencia_'.$i]);
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
            
            /// actualizamos la caja
            $this->caja->dinero_fin += $this->albaran->totaleuros;
            if( !$this->caja->save() )
               $this->new_error_msg("¡Imposible actualizar la caja!");
         }
         else
            $this->new_error_msg("¡Imposible actualizar el albaran!");
      }
      else
         $this->new_error_msg("¡Imposible guardar el albaran!");
   }
   
   private function borrar_ticket()
   {
      $this->albaran = new albaran_cliente();
      $alb = $this->albaran->get_by_codigo($_GET['delete']);
      if($alb)
      {
         if( $alb->delete() )
         {
            $this->new_message("Ticket ".$_GET['delete']." borrado correctamente.");
            
            /// actualizamos la caja
            $this->caja->dinero_fin -= $alb->totaleuros;
            if( !$this->caja->save() )
               $this->new_error_msg("¡Imposible actualizar la caja!");
         }
         else
            $this->new_error_msg("¡Imposible borrar el ticket ".$_GET['delete']."!");
      }
      else
         $this->new_error_msg("Ticket no encontrado.");
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
            $empresa = new empresa();
            $linea = "\n".chr(27).chr(33).chr(56)."CIERRE DE CAJA:".chr(27).chr(33).chr(1)."\n"; /// letras grandes
            fwrite($file, $linea);
            $linea = "Agente: ".$this->user->codagente." ".$this->agente->get_fullname()."\n";
            fwrite($file, $linea);
            $linea = "Caja: ".$this->caja->fs_id."\n";
            fwrite($file, $linea);
            $linea = "Fecha inicial: ".$this->caja->show_fecha_inicial()."\n";
            fwrite($file, $linea);
            $linea = "Dinero inicial: ".$this->caja->show_dinero_inicial()." Eur.\n";
            fwrite($file, $linea);
            $linea = "Fecha fin: ".$this->caja->show_fecha_fin()."\n";
            fwrite($file, $linea);
            $linea = "Dinero fin: ".$this->caja->show_dinero_fin()." Eur.\n";
            fwrite($file, $linea);
            $linea = "Diferencia: ".$this->caja->show_diferencia()." Eur.\n\n";
            fwrite($file, $linea);
            $linea = "Dinero pesado:\n\n\n";
            fwrite($file, $linea);
            $linea = "Observaciones:\n\n\n\n";
            fwrite($file, $linea);
            $linea = "Firma:\n\n\n\n\n\n\n";
            fwrite($file, $linea);
            
            /// encabezado común para los tickets
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
            shell_exec("echo '".chr(27).chr(112).chr(48)."' | lp".$imp); /// abre el cajón
            unlink("/tmp/ticket.txt"); /// borra el ticket
         }
         
         /// recargamos la página
         header('location: '.$this->url());
      }
      else
         $this->new_error_msg("¡Imposible cerrar la caja!");
   }

   private function imprimir_ticket()
   {
      /// abrimos el archivo temporal
      $file = fopen("/tmp/ticket.txt", "w");
      if($file)
      {
         $empresa = new empresa();
         $linea = "\nTicket: " . $this->albaran->codigo;
         $linea .= " " . $this->albaran->show_fecha();
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
            $this->center_text("IVA: " . number_format($this->albaran->totaliva,2,',','.') . " Eur.  ".
            "Total: " . $this->albaran->show_total() . " Eur.") . "\n\n";
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
