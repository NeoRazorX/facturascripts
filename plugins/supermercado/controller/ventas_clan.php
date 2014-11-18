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

require_model('cliente.php');
require_model('clan_familiar.php');

class ventas_clan extends fs_controller
{
   public $busqueda;
   public $clan;
   public $clientes;
   public $resultado;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Clan', 'ventas', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->show_fs_toolbar = FALSE;
      $this->busqueda = '';
      $this->resultado = array();
      
      if( isset($_GET['cod']) )
      {
         $clan = new clan_familiar();
         $this->clan = $clan->get($_GET['cod']);
      }
      
      if( isset($_POST['buscar_cliente']) )
      {
         $this->buscar_cliente();
      }
      else if($this->clan)
      {
         if( isset($_POST['nombre']) )
         {
            $this->clan->nombre = $_POST['nombre'];
            $this->clan->limite = floatval($_POST['limite']);
            $this->clan->restringido = isset($_POST['restringido']);
            if( $this->clan->save() )
               $this->new_message('Datos modificados correctamente.');
            else
               $this->new_error_msg('Imposible guardar los cambios.');
            
            $this->clientes = $this->clan->get_clientes();
            foreach($this->clientes as $i => $value)
            {
               if( !isset($_POST['codcliente']) )
               {
                  $this->quitar_cliente($value->codcliente);
                  unset($this->clientes[$i]);
               }
               else if( !in_array($value->codcliente, $_POST['codcliente']) )
               {
                  $this->quitar_cliente($value->codcliente);
                  unset($this->clientes[$i]);
               }
            }
         }
         else if( isset($_GET['cliente']) )
         {
            $cliente2clan = new cliente2clan();
            $cliente2clan->codclan = $this->clan->codclan;
            $cliente2clan->codcliente = $_GET['cliente'];
            if( $cliente2clan->save() )
               $this->new_message('Cliente añadido correctamente.');
            else
               $this->new_error_msg('Error al añadir el cliente.');
         }
         
         $this->page->title = 'Clan '.$this->clan->codclan;
         $this->clientes = $this->clan->get_clientes();
      }
      else
         $this->new_error_msg('Clan familiar no encontrado.');
   }
   
   public function url()
   {
      if( !isset($this->clan) )
         return parent::url();
      else if($this->clan)
         return $this->clan->url();
      else
         return parent::url();
   }
   
   private function quitar_cliente($cod)
   {
      $cliente2clan = new cliente2clan();
      $cliente2clan->codclan = $this->clan->codclan;
      $cliente2clan->codcliente = $cod;
      $cliente2clan->delete();
   }
   
   private function buscar_cliente()
   {
      $this->template = 'ajax_buscar_cliente';
      $this->busqueda = trim($_POST['buscar_cliente']);
      $cliente = new cliente();
      $this->resultado = $cliente->search($this->busqueda);
   }
}
