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

require_model('especie.php');
require_model('raza.php');

class veterinaria_razas extends fs_controller
{
   public $especie;
   public $raza;

   public function __construct()
   {
      parent::__construct('veterinaria_razas', 'Razas', 'Veterinaria', TRUE, TRUE);
   }
   
   protected function process()
   {
      $this->especie = new especie();
      $this->raza = new raza();
      
      if( isset($_POST['idraza']) ) /// modificar una raza
      {
         $raza0 = $this->raza->get($_POST['idraza']);
         if($raza0)
         {
            $raza0->especie = $_POST['sespecie'];
            $raza0->nombre = $_POST['snombre'];
            if( $raza0->save() )
               $this->new_message('Raza guardada correctamente.');
            else
               $this->new_error_msg('Imposible guardar la raza.');
         }
         else
            $this->new_error_msg('Raza no encontrada.');
      }
      else if( isset($_POST['snombre']) ) /// añadir una raza
      {
         $this->raza->especie = $_POST['sespecie'];
         $this->raza->nombre = $_POST['snombre'];
         if( $this->raza->save() )
            $this->new_message('Raza guardada correctamente.');
         else
            $this->new_error_msg('Imposible guardar la raza.');
      }
      else if( isset($_GET['delete']) ) /// eliminar una raza
      {
         $raza0 = $this->raza->get($_GET['delete']);
         if($raza0)
         {
            if( $raza0->delete() )
               $this->new_message('Raza eliminada correctamente.');
            else
               $this->new_error_msg('Error al eliminar la raza.');
         }
         else
            $this->new_error_msg('Raza no encontrada.');
      }
   }
}

?>