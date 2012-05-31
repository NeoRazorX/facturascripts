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

require_once 'model/proveedor.php';

class general_proveedor extends fs_controller
{
   public $proveedor;
   public $albaranes;
   public $offset;
   
   public function __construct()
   {
      parent::__construct('general_proveedor', 'Proveedor', 'general', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->ppage = $this->page->get('general_proveedores');
      
      if( isset($_POST['codproveedor']) )
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
         
         if( isset($_GET['offset']) )
            $this->offset = intval($_GET['offset']);
         else
            $this->offset = 0;
         
         $this->albaranes = $this->proveedor->get_albaranes($this->offset);
      }
   }
   
   public function url()
   {
      if($this->proveedor)
         return $this->proveedor->url();
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
