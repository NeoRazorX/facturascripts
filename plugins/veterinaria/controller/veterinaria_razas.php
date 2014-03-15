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

require_model('razas.php');

class veterinaria_razas extends fs_controller
{
   public $razas;

   public function __construct()
   {
      parent::__construct('veterinaria_razas', 'Razas', 'Veterinaria', TRUE, TRUE);
   }
   
   protected function process()
   {
      $this->razas = new razas();
      
      if( isset($_POST['snombre']) )
      {
         
            $razas = new razas();
            
         
         $razas->nombre_raza = $_POST['snombre'];
         if( $razas->save() )
            $this->new_message("Raza guardada correctamente.");
         else
            $this->new_error_msg("¡Imposible guardar la raza!");
      }
      else if( isset($_GET['delete']) )
      {
         
            $razas = $this->razas->get($_GET['delete']);
            if( $razas )
            {
               if( $razas->delete() )
                  $this->new_message("Raza eliminada correctamente.");
               else
                  $this->new_error_msg("¡Imposible eliminar la raza!");
            }
            else
               $this->new_error_msg("¡Raza no encontrada!");
         
      }
   }
}

?>