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

require_once 'model/almacen.php';
require_once 'model/divisa.php';
require_once 'model/ejercicio.php';
require_once 'model/forma_pago.php';
require_once 'model/serie.php';
require_once 'model/pais.php';

class admin_empresa extends fs_controller
{
   public $almacen;
   public $divisa;
   public $ejercicio;
   public $forma_pago;
   public $serie;
   public $pais;
   
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
         $this->empresa->email_password = $_POST['email_password'];
         $this->empresa->lema = $_POST['lema'];
         $this->empresa->horario = $_POST['horario'];
         $this->empresa->contintegrada = isset($_POST['contintegrada']);
         $this->empresa->codejercicio = $_POST['codejercicio'];
         $this->empresa->codserie = $_POST['codserie'];
         $this->empresa->coddivisa = $_POST['coddivisa'];
         $this->empresa->codpago = $_POST['codpago'];
         $this->empresa->codalmacen = $_POST['codalmacen'];
         
         if( $this->empresa->save() )
         {
            $this->new_message('Datos guardados correctamente.');
            setcookie('empresa', $this->empresa->nombre, time()+FS_COOKIES_EXPIRE);
         }
         else
            $this->new_error_msg ('Error al guardar los datos.');
      }
   }
   
   public function version()
   {
      return parent::version().'-7';
   }
}

?>
