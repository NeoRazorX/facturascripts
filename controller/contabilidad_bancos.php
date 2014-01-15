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

require_model('banco.php');
require_model('proveedor.php');

class contabilidad_bancos extends fs_controller
{
   public $banco;
   public $proveedor;
   
   public function __construct()
   {
      parent::__construct('contabilidad_bancos', 'Bancos', 'contabilidad', FALSE, TRUE);
   }
   
   protected function process()
   {
      $this->banco = new banco();
      $this->proveedor = new proveedor();
      $this->buttons[] = new fs_button_img('b_nuevo_banco', 'nuevo');
      
      if( isset($_POST['entidad']) )
      {
         $banco2 = $this->banco->get($_POST['entidad']);
         if($banco2)
            $this->new_error_msg('Ya existe la entidad <a href="'.$banco2->url().'">'.$banco2->entidad.'</a>');
         else
         {
            $this->banco->entidad = $_POST['entidad'];
            $this->banco->nombre = $_POST['nombre'];
            
            if($_POST['codproveedor'] != '-1')
               $this->banco->codproveedor = $_POST['codproveedor'];
            
            if( $this->banco->save() )
               header('Location: '.$this->banco->url());
            else
               $this->new_error_msg('Error al guardar el banco.');
         }
      }
      else if( isset($_GET['delete']) )
      {
         $banco2 = $this->banco->get($_GET['delete']);
         if($banco2)
         {
            if( $banco2->delete() )
               $this->new_message('Banco eliminado correctamente.');
            else
               $this->new_error_msg('Ha sido imposible eliminar el banco.');
         }
         else
            $this->new_error_msg('Banco no encontrado.');
      }
   }
}

?>