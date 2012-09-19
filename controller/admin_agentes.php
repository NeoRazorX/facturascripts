<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2012  Carlos Garcia Gomez  neorazorx@gmail.com
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

require_once 'model/agente.php';

class admin_agentes extends fs_controller
{
   public $agente;
   
   public function __construct()
   {
      parent::__construct('admin_agentes', 'Agentes', 'admin', TRUE, TRUE);
   }
   
   protected function process()
   {
      $this->agente = new agente();
      $this->buttons[] = new fs_button('b_nuevo_agente', 'nuevo');
      
      if( isset($_POST['scodagente']) )
      {
         $age0 = $this->agente->get($_POST['scodagente']);
         if( !$age0 )
         {
            $age0 = new agente();
            $age0->codagente = $_POST['scodagente'];
         }
         $age0->nombre = $_POST['snombre'];
         $age0->apellidos = $_POST['sapellidos'];
         $age0->dnicif = $_POST['sdnicif'];
         $age0->telefono = $_POST['stelefono'];
         $age0->email = $_POST['semail'];
         if( $age0->save() )
            $this->new_message("Agente ".$age0->codagente." modificado correctamente.");
         else
            $this->new_error_msg("¡Imposible modificar el agente ".$age0->codagente."!");
      }
      else if( isset($_GET['delete']) )
      {
         $age0 = $this->agente->get($_GET['delete']);
         if($age0)
         {
            if( $this->agente->delete() )
               $this->new_message("Agente ".$age0->codagente." eliminado correctamente.");
            else
               $this->new_error_msg("¡Imposible eliminar el agente ".$age0->codagente."!");
         }
         else
            $this->new_error_msg("¡Agente no encontrado!");
      }
   }
   
   public function version() {
      return parent::version().'-2';
   }
}

?>
