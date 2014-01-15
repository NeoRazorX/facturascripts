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

require_once 'model/fs_user.php';

require_model('agente.php');

class admin_users extends fs_controller
{
   public $agente;
   
   public function __construct()
   {
      parent::__construct('admin_users', 'Usuarios', 'admin', TRUE, TRUE);
   }
   
   protected function process()
   {
      $this->agente = new agente();
      $this->buttons[] = new fs_button_img('b_nuevo_usuario', 'nuevo');
      
      if( isset($_POST['nnick']) )
      {
         $nu = $this->user->get($_POST['nnick']);
         if( $nu )
            Header( 'location: '.$nu->url() );
         else
         {
            $nu = new fs_user();
            $nu->nick = $_POST['nnick'];
            if( $nu->set_password($_POST['npassword']) )
            {
               if( isset($_POST['nadmin']) )
                  $nu->admin = TRUE;
               
               if( isset($_POST['ncodagente']) )
               {
                  if($_POST['ncodagente'] != '')
                     $nu->codagente = $_POST['ncodagente'];
               }
               
               if( $nu->save() )
                  Header('location: index.php?page=admin_user&snick=' . $nu->nick);
               else
                  $this->new_error_msg("¡Imposible guardar el usuario!");
            }
         }
      }
      else if( isset($_GET['delete']) )
      {
         $nu = $this->user->get($_GET['delete']);
         if( $nu )
         {
            if( FS_DEMO )
            {
               $this->new_error_msg('En el modo <b>demo</b> no se pueden eliminar usuarios.
                  Esto es así para evitar malas prácticas entre usuarios que prueban la demo.');
            }
            else if( $nu->delete() )
               $this->new_message("Usuario ".$nu->nick." eliminado correctamente.");
            else
               $this->new_error_msg("¡Imposible eliminar al usuario!");
         }
         else
            $this->new_error_msg("¡Usuario no encontrado!");
      }
   }
}

?>