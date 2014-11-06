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

require_model('pais.php');
require_model('proveedor.php');

class compras_proveedores extends fs_controller
{
   public $offset;
   public $pais;
   public $proveedor;
   public $resultados;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Proveedores', 'compras', FALSE, TRUE);
   }
   
   protected function process()
   {
      $this->custom_search = TRUE;
      $this->buttons[] = new fs_button_img('b_nuevo_proveedor', 'Nuevo', 'add.png', '#nuevo');
      $this->pais = new pais();
      $this->proveedor = new proveedor();
      
      if( isset($_GET['delete']) )
      {
         $proveedor = $this->proveedor->get($_GET['delete']);
         if($proveedor)
         {
            if(FS_DEMO)
            {
               $this->new_error_msg('En el modo demo no se pueden eliminar proveedores.
                  Otros usuarios podrían necesitarlos.');
            }
            else if( $proveedor->delete() )
               $this->new_message('Proveedor eliminado correctamente.');
            else
               $this->new_error_msg('Ha sido imposible borrar el proveedor.');
         }
         else
            $this->new_message('Proveedor no encontrado.');
      }
      else if( isset($_POST['cifnif']) )
      {
         $this->save_codpais( $_POST['pais'] );
         
         $proveedor = new proveedor();
         $proveedor->codproveedor = $proveedor->get_new_codigo();
         $proveedor->nombre = $_POST['nombre'];
         $proveedor->nombrecomercial = $_POST['nombre'];
         $proveedor->cifnif = $_POST['cifnif'];
         if( $proveedor->save() )
         {
            $dirproveedor = new direccion_proveedor();
            $dirproveedor->codproveedor = $proveedor->codproveedor;
            $dirproveedor->descripcion = "Principal";
            $dirproveedor->codpais = $_POST['pais'];
            $dirproveedor->provincia = $_POST['provincia'];
            $dirproveedor->ciudad = $_POST['ciudad'];
            $dirproveedor->codpostal = $_POST['codpostal'];
            $dirproveedor->direccion = $_POST['direccion'];
            if( $dirproveedor->save() )
               header('location: '.$proveedor->url());
            else
               $this->new_error_msg("¡Imposible guardar la dirección el proveedor!");
         }
         else
            $this->new_error_msg("¡Imposible guardar el proveedor!");
      }
      
      if( isset($_GET['offset']) )
         $this->offset = intval($_GET['offset']);
      else
         $this->offset = 0;
      
      if($this->query != '')
         $this->resultados = $this->proveedor->search($this->query, $this->offset);
      else
         $this->resultados = $this->proveedor->all($this->offset);
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
