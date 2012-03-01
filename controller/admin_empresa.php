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

require_once 'model/empresa.php';
require_once 'model/almacen.php';
require_once 'model/ejercicio.php';
require_once 'model/pais.php';

class admin_empresa extends fs_controller
{
   public $empresa;
   
   public function __construct()
   {
      parent::__construct('admin_empresa', 'Empresa', 'admin', TRUE, TRUE);
   }
   
   protected function process()
   {
      $this->empresa = new empresa();
      
      if( isset($_POST['nombre']) )
      {
         $this->empresa->nombre = $_POST['nombre'];
         
         if( isset($_POST['cifnif']) )
            $this->empresa->cifnif = $_POST['cifnif'];
         if( isset($_POST['administrador']) )
            $this->empresa->administrador = $_POST['administrador'];
         if( isset($_POST['direccion']) )
            $this->empresa->direccion = $_POST['direccion'];
         if( isset($_POST['ciudad']) )
            $this->empresa->ciudad = $_POST['ciudad'];
         if( isset($_POST['codpostal']) )
            $this->empresa->codpostal = $_POST['codpostal'];
         if( isset($_POST['telefono']) )
            $this->empresa->telefono = $_POST['telefono'];
         if( isset($_POST['fax']) )
            $this->empresa->fax = $_POST['fax'];
         if( isset($_POST['web']) )
            $this->empresa->web = $_POST['web'];
         if( isset($_POST['email']) )
            $this->empresa->email = $_POST['email'];
         
         if( $this->empresa->save() )
         {
            $this->new_message('Datos guardados correctamente.');
            setcookie('empresa', $this->empresa->nombre, time()+FS_COOKIES_EXPIRE);
         }
         else
            $this->new_error_msg ('Error al actualizar la base de datos.');
      }
   }
   
   public function almacenes()
   {
      $almacen = new almacen();
      return $almacen->all();
   }
   
   public function ejercicios()
   {
      $ejercicio = new ejercicio();
      return $ejercicio->all();
   }
   
   public function paises()
   {
      $pais = new pais();
      return $pais->all();
   }
}

?>
