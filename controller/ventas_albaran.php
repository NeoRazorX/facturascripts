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

require_once 'base/fs_pdf.php';
require_model('albaran_cliente.php');
require_model('articulo.php');
require_model('asiento.php');
require_model('cliente.php');
require_model('ejercicio.php');
require_model('factura_cliente.php');
require_model('familia.php');
require_model('fs_var.php');
require_model('impuesto.php');
require_model('partida.php');
require_model('regularizacion_iva.php');
require_model('serie.php');
require_model('subcuenta.php');
require_once 'extras/phpmailer/class.phpmailer.php';
require_once 'extras/phpmailer/class.smtp.php';

class ventas_albaran extends fs_controller
{
   public $agente;
   public $albaran;
   public $cliente;
   public $cliente_email;
   public $ejercicio;
   public $familia;
   public $impuesto;
   public $nuevo_albaran_url;
   public $serie;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, FS_ALBARAN.' de cliente', 'ventas', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->ppage = $this->page->get('ventas_albaranes');
      $this->agente = FALSE;
      $albaran = new albaran_cliente();
      $this->albaran = FALSE;
      $this->cliente = new cliente();
      $this->cliente_email = '';
      $this->ejercicio = new ejercicio();
      $this->familia = new familia();
      $this->impuesto = new impuesto();
      $this->nuevo_albaran_url = FALSE;
      $this->serie = new serie();
      
      /**
       * Comprobamos si el usuario tiene acceso a nueva_venta,
       * necesario para poder añadir líneas.
       */
      if( $this->user->have_access_to('nueva_venta', FALSE) )
      {
         $nuevoalbp = $this->page->get('nueva_venta');
         if($nuevoalbp)
            $this->nuevo_albaran_url = $nuevoalbp->url();
      }
      
      if( isset($_POST['idalbaran']) )
      {
         $this->albaran = $albaran->get($_POST['idalbaran']);
         $this->modificar();
      }
      else if( isset($_GET['id']) )
      {
         $this->albaran = $albaran->get($_GET['id']);
      }
      
