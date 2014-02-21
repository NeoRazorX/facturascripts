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

require_once 'model/fs_var.php';

require_model('almacen.php');
require_model('divisa.php');
require_model('ejercicio.php');
require_model('forma_pago.php');
require_model('serie.php');
require_model('pais.php');

class admin_empresa extends fs_controller
{
   public $almacen;
   public $divisa;
   public $ejercicio;
   public $forma_pago;
   public $mail;
   public $serie;
   public $pais;
   
   public $logo;
   
   public function __construct()
   {
      parent::__construct('admin_empresa', 'Empresa', 'admin', TRUE, TRUE);
   }
   
   protected function process()
   {
      $this->almacen = new almacen();
      $this->divisa = new divisa();
      $this->ejercicio = new ejercicio();
      $this->forma_pago = new forma_pago();
      $this->serie = new serie();
      $this->pais = new pais();
      
      /// obtenemos los datos de configuración del email
      $this->mail = array(
          'mail_host' => 'smtp.gmail.com',
          'mail_port' => '465',
          'mail_enc' => 'ssl',
          'mail_user' => ''
      );
      $fsvar = new fs_var();
      foreach($fsvar->multi_get( array('mail_host','mail_port','mail_enc','mail_user') ) as $var)
         $this->mail[$var->name] = $var->varchar;
      
      if( isset($_POST['nombre']) )
      {
         /*
          * Guardamos los elementos por defecto
          */
         $this->save_codalmacen( $_POST['codalmacen'] );
         $this->save_coddivisa( $_POST['coddivisa'] );
         $this->save_codejercicio( $_POST['codejercicio'] );
         $this->save_codpago( $_POST['codpago'] );
         $this->save_codserie( $_POST['codserie'] );
         $this->save_codpais( $_POST['codpais'] );
         
         /// guardamos los datos de la empresa
         $this->empresa->nombre = $_POST['nombre'];
         $this->empresa->cifnif = $_POST['cifnif'];
         $this->empresa->administrador = $_POST['administrador'];
         $this->empresa->codpais = $_POST['codpais'];
         $this->empresa->provincia = $_POST['provincia'];
         $this->empresa->ciudad = $_POST['ciudad'];
         $this->empresa->direccion = $_POST['direccion'];
         $this->empresa->codpostal = $_POST['codpostal'];
         $this->empresa->telefono = $_POST['telefono'];
         $this->empresa->fax = $_POST['fax'];
         $this->empresa->web = $_POST['web'];
         $this->empresa->email = $_POST['email'];
         $this->empresa->email_firma = $_POST['email_firma'];
         $this->empresa->email_password = $_POST['email_password'];
         $this->empresa->lema = $_POST['lema'];
         $this->empresa->horario = $_POST['horario'];
         $this->empresa->contintegrada = isset($_POST['contintegrada']);
         $this->empresa->codejercicio = $_POST['codejercicio'];
         $this->empresa->codserie = $_POST['codserie'];
         $this->empresa->coddivisa = $_POST['coddivisa'];
         $this->empresa->codpago = $_POST['codpago'];
         $this->empresa->codalmacen = $_POST['codalmacen'];
         $this->empresa->pie_factura = $_POST['pie_factura'];
         if( $this->empresa->save() )
         {
            $this->new_message('Datos guardados correctamente.');
            
            $eje0 = $this->ejercicio->get_by_fecha( $this->today() );
            if($eje0)
            {
               $this->new_message('Ahora es el momento de <a href="'.$eje0->url().'">
                  importar los datos del ejercicio fiscal</a>, si todavía no lo has hecho.');
            }
         }
         else
            $this->new_error_msg ('Error al guardar los datos.');
         
         /// guardamos los datos del email
         if( isset($_POST['mail_host']) )
         {
            $this->mail['mail_host'] = $_POST['mail_host'];
            $this->mail['mail_port'] = $_POST['mail_port'];
            $this->mail['mail_enc'] = $_POST['mail_enc'];
            $this->mail['mail_user'] = $_POST['mail_user'];
            if( !$fsvar->multi_save($this->mail) )
               $this->new_error_msg('Error al guardar los datos del email.');
         }
      }
      else if( isset($_POST['logo']) )
      {
         if( is_uploaded_file($_FILES['fimagen']['tmp_name']) )
         {
            copy($_FILES['fimagen']['tmp_name'], "tmp/logo.png");
            $this->new_message('Logotipo guardado correctamente.');
         }
      }
      else if( isset($_GET['delete_logo']) )
      {
         if( file_exists('tmp/logo.png') )
         {
            unlink('tmp/logo.png');
            $this->new_message('Logotipo borrado correctamente.');
         }
      }
      
      $this->logo = file_exists('tmp/logo.png');
   }
}

?>