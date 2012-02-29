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

class new_fs_controller extends fs_controller
{
   public function __construct()
   {
      parent::__construct('admin_agentes', 'Agentes', 'admin', TRUE, TRUE);
   }
   
   protected function process()
   {
      if( isset($_POST['scodagente']) )
      {
         $agente = new agente();
         $agente->codagente = $_POST['scodagente'];
         $agente->nombre = $_POST['snombre'];
         $agente->apellidos = $_POST['sapellidos'];
         $agente->dnicif = $_POST['sdnicif'];
         $agente->telefono = $_POST['stelefono'];
         $agente->email = $_POST['semail'];
         if( $agente->save() )
            $this->new_message("Datos del agente actualizados");
      }
      else if( isset($_GET['delete']) )
      {
         $agente = new agente();
         $agente = $agente->get($_GET['delete']);
         $agente->delete();
      }
   }

   public function all()
   {
      $a = new agente();
      return $a->all();
   }
}

?>
