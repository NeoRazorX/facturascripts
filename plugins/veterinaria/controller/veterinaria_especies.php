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

require_model('especies.php');

class veterinaria_especies extends fs_controller
{
   public $especies;

   public function __construct()
   {
      parent::__construct('veterinaria_especies', 'Especies', 'Veterinaria', TRUE, TRUE);
   }
   
   protected function process()
   {
      $this->especies = new especies();
      
      if( isset($_POST['snombre']) )
      {
         $especies = new especies();
         $especies->nombre_especie = $_POST['snombre'];
         if( $especies->save() )
            $this->new_message("Especie guardada correctamente.");
         else
            $this->new_error_msg("¡Imposible guardar la especie!");
      }
      else if( isset($_GET['delete']) )
      {
         
            $especies = $this->especies->get($_GET['delete']);
            if( $especies )
            {
               if( $especies->delete() )
                  $this->new_message("Especie eliminada correctamente.");
               else
                  $this->new_error_msg("¡Imposible eliminar la especie!");
            }
            else
               $this->new_error_msg("¡Especie no encontrada!");
         
      }
   }
}

?>