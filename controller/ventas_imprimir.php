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
require_once 'extras/phpmailer/class.phpmailer.php';
require_once 'extras/phpmailer/class.smtp.php';

require_model('cliente.php');
require_model('fs_var.php');

/**
 * Esta clase agrupa los procedimientos de imprimir/enviar albaranes y facturas.
 */
class ventas_imprimir extends fs_controller
{
   public $albaran;
   public $cliente;
   public $factura;
   public $impuesto;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'imprimir', 'ventas', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->show_fs_toolbar = FALSE;
      $this->albaran = FALSE;
      $this->cliente = FALSE;
      $this->factura = FALSE;
      $this->impuesto = new impuesto();
      
      if( isset($_REQUEST['albaran']) AND isset($_REQUEST['id']) )
      {
         $alb = new albaran_cliente();
         $this->albaran = $alb->get($_REQUEST['id']);
         if($this->albaran)
         {
            $cliente = new cliente();
            $this->cliente = $cliente->get($this->albaran->codcliente);
         }
         
         if( isset($_POST['email']) )
         {
            $this->enviar_email('albaran');
         }
         else
            $this->generar_pdf_albaran();
      }
      else if( isset($_REQUEST['factura']) AND isset($_REQUEST['id']) )
      {
         $fac = new factura_cliente();
         $this->factura = $fac->get($_REQUEST['id']);
         if($this->factura)
         {
            $cliente = new cliente();
            $this->cliente = $cliente->get($this->factura->codcliente);
         }
         
         if( isset($_POST['email']) )
         {
            $this->enviar_email('factura', $_REQUEST['tipo']);
         }
         else
            $this->generar_pdf_factura($_REQUEST['tipo']);
      }
      
