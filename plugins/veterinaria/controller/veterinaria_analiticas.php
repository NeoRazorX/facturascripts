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

require_model('analiticas.php');

class veterinaria_analiticas extends fs_controller
{
   public $analiticas;

   public function __construct()
   {
      parent::__construct('veterinaria_analiticas', 'Tipos Analiticas', 'Veterinaria', TRUE, TRUE);
   }
   
   protected function process()
   {
      $this->analiticas = new analiticas();
      
      if( isset($_POST['snombre']) )
      {
         
            $analiticas = new analiticas();
            
         
         $analiticas->nombre_ana = $_POST['snombre'];
         if( $analiticas->save() )
            $this->new_message("Tipo Analitica guardada correctamente.");
         else
            $this->new_error_msg("¡Imposible guardar el tipo de analitica!");
      }
      else if( isset($_GET['delete']) )
      {
         
            $analiticas = $this->analiticas->get($_GET['delete']);
            if( $analiticas )
            {
               if( $analiticas->delete() )
                  $this->new_message("Tipo de analitica eliminada correctamente.");
               else
                  $this->new_error_msg("¡Imposible eliminar el tipo de analitica!");
            }
            else
               $this->new_error_msg("¡Tipo de analitica no encontrada!");
         
      }
   }
}

?>