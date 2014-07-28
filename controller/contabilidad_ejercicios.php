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

require_model('ejercicio.php');

class contabilidad_ejercicios extends fs_controller
{
   public $ejercicio;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Ejercicios', 'contabilidad', FALSE, TRUE);
   }
   
   protected function process()
   {
      $this->ejercicio = new ejercicio();
      $this->buttons[] = new fs_button_img('b_nuevo_ejercicio', 'Nuevo');
      
      if( isset($_GET['delete']) )
      {
         $eje0 = $this->ejercicio->get($_GET['delete']);
         if($eje0)
         {
            if( $eje0->delete() )
               $this->new_message('Ejercicio eliminado correctamente.');
            else
               $this->new_error_msg("¡Imposible eliminar el ejercicio!");
         }
         else
            $this->new_error_msg("Ejercicio no encontrado");
      }
      else if( isset($_POST['codejercicio']) )
      {
         $this->ejercicio->codejercicio = $_POST['codejercicio'];
         $this->ejercicio->nombre = $_POST['nombre'];
         $this->ejercicio->fechainicio = $_POST['fechainicio'];
         $this->ejercicio->fechafin = $_POST['fechafin'];
         $this->ejercicio->estado = $_POST['estado'];
         if( $this->ejercicio->save() )
         {
            $this->new_message("Ejercicio ".$this->ejercicio->codejercicio." guardado correctamente.");
            header('location: '.$this->ejercicio->url());
         }
         else
            $this->new_error_msg("¡Imposible guardar el ejercicio!");
      }
   }
}
