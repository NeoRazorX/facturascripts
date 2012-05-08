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

require_once 'model/cliente.php';
require_once 'model/serie.php';

class general_clientes extends fs_controller
{
   public $cliente;
   public $offset;
   public $resultados;
   public $serie;
   
   public function __construct()
   {
      parent::__construct('general_clientes', 'Clientes', 'general', FALSE, TRUE);
   }
   
   protected function process()
   {
      $this->custom_search = TRUE;
      $this->buttons[] = new fs_button('b_nuevo_cliente', 'nuevo cliente');
      $this->cliente = new cliente();
      $this->serie = new serie();
      
      if( isset($_GET['offset']) )
         $this->offset = intval($_GET['offset']);
      else
         $this->offset = 0;
      
      if($this->query != '')
         $this->resultados = $this->cliente->search($this->query, $this->offset);
      else
         $this->resultados = $this->cliente->all($this->offset);
      
      if( isset($_POST['codcliente']) )
      {
         $this->cliente->codcliente = $_POST['codcliente'];
         $this->cliente->nombre = $_POST['nombre'];
         $this->cliente->nombrecomercial = $_POST['nombre'];
         $this->cliente->cifnif = $_POST['cifnif'];
         $this->cliente->codserie = $_POST['codserie'];
         if( $this->cliente->save() )
            header('location: '.$this->cliente->url());
         else
            $this->new_error_msg("¡Imposible guardar los datos del cliente!");
      }
   }
   
   public function anterior_url()
   {
      $url = '';
      if($this->query!='' AND $this->offset>'0')
         $url = $this->url()."&query=".$this->query."&offset=".($this->offset-FS_ITEM_LIMIT);
      else if($this->query=='' AND $this->offset>'0')
         $url = $this->url()."&offset=".($this->offset-FS_ITEM_LIMIT);
      return $url;
   }
   
   public function siguiente_url()
   {
      $url = '';
      if($this->query!='' AND count($this->resultados)==FS_ITEM_LIMIT)
         $url = $this->url()."&query=".$this->query."&offset=".($this->offset+FS_ITEM_LIMIT);
      else if($this->query=='' AND count($this->resultados)==FS_ITEM_LIMIT)
         $url = $this->url()."&offset=".($this->offset+FS_ITEM_LIMIT);
      return $url;
   }
}

?>
