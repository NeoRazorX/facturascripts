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

require_once 'model/fs_user.php';
require_once 'model/agente.php';

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
      
      if( isset($_POST['nnick']) )
      {
         $nu = new fs_user();
         if($nu->set_nick($_POST['nnick']) AND $nu->set_password($_POST['npassword']))
         {
            if( isset($_POST['nadmin']) )
               $nu->admin = TRUE;
            if( isset($_POST['ncodagente']) )
               $nu->codagente = $_POST['ncodagente'];
            if( !$nu->exists() )
            {
               if( $nu->save() )
                  Header('location: index.php?page=admin_user&snick=' . $nu->nick);
            }
            else
               Header('location: index.php?page=admin_user&snick=' . $nu->nick);
         }
         $this->new_error_msg( $nu->error_msg );
      }
      else if( isset($_GET['delete']) )
      {
         $nu = $this->user->get($_GET['delete']);
         $nu->delete();
      }
   }
}

?>
