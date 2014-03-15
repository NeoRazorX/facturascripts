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

require_model('vacunas.php');

class veterinaria_vacunas extends fs_controller
{
   public $vacunas;

   public function __construct()
   {
      parent::__construct('veterinaria_vacunas', 'Tipos Vacunas', 'Veterinaria', TRUE, TRUE);
   }
   
   protected function process()
   {
      $this->vacunas = new vacunas();
      
      if( isset($_POST['snombre']) )
      {
         
            $vacunas = new vacunas();
            
         
         $vacunas->nombre_vac = $_POST['snombre'];
         if( $vacunas->save() )
            $this->new_message("Tipo de vacuna guardada correctamente.");
         else
            $this->new_error_msg("¡Imposible guardar el tipo de vacuna!");
      }
      else if( isset($_GET['delete']) )
      {
         
            $vacunas = $this->vacunas->get($_GET['delete']);
            if( $vacunas )
            {
               if( $vacunas->delete() )
                  $this->new_message("Tipo de vacuna eliminada correctamente.");
               else
                  $this->new_error_msg("¡Imposible eliminar el tipo de vacuna!");
            }
            else
               $this->new_error_msg("¡Tipo de vacuna no encontrada!");
         
      }
   }
}

?>