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

class admin_agente extends fs_controller
{
   public $agente;
   public $listado;
   public $listar;
   public $offset;
   
   public function __construct()
   {
      parent::__construct('admin_agente', 'Agente', 'admin', TRUE, FALSE);
   }
   
   protected function process()
   {
      $this->ppage = $this->page->get('admin_agentes');
      
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
         
         $this->page->title .= ' ' . $this->agente->codagente;
         $this->buttons[] = new fs_button('b_delete_agente', 'eliminar',
                 $this->ppage->url().'&delete='.$this->agente->codagente, 'remove', 'img/remove.png');
         
         if( isset($_GET['offset']) )
            $this->offset = intval($_GET['offset']);
         else
            $this->offset = 0;
         
         $this->listar = 'albaranes_cli';
         if( isset($_GET['listar']) )
         {
            if($_GET['listar'] == 'albaranes_prov')
               $this->listar = 'albaranes_prov';
         }
         
         if($this->listar == 'albaranes_prov')
            $this->listado = $this->agente->get_albaranes_prov($this->offset);
         else
            $this->listado = $this->agente->get_albaranes_cli($this->offset);
      }
      else
         $this->new_error_msg("Agente no encontrado.");
   }
   
   public function url()
   {
      if( $this->agente )
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
