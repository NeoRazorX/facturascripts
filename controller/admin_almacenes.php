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

class new_fs_controller extends fs_controller
{
   public function __construct()
   {
      parent::__construct('admin_almacenes', 'Almacenes', 'admin', TRUE, TRUE);
   }
   
   protected function process()
   {
      if( isset($_POST['scodalmacen']) )
      {
         $a = new almacen();
         $a->codalmacen = $_POST['scodalmacen'];
         $a->nombre = $_POST['snombre'];
         $a->direccion = $_POST['sdireccion'];
         $a->codpostal = $_POST['scodpostal'];
         $a->poblacion = $_POST['spoblacion'];
         $a->telefono = $_POST['stelefono'];
         $a->fax = $_POST['sfax'];
         $a->contacto = $_POST['scontacto'];
         $a->save();
      }
      else if( isset($_GET['delete']) )
      {
         $a = new almacen();
         $a->codalmacen = $_GET['delete'];
         $a->delete();
      }
   }

   public function all()
   {
      $a = new almacen();
      return $a->all();
   }
}

?>
