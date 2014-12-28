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
require_model('grupo_clientes.php');
require_model('pais.php');
require_model('serie.php');
require_model('tarifa.php');

class ventas_clientes extends fs_controller
{
   public $cliente;
   public $clientes_grupo;
   public $grupo;
   public $grupos;
   public $offset;
   public $pais;
   public $resultados;
   public $serie;
   public $tarifa;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Clientes', 'ventas', FALSE, TRUE);
   }
   
   protected function process()
   {
      $this->show_fs_toolbar = FALSE;
      $this->cliente = new cliente();
      $this->clientes_grupo = FALSE;
      $this->grupo = new grupo_clientes();
      $this->pais = new pais();
      $this->serie = new serie();
      $this->tarifa = new tarifa();
      
      if( isset($_GET['delete_grupo']) ) /// eliminar un grupo
      {
         $grupo = $this->grupo->get($_GET['delete_grupo']);
         if($grupo)
         {
            if( $grupo->delete() )
            {
               $this->new_message('Grupo eliminado correctamente.');
            }
            else
               $this->new_error_msg('Imposible eliminar el grupo.');
         }
         else
            $this->new_error_msg('Grupo no encontrado.');
      }
      else if( isset($_POST['codgrupo']) ) /// añadir/modificar un grupo
      {
         $grupo = $this->grupo->get($_POST['codgrupo']);
         if(!$grupo)
         {
            $grupo = new grupo_clientes();
            $grupo->codgrupo = $_POST['codgrupo'];
         }
         $grupo->nombre = $_POST['nombre'];
         
         if($_POST['codtarifa'] == '---')
         {
            $grupo->codtarifa = NULL;
         }
         else
            $grupo->codtarifa = $_POST['codtarifa'];
         
         if( $grupo->save() )
         {
            $this->new_message('Grupo guardado correctamente.');
         }
         else
            $this->new_error_msg('Imposible guardar el grupo.');
      }
      else if( isset($_GET['delete']) ) /// eliminar un cliente
      {
         $cliente = $this->cliente->get($_GET['delete']);
         if($cliente)
         {
            if(FS_DEMO)
            {
               $this->new_error_msg('En el modo demo no se pueden eliminar clientes. Otros usuarios podrían necesitarlos.');
            }
            else if( $cliente->delete() )
            {
               $this->new_message('Cliente eliminado correctamente.');
            }
            else
               $this->new_error_msg('Ha sido imposible eliminar el cliente.');
         }
         else
            $this->new_error_msg('Cliente no encontrado.');
      }
      else if( isset($_POST['cifnif']) ) /// añadir un nuevo cliente
      {
         $this->save_codpais( $_POST['pais'] );
         $this->save_codserie( $_POST['codserie'] );
         
         $cliente = new cliente();
         $cliente->codcliente = $cliente->get_new_codigo();
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
            {
               header('location: '.$cliente->url());
            }
            else
               $this->new_error_msg("¡Imposible guardar la dirección del cliente!");
         }
         else
            $this->new_error_msg("¡Imposible guardar los datos del cliente!");
      }
      
      $this->offset = 0;
      if( isset($_GET['offset']) )
         $this->offset = intval($_GET['offset']);
      
      if($this->query != '')
      {
         $this->resultados = $this->cliente->search($this->query, $this->offset);
      }
      else
         $this->resultados = $this->cliente->all($this->offset);
      
      $this->grupos = $this->grupo->all();
      
      if( isset($_GET['grupo']) )
      {
         $this->clientes_grupo = $this->clientes_from_grupo($_GET['grupo']);
      }
   }
   
   public function anterior_url()
   {
      $url = '';
      
      if($this->query!='' AND $this->offset>'0')
      {
         $url = $this->url()."&query=".$this->query."&offset=".($this->offset-FS_ITEM_LIMIT);
      }
      else if($this->query=='' AND $this->offset>'0')
      {
         $url = $this->url()."&offset=".($this->offset-FS_ITEM_LIMIT);
      }
      
      return $url;
   }
   
   public function siguiente_url()
   {
      $url = '';
      
      if($this->query!='' AND count($this->resultados)==FS_ITEM_LIMIT)
      {
         $url = $this->url()."&query=".$this->query."&offset=".($this->offset+FS_ITEM_LIMIT);
      }
      else if($this->query=='' AND count($this->resultados)==FS_ITEM_LIMIT)
      {
         $url = $this->url()."&offset=".($this->offset+FS_ITEM_LIMIT);
      }
      
      return $url;
   }
   
   private function clientes_from_grupo($cod)
   {
      $clist = array();
      
      $data = $this->db->select("SELECT * FROM clientes WHERE codgrupo = ".$this->cliente->var2str($cod).";");
      if($data)
      {
         foreach($data as $d)
            $clist[] = new cliente($d);
      }
      
      return $clist;
   }
   
   public function nombre_grupo($cod)
   {
      $nombre = '-';
      
      foreach($this->grupos as $g)
      {
         if($g->codgrupo == $cod)
         {
            $nombre = $g->nombre;
            break;
         }
      }
      
      return $nombre;
   }
}
