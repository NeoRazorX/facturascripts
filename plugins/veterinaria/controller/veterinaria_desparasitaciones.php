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

require_model('desparasitaciones.php');

class veterinaria_desparasitaciones extends fs_controller
{
   public $desparasitaciones;

   public function __construct()
   {
      parent::__construct('veterinaria_desparasitaciones', 'Tipos Desparasitaciones', 'Veterinaria', TRUE, TRUE);
   }
   
   protected function process()
   {
      $this->desparasitaciones = new desparasitaciones();
      
      if( isset($_POST['snombre']) )
      {
         
            $desparasitaciones = new desparasitaciones();
            
         
         $desparasitaciones->nombre_desp = $_POST['snombre'];
         if( $desparasitaciones->save() )
            $this->new_message("Tipo Desparasitacion guardada correctamente.");
         else
            $this->new_error_msg("¡Imposible guardar el tipo de desparasitacion!");
      }
      else if( isset($_GET['delete']) )
      {
         
            $desparasitaciones = $this->desparasitaciones->get($_GET['delete']);
            if( $desparasitaciones )
            {
               if( $desparasitaciones->delete() )
                  $this->new_message("Tipo de desparasitacion eliminada correctamente.");
               else
                  $this->new_error_msg("¡Imposible eliminar el tipo de desparasitacion!");
            }
            else
               $this->new_error_msg("¡Tipo de desparasitacion no encontrada!");
         
      }
   }
}

?>