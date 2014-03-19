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
require_model('asiento.php');
require_model('cliente.php');
require_model('ejercicio.php');
require_model('factura_cliente.php');
require_once 'model/fs_var.php';
require_model('partida.php');
require_model('subcuenta.php');
require_once 'extras/phpmailer/class.phpmailer.php';
require_once 'extras/phpmailer/class.smtp.php';

class contabilidad_factura_cli extends fs_controller
{
   public $agente;
   public $cliente;
   public $ejercicio;
   public $factura;
   
   public function __construct()
   {
      parent::__construct('contabilidad_factura_cli', 'Factura de cliente', 'contabilidad', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->ppage = $this->page->get('contabilidad_facturas_cli');
      $this->ejercicio = new ejercicio();
      
      if( isset($_POST['idfactura']) )
      {
         $this->factura = new factura_cliente();
         $this->factura = $this->factura->get($_POST['idfactura']);
         $this->factura->observaciones = $_POST['observaciones'];
         
         $this->cambiar_numero_factura();
         
         /// obtenemos el ejercicio para poder acotar la fecha
         $eje0 = $this->ejercicio->get( $this->factura->codejercicio );
         if( $eje0 )
            $this->factura->fecha = $eje0->get_best_fecha($_POST['fecha'], TRUE);
         else
            $this->new_error_msg('No se encuentra el ejercicio asociado a la factura.');
         
         if( $this->factura->save() )
         {
            $asiento = $this->factura->get_asiento();
            if($asiento)
            {
               $asiento->fecha = $_POST['fecha'];
               if( !$asiento->save() )
                  $this->new_error_msg("Imposible modificar la fecha del asiento.");
            }
            $this->new_message("Factura modificada correctamente.");
         }
         else
            $this->new_error_msg("¡Imposible modificar la factura!");
      }
      else if( isset($_GET['id']) )
      {
         $this->factura = new factura_cliente();
         $this->factura = $this->factura->get($_GET['id']);
      }
      
      if($this->factura)
      {
         $cliente = new cliente();
         $this->cliente = $cliente->get($this->factura->codcliente);
         
         if( isset($_GET['imprimir']) )
            $this->generar_pdf($_GET['imprimir']);
         else
         {
            if( isset($_GET['gen_asiento']) AND isset($_GET['petid']) )
            {
               if( $this->duplicated_petition($_GET['petid']) )
                  $this->new_error_msg('Petición duplicada. Evita hacer doble clic sobre los botones.');
               else
                  $this->generar_asiento();
            }
            else if( isset($_POST['email']) )
            {
               $this->enviar_email();
            }
            else if( isset($_GET['updatedir']) )
            {
               $this->actualizar_direccion();
            }
            
            /// comprobamos la factura
            $this->factura->full_test();
            
            $this->page->title = $this->factura->codigo;
            $this->agente = $this->factura->get_agente();
            $this->buttons[] = new fs_button_img('b_imprimir', 'imprimir', 'print.png');
            
            if( $this->empresa->can_send_mail() )
            {
               $this->buttons[] = new fs_button_img('b_enviar', 'enviar', 'send.png');
            }
            
            if($this->factura->idasiento)
            {
               $this->buttons[] = new fs_button('b_ver_asiento', 'asiento', $this->factura->asiento_url());
            }
            else
            {
               $this->buttons[] = new fs_button('b_gen_asiento', 'generar asiento', $this->url().'&gen_asiento=TRUE&petid='.$this->random_string());
            }
            
            $this->buttons[] = new fs_button_img('b_eliminar', 'eliminar', 'trash.png', '#', TRUE);
         }
      }
      else
         $this->new_error_msg("¡Factura de cliente no encontrada!");
   }
   
   public function url()
   {
      if( !isset($this->factura) )
         return parent::url ();
      else if($this->factura)
         return $this->factura->url();
      else
         return $this->ppage->url();
   }
   
   private function cambiar_numero_factura()
   {
      $new_numero = intval($_POST['numero']);
      if($new_numero != $this->factura->numero)
      {
         $new_codigo = $this->factura->codejercicio.sprintf('%02s', $this->factura->codserie).sprintf('%06s', $new_numero);
         if( $this->factura->get_by_codigo($new_codigo) )
            $this->new_error_msg("Ya hay una factura con el número ".$new_numero);
         else
         {
            $asiento = $this->factura->get_asiento();
            if($asiento)
            {
               if( $asiento->delete() )
               {
                  $this->new_message('Asiento eliminado, debes regenerarlo!');
                  $this->factura->numero = $new_numero;
                  $this->factura->codigo = $new_codigo;
                  $this->factura->idasiento = NULL;
               }
            }
            else
            {
               $this->factura->numero = $new_numero;
               $this->factura->codigo = $new_codigo;
            }
         }
      }
   }
   
   private function actualizar_direccion()
   {
      foreach($this->cliente->get_direcciones() as $dir)
      {
         if($dir->domfacturacion)
         {
            $this->factura->cifnif = $this->cliente->cifnif;
            $this->factura->nombrecliente = $this->cliente->nombrecomercial;
            
            $this->factura->apartado = $dir->apartado;
            $this->factura->ciudad = $dir->ciudad;
            $this->factura->coddir = $dir->id;
            $this->factura->codpais = $dir->codpais;
            $this->factura->codpostal = $dir->codpostal;
            $this->factura->direccion = $dir->direccion;
            $this->factura->provincia = $dir->provincia;
            
            if( $this->factura->save() )
               $this->new_message('Dirección actualizada correctamente.');
            else
               $this->new_error_msg('Imposible actualizar la dirección de la factura.');
            
            break;
         }
      }
   }
   
   private function generar_pdf($tipo='simple', $archivo=FALSE)
   {
      if( !$archivo )
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
      if( $lineas )
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
               $pdf_doc->pdf->ezNewPage();
            
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
                      'campos' => "<b>Factura de cliente:</b>\n<b>Fecha:</b>\n<b>CIF/NIF:</b>",
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
               if( file_exists('tmp/logo.png') )
               {
                  $pdf_doc->pdf->ezImage('tmp/logo.png');
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
                     'campo2' => "<b>CIF/NIF:</b>",
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
             * Albarán  Descripción    PVP   DTO   Cantidad    Importe
             */
            $pdf_doc->new_table();
            $pdf_doc->add_table_header(
               array(
                  'albaran' => '<b>'.ucfirst(FS_ALBARAN).'</b>',
                  'descripcion' => '<b>Descripción</b>',
                  'pvp' => '<b>PVP</b>',
                  'dto' => '<b>DTO</b>',
                  'cantidad' => '<b>Cantidad</b>',
                  'importe' => '<b>Importe</b>'
               )
            );
            $saltos = 0;
            for($i = $linea_actual; (($linea_actual < ($lppag + $i)) AND ($linea_actual < $lineasfact));)
            {
               $fila = array(
                  'albaran' => $lineas[$linea_actual]->albaran_numero(),
                  'descripcion' => substr($lineas[$linea_actual]->descripcion, 0, 45),
                  'pvp' => $this->show_precio($lineas[$linea_actual]->pvpunitario, $this->factura->coddivisa),
                  'dto' => $this->show_numero($lineas[$linea_actual]->dtopor, 0) . " %",
                  'cantidad' => $lineas[$linea_actual]->cantidad,
                  'importe' => $this->show_precio($lineas[$linea_actual]->pvptotal, $this->factura->coddivisa)
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
                       'albaran' => array('justification' => 'center'),
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
                'neto' => $this->show_precio($this->factura->neto, $this->factura->coddivisa),
            );
            $opciones = array(
                'cols' => array(
                    'neto' => array('justification' => 'right'),
                ),
                'showLines' => 0,
                'width' => 540
            );
            foreach($lineas_iva as $li)
            {
               $titulo['iva'.$li->iva] = '<b>IVA'.$li->iva.'%</b>';
               $fila['iva'.$li->iva] = $this->show_precio($li->totaliva, $this->factura->coddivisa);
               $opciones['cols']['iva'.$li->iva] = array('justification' => 'right');
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
         if( !file_exists('tmp/enviar') )
            mkdir('tmp/enviar');
         
         $pdf_doc->save('tmp/enviar/'.$archivo);
      }
      else
         $pdf_doc->show();
   }
   
   private function generar_asiento()
   {
      if( $this->factura->get_asiento() )
         $this->new_error_msg('Ya hay un asiento asociado a esta factura.');
      else
      {
         $subcuenta_cli = $this->cliente->get_subcuenta($this->factura->codejercicio);
         if( !$subcuenta_cli )
         {
            $this->new_message("El cliente no tiene asociada una subcuenta y por
               tanto no se generará un asiento.");
         }
         else
         {
            $asiento = new asiento();
            $asiento->codejercicio = $this->factura->codejercicio;
            $asiento->concepto = "Nuestra factura ".$this->factura->codigo." - ".$this->factura->nombrecliente;
            $asiento->documento = $this->factura->codigo;
            $asiento->editable = FALSE;
            $asiento->fecha = $this->factura->fecha;
            $asiento->importe = $this->factura->total;
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
               $partida0->debe = $this->factura->total;
               $partida0->coddivisa = $this->factura->coddivisa;
               if( !$partida0->save() )
               {
                  $asiento_correcto = FALSE;
                  $this->new_error_msg("¡Imposible generar la partida para la subcuenta ".$partida0->codsubcuenta."!");
               }
               
               /// generamos una partida por cada impuesto
               $subcuenta_iva = $subcuenta->get_by_codigo('4770000000', $asiento->codejercicio);
               foreach($this->factura->get_lineas_iva() as $li)
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
                     $partida1->cifnif = $this->cliente->cifnif;
                     $partida1->documento = $asiento->documento;
                     $partida1->tipodocumento = $asiento->tipodocumento;
                     $partida1->codserie = $this->factura->codserie;
                     $partida1->factura = $this->factura->numero;
                     $partida1->baseimponible = $li->neto;
                     $partida1->iva = $li->iva;
                     $partida1->coddivisa = $this->factura->coddivisa;
                     if( !$partida1->save() )
                     {
                        $asiento_correcto = FALSE;
                        $this->new_error_msg("¡Imposible generar la partida para la subcuenta ".$partida1->codsubcuenta."!");
                     }
                  }
               }
               
               $subcuenta_ventas = $subcuenta->get_by_codigo('7000000000', $asiento->codejercicio);
               if($subcuenta_ventas AND $asiento_correcto)
               {
                  $partida2 = new partida();
                  $partida2->idasiento = $asiento->idasiento;
                  $partida2->concepto = $asiento->concepto;
                  $partida2->idsubcuenta = $subcuenta_ventas->idsubcuenta;
                  $partida2->codsubcuenta = $subcuenta_ventas->codsubcuenta;
                  $partida2->haber = $this->factura->neto;
                  $partida2->coddivisa = $this->factura->coddivisa;
                  if( !$partida2->save() )
                  {
                     $asiento_correcto = FALSE;
                     $this->new_error_msg("¡Imposible generar la partida para la subcuenta ".$partida2->codsubcuenta."!");
                  }
               }
               
               if($asiento_correcto)
               {
                  $this->factura->idasiento = $asiento->idasiento;
                  if( $this->factura->save() )
                     $this->new_message("<a href='".$asiento->url()."'>Asiento</a> generado correctamente.");
                  else
                     $this->new_error_msg("¡Imposible añadir el asiento a la factura!");
               }
               else
               {
                  if( $asiento->delete() )
                     $this->new_message("El asiento se ha borrado.");
                  else
                     $this->new_error_msg("¡Imposible borrar el asiento!");
               }
            }
            else
               $this->new_error_msg("¡Imposible guardar el asiento!");
         }
      }
   }
   
   private function enviar_email()
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
         foreach($fsvar->multi_get( array('mail_host','mail_port','mail_enc','mail_user') ) as $var)
            $mailop[$var->name] = $var->varchar;
         
         $filename = 'factura_'.$this->factura->codigo.'.pdf';
         $this->generar_pdf('simple', $filename);
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
            $mail->Subject = $this->empresa->nombre . ': Su factura '.$this->factura->codigo;
            $mail->AltBody = 'Buenos días, le adjunto su factura '.$this->factura->codigo.".\n".$this->empresa->email_firma;
            $mail->WordWrap = 50;
            $mail->MsgHTML( nl2br($_POST['mensaje']) );
            $mail->AddAttachment('tmp/enviar/'.$filename);
            $mail->AddAddress($_POST['email'], $this->cliente->nombrecomercial);
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

?>