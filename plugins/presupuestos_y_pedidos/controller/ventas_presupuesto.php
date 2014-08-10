<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2014  Carlos Garcia Gomez  neorazorx@gmail.com
 * Copyright (C) 2014  Francesc Pineda Segarra  shawe.ewahs@gmail.com
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
require_model('articulo.php');
require_model('asiento.php');
require_model('cliente.php');
require_model('ejercicio.php');
require_model('pedido_cliente.php');
require_model('familia.php');
require_model('fs_var.php');
require_model('impuesto.php');
require_model('linea_presupuesto_cliente.php');
require_model('partida.php');
require_model('presupuesto_cliente.php');
require_model('regularizacion_iva.php');
require_model('serie.php');
require_model('subcuenta.php');
require_once 'extras/phpmailer/class.phpmailer.php';
require_once 'extras/phpmailer/class.smtp.php';

class ventas_presupuesto extends fs_controller
{
   public $agente;
   public $cliente;
   public $cliente_email;
   public $ejercicio;
   public $familia;
   public $impuesto;
   public $nuevo_presupuesto_url;
   public $presupuesto;
   public $serie;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Presupuesto', 'ventas', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->ppage = $this->page->get('ventas_presupuestos');
      $this->agente = FALSE;
      $presupuesto = new presupuesto_cliente();
      $this->presupuesto = FALSE;
      $this->cliente = new cliente();
      $this->cliente_email = '';
      $this->ejercicio = new ejercicio();
      $this->familia = new familia();
      $this->impuesto = new impuesto();
      $this->nuevo_presupuesto_url = FALSE;
      $this->serie = new serie();
      
      /**
       * Comprobamos si el usuario tiene acceso a nueva_venta,
       * necesario para poder añadir líneas.
       */
      if( $this->user->have_access_to('nueva_venta', FALSE) )
      {
         $nuevoprep = $this->page->get('nueva_venta');
         if($nuevoprep)
            $this->nuevo_presupuesto_url = $nuevoprep->url();
      }
      
      if( isset($_POST['idpresupuesto']) )
      {
         $this->presupuesto = $presupuesto->get($_POST['idpresupuesto']);
         $this->modificar();
      }
      else if( isset($_GET['id']) )
      {
         $this->presupuesto = $presupuesto->get($_GET['id']);
      }
      
      if( $this->presupuesto AND isset($_GET['imprimir']) )
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
      else if( $this->presupuesto )
      {
         $this->page->title = $this->presupuesto->codigo;
         $this->agente = $this->presupuesto->get_agente();
         
         /**
          * Como es una plantilla compleja, he separado el código HTML
          * en dos archivos: ventas_presupuesto_cli_edit.html para los
          * presupuestos editables y ventas_presupuesto_cli.html para los demás.
          */
         if( is_null($this->presupuesto->idpedido) )
            $this->template = 'ventas_presupuesto_edit';
         else
            $this->template = 'ventas_presupuesto';
         
         /// comprobamos el presupuesto
         if( $this->presupuesto->full_test() )
         {
            if( isset($_GET['pedir']) AND isset($_GET['petid']) AND is_null($this->presupuesto->idpedido) )
            {
               if( $this->duplicated_petition($_GET['petid']) )
                  $this->new_error_msg('Petición duplicada. Evita hacer doble clic sobre los botones.');
               else
                  $this->generar_pedido();
            }
            
            $this->buttons[] = new fs_button('b_copiar', 'Copiar', 'index.php?page=copy_presupuesto&idprecli='.$this->presupuesto->idpresupuesto, TRUE);
            $this->buttons[] = new fs_button_img('b_imprimir', 'Imprimir', 'print.png');
            
            /// comprobamos si se pueden enviar emails
            if( $this->empresa->can_send_mail() )
            {
               $cliente = $this->cliente->get($this->presupuesto->codcliente);
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
         
            if( is_null($this->presupuesto->idpedido) )
            {
               $this->buttons[] = new fs_button('b_pedir', 'Generar pedido', $this->url()."&pedir=TRUE&petid=".$this->random_string());
            }
            else
            {
               $this->buttons[] = new fs_button('b_ver_pedido', 'Ver pedido', $this->presupuesto->pedido_url());
            }
         }
         
         $this->buttons[] = new fs_button_img('b_remove_presupuesto', 'Eliminar', 'trash.png', '#', TRUE);
      }
      else
         $this->new_error_msg("¡Presupuesto de cliente no encontrado!");
   }
   
