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

require_model('asiento.php');
require_model('cliente.php');
require_model('ejercicio.php');
require_model('factura_cliente.php');
require_model('fs_extension.php');
require_model('fs_var.php');
require_model('partida.php');
require_model('subcuenta.php');
require_once 'extras/phpmailer/class.phpmailer.php';
require_once 'extras/phpmailer/class.smtp.php';

class ventas_factura extends fs_controller
{
   public $agente;
   public $cliente;
   public $ejercicio;
   public $extensiones;
   public $factura;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Factura de cliente', 'ventas', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->ppage = $this->page->get('ventas_facturas');
      $this->ejercicio = new ejercicio();
      
      /// cargamos las extensiones para esta página
      $fsex = new fs_extension();
      $this->extensiones = $fsex->all_to(__CLASS__);
      
      $this->factura = FALSE;
      if( isset($_POST['idfactura']) )
      {
         $factura = new factura_cliente();
         $this->factura = $factura->get($_POST['idfactura']);
         $this->factura->observaciones = $_POST['observaciones'];
         $this->factura->numero2 = $_POST['numero2'];
         
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
            $this->new_change('Factura Cliente '.$this->factura->codigo, $this->factura->url());
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
         {
            $this->generar_pdf($_GET['imprimir']);
         }
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
            
            /// cargamos el agente
            if( !is_null($this->factura->codagente) )
            {
               $agente = new agente();
               $this->agente = $agente->get($this->factura->codagente);
            }
            
            $this->buttons[] = new fs_button_img('b_imprimir', 'Imprimir', 'print.png');
            
            if( $this->empresa->can_send_mail() )
            {
               $this->buttons[] = new fs_button_img('b_enviar', 'Enviar', 'send.png');
            }
            
            if($this->factura->idasiento)
            {
               $this->buttons[] = new fs_button('b_ver_asiento', 'Asiento', $this->factura->asiento_url());
            }
            else
            {
               $this->buttons[] = new fs_button('b_gen_asiento', 'Generar asiento', $this->url().'&gen_asiento=TRUE&petid='.$this->random_string());
            }
            
            $this->buttons[] = new fs_button_img('b_eliminar', 'Eliminar', 'trash.png', '#', TRUE);
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
   
   private function generar_pdf($tipo, $archivo=FALSE)
   {
      if( !file_exists('tmp/'.FS_TMP_NAME.'/pdf_templates') )
         mkdir('tmp/'.FS_TMP_NAME.'/pdf_templates');
      
      foreach($this->extensiones as $ext)
      {
         if( $ext->type == 'pdf' AND $ext->name == urldecode($tipo) )
         {
            file_put_contents('tmp/'.FS_TMP_NAME.'/pdf_templates/'.__CLASS__.$tipo.'.html', $ext->text);
            $this->template = __CLASS__.$tipo;
            break;
         }
      }
   }
   
   private function generar_asiento()
   {
      if( $this->factura->get_asiento() )
      {
         $this->new_error_msg('Ya hay un asiento asociado a esta factura.');
      }
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
               $subcuenta_iva = $subcuenta->get_cuentaesp('IVAREP', $asiento->codejercicio);
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
               
               $subcuenta_ventas = $subcuenta->get_cuentaesp('VENTAS', $asiento->codejercicio);
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
         $mailop = $fsvar->array_get($mailop, FALSE);
         
         $filename = 'factura_'.$this->factura->codigo.'.pdf';
         $this->generar_pdf('simple', $filename);
         if( file_exists('tmp/'.FS_TMP_NAME.'enviar/'.$filename) )
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
            $mail->AddAttachment('tmp/'.FS_TMP_NAME.'enviar/'.$filename);
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