      $this->share_extensions();
   }
   
   private function share_extensions()
   {
      $extensiones = array(
          array(
              'name' => 'imprimir_albaran',
              'page_from' => __CLASS__,
              'page_to' => 'ventas_albaran',
              'type' => 'pdf',
              'text' => ucfirst(FS_ALBARAN).' simple',
              'params' => '&albaran=TRUE'
          ),
          array(
              'name' => 'email_albaran',
              'page_from' => __CLASS__,
              'page_to' => 'ventas_albaran',
              'type' => 'email',
              'text' => ucfirst(FS_ALBARAN).' simple',
              'params' => '&albaran=TRUE'
          ),
          array(
              'name' => 'imprimir_factura',
              'page_from' => __CLASS__,
              'page_to' => 'ventas_factura',
              'type' => 'pdf',
              'text' => 'Factura simple',
              'params' => '&factura=TRUE&tipo=simple'
          ),
          array(
              'name' => 'imprimir_factura_firma',
              'page_from' => __CLASS__,
              'page_to' => 'ventas_factura',
              'type' => 'pdf',
              'text' => 'Factura con firma',
              'params' => '&factura=TRUE&tipo=firma'
          ),
          array(
              'name' => 'imprimir_factura_carta',
              'page_from' => __CLASS__,
              'page_to' => 'ventas_factura',
              'type' => 'pdf',
              'text' => 'Modelo carta',
              'params' => '&factura=TRUE&tipo=carta'
          ),
          array(
              'name' => 'email_factura',
              'page_from' => __CLASS__,
              'page_to' => 'ventas_factura',
              'type' => 'email',
              'text' => 'Factura simple',
              'params' => '&factura=TRUE&tipo=simple'
          )
      );
      foreach($extensiones as $ext)
      {
         $fsext = new fs_extension($ext);
         if( !$fsext->save() )
         {
            $this->new_error_msg('Error al guardar la extensión '.$ext['name']);
         }
      }
   }
   
   private function generar_pdf_albaran($archivo = FALSE)
   {
      if(!$archivo)
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
            if( file_exists('tmp/'.FS_TMP_NAME.'logo.png') )
            {
               $pdf_doc->pdf->ezImage('tmp/'.FS_TMP_NAME.'logo.png', 0, 200, 'none');
               $lppag -= 2; /// si metemos el logo, caben menos líneas
            }
            else
            {
               $pdf_doc->pdf->ezText("<b>".$this->empresa->nombre."</b>", 16, array('justification' => 'center'));
               $pdf_doc->pdf->ezText(FS_CIFNIF.": ".$this->empresa->cifnif, 8, array('justification' => 'center'));
               
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
                   'campo2' => "<b>".FS_CIFNIF.":</b>",
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
                  'cantidad' => '<b>Cantidad</b>',
                  'pvp' => '<b>PVP</b>',
                  'dto' => '<b>DTO</b>',
                  'importe' => '<b>Importe</b>'
               )
            );
            $saltos = 0;
            $subtotal = 0;
            $impuestos = array();
            for($i = $linea_actual; (($linea_actual < ($lppag + $i)) AND ($linea_actual < count($lineas)));)
            {
               if( !isset($impuestos[$lineas[$linea_actual]->codimpuesto]) )
               {
                  $impuestos[$lineas[$linea_actual]->codimpuesto] = $lineas[$linea_actual]->pvptotal * $lineas[$linea_actual]->iva / 100;
               }
               else
                  $impuestos[$lineas[$linea_actual]->codimpuesto] += $lineas[$linea_actual]->pvptotal * $lineas[$linea_actual]->iva / 100;
               
               $fila = array(
                  'descripcion' => $lineas[$linea_actual]->descripcion,
                  'cantidad' => $lineas[$linea_actual]->cantidad,
                  'pvp' => $this->show_precio($lineas[$linea_actual]->pvpunitario, $this->albaran->coddivisa),
                  'dto' => $this->show_numero($lineas[$linea_actual]->dtopor, 0) . " %",
                  'importe' => $this->show_precio($lineas[$linea_actual]->pvptotal, $this->albaran->coddivisa)
               );
               
               $pdf_doc->add_table_row($fila);
               $saltos++;
               $linea_actual++;
            }
            $pdf_doc->save_table(
               array(
                   'fontSize' => 8,
                   'cols' => array(
                       'cantidad' => array('justification' => 'right'),
                       'pvp' => array('justification' => 'right'),
                       'dto' => array('justification' => 'right'),
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
               $imp = $this->impuesto->get($i);
               if($imp)
               {
                  $titulo['iva'.$i] = '<b>'.$imp->descripcion.'</b>';
               }
               else
                  $titulo['iva'.$i] = '<b>IVA '.$i.'%</b>';
               
               $fila['iva'.$i] = $this->show_precio($value, $this->albaran->coddivisa);
               $opciones['cols']['iva'.$i] = array('justification' => 'right');
            }
            
            if($this->albaran->totalirpf != 0)
            {
               $titulo['irpf'] = '<b>IRPF</b>';
               $fila['irpf'] = $this->show_precio(0 - $this->albaran->totalirpf);
               $opciones['cols']['irpf'] = array('justification' => 'right');
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
         if( !file_exists('tmp/'.FS_TMP_NAME.'enviar') )
            mkdir('tmp/'.FS_TMP_NAME.'enviar');
         
         $pdf_doc->save('tmp/'.FS_TMP_NAME.'enviar/'.$archivo);
      }
      else
         $pdf_doc->show();
   }
   
   private function generar_pdf_factura($tipo='simple', $archivo=FALSE)
   {
      if(!$archivo)
      {
         /// desactivamos la plantilla HTML
         $this->template = FALSE;
      }
      
      /// Creamos el PDF y escribimos sus metadatos
      $pdf_doc = new fs_pdf();
      $pdf_doc->pdf->addInfo('Title', 'Factura ' . $this->factura->codigo);
      $pdf_doc->pdf->addInfo('Subject', 'Factura de cliente ' . $this->factura->codigo);
      $pdf_doc->pdf->addInfo('Author', $this->empresa->nombre);
      
      $lineas = $this->factura->get_lineas();
      $lineas_iva = $this->factura->get_lineas_iva();
      if($lineas)
      {
         $lineasfact = count($lineas);
         $linea_actual = 0;
         $lppag = 42; /// líneas por página
         $pagina = 1;
         
         // Imprimimos las páginas necesarias
         while($linea_actual < $lineasfact)
         {
            /// salto de página
            if($linea_actual > 0)
            {
               $pdf_doc->pdf->ezNewPage();
            }
            
            /*
             * Creamos la cabecera de la página, en este caso para el modelo carta
             */
            if($tipo == 'carta')
            {
               $lppag = 40; /// en el modelo carta caben menos líneas
               
               $direccion = $this->factura->nombrecliente."\n".$this->factura->direccion;
               if($this->factura->codpostal AND $this->factura->ciudad)
                  $direccion .= "\n CP: " . $this->factura->codpostal . ' ' . $this->factura->ciudad;
               else if($this->factura->ciudad)
                  $direccion .= "\n" . $this->factura->ciudad;
               
               if($this->factura->provincia)
                  $direccion .= "\n(" . $this->factura->provincia . ")";
               
               $pdf_doc->pdf->ezText("\n\n", 10);
               $pdf_doc->new_table();
               $pdf_doc->add_table_row(
                  array(
                      'campos' => "<b>Factura de cliente:</b>\n<b>Fecha:</b>\n<b>".FS_CIFNIF.":</b>",
                      'factura' => $this->factura->codigo."\n".$this->factura->fecha."\n".$this->factura->cifnif,
                      'cliente' => $direccion
                  )
               );
               $pdf_doc->save_table(
                  array(
                      'cols' => array(
                          'campos' => array('justification' => 'right', 'width' => 100),
                          'factura' => array('justification' => 'left'),
                          'cliente' => array('justification' => 'right')
                      ),
                      'showLines' => 0,
                      'width' => 540
                  )
               );
               $pdf_doc->pdf->ezText("\n\n\n", 14);
            }
            else /// esta es la cabecera de la página para los modelos 'simple' y 'firma'
            {
               /// ¿Añadimos el logo?
               if( file_exists('tmp/'.FS_TMP_NAME.'logo.png') )
               {
                  $pdf_doc->pdf->ezImage('tmp/'.FS_TMP_NAME.'logo.png', 0, 200, 'none');
                  $lppag -= 2; /// si metemos el logo, caben menos líneas
               }
               else
               {
                  $pdf_doc->pdf->ezText("<b>".$this->empresa->nombre."</b>", 16, array('justification' => 'center'));
                  $pdf_doc->pdf->ezText(FS_CIFNIF.": ".$this->empresa->cifnif, 8, array('justification' => 'center'));
                  
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
                * Factura:             Fecha:
                * Cliente:             CIF/NIF:
                * Dirección:           Teléfonos:
                */
               $pdf_doc->new_table();
               $pdf_doc->add_table_row(
                  array(
                     'campo1' => "<b>Factura:</b>",
                     'dato1' => $this->factura->codigo,
                     'campo2' => "<b>Fecha:</b>",
                     'dato2' => $this->factura->fecha
                  )
               );
               $pdf_doc->add_table_row(
                  array(
                     'campo1' => "<b>Cliente:</b>",
                     'dato1' => $this->factura->nombrecliente,
                     'campo2' => "<b>".FS_CIFNIF.":</b>",
                     'dato2' => $this->factura->cifnif
                  )
               );
               $pdf_doc->add_table_row(
                  array(
                     'campo1' => "<b>Dirección:</b>",
                     'dato1' => $this->factura->direccion.' CP: '.$this->factura->codpostal.' - '.$this->factura->ciudad.
                                 ' ('.$this->factura->provincia.')',
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
               
               /// en el tipo 'firma' caben menos líneas
               if($tipo == 'firma')
                  $lppag -= 10;
            }
            
            
            /*
             * Creamos la tabla con las lineas de la factura:
             * 
             * Descripción    Cantidad  PVP   DTO    Importe
             */
            $pdf_doc->new_table();
            $pdf_doc->add_table_header(
               array(
                  'descripcion' => '<b>Descripción</b>',
                  'cantidad' => '<b>Cantidad</b>',
                  'pvp' => '<b>PVP</b>',
                  'dto' => '<b>DTO</b>',
                  'importe' => '<b>Importe</b>'
               )
            );
            $saltos = 0;
            for($i = $linea_actual; (($linea_actual < ($lppag + $i)) AND ($linea_actual < $lineasfact));)
            {
               $fila = array(
                  'descripcion' => $lineas[$linea_actual]->descripcion,
                  'cantidad' => $lineas[$linea_actual]->cantidad,
                  'pvp' => $this->show_precio($lineas[$linea_actual]->pvpunitario, $this->factura->coddivisa),
                  'dto' => $this->show_numero($lineas[$linea_actual]->dtopor, 0) . " %",
                  'importe' => $this->show_precio($lineas[$linea_actual]->pvptotal, $this->factura->coddivisa)
               );
               
               $pdf_doc->add_table_row($fila);
               $saltos++;
               $linea_actual++;
            }
            $pdf_doc->save_table(
               array(
                   'fontSize' => 8,
                   'cols' => array(
                       'cantidad' => array('justification' => 'right'),
                       'pvp' => array('justification' => 'right'),
                       'dto' => array('justification' => 'right'),
                       'importe' => array('justification' => 'right')
                   ),
                   'width' => 540,
                   'shaded' => 0
               )
            );
            
            
            /*
             * Rellenamos el hueco que falta hasta donde debe aparecer la última tabla
             */
            if($this->factura->observaciones == '' OR $tipo == 'firma')
            {
               $salto = '';
            }
            else
            {
               $salto = "\n<b>Observaciones</b>: " . $this->factura->observaciones;
               $saltos += count( explode("\n", $this->factura->observaciones) ) - 1;
            }
            
            if($saltos < $lppag)
            {
               for(;$saltos < $lppag; $saltos++)
                  $salto .= "\n";
               $pdf_doc->pdf->ezText($salto, 11);
            }
            else if($linea_actual >= $lineasfact)
            {
               $pdf_doc->pdf->ezText($salto, 11);
            }
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
                'neto' => $this->show_precio($this->factura->neto, $this->factura->coddivisa),
            );
            $opciones = array(
                'cols' => array(
                    'neto' => array('justification' => 'right'),
                ),
                'showLines' => 4,
                'width' => 540
            );
            foreach($lineas_iva as $li)
            {
               $imp = $this->impuesto->get($li->codimpuesto);
               if($imp)
               {
                  $titulo['iva'.$li->iva] = '<b>'.$imp->descripcion.'</b>';
               }
               else
                  $titulo['iva'.$li->iva] = '<b>IVA '.$li->iva.'%</b>';
               
               $fila['iva'.$li->iva] = $this->show_precio($li->totaliva, $this->factura->coddivisa);
               $opciones['cols']['iva'.$li->iva] = array('justification' => 'right');
            }
            
            if($this->factura->totalirpf != 0)
            {
               $titulo['irpf'] = '<b>IRPF</b>';
               $fila['irpf'] = $this->show_precio(0 - $this->factura->totalirpf);
               $opciones['cols']['irpf'] = array('justification' => 'right');
            }
            
            $titulo['liquido'] = '<b>Total</b>';
            $fila['liquido'] = $this->show_precio($this->factura->total, $this->factura->coddivisa);
            $opciones['cols']['liquido'] = array('justification' => 'right');
            $pdf_doc->add_table_header($titulo);
            $pdf_doc->add_table_row($fila);
            $pdf_doc->save_table($opciones);
            
            
            /*
             * Añadimos la parte de la firma y las observaciones,
             * para el tipo 'firma'
             */
            if($tipo == 'firma')
            {
               $pdf_doc->new_table();
               $pdf_doc->add_table_row(
                  array(
                     'campo1' => "<b>Observaciones</b>",
                     'campo2' => "<b>Firma</b>"
                  )
               );
               $pdf_doc->add_table_row(
                  array(
                     'campo1' => $this->factura->observaciones,
                     'campo2' => ""
                  )
               );
               $pdf_doc->save_table(
                  array(
                     'cols' => array(
                        'campo1' => array('justification' => 'left'),
                        'campo2' => array('justification' => 'right')
                     ),
                     'showLines' => 0,
                     'width' => 540,
                     'shaded' => 0
                  )
               );
            }
            
            
            /// pié de página para la factura
            if($tipo == 'simple' OR $tipo == 'firma')
               $pdf_doc->pdf->addText(10, 10, 8, $pdf_doc->center_text($this->empresa->pie_factura, 153), 0, 1.5);
            
            $pagina++;
         }
      }
      
      if($archivo)
      {
         if( !file_exists('tmp/'.FS_TMP_NAME.'enviar') )
            mkdir('tmp/'.FS_TMP_NAME.'enviar');
         
         $pdf_doc->save('tmp/'.FS_TMP_NAME.'enviar/'.$archivo);
      }
      else
         $pdf_doc->show();
   }
   
   private function enviar_email($doc, $tipo='simple')
   {
      if( $this->empresa->can_send_mail() )
      {
         if( $_POST['email'] != $this->cliente->email )
         {
            $this->cliente->email = $_POST['email'];
            $this->cliente->save();
         }
         
         /// obtenemos la configuración extra del email
         $mailop = array(
             'mail_host' => 'smtp.gmail.com',
             'mail_port' => '465',
             'mail_user' => '',
             'mail_enc' => 'ssl'
         );
         $fsvar = new fs_var();
         $mailop = $fsvar->array_get($mailop, FALSE);
         
         if($doc == 'factura')
         {
            $filename = 'factura_'.$this->factura->codigo.'.pdf';
            $this->generar_pdf_factura($tipo, $filename);
         }
         else
         {
            $filename = 'albaran_'.$this->albaran->codigo.'.pdf';
            $this->generar_pdf_albaran($filename);
         }
         
         if( file_exists('tmp/'.FS_TMP_NAME.'enviar/'.$filename) )
         {
            $mail = new PHPMailer();
            $mail->IsSMTP();
            $mail->SMTPAuth = TRUE;
            $mail->SMTPSecure = $mailop['mail_enc'];
            $mail->Host = $mailop['mail_host'];
            $mail->Port = intval($mailop['mail_port']);
            
            $mail->Username = $this->empresa->email;
            if($mailop['mail_user'] != '')
            {
               $mail->Username = $mailop['mail_user'];
            }
            
            $mail->Password = $this->empresa->email_password;
            $mail->From = $this->empresa->email;
            $mail->FromName = $this->user->nick;
            $mail->CharSet = 'UTF-8';
            
            if($doc == 'factura')
            {
               $mail->Subject = $this->empresa->nombre . ': Su factura '.$this->factura->codigo;
               $mail->AltBody = 'Buenos días, le adjunto su factura '.$this->factura->codigo.".\n".$this->empresa->email_firma;
            }
            else
            {
               $mail->Subject = $this->empresa->nombre . ': Su '.FS_ALBARAN.' '.$this->albaran->codigo;
               $mail->AltBody = 'Buenos días, le adjunto su '.FS_ALBARAN.' '.$this->albaran->codigo.".\n".$this->empresa->email_firma;
            }
            
            $mail->WordWrap = 50;
            $mail->MsgHTML( nl2br($_POST['mensaje']) );
            $mail->AddAttachment('tmp/'.FS_TMP_NAME.'enviar/'.$filename);
            $mail->AddAddress($_POST['email'], $this->cliente->nombrecomercial);
            $mail->IsHTML(TRUE);
            
            if( $mail->Send() )
            {
               $this->new_message('Mensaje enviado correctamente.');
            }
            else
               $this->new_error_msg("Error al enviar el email: " . $mail->ErrorInfo);
            
            unlink('tmp/'.FS_TMP_NAME.'enviar/'.$filename);
         }
         else
            $this->new_error_msg('Imposible generar el PDF.');
      }
   }
}