   public function url()
   {
      if( !isset($this->presupuesto) )
         return parent::url();
      else if($this->presupuesto)
         return $this->presupuesto->url();
      else
         return $this->page->url();
   }
   
   private function modificar()
   {
      $this->presupuesto->hora = $_POST['hora'];
      $this->presupuesto->observaciones = $_POST['observaciones'];
      
      if($this->presupuesto->idpedido)
      {
         /// obtenemos los datos del ejercicio para acotar la fecha
         $eje0 = $this->ejercicio->get( $this->presupuesto->codejercicio );
         if($eje0)
            $this->presupuesto->fecha = $eje0->get_best_fecha($_POST['fecha'], TRUE);
         else
            $this->new_error_msg('No se encuentra el ejercicio asociado al presupuesto');
         
         /// ¿cambiamos el cliente?
         if($_POST['cliente'] != $this->presupuesto->codcliente)
         {
            $cliente = $this->cliente->get($_POST['cliente']);
            if($cliente)
            {
               foreach($cliente->get_direcciones() as $d)
               {
                  if($d->domfacturacion)
                  {
                     $this->presupuesto->codcliente = $cliente->codcliente;
                     $this->presupuesto->cifnif = $cliente->cifnif;
                     $this->presupuesto->nombrecliente = $cliente->nombrecomercial;
                     $this->presupuesto->apartado = $d->apartado;
                     $this->presupuesto->ciudad = $d->ciudad;
                     $this->presupuesto->coddir = $d->id;
                     $this->presupuesto->codpais = $d->codpais;
                     $this->presupuesto->codpostal = $d->codpostal;
                     $this->presupuesto->direccion = $d->direccion;
                     $this->presupuesto->provincia = $d->provincia;
                     break;
                  }
               }
            }
         }
         
         $serie = $this->serie->get($this->presupuesto->codserie);
         
         /// ¿cambiamos la serie?
         if($_POST['serie'] != $this->presupuesto->codserie)
         {
            $this->presupuesto->codserie = $_POST['serie'];
            $this->presupuesto->new_codigo();
         }
         
         if( isset($_POST['lineas']) )
         {
            $this->presupuesto->neto = 0;
            $this->presupuesto->totaliva = 0;
            $lineas = $this->presupuesto->get_lineas();
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
                        $art0->sum_stock($this->presupuesto->codalmacen, $l->cantidad);
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
                           $this->presupuesto->neto += ($value->cantidad*$value->pvpunitario*(100-$value->dtopor)/100);
                           $this->presupuesto->totaliva += ($value->cantidad*$value->pvpunitario*(100-$value->dtopor)/100*$value->iva/100);
                           
                           /// actualizamos el stock
                           $art0 = $articulo->get($value->referencia);
                           if($art0)
                              $art0->sum_stock($this->presupuesto->codalmacen, $cantidad_old - $lineas[$k]->cantidad);
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
                        $linea = new linea_presupuesto_cliente();
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
                        
                        $linea->idpresupuesto = $this->presupuesto->idpresupuesto;
                        $linea->cantidad = floatval($_POST['cantidad_'.$num]);
                        $linea->pvpunitario = floatval($_POST['pvp_'.$num]);
                        $linea->dtopor = floatval($_POST['dto_'.$num]);
                        $linea->pvpsindto = ($linea->cantidad * $linea->pvpunitario);
                        $linea->pvptotal = ($linea->cantidad * $linea->pvpunitario * (100 - $linea->dtopor)/100);
                        
                        if( $linea->save() )
                        {
                           $this->presupuesto->neto += ($linea->cantidad*$linea->pvpunitario*(100-$linea->dtopor)/100);
                           $this->presupuesto->totaliva += ($linea->cantidad*$linea->pvpunitario*(100-$linea->dtopor)/100*$linea->iva/100);
                           
                           /// actualizamos el stock
                           $art0->sum_stock($this->presupuesto->codalmacen, 0 - $linea->cantidad);
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
            $this->presupuesto->neto = round($this->presupuesto->neto, 2);
            $this->presupuesto->totaliva = round($this->presupuesto->totaliva, 2);
            $this->presupuesto->total = $this->presupuesto->neto + $this->presupuesto->totaliva;
         }
      }
      
      if( $this->presupuesto->save() )
      {
         $this->new_message("Presupuesto modificado correctamente.");
         $this->new_change('Presupuesto Cliente '.$this->presupuesto->codigo, $this->presupuesto->url());
      }
      else
         $this->new_error_msg("¡Imposible modificar el presupuesto!");
   }
   
   private function generar_pedido()
   {
      $pedido = new pedido_cliente();
      $pedido->apartado = $this->presupuesto->apartado;
      $pedido->automatica = TRUE;
      $pedido->cifnif = $this->presupuesto->cifnif;
      $pedido->ciudad = $this->presupuesto->ciudad;
      $pedido->codagente = $this->presupuesto->codagente;
      $pedido->codalmacen = $this->presupuesto->codalmacen;
      $pedido->codcliente = $this->presupuesto->codcliente;
      $pedido->coddir = $this->presupuesto->coddir;
      $pedido->coddivisa = $this->presupuesto->coddivisa;
      $pedido->tasaconv = $this->presupuesto->tasaconv;
      $pedido->codejercicio = $this->presupuesto->codejercicio;
      $pedido->codpago = $this->presupuesto->codpago;
      $pedido->codpais = $this->presupuesto->codpais;
      $pedido->codpostal = $this->presupuesto->codpostal;
      $pedido->codserie = $this->presupuesto->codserie;
      $pedido->direccion = $this->presupuesto->direccion;
      $pedido->editable = FALSE;
      $pedido->neto = $this->presupuesto->neto;
      $pedido->nombrecliente = $this->presupuesto->nombrecliente;
      $pedido->observaciones = $this->presupuesto->observaciones;
      $pedido->provincia = $this->presupuesto->provincia;
      $pedido->total = $this->presupuesto->total;
      $pedido->totaliva = $this->presupuesto->totaliva;
      
      /// asignamos la mejor fecha posible, pero dentro del ejercicio
      $eje0 = $this->ejercicio->get($pedido->codejercicio);
      $pedido->fecha = $eje0->get_best_fecha($pedido->fecha);
      
      $regularizacion = new regularizacion_iva();
      
      if( !$eje0->abierto() )
      {
         $this->new_error_msg("El ejercicio está cerrado.");
      }
      else if( $regularizacion->get_fecha_inside($pedido->fecha) )
      {
         $this->new_error_msg("El IVA de ese periodo ya ha sido regularizado.
            No se pueden añadir más pedidos en esa fecha.");
      }
      else if( $pedido->save() )
      {
         $continuar = TRUE;
         foreach($this->presupuesto->get_lineas() as $l)
         {
            $n = new linea_pedido_cliente();
            $n->idpresupuesto = $l->idpresupuesto;
            $n->idpedido = $pedido->idpedido;
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
            $this->presupuesto->idpedido = $pedido->idpedido;
            if( $this->presupuesto->save() )
            {
               //$this->generar_asiento($factura);
            }
            else
            {
               $this->new_error_msg("¡Imposible vincular el presupuesto con el nuevo pedido!");
               if( $pedido->delete() )
                  $this->new_error_msg("El pedido se ha borrado.");
               else
                  $this->new_error_msg("¡Imposible borrar el pedido!");
            }
         }
         else
         {
            if( $pedido->delete() )
               $this->new_error_msg("El pedido se ha borrado.");
            else
               $this->new_error_msg("¡Imposible borrar el pedido!");
         }
      }
      else
         $this->new_error_msg("¡Imposible guardar el pedido!");
   }
   
   private function generar_pdf_simple($archivo = FALSE)
   {
      if( !$archivo )
      {
         /// desactivamos la plantilla HTML
         $this->template = FALSE;
      }
      
      $pdf_doc = new fs_pdf();
      $pdf_doc->pdf->addInfo('Title', 'Presupuesto '. $this->presupuesto->codigo);
      $pdf_doc->pdf->addInfo('Subject', 'Presupuesto de cliente ' . $this->presupuesto->codigo);
      $pdf_doc->pdf->addInfo('Author', $this->empresa->nombre);
      
      $lineas = $this->presupuesto->get_lineas();
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
             * Presupuesto:             Fecha:
             * Cliente:             CIF/NIF:
             * Dirección:           Teléfonos:
             */
            $pdf_doc->new_table();
            $pdf_doc->add_table_row(
               array(
                   'campo1' => "<b>Presupuesto:</b>",
                   'dato1' => $this->presupuesto->codigo,
                   'campo2' => "<b>Fecha:</b>",
                   'dato2' => $this->presupuesto->fecha
               )
            );
            $pdf_doc->add_table_row(
               array(
                   'campo1' => "<b>Cliente:</b>",
                   'dato1' => $this->presupuesto->nombrecliente,
                   'campo2' => "<b>CIF/NIF:</b>",
                   'dato2' => $this->presupuesto->cifnif
               )
            );
            $pdf_doc->add_table_row(
               array(
                   'campo1' => "<b>Dirección:</b>",
                   'dato1' => $this->presupuesto->direccion.' CP: '.$this->presupuesto->codpostal.' - '.$this->presupuesto->ciudad.' ('.$this->presupuesto->provincia.')',
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
             * Creamos la tabla con las lineas del presupuesto:
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
                  'pvp' => $this->show_precio($lineas[$linea_actual]->pvpunitario, $this->presupuesto->coddivisa),
                  'dto' => $this->show_numero($lineas[$linea_actual]->dtopor, 0) . " %",
                  'cantidad' => $lineas[$linea_actual]->cantidad,
                  'importe' => $this->show_precio($lineas[$linea_actual]->pvptotal, $this->presupuesto->coddivisa)
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
            if($this->presupuesto->observaciones == '')
            {
               $salto = '';
            }
            else
            {
               $salto = "\n<b>Observaciones</b>: " . $this->presupuesto->observaciones;
               $saltos += count( explode("\n", $this->presupuesto->observaciones) ) - 1;
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
                'neto' => $this->show_precio($this->presupuesto->neto, $this->presupuesto->coddivisa),
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
               $fila['iva'.$i] = $this->show_precio($value, $this->presupuesto->coddivisa);
               $opciones['cols']['iva'.$i] = array('justification' => 'right');
            }
            $titulo['liquido'] = '<b>Total</b>';
            $fila['liquido'] = $this->show_precio($this->presupuesto->total, $this->presupuesto->coddivisa);
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
      $pdf_doc->pdf->addInfo('Title', 'Presupuesto '. $this->presupuesto->codigo);
      $pdf_doc->pdf->addInfo('Subject', 'Presupuesto de cliente ' . $this->presupuesto->codigo);
      $pdf_doc->pdf->addInfo('Author', $this->empresa->nombre);
      
      $lineas = $this->presupuesto->get_lineas();
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
            $texto = "<b>Presupuesto:</b> ".$this->presupuesto->codigo."\n".
                    "<b>Fecha:</b> ".$this->presupuesto->fecha."\n".
                    "<b>SR. D:</b> ".$this->presupuesto->nombrecliente;
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
                      'pvp' => $this->show_precio($lineas[$linea_actual]->pvpunitario, $this->presupuesto->coddivisa),
                      'importe' => $this->show_precio($lineas[$linea_actual]->pvptotal, $this->presupuesto->coddivisa)
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
            if($this->presupuesto->observaciones == '')
               $salto = '';
            else
            {
               $salto = "\n<b>Observaciones</b>: " . $this->presupuesto->observaciones;
               $saltos += count( explode("\n", $this->presupuesto->observaciones) ) - 1;
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
            $neto .= '        <b>Neto</b>: ' . $this->show_precio($this->presupuesto->neto, $this->presupuesto->coddivisa);
            $neto .= '    <b>IVA</b>: ' . $this->show_precio($this->presupuesto->totaliva, $this->presupuesto->coddivisa);
            $neto .= '    <b>Total</b>: ' . $this->show_precio($this->presupuesto->total, $this->presupuesto->coddivisa);
            $pdf_doc->pdf->ezText($neto, 12, $opciones);
            
            $pagina++;
         }
      }
      
      $pdf_doc->show();
   }
   
   private function enviar_email()
   {
      $cliente = $this->cliente->get($this->presupuesto->codcliente);
      
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
         
         $filename = 'presupuesto_'.$this->presupuesto->codigo.'.pdf';
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
            $mail->Subject = $this->empresa->nombre . ': Su presupuesto '.$this->presupuesto->codigo;
            $mail->AltBody = 'Buenos días, le adjunto su presupuesto '.$this->presupuesto->codigo.".\n".$this->empresa->email_firma;
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
