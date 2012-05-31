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
   public $agentes;
   
   public function __construct()
   {
      parent::__construct('admin_agentes', 'Agentes', 'admin', TRUE, TRUE);
   }
   
   protected function process()
   {
      $this->agente = new agente();
      $this->buttons[] = new fs_button('b_nuevo_agente', 'nuevo agente');
      
      if( isset($_POST['scodagente']) )
      {
         $this->agente->codagente = $_POST['scodagente'];
         $this->agente->nombre = $_POST['snombre'];
         $this->agente->apellidos = $_POST['sapellidos'];
         $this->agente->dnicif = $_POST['sdnicif'];
         $this->agente->telefono = $_POST['stelefono'];
         $this->agente->email = $_POST['semail'];
         if( $this->agente->save() )
            $this->new_message("Datos del agente actualizados");
      }
      else if( isset($_GET['delete']) )
      {
         $this->agente = new agente();
         $this->agente = $this->agente->get($_GET['delete']);
         $this->agente->delete();
      }
      
      $this->agentes = $this->agente->all();
      if( !$this->agentes )
         $this->new_message("No hay agentes. Pulsa el botón <b>nuevo agente</b> para crear uno.");
   }
}

?>
