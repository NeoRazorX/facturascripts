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
require_once 'model/fs_access.php';

class admin_user extends fs_controller
{
   public $suser;
   public $agentes;

   public function __construct()
   {
      parent::__construct('admin_user', 'Usuario', 'admin', TRUE, FALSE);
   }
   
   public function process()
   {
      /// la página previa o padre
      $this->ppage = $this->page->get('admin_users');
      
      /// cargamos los agentes
      $agente = new agente();
      $this->agentes = $agente->all();
      
      if( isset($_GET['snick']) )
      {
         $suser = $this->user->get($_GET['snick']);
         if( $suser->exists() )
         {
            $this->page->title = $suser->nick;
            $this->suser = $suser;
            
            if( isset($_POST['spassword']) OR isset($_POST['scodagente']) OR isset($_POST['sadmin']) )
            {
               if( isset($_POST['spassword']) )
                  $this->suser->set_password($_POST['spassword']);
               if( isset($_POST['scodagente']) )
                  $this->suser->codagente = $_POST['scodagente'];
               if( isset($_POST['sadmin']) )
                  $this->suser->admin = TRUE;
               else
                  $this->suser->admin = FALSE;
               if( $this->suser->save() )
                  $this->new_message("Datos modificados correctamente.");
               else
                  $this->new_error_msg( $this->suser->error_msg );
            }
            else if( isset($_GET['enable']) )
            {
               $a = new fs_access( array('fs_user'=> $this->suser->nick,
                                         'fs_page'=>$_GET['enable']) );
               $a->save();
            }
            else if( isset($_GET['disable']) )
            {
               $a = new fs_access( array('fs_user'=> $this->suser->nick,
                                         'fs_page'=>$_GET['disable']) );
               if( $a->exists() )
                  $a->delete();
            }
         }
      }
      else
         $this->suser = FALSE;
   }
   
   public function all()
   {
      $returnlist = array();
      $access = new fs_access();
      $access = $access->all_from_nick($this->suser->nick);
      foreach($this->menu as $m)
      {
         foreach($access as $a)
         {
            if($m->name == $a->fs_page)
            {
               $m->enabled = TRUE;
            }
         }
         $returnlist[] = $m;
      }
      return $returnlist;
   }
   
   public function get_agente_name($cod='')
   {
      $name = '-';
      if($cod != '' AND $this->agentes)
      {
         foreach($this->agentes as $a)
         {
            if($a->codagente == $cod)
            {
               $name = "<a href='index.php?page=admin_agentes#".$a->codagente."'>".$a->nombre.' '.$a->apellidos."</a>";
               break;
            }
         }
      }
      return $name;
   }
   
   public function url()
   {
      if( $this->suser )
         return $this->suser->url();
      else
         return $this->page->url();
   }
}

?>