      if( $this->albaran AND isset($_GET['imprimir']) )
      {
         if($_GET['imprimir'] == 'simple')
         {
            $this->generar_pdf_simple();
         }
         else
         {
            $this->generar_pdf_cuartilla();
         }
      }
      else if( $this->albaran )
      {
         $this->page->title = $this->albaran->codigo;
         $this->agente = $this->albaran->get_agente();
         
         /**
          * Como es una plantilla compleja, he separado el código HTML
          * en dos archivos: general_albaran_cli_edit.html para los
          * albaranes editables y general_albaran_cli.html para los demás.
          */
         if($this->albaran->ptefactura)
            $this->template = 'ventas_albaran_edit';
         else
            $this->template = 'ventas_albaran';
         
         /// comprobamos el albarán
         if( $this->albaran->full_test() )
         {
            if( isset($_GET['facturar']) AND isset($_GET['petid']) AND $this->albaran->ptefactura )
            {
               if( $this->duplicated_petition($_GET['petid']) )
                  $this->new_error_msg('Petición duplicada. Evita hacer doble clic sobre los botones.');
               else
                  $this->generar_factura();
            }
            
            $this->buttons[] = new fs_button('b_copiar', 'Copiar', 'index.php?page=copy_albaran&idalbcli='.$this->albaran->idalbaran, TRUE);
            $this->buttons[] = new fs_button_img('b_imprimir', 'Imprimir', 'print.png');
            
            /// comprobamos si se pueden enviar emails
            if( $this->empresa->can_send_mail() )
            {
               $cliente = $this->cliente->get($this->albaran->codcliente);
               if($cliente)
               {
                  $this->cliente_email = $cliente->email;
               }
               
               $this->buttons[] = new fs_button_img('b_enviar', 'Enviar', 'send.png');
               
               if( isset($_POST['email']) )
               {
                  $this->enviar_email();
               }
            }
         
            if( $this->albaran->ptefactura )
            {
               $this->buttons[] = new fs_button('b_facturar', 'Generar factura', $this->url()."&facturar=TRUE&petid=".$this->random_string());
            }
            else if( isset($this->albaran->idfactura) )
            {
               $this->buttons[] = new fs_button('b_ver_factura', 'Factura', $this->albaran->factura_url());
            }
         }
         
         $this->buttons[] = new fs_button_img('b_remove_albaran', 'Eliminar', 'trash.png', '#', TRUE);
      }
      else
         $this->new_error_msg("¡".FS_ALBARAN." de cliente no encontrado!");
   }
   
   public function url()
   {
      if( !isset($this->albaran) )
         return parent::url();
      else if($this->albaran)
         return $this->albaran->url();
      else
         return $this->page->url();
   }
   
   private function modificar()
   {
      $this->albaran->numero2 = $_POST['numero2'];
      $this->albaran->hora = $_POST['hora'];
      $this->albaran->observaciones = $_POST['observaciones'];
      
      if($this->albaran->ptefactura)
      {
         /// obtenemos los datos del ejercicio para acotar la fecha
         $eje0 = $this->ejercicio->get( $this->albaran->codejercicio );
         if($eje0)
            $this->albaran->fecha = $eje0->get_best_fecha($_POST['fecha'], TRUE);
         else
            $this->new_error_msg('No se encuentra el ejercicio asociado al '.FS_ALBARAN);
         
         /// ¿cambiamos el cliente?
         if($_POST['cliente'] != $this->albaran->codcliente)
         {
            $cliente = $this->cliente->get($_POST['cliente']);
            if($cliente)
            {
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
            }
         }
         
         $serie = $this->serie->get($this->albaran->codserie);
         
         /// ¿cambiamos la serie?
         if($_POST['serie'] != $this->albaran->codserie)
         {
            $this->albaran->codserie = $_POST['serie'];
            $this->albaran->new_codigo();
         }
         
         if( isset($_POST['lineas']) )
         {
            $this->albaran->neto = 0;
            $this->albaran->totaliva = 0;
            $lineas = $this->albaran->get_lineas();
            $articulo = new articulo();
            
            /// eliminamos las líneas que no encontremos en el $_POST
            foreach($lineas as $l)
            {
               $encontrada = FALSE;
               for($num = 0; $num <= 200; $num++)
               {
                  if( isset($_POST['idlinea_'.$num]) )
                  {
                     if($l->idlinea == intval($_POST['idlinea_'.$num]))
                     {
                        $encontrada = TRUE;
                        break;
                     }
                  }
               }
               if( !$encontrada )
               {
                  if( $l->delete() )
                  {
                     /// actualizamos el stock
                     $art0 = $articulo->get($l->referencia);
                     if($art0)
                        $art0->sum_stock($this->albaran->codalmacen, $l->cantidad);
                  }
                  else
                     $this->new_error_msg("¡Imposible eliminar la línea del artículo ".$l->referencia."!");
               }
            }
            
            /// modificamos y/o añadimos las demás líneas
            for($num = 0; $num <= 200; $num++)
            {
               $encontrada = FALSE;
               if( isset($_POST['idlinea_'.$num]) )
               {
                  foreach($lineas as $k => $value)
                  {
                     /// modificamos la línea
                     if($value->idlinea == intval($_POST['idlinea_'.$num]))
                     {
                        $encontrada = TRUE;
                        $cantidad_old = $value->cantidad;
                        $lineas[$k]->cantidad = floatval($_POST['cantidad_'.$num]);
                        $lineas[$k]->pvpunitario = floatval($_POST['pvp_'.$num]);
                        $lineas[$k]->dtopor = floatval($_POST['dto_'.$num]);
                        $lineas[$k]->dtolineal = 0;
                        $lineas[$k]->pvpsindto = ($value->cantidad * $value->pvpunitario);
                        $lineas[$k]->pvptotal = ($value->cantidad * $value->pvpunitario * (100 - $value->dtopor)/100);
                        
                        if( isset($_POST['desc_'.$num]) )
                           $lineas[$k]->descripcion = $_POST['desc_'.$num];
                        
                        if( $serie->siniva OR $cliente->regimeniva == 'Exento' )
                        {
                           $lineas[$k]->codimpuesto = NULL;
                           $lineas[$k]->iva = 0;
                        }
                        else
                        {
                           $imp0 = $this->impuesto->get_by_iva($_POST['iva_'.$num]);
                           if($imp0)
                           {
                              $lineas[$k]->codimpuesto = $imp0->codimpuesto;
                              $lineas[$k]->iva = $imp0->iva;
                           }
                           else
                           {
                              $lineas[$k]->codimpuesto = NULL;
                              $lineas[$k]->iva = floatval($_POST['iva_'.$num]);
                           }
                        }
                        
                        if( $lineas[$k]->save() )
                        {
                           $this->albaran->neto += ($value->cantidad*$value->pvpunitario*(100-$value->dtopor)/100);
                           $this->albaran->totaliva += ($value->cantidad*$value->pvpunitario*(100-$value->dtopor)/100*$value->iva/100);
                           
                           /// actualizamos el stock
                           $art0 = $articulo->get($value->referencia);
                           if($art0)
                              $art0->sum_stock($this->albaran->codalmacen, $cantidad_old - $lineas[$k]->cantidad);
                        }
                        else
                           $this->new_error_msg("¡Imposible modificar la línea del artículo ".$value->referencia."!");
                        break;
                     }
                  }
                  
                  /// añadimos la línea
                  if(!$encontrada AND intval($_POST['idlinea_'.$num]) == -1 AND isset($_POST['referencia_'.$num]))
                  {
                     $art0 = $articulo->get( $_POST['referencia_'.$num] );
                     if($art0)
                     {
                        $linea = new linea_albaran_cliente();
                        $linea->referencia = $art0->referencia;
                        
                        if( isset($_POST['desc_'.$num]) )
                           $linea->descripcion = $_POST['desc_'.$num];
                        else
                           $linea->descripcion = $art0->descripcion;
                        
                        if( $serie->siniva OR $cliente->regimeniva == 'Exento' )
                        {
                           $linea->codimpuesto = NULL;
                           $linea->iva = 0;
                        }
                        else
                        {
                           $imp0 = $this->impuesto->get_by_iva($_POST['iva_'.$num]);
                           if($imp0)
                           {
                              $linea->codimpuesto = $imp0->codimpuesto;
                              $linea->iva = $imp0->iva;
                           }
                           else
                           {
                              $linea->codimpuesto = NULL;
                              $linea->iva = floatval($_POST['iva_'.$num]);
                           }
                        }
                        
                        $linea->idalbaran = $this->albaran->idalbaran;
                        $linea->cantidad = floatval($_POST['cantidad_'.$num]);
                        $linea->pvpunitario = floatval($_POST['pvp_'.$num]);
                        $linea->dtopor = floatval($_POST['dto_'.$num]);
                        $linea->pvpsindto = ($linea->cantidad * $linea->pvpunitario);
                        $linea->pvptotal = ($linea->cantidad * $linea->pvpunitario * (100 - $linea->dtopor)/100);
                        
                        if( $linea->save() )
                        {
                           $this->albaran->neto += ($linea->cantidad*$linea->pvpunitario*(100-$linea->dtopor)/100);
                           $this->albaran->totaliva += ($linea->cantidad*$linea->pvpunitario*(100-$linea->dtopor)/100*$linea->iva/100);
                           
                           /// actualizamos el stock
                           $art0->sum_stock($this->albaran->codalmacen, 0 - $linea->cantidad);
                        }
                        else
                           $this->new_error_msg("¡Imposible guardar la línea del artículo ".$linea->referencia."!");
                     }
                     else
                        $this->new_error_msg("¡Artículo ".$_POST['referencia_'.$num]." no encontrado!");
                  }
               }
            }
            
            /// redondeamos
            $this->albaran->neto = round($this->albaran->neto, 2);
            $this->albaran->totaliva = round($this->albaran->totaliva, 2);
            $this->albaran->total = $this->albaran->neto + $this->albaran->totaliva;
         }
      }
      
      if( $this->albaran->save() )
      {
         $this->new_message(ucfirst(FS_ALBARAN)." modificado correctamente.");
         $this->new_change(ucfirst(FS_ALBARAN).' Cliente '.$this->albaran->codigo, $this->albaran->url());
      }
      else
         $this->new_error_msg("¡Imposible modificar el ".FS_ALBARAN."!");
   }
   
   private function generar_factura()
   {
      $factura = new factura_cliente();
      $factura->apartado = $this->albaran->apartado;
      $factura->automatica = TRUE;
      $factura->cifnif = $this->albaran->cifnif;
      $factura->ciudad = $this->albaran->ciudad;
      $factura->codagente = $this->albaran->codagente;
      $factura->codalmacen = $this->albaran->codalmacen;
      $factura->codcliente = $this->albaran->codcliente;
      $factura->coddir = $this->albaran->coddir;
      $factura->coddivisa = $this->albaran->coddivisa;
      $factura->tasaconv = $this->albaran->tasaconv;
      $factura->codejercicio = $this->albaran->codejercicio;
      $factura->codpago = $this->albaran->codpago;
      $factura->codpais = $this->albaran->codpais;
      $factura->codpostal = $this->albaran->codpostal;
      $factura->codserie = $this->albaran->codserie;
      $factura->direccion = $this->albaran->direccion;
      $factura->editable = FALSE;
      $factura->neto = $this->albaran->neto;
      $factura->nombrecliente = $this->albaran->nombrecliente;
      $factura->observaciones = $this->albaran->observaciones;
      $factura->provincia = $this->albaran->provincia;
      $factura->total = $this->albaran->total;
      $factura->totaliva = $this->albaran->totaliva;
      
      /// asignamos la mejor fecha posible, pero dentro del ejercicio
      $eje0 = $this->ejercicio->get($factura->codejercicio);
      $factura->fecha = $eje0->get_best_fecha($factura->fecha);
      
      $regularizacion = new regularizacion_iva();
      
      if( !$eje0->abierto() )
      {
         $this->new_error_msg("El ejercicio está cerrado.");
      }
      else if( $regularizacion->get_fecha_inside($factura->fecha) )
      {
         $this->new_error_msg("El IVA de ese periodo ya ha sido regularizado.
            No se pueden añadir más facturas en esa fecha.");
      }
      else if( $factura->save() )
      {
         $continuar = TRUE;
         foreach($this->albaran->get_lineas() as $l)
         {
            $n = new linea_factura_cliente();
            $n->idalbaran = $l->idalbaran;
            $n->idfactura = $factura->idfactura;
            $n->cantidad = $l->cantidad;
            $n->codimpuesto = $l->codimpuesto;
            $n->descripcion = $l->descripcion;
            $n->dtolineal = $l->dtolineal;
            $n->dtopor = $l->dtopor;
            $n->irpf = $l->irpf;
            $n->iva = $l->iva;
            $n->pvpsindto = $l->pvpsindto;
            $n->pvptotal = $l->pvptotal;
            $n->pvpunitario = $l->pvpunitario;
            $n->recargo = $l->recargo;
            $n->referencia = $l->referencia;
            if( !$n->save() )
            {
               $continuar = FALSE;
               $this->new_error_msg("¡Imposible guardar la línea el artículo ".$n->referencia."! ");
               break;
            }
         }
         
         if($continuar)
         {
            $this->albaran->idfactura = $factura->idfactura;
            $this->albaran->ptefactura = FALSE;
            if( $this->albaran->save() )
            {
               $this->generar_asiento($factura);
            }
            else
            {
               $this->new_error_msg("¡Imposible vincular el ".FS_ALBARAN." con la nueva factura!");
               if( $factura->delete() )
                  $this->new_error_msg("La factura se ha borrado.");
               else
                  $this->new_error_msg("¡Imposible borrar la factura!");
            }
         }
         else
         {
            if( $factura->delete() )
               $this->new_error_msg("La factura se ha borrado.");
            else
               $this->new_error_msg("¡Imposible borrar la factura!");
         }
      }
      else
         $this->new_error_msg("¡Imposible guardar la factura!");
   }
   
   private function generar_asiento($factura)
   {
      $cliente = $this->cliente->get($factura->codcliente);
      $subcuenta_cli = $cliente->get_subcuenta($factura->codejercicio);
      
      if( !$this->empresa->contintegrada )
      {
         $this->new_message("<a href='".$factura->url()."'>Factura</a> generada correctamente.");
         $this->new_change('Factura Cliente '.$factura->codigo, $factura->url(), TRUE);
      }
      else if( !$subcuenta_cli )
      {
         $eje0 = $this->ejercicio->get( $this->albaran->codejercicio );
         $this->new_message("No se ha podido generar una subcuenta para el cliente
            <a href='".$eje0->url()."'>¿Has importado los datos del ejercicio?</a>
            Aun así la <a href='".$factura->url()."'>factura</a> se ha generado correctamente,
            pero sin asiento contable.");
      }
      else
      {
         $asiento = new asiento();
         $asiento->codejercicio = $factura->codejercicio;
         $asiento->concepto = "Nuestra factura ".$factura->codigo." - ".$factura->nombrecliente;
         $asiento->documento = $factura->codigo;
         $asiento->editable = FALSE;
         $asiento->fecha = $factura->fecha;
         $asiento->importe = $factura->total;
         $asiento->tipodocumento = 'Factura de cliente';
         if( $asiento->save() )
         {
            $asiento_correcto = TRUE;
            $subcuenta = new subcuenta();
            $partida0 = new partida();
            $partida0->idasiento = $asiento->idasiento;
            $partida0->concepto = $asiento->concepto;
            $partida0->idsubcuenta = $subcuenta_cli->idsubcuenta;
            $partida0->codsubcuenta = $subcuenta_cli->codsubcuenta;
            $partida0->debe = $factura->total;
            $partida0->coddivisa = $factura->coddivisa;
            $partida0->tasaconv = $factura->tasaconv;
            if( !$partida0->save() )
            {
               $asiento_correcto = FALSE;
               $this->new_error_msg("¡Imposible generar la partida para la subcuenta ".$partida0->codsubcuenta."!");
            }
            
            /// generamos una partida por cada impuesto
            $subcuenta_iva = $subcuenta->get_cuentaesp('IVAREP', $asiento->codejercicio);
            foreach($factura->get_lineas_iva() as $li)
            {
               if($subcuenta_iva AND $asiento_correcto)
               {
                  $partida1 = new partida();
                  $partida1->idasiento = $asiento->idasiento;
                  $partida1->concepto = $asiento->concepto;
                  $partida1->idsubcuenta = $subcuenta_iva->idsubcuenta;
                  $partida1->codsubcuenta = $subcuenta_iva->codsubcuenta;
                  $partida1->haber = $li->totaliva;
                  $partida1->idcontrapartida = $subcuenta_cli->idsubcuenta;
                  $partida1->codcontrapartida = $subcuenta_cli->codsubcuenta;
                  $partida1->cifnif = $cliente->cifnif;
                  $partida1->documento = $asiento->documento;
                  $partida1->tipodocumento = $asiento->tipodocumento;
                  $partida1->codserie = $factura->codserie;
                  $partida1->factura = $factura->numero;
                  $partida1->baseimponible = $li->neto;
                  $partida1->iva = $li->iva;
                  $partida1->coddivisa = $factura->coddivisa;
                  $partida1->tasaconv = $factura->tasaconv;
                  if( !$partida1->save() )
                  {
                     $asiento_correcto = FALSE;
                     $this->new_error_msg("¡Imposible generar la partida para la subcuenta ".$partida1->codsubcuenta."!");
                  }
               }
            }
            
            $subcuenta_ventas = $subcuenta->get_cuentaesp('VENTAS', $asiento->codejercicio);
            if($subcuenta_ventas AND $asiento_correcto)
            {
               $partida2 = new partida();
               $partida2->idasiento = $asiento->idasiento;
               $partida2->concepto = $asiento->concepto;
               $partida2->idsubcuenta = $subcuenta_ventas->idsubcuenta;
               $partida2->codsubcuenta = $subcuenta_ventas->codsubcuenta;
               $partida2->haber = $factura->neto;
               $partida2->coddivisa = $factura->coddivisa;
               $partida2->tasaconv = $factura->tasaconv;
               if( !$partida2->save() )
               {
                  $asiento_correcto = FALSE;
                  $this->new_error_msg("¡Imposible generar la partida para la subcuenta ".$partida2->codsubcuenta."!");
               }
            }
            
            if($asiento_correcto)
            {
               $factura->idasiento = $asiento->idasiento;
               if( $factura->save() )
               {
                  $this->new_message("<a href='".$factura->url()."'>Factura</a> generada correctamente.");
                  $this->new_change('Factura Cliente '.$factura->codigo, $factura->url(), TRUE);
               }
               else
                  $this->new_error_msg("¡Imposible añadir el asiento a la factura!");
            }
            else
            {
               if( $asiento->delete() )
               {
                  $this->new_message("El asiento se ha borrado.");
                  if( $factura->delete() )
                     $this->new_message("La factura se ha borrado.");
                  else
                     $this->new_error_msg("¡Imposible borrar la factura!");
               }
               else
                  $this->new_error_msg("¡Imposible borrar el asiento!");
            }
         }
         else
         {
            $this->new_error_msg("¡Imposible guardar el asiento!");
            if( $factura->delete() )
               $this->new_error_msg("La factura se ha borrado.");
            else
               $this->new_error_msg("¡Imposible borrar la factura!");
         }
      }
   }
   
   private function generar_pdf_simple($archivo = FALSE)
   {
      if( !$archivo )
      {
         /// desactivamos la plantilla HTML
         $this->template = FALSE;
      }
      
      $pdf_doc = new fs_pdf();
      $pdf_doc->pdf->addInfo('Title', FS_ALBARAN.' '. $this->albaran->codigo);
      $pdf_doc->pdf->addInfo('Subject', FS_ALBARAN.' de cliente ' . $this->albaran->codigo);
      $pdf_doc->pdf->addInfo('Author', $this->empresa->nombre);
      
      $lineas = $this->albaran->get_lineas();
      if($lineas)
      {
         $linea_actual = 0;
         $lppag = 42;
         $pagina = 1;
         
         /// imprimimos las páginas necesarias
         while( $linea_actual < count($lineas) )
         {
            /// salto de página
            if($linea_actual > 0)
               $pdf_doc->pdf->ezNewPage();
            
            /// ¿Añadimos el logo?
            if( file_exists('tmp/logo.png') )
            {
               $pdf_doc->pdf->ezImage('tmp/logo.png', 0, 200, 'none');
               $lppag -= 2; /// si metemos el logo, caben menos líneas
            }
            else
            {
               $pdf_doc->pdf->ezText("<b>".$this->empresa->nombre."</b>", 16, array('justification' => 'center'));
               $pdf_doc->pdf->ezText("CIF/NIF: ".$this->empresa->cifnif, 8, array('justification' => 'center'));
               
               $direccion = $this->empresa->direccion;
               if($this->empresa->codpostal)
                  $direccion .= ' - ' . $this->empresa->codpostal;
               if($this->empresa->ciudad)
                  $direccion .= ' - ' . $this->empresa->ciudad;
               if($this->empresa->provincia)
                  $direccion .= ' (' . $this->empresa->provincia . ')';
               if($this->empresa->telefono)
                  $direccion .= ' - Teléfono: ' . $this->empresa->telefono;
               $pdf_doc->pdf->ezText($direccion, 9, array('justification' => 'center'));
            }
            
            /*
             * Esta es la tabla con los datos del cliente:
             * Albarán:             Fecha:
             * Cliente:             CIF/NIF:
             * Dirección:           Teléfonos:
             */
            $pdf_doc->new_table();
            $pdf_doc->add_table_row(
               array(
                   'campo1' => "<b>".ucfirst(FS_ALBARAN).":</b>",
                   'dato1' => $this->albaran->codigo,
                   'campo2' => "<b>Fecha:</b>",
                   'dato2' => $this->albaran->fecha
               )
            );
            $pdf_doc->add_table_row(
               array(
                   'campo1' => "<b>Cliente:</b>",
                   'dato1' => $this->albaran->nombrecliente,
                   'campo2' => "<b>CIF/NIF:</b>",
                   'dato2' => $this->albaran->cifnif
               )
            );
            $pdf_doc->add_table_row(
               array(
                   'campo1' => "<b>Dirección:</b>",
                   'dato1' => $this->albaran->direccion.' CP: '.$this->albaran->codpostal.' - '.$this->albaran->ciudad.' ('.$this->albaran->provincia.')',
                   'campo2' => "<b>Teléfonos:</b>",
                   'dato2' => $this->cliente->telefono1.'  '.$this->cliente->telefono2
               )
            );
            $pdf_doc->save_table(
               array(
                   'cols' => array(
                       'campo1' => array('justification' => 'right'),
                       'dato1' => array('justification' => 'left'),
                       'campo2' => array('justification' => 'right'),
                       'dato2' => array('justification' => 'left')
                   ),
                   'showLines' => 0,
                   'width' => 540,
                   'shaded' => 0
               )
            );
            $pdf_doc->pdf->ezText("\n", 10);
            
            
            /*
             * Creamos la tabla con las lineas del albarán:
             * 
             * Descripción    PVP   DTO   Cantidad    Importe
             */
            $pdf_doc->new_table();
            $pdf_doc->add_table_header(
               array(
                  'descripcion' => '<b>Descripción</b>',
                  'pvp' => '<b>PVP</b>',
                  'dto' => '<b>DTO</b>',
                  'cantidad' => '<b>Cantidad</b>',
                  'importe' => '<b>Importe</b>'
               )
            );
            $saltos = 0;
            $subtotal = 0;
            $impuestos = array();
            for($i = $linea_actual; (($linea_actual < ($lppag + $i)) AND ($linea_actual < count($lineas)));)
            {
               if( !isset($impuestos[$lineas[$linea_actual]->iva]) )
                  $impuestos[$lineas[$linea_actual]->iva] = $lineas[$linea_actual]->pvptotal * $lineas[$linea_actual]->iva / 100;
               else
                  $impuestos[$lineas[$linea_actual]->iva] += $lineas[$linea_actual]->pvptotal * $lineas[$linea_actual]->iva / 100;
               
               $fila = array(
                  'descripcion' => substr($lineas[$linea_actual]->descripcion, 0, 45),
                  'pvp' => $this->show_precio($lineas[$linea_actual]->pvpunitario, $this->albaran->coddivisa),
                  'dto' => $this->show_numero($lineas[$linea_actual]->dtopor, 0) . " %",
                  'cantidad' => $lineas[$linea_actual]->cantidad,
                  'importe' => $this->show_precio($lineas[$linea_actual]->pvptotal, $this->albaran->coddivisa)
               );
               
               if($lineas[$linea_actual]->referencia != '0')
                  $fila['descripcion'] = substr($lineas[$linea_actual]->referencia.' - '.$lineas[$linea_actual]->descripcion, 0, 40);
               
               $pdf_doc->add_table_row($fila);
               $saltos++;
               $linea_actual++;
            }
            $pdf_doc->save_table(
               array(
                   'fontSize' => 8,
                   'cols' => array(
                       'pvp' => array('justification' => 'right'),
                       'dto' => array('justification' => 'right'),
                       'cantidad' => array('justification' => 'right'),
                       'importe' => array('justification' => 'right')
                   ),
                   'width' => 540,
                   'shaded' => 0
               )
            );
            
            
            /*
             * Rellenamos el hueco que falta hasta donde debe aparecer la última tabla
             */
            if($this->albaran->observaciones == '')
            {
               $salto = '';
            }
            else
            {
               $salto = "\n<b>Observaciones</b>: " . $this->albaran->observaciones;
               $saltos += count( explode("\n", $this->albaran->observaciones) ) - 1;
            }
            
            if($saltos < $lppag)
            {
               for(;$saltos < $lppag; $saltos++)
                  $salto .= "\n";
               $pdf_doc->pdf->ezText($salto, 11);
            }
            else if($linea_actual >= $lineasfact)
               $pdf_doc->pdf->ezText($salto, 11);
            else
               $pdf_doc->pdf->ezText("\n", 11);
            
            
            /*
             * Rellenamos la última tabla de la página:
             * 
             * Página            Neto    IVA   Total
             */
            $pdf_doc->new_table();
            $titulo = array('pagina' => '<b>Página</b>', 'neto' => '<b>Neto</b>',);
            $fila = array(
                'pagina' => $pagina . '/' . ceil(count($lineas) / $lppag),
                'neto' => $this->show_precio($this->albaran->neto, $this->albaran->coddivisa),
            );
            $opciones = array(
                'cols' => array(
                    'neto' => array('justification' => 'right'),
                ),
                'showLines' => 4,
                'width' => 540
            );
            foreach($impuestos as $i => $value)
            {
               $titulo['iva'.$i] = '<b>IVA '.$i.'%</b>';
               $fila['iva'.$i] = $this->show_precio($value, $this->albaran->coddivisa);
               $opciones['cols']['iva'.$i] = array('justification' => 'right');
            }
            $titulo['liquido'] = '<b>Total</b>';
            $fila['liquido'] = $this->show_precio($this->albaran->total, $this->albaran->coddivisa);
            $opciones['cols']['liquido'] = array('justification' => 'right');
            $pdf_doc->add_table_header($titulo);
            $pdf_doc->add_table_row($fila);
            $pdf_doc->save_table($opciones);
            $pdf_doc->pdf->ezText("\n", 10);
            
            $pdf_doc->pdf->addText(10, 10, 8, $pdf_doc->center_text($this->empresa->pie_factura, 153), 0, 1.5);
            
            $pagina++;
         }
      }
      
      if($archivo)
      {
         if( !file_exists('tmp/enviar') )
            mkdir('tmp/enviar');
         
         $pdf_doc->save('tmp/enviar/'.$archivo);
      }
      else
         $pdf_doc->show();
   }
   
   private function generar_pdf_cuartilla()
   {
      /// desactivamos el motor de plantillas
      $this->template = FALSE;
      
      $pdf_doc = new fs_pdf();
      $pdf_doc->pdf->addInfo('Title', FS_ALBARAN.' '. $this->albaran->codigo);
      $pdf_doc->pdf->addInfo('Subject', FS_ALBARAN.' de cliente ' . $this->albaran->codigo);
      $pdf_doc->pdf->addInfo('Author', $this->empresa->nombre);
      
      $lineas = $this->albaran->get_lineas();
      if($lineas)
      {
         $linea_actual = 0;
         $lppag = 14;
         $pagina = 1;
         
         /// imprimimos las páginas necesarias
         while( $linea_actual < count($lineas) )
         {
            /// salto de página
            if($linea_actual > 0)
               $pdf_doc->pdf->ezNewPage();
            
            /// encabezado
            $texto = "<b>".ucfirst(FS_ALBARAN).":</b> ".$this->albaran->codigo."\n".
                    "<b>Fecha:</b> ".$this->albaran->fecha."\n".
                    "<b>SR. D:</b> ".$this->albaran->nombrecliente;
            $pdf_doc->pdf->ezText($texto, 12, array('justification' => 'right'));
            $pdf_doc->pdf->ezText("\n", 12);
            
            
            /// tabla principal
            $pdf_doc->new_table();
            $pdf_doc->add_table_header(
               array(
                   'unidades' => '<b>Ud.</b>',
                   'descripcion' => '<b>Descripción</b>',
                   'dto' => '<b>DTO.</b>',
                   'pvp' => '<b>P.U.</b>',
                   'importe' => '<b>Importe</b>'
               )
            );
            $saltos = 0;
            for($i = $linea_actual; (($linea_actual < ($lppag + $i)) AND ($linea_actual < count($lineas)));)
            {
               $pdf_doc->add_table_row(
                  Array(
                      'unidades' => $lineas[$linea_actual]->cantidad,
                      'descripcion' => $lineas[$linea_actual]->referencia.' - '.$lineas[$linea_actual]->descripcion,
                      'dto' => $this->show_numero($lineas[$linea_actual]->dtopor, 2).' %',
                      'pvp' => $this->show_precio($lineas[$linea_actual]->pvpunitario, $this->albaran->coddivisa),
                      'importe' => $this->show_precio($lineas[$linea_actual]->pvptotal, $this->albaran->coddivisa)
                  )
               );
               
               $linea_actual++;
               $saltos++;
            }
            $pdf_doc->save_table(
               array(
                   'fontSize' => 9,
                   'cols' => array(
                       'dto' => array('justification' => 'right'),
                       'pvp' => array('justification' => 'right'),
                       'importe' => array('justification' => 'right')
                   ),
                   'width' => 540,
                   'shadeCol' => array(0.9, 0.9, 0.9)
               )
            );
            
            /// Rellenamos el hueco que falta hasta donde debe aparecer la última tabla
            if($this->albaran->observaciones == '')
               $salto = '';
            else
            {
               $salto = "\n<b>Observaciones</b>: " . $this->albaran->observaciones;
               $saltos += count( explode("\n", $this->albaran->observaciones) ) - 1;
            }
            
            if($saltos < $lppag)
            {
               for(;$saltos < $lppag; $saltos++) { $salto .= "\n"; }
                  $pdf_doc->pdf->ezText($salto, 12);
            }
            else if( $linea_actual >= count($lineas) )
               $pdf_doc->pdf->ezText($salto, 12);
            else
               $pdf_doc->pdf->ezText("\n", 10);
            
            
            /// Escribimos los totales
            $opciones = array('justification' => 'right');
            $neto = '<b>Pag</b>: ' . $pagina . '/' . ceil(count($lineas) / $lppag);
            $neto .= '        <b>Neto</b>: ' . $this->show_precio($this->albaran->neto, $this->albaran->coddivisa);
            $neto .= '    <b>IVA</b>: ' . $this->show_precio($this->albaran->totaliva, $this->albaran->coddivisa);
            $neto .= '    <b>Total</b>: ' . $this->show_precio($this->albaran->total, $this->albaran->coddivisa);
            $pdf_doc->pdf->ezText($neto, 12, $opciones);
            
            $pagina++;
         }
      }
      
      $pdf_doc->show();
   }
   
   private function enviar_email()
   {
      $cliente = $this->cliente->get($this->albaran->codcliente);
      
      if( $this->empresa->can_send_mail() AND $cliente )
      {
         if( $_POST['email'] != $cliente->email )
         {
            $cliente->email = $_POST['email'];
            $cliente->save();
         }
         
         /// obtenemos la configuración extra del email
         $mailop = array(
             'mail_host' => 'smtp.gmail.com',
             'mail_port' => '465',
             'mail_user' => '',
             'mail_enc' => 'ssl'
         );
         $fsvar = new fs_var();
         $mailop = $fsvar->array_get($mailop);
         
         $filename = 'albaran_'.$this->albaran->codigo.'.pdf';
         $this->generar_pdf_simple($filename);
         if( file_exists('tmp/enviar/'.$filename) )
         {
            $mail = new PHPMailer();
            $mail->IsSMTP();
            $mail->SMTPAuth = TRUE;
            $mail->SMTPSecure = $mailop['mail_enc'];
            $mail->Host = $mailop['mail_host'];
            $mail->Port = intval($mailop['mail_port']);
            
            if($mailop['mail_user'] != '')
               $mail->Username = $mailop['mail_user'];
            else
               $mail->Username = $this->empresa->email;
            
            $mail->Password = $this->empresa->email_password;
            $mail->From = $this->empresa->email;
            $mail->FromName = $this->user->nick;
            $mail->Subject = $this->empresa->nombre . ': Su '.FS_ALBARAN.' '.$this->albaran->codigo;
            $mail->AltBody = 'Buenos días, le adjunto su '.FS_ALBARAN.' '.$this->albaran->codigo.".\n".$this->empresa->email_firma;
            $mail->WordWrap = 50;
            $mail->MsgHTML( nl2br($_POST['mensaje']) );
            $mail->AddAttachment('tmp/enviar/'.$filename);
            $mail->AddAddress($_POST['email'], $cliente->nombrecomercial);
            $mail->IsHTML(TRUE);
            
            if( $mail->Send() )
               $this->new_message('Mensaje enviado correctamente.');
            else
               $this->new_error_msg("Error al enviar el email: " . $mail->ErrorInfo);
         }
         else
            $this->new_error_msg('Imposible generar el PDF.');
      }
   }
}
