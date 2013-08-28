<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2013  Carlos Garcia Gomez  neorazorx@gmail.com
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
require_once 'model/ejercicio.php';
require_once 'model/fs_access.php';

class admin_user extends fs_controller
{
   public $agente;
   public $ejercicio;
   public $suser;
   
   public function __construct()
   {
      parent::__construct('admin_user', 'Usuario', 'admin', TRUE, FALSE);
   }
   
   public function process()
   {
      $this->ppage = $this->page->get('admin_users');
      $this->agente = new agente();
      $this->ejercicio = new ejercicio();
      $user_no_more_admin = FALSE;
      
      if( isset($_GET['snick']) )
         $this->suser = $this->user->get($_GET['snick']);
      
      if( $this->suser )
      {
         $this->page->title = $this->suser->nick;
         
         if($this->suser->admin)
            $this->new_advice('Los administradores tienen acceso a cualquier página.');
         
         if($this->user->nick != $this->suser->nick)
            $this->buttons[] = new fs_button_img('b_eliminar_usuario', 'eliminar', 'trash.png', '#', TRUE);
         
         if( isset($_POST['spassword']) OR isset($_POST['scodagente']) OR isset($_POST['sadmin']) )
         {
            if($_POST['spassword'] != '')
               $this->suser->set_password($_POST['spassword']);
            
            if( isset($_POST['scodagente']) )
            {
               if($_POST['scodagente'] == '')
                  $this->suser->codagente = NULL;
               else
                  $this->suser->codagente = $_POST['scodagente'];
            }
            
            /*
             * El propio usuario no puede decidir dejar de ser administrador.
             */
            if($this->user->nick != $this->suser->nick)
            {
               /*
                * Si un usuario es administrador y deja de serlo, hay que darle acceso
                * a algunas páginas, en caso contrario no podrá continuar
                */
               if($this->suser->admin AND !isset($_POST['sadmin']))
                  $user_no_more_admin = TRUE;
               
               $this->suser->admin = isset($_POST['sadmin']);
            }
            
            if( isset($_POST['udpage']) )
               $this->suser->fs_page = $_POST['udpage'];
            else
               $this->suser->fs_page = NULL;
            
            if( isset($_POST['ejercicio']) )
               $this->suser->codejercicio = $_POST['ejercicio'];
            else
               $this->suser->codejercicio = NULL;
            
            if(FS_DEMO AND $this->user->nick != $this->suser->nick)
            {
               $this->new_error_msg('En el modo <b>demo</b> sólo puedes modificar los datos de TU usuario.
                  Esto es así para evitar malas prácticas entre usuarios que prueban la demo.');
               $this->suser = $this->user->get($_GET['snick']);
            }
            else if( $this->suser->save() )
            {
               if( !$this->suser->admin )
               {
                  foreach($this->all_pages() as $p)
                  {
                     $a = new fs_access( array('fs_user'=> $this->suser->nick, 'fs_page'=>$p->name) );
                     
                     if( $user_no_more_admin )
                        $a->save();
                     else if( !isset($_POST['enabled']) )
                        $a->delete();
                     else if( !$p->enabled AND in_array($p->name, $_POST['enabled']) )
                        $a->save();
                     else if( $p->enabled AND !in_array($p->name, $_POST['enabled']) )
                        $a->delete();
                  }
               }
               
               $this->new_message("Datos modificados correctamente.");
            }
            else
               $this->new_error_msg("¡Imposible modificar los datos!");
         }
      }
      else
         $this->new_error_msg("Usuario no encontrado.");
   }
   
   public function version()
   {
      return parent::version().'-8';
   }
   
   public function url()
   {
      if( !isset($this->suser) )
         return parent::url();
      else if($this->suser)
         return $this->suser->url();
      else
         return $this->page->url();
   }
   
   public function all_pages()
   {
      $returnlist = array();
      foreach($this->menu as $m)
      {
         $m->enabled = FALSE;
         $returnlist[] = $m;
      }
      
      $access = $this->suser->get_accesses();
      foreach($returnlist as $i => $value)
      {
         foreach($access as $a)
         {
            if($value->name == $a->fs_page)
            {
               $returnlist[$i]->enabled = TRUE;
               break;
            }
         }
      }
      return $returnlist;
   }
}

?>