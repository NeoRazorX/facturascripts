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

require_once 'model/albaran_cliente.php';
require_once 'model/cliente.php';
require_once 'model/divisa.php';
require_once 'model/forma_pago.php';
require_once 'model/pais.php';
require_once 'model/serie.php';

class general_cliente extends fs_controller
{
   public $buscar_lineas;
   public $cliente;
   public $divisa;
   public $forma_pago;
   public $listado;
   public $listar;
   public $pais;
   public $offset;
   public $serie;
   
   public function __construct()
   {
      parent::__construct('general_cliente', 'Cliente', 'general', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->ppage = $this->page->get('general_clientes');
      $this->divisa = new divisa();
      $this->forma_pago = new forma_pago();
      $this->pais = new pais();
      $this->serie = new serie();
      
      if( isset($_POST['buscar_lineas']) )
      {
         $this->buscar_lineas();
      }
      else if( isset($_POST['coddir']) )
      {
         $cliente = new cliente();
         $this->cliente = $cliente->get( $_POST['codcliente'] );
         $dir = new direccion_cliente();
         if($_POST['coddir'] != '')
            $dir = $dir->get($_POST['coddir']);
         $dir->apartado = $_POST['apartado'];
         $dir->ciudad = $_POST['ciudad'];
         $dir->codcliente = $this->cliente->codcliente;
         $dir->codpais = $_POST['pais'];
         $dir->codpostal = $_POST['codpostal'];
         $dir->descripcion = $_POST['descripcion'];
         $dir->direccion = $_POST['direccion'];
         $dir->domenvio = isset($_POST['direnvio']);
         $dir->domfacturacion = isset($_POST['dirfact']);
         $dir->provincia = $_POST['provincia'];
         if( $dir->save() )
            $this->new_message("Dirección guardada correctamente.");
         else
            $this->new_message("¡Imposible guardar la dirección!");
      }
      else if( isset($_POST['codcliente']) )
      {
         $cliente = new cliente();
         $this->cliente = $cliente->get( $_POST['codcliente'] );
         $this->cliente->nombre = $_POST['nombre'];
         $this->cliente->nombrecomercial = $_POST['nombrecomercial'];
         $this->cliente->cifnif = $_POST['cifnif'];
         $this->cliente->telefono1 = $_POST['telefono1'];
         $this->cliente->telefono2 = $_POST['telefono2'];
         $this->cliente->fax = $_POST['fax'];
         $this->cliente->web = $_POST['web'];
         $this->cliente->email = $_POST['email'];
         $this->cliente->observaciones = $_POST['observaciones'];
         $this->cliente->codserie = $_POST['codserie'];
         $this->cliente->codpago = $_POST['codpago'];
         $this->cliente->coddivisa = $_POST['coddivisa'];
         if( $this->cliente->save() )
            $this->new_message("Datos del cliente modificados correctamente.");
         else
            $this->new_error_msg("¡Imposible modificar los datos del cliente!");
      }
      else if( isset($_GET['cod']) )
      {
         $cliente = new cliente();
         $this->cliente = $cliente->get($_GET['cod']);
         $this->page->title = $_GET['cod'];
      }
      
      if($this->cliente)
      {
         $this->buttons[] = new fs_button_img('b_eliminar', 'eliminar', 'trash.png', '#', TRUE);
         
         if( isset($_GET['offset']) )
            $this->offset = intval($_GET['offset']);
         else
            $this->offset = 0;
         
         $this->listar = 'albaranes';
         if( isset($_GET['listar']) )
         {
            if($_GET['listar'] == 'facturas')
               $this->listar = 'facturas';
         }
         
         if($this->listar == 'facturas')
            $this->listado = $this->cliente->get_facturas($this->offset);
         else
            $this->listado = $this->cliente->get_albaranes($this->offset);
      }
      else if( !isset($_POST['buscar_lineas']) )
         $this->new_error_msg("¡Cliente no encontrado!");
   }
   
   public function version()
   {
      return parent::version().'-6';
   }
   
   public function url()
   {
      if( !isset($this->cliente) )
         return parent::url();
      else if($this->cliente)
         return $this->cliente->url();
      else
         return $this->ppage->url();
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
   
   public function buscar_lineas()
   {
      /// cambiamos la plantilla HTML
      $this->template = 'ajax/general_lineas_albaranes_cli';
      
      $this->buscar_lineas = $_POST['buscar_lineas']; /// necesario para el html
      $linea = new linea_albaran_cliente();
      $this->lineas = $linea->search_from_cliente($_POST['codcliente'], $this->buscar_lineas);
   }
}

?>