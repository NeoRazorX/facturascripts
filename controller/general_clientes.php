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
require_once 'model/pais.php';
require_once 'model/serie.php';

class general_clientes extends fs_controller
{
   public $cliente;
   public $offset;
   public $pais;
   public $resultados;
   public $serie;
   
   public function __construct()
   {
      parent::__construct('general_clientes', 'Clientes', 'general', FALSE, TRUE);
   }
   
   protected function process()
   {
      $this->custom_search = TRUE;
      $this->buttons[] = new fs_button('b_nuevo_cliente', 'nuevo');
      $this->cliente = new cliente();
      $this->pais = new pais();
      $this->serie = new serie();
      
      if( isset($_POST['codcliente']) )
      {
         $this->set_default_elements();
         
         $cliente = new cliente();
         $cliente->codcliente = $_POST['codcliente'];
         $cliente->nombre = $_POST['nombre'];
         $cliente->nombrecomercial = $_POST['nombre'];
         $cliente->cifnif = $_POST['cifnif'];
         $cliente->codserie = $_POST['codserie'];
         if( $cliente->save() )
         {
            $dircliente = new direccion_cliente();
            $dircliente->codcliente = $cliente->codcliente;
            $dircliente->codpais = $_POST['pais'];
            $dircliente->provincia = $_POST['provincia'];
            $dircliente->ciudad = $_POST['ciudad'];
            $dircliente->codpostal = $_POST['codpostal'];
            $dircliente->direccion = $_POST['direccion'];
            $dircliente->descripcion = 'Principal';
            if( $dircliente->save() )
               header('location: '.$cliente->url());
            else
               $this->new_error_msg("¡Imposible guardar la dirección del cliente!");
         }
         else
            $this->new_error_msg("¡Imposible guardar los datos del cliente!");
      }
      
      if( isset($_GET['offset']) )
         $this->offset = intval($_GET['offset']);
      else
         $this->offset = 0;
      
      if($this->query != '')
         $this->resultados = $this->cliente->search($this->query, $this->offset);
      else
         $this->resultados = $this->cliente->all($this->offset);
   }
   
   public function version() {
      return parent::version().'-4';
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
   
   private function set_default_elements()
   {
      if( isset($_POST['pais']) )
      {
         $pais = $this->pais->get($_POST['pais']);
         if( $pais )
            $pais->set_default();
      }
      
      if( isset($_POST['codserie']) )
      {
         $serie = $this->serie->get($_POST['codserie']);
         if( $serie )
            $serie->set_default();
      }
   }
}

?>
