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

class admin_almacenes extends fs_controller
{
   public $almacen;
   
   public function __construct()
   {
      parent::__construct('admin_almacenes', 'Almacenes', 'admin', TRUE, TRUE);
   }
   
   protected function process()
   {
      $this->almacen = new almacen();
      $this->buttons[] = new fs_button('b_nuevo_almacen', 'nuevo almacen');
      
      if( isset($_POST['scodalmacen']) )
      {
         $this->almacen->codalmacen = $_POST['scodalmacen'];
         $this->almacen->nombre = $_POST['snombre'];
         $this->almacen->direccion = $_POST['sdireccion'];
         $this->almacen->codpostal = $_POST['scodpostal'];
         $this->almacen->poblacion = $_POST['spoblacion'];
         $this->almacen->telefono = $_POST['stelefono'];
         $this->almacen->fax = $_POST['sfax'];
         $this->almacen->contacto = $_POST['scontacto'];
         $this->almacen->save();
      }
      else if( isset($_GET['delete']) )
      {
         $this->almacen->codalmacen = $_GET['delete'];
         if( $this->almacen->delete() )
            $this->new_message("Almacén eliminado correctamente");
         else
            $this->new_error_msg("¡Imposible eliminar el almacén!".$this->almacen->error_msg);
      }
   }
   
   public function version() {
      return parent::version().'-1';
   }
}

?>
