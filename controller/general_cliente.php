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

require_once 'model/cliente.php';

class general_cliente extends fs_controller
{
   public $cliente;
   public $albaranes;
   public $offset;
   
   public function __construct()
   {
      parent::__construct('general_cliente', 'Cliente', 'general', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->ppage = $this->page->get('general_clientes');
      $this->cliente = new cliente();
      
      if( isset($_POST['codcliente']) )
      {
         $this->cliente = $this->cliente->get( $_POST['codcliente'] );
         $this->cliente->nombre = $_POST['nombre'];
         $this->cliente->nombrecomercial = $_POST['nombrecomercial'];
         $this->cliente->cifnif = $_POST['cifnif'];
         $this->cliente->telefono1 = $_POST['telefono1'];
         $this->cliente->telefono2 = $_POST['telefono2'];
         $this->cliente->fax = $_POST['fax'];
         $this->cliente->web = $_POST['web'];
         $this->cliente->email = $_POST['email'];
         if( $this->cliente->save() )
            $this->new_message("Datos del cliente modificados correctamente");
         else
            $this->new_error_msg("¡Imposible modificar los datos del cliente!".$this->cliente->error_msg);
      }
      else if( isset($_GET['cod']) )
      {
         $this->cliente = $this->cliente->get($_GET['cod']);
         $this->page->title = $_GET['cod'];
      }
      
      if( $this->cliente )
      {
         $this->buttons[] = new fs_button('b_direcciones', 'direcciones', '#', 'button', 'img/zoom.png');
         $this->buttons[] = new fs_button('b_subcuentas', 'subcuentas', '#', 'button', 'img/zoom.png');
         
         if( isset($_GET['offset']) )
            $this->offset = intval($_GET['offset']);
         else
            $this->offset = 0;
         
         $this->albaranes = $this->cliente->get_albaranes($this->offset);
      }
   }
   
   public function url()
   {
      if($this->cliente)
         return $this->cliente->url();
      else
         return $this->page->url();
   }
   
   public function anterior_url()
   {
      if($this->offset > '0')
         return $this->url()."&offset=".($this->offset-FS_ITEM_LIMIT);
   }
   
   public function siguiente_url()
   {
      if(count($this->albaranes) == FS_ITEM_LIMIT)
         return $this->url()."&offset=".($this->offset+FS_ITEM_LIMIT);
   }
}

?>
