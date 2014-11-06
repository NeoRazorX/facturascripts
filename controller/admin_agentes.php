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

require_model('agente.php');

class admin_agentes extends fs_controller
{
   public $agente;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Empleados', 'admin', TRUE, TRUE);
   }
   
   protected function process()
   {
      $this->agente = new agente();
      $this->buttons[] = new fs_button_img('b_nuevo_agente', 'Nuevo');
      
      if( isset($_POST['sdnicif']) )
      {
         $age0 = new agente();
         $age0->codagente = $age0->get_new_codigo();
         $age0->nombre = $_POST['snombre'];
         $age0->apellidos = $_POST['sapellidos'];
         $age0->dnicif = $_POST['sdnicif'];
         $age0->telefono = $_POST['stelefono'];
         $age0->email = $_POST['semail'];
         if( $age0->save() )
         {
            $this->new_message("Empleado ".$age0->codagente." guardado correctamente.");
            header('location: '.$age0->url());
         }
         else
            $this->new_error_msg("¡Imposible guardar el empleado!");
      }
      else if( isset($_GET['delete']) )
      {
         $age0 = $this->agente->get($_GET['delete']);
         if($age0)
         {
            if( FS_DEMO )
            {
               $this->new_error_msg('En el modo <b>demo</b> no se pueden eliminar empleados. Otro usuario podría estar usándolo.');
            }
            else if( $age0->delete() )
            {
               $this->new_message("Empleado ".$age0->codagente." eliminado correctamente.");
            }
            else
               $this->new_error_msg("¡Imposible eliminar el empleado!");
         }
         else
            $this->new_error_msg("¡Empleado no encontrado!");
      }
   }
}
