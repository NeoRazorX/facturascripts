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

require_model('vacuna.php');

class veterinaria_vacunas extends fs_controller
{
   public $vacuna;

   public function __construct()
   {
      parent::__construct('veterinaria_vacunas', 'Tipos Vacunas', 'Veterinaria', TRUE, TRUE);
   }
   
   protected function process()
   {
      $this->vacuna = new vacuna();
      
      if( isset($_POST['idraza']) ) /// modificar una vacuna
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
      else if( isset($_POST['snombre']) ) /// añadir un tipo de vacuna
      {
         $this->vacuna->nombre = $_POST['snombre'];
         if( $this->vacuna->save() )
            $this->new_message('Tipo de Vacuna guardada correctamente.');
         else
            $this->new_error_msg('Imposible guardar el tipo de vacuna.');
      }
      else if( isset($_GET['delete']) ) /// eliminar un tipo de vacuna
      {
         $vacuna0 = $this->vacuna->get($_GET['delete']);
         if($vacuna0)
         {
            if( $vacuna0->delete() )
               $this->new_message('Tipo de vacuna eliminada correctamente.');
            else
               $this->new_error_msg('Error al eliminar el tipo de vacuna.');
         }
         else
            $this->new_error_msg('Tipo de vacuna no encontrada.');
      }
      
      
      
      
      
      
      
      
      
      
      
      
   }
}

?>