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

require_once 'model/divisa.php';
require_once 'model/forma_pago.php';
require_once 'model/pais.php';
require_once 'model/proveedor.php';
require_once 'model/serie.php';

class general_proveedor extends fs_controller
{
   public $divisa;
   public $forma_pago;
   public $listado;
   public $listar;
   public $offset;
   public $pais;
   public $proveedor;
   public $serie;

   public function __construct()
   {
      parent::__construct('general_proveedor', 'Proveedor', 'general', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->ppage = $this->page->get('general_proveedores');
      $this->divisa = new divisa();
      $this->forma_pago = new forma_pago();
      $this->pais = new pais();
      $this->serie = new serie();
      
      if( isset($_POST['coddir']) )
      {
         $this->proveedor = new proveedor();
         $this->proveedor = $this->proveedor->get($_POST['codproveedor']);
         if( $this->proveedor )
         {
            $direccion = new direccion_proveedor();
            if($_POST['coddir'] != '')
               $direccion = $direccion->get($_POST['coddir']);
            $direccion->apartado = $_POST['apartado'];
            $direccion->ciudad = $_POST['ciudad'];
            $direccion->codpais = $_POST['pais'];
            $direccion->codpostal = $_POST['codpostal'];
            $direccion->codproveedor = $this->proveedor->codproveedor;
            $direccion->descripcion = $_POST['descripcion'];
            $direccion->direccion = $_POST['direccion'];
            $direccion->direccionppal = isset($_POST['direccionppal']);
            $direccion->provincia = $_POST['provincia'];
            if( $direccion->save() )
               $this->new_message("Dirección guardada correctamente.");
            else
               $this->new_error_msg("¡Imposible guardar la dirección!");
         }
      }
      else if( isset($_POST['codproveedor']) )
      {
         $this->proveedor = new proveedor();
         $this->proveedor = $this->proveedor->get($_POST['codproveedor']);
         if( $this->proveedor )
         {
            $this->proveedor->nombre = $_POST['nombre'];
            $this->proveedor->nombrecomercial = $_POST['nombrecomercial'];
            $this->proveedor->cifnif = $_POST['cifnif'];
            $this->proveedor->telefono1 = $_POST['telefono1'];
            $this->proveedor->telefono2 = $_POST['telefono2'];
            $this->proveedor->fax = $_POST['fax'];
            $this->proveedor->email = $_POST['email'];
            $this->proveedor->web = $_POST['web'];
            $this->proveedor->observaciones = $_POST['observaciones'];
            $this->proveedor->codserie = $_POST['codserie'];
            $this->proveedor->codpago = $_POST['codpago'];
            $this->proveedor->coddivisa = $_POST['coddivisa'];
            if( $this->proveedor->save() )
               $this->new_message('Datos del proveedor modificados correctamente.');
            else
               $this->new_error_msg('¡Imposible modificar los datos del proveedor!');
         }
      }
      else if( isset($_GET['cod']) )
      {
         $this->proveedor = new proveedor();
         $this->proveedor = $this->proveedor->get($_GET['cod']);
      }
      
      if( $this->proveedor )
      {
         $this->page->title = $this->proveedor->codproveedor;
         $this->buttons[] = new fs_button('b_direcciones', 'direcciones', '#', 'button', 'img/zoom.png');
         $this->buttons[] = new fs_button('b_subcuentas', 'subcuentas', '#', 'button', 'img/zoom.png');
         
         if( isset($_GET['offset']) )
            $this->offset = intval($_GET['offset']);
         else
            $this->offset = 0;
         
         $this->listar = 'albaranes';
         if( isset($_GET['listar']) )
         {
            if($_GET['listar'] == 'facturas')
               $this->listar = 'facturas';
         }
         
         if($this->listar == 'albaranes')
            $this->listado = $this->proveedor->get_albaranes($this->offset);
         else
            $this->listado = $this->proveedor->get_facturas($this->offset);
      }
      else
         $this->new_error_msg("¡Proveedor no encontrado!");
   }
   
   public function version()
   {
      return parent::version().'-3';
   }
   
   public function url()
   {
      if($this->proveedor)
         return $this->proveedor->url();
      else
         return $this->ppage->url();
   }
   
   public function anterior_url()
   {
      if($this->offset > '0')
         return $this->url()."&listar=".$this->listar."&offset=".($this->offset-FS_ITEM_LIMIT);
      else
         return '';
   }
   
   public function siguiente_url()
   {
      if(count($this->listado) == FS_ITEM_LIMIT)
         return $this->url()."&listar=".$this->listar."&offset=".($this->offset+FS_ITEM_LIMIT);
      else
         return '';
   }
}

?>
