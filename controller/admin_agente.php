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

require_once 'model/caja.php';
require_once 'model/agente.php';

class admin_agente extends fs_controller
{
   public $agente;
   public $caja;
   public $listado;
   public $listar;
   public $offset;
   
   /*
    * Esta página está en la carpeta admin, pero no se necesita ser admin para usarla.
    * Está en la carpeta admin porque su antecesora también lo está (y debe estarlo).
    */
   public function __construct()
   {
      parent::__construct('admin_agente', 'Agente', 'admin', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->ppage = $this->page->get('admin_agentes');
      $this->caja = new caja();
      
      if( isset($_GET['cod']) )
      {
         $this->agente = new agente();
         $this->agente = $this->agente->get($_GET['cod']);
      }
      else
         $this->agente = FALSE;
      
      if( $this->agente )
      {
         if( isset($_POST['nombre']) )
         {
            if( $this->user_can_edit() )
            {
               $this->agente->nombre = $_POST['nombre'];
               $this->agente->apellidos = $_POST['apellidos'];
               $this->agente->dnicif = $_POST['dnicif'];
               $this->agente->email = $_POST['email'];
               $this->agente->telefono = $_POST['telefono'];
               if( $this->agente->save() )
                  $this->new_message("Datos del agente guardados correctamente.");
               else
                  $this->new_error_msg("¡Imposible guardar los datos del agente!");
            }
            else
               $this->new_error_msg('No tienes permiso para modificar estos datos.');
         }
         
         $this->page->title .= ' ' . $this->agente->codagente;
         
         if($this->user->codagente != $this->agente->codagente)
            $this->buttons[] = new fs_button_img('b_delete_agente', 'eliminar', 'trash.png', '#', TRUE);
         
         if( isset($_GET['offset']) )
            $this->offset = intval($_GET['offset']);
         else
            $this->offset = 0;
         
         $this->listar = 'albaranes_cli';
         if( isset($_GET['listar']) )
         {
            if($_GET['listar'] == 'albaranes_prov')
               $this->listar = 'albaranes_prov';
            else if($_GET['listar'] == 'caja')
               $this->listar = 'caja';
         }
         
         switch($this->listar)
         {
            default:
               $this->listado = $this->caja->all_by_agente($this->agente->codagente, $this->offset);
               break;
            
            case 'albaranes_cli':
               $this->listado = $this->agente->get_albaranes_cli($this->offset);
               break;
            
            case 'albaranes_prov':
               $this->listado = $this->agente->get_albaranes_prov($this->offset);
               break;
            
         }
      }
      else
         $this->new_error_msg("Agente no encontrado.");
   }
   
   private function user_can_edit()
   {
      if( FS_DEMO AND $this->user->codagente == $this->agente->codagente )
         return TRUE;
      else if( $this->user->admin )
         return TRUE;
      else if($this->user->codagente == $this->agente->codagente)
         return TRUE;
      else
         FALSE;
   }
   
   public function version()
   {
      return parent::version().'-6';
   }
   
   public function url()
   {
      if( !isset($this->agente) )
         return parent::url();
      else if($this->agente)
         return $this->agente->url();
      else
         return $this->page->url();
   }
   
   public function anterior_url()
   {
      if($this->offset > '0')
         return $this->url()."&listar=".$this->listar."&offset=".($this->offset-FS_ITEM_LIMIT);
      else
         return '';
   }
   
   public function siguiente_url()
   {
      if(count($this->listado) == FS_ITEM_LIMIT)
         return $this->url()."&listar=".$this->listar."&offset=".($this->offset+FS_ITEM_LIMIT);
      else
         return '';
   }
}

?>