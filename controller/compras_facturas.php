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

require_model('agente.php');
require_model('articulo.php');
require_model('factura_proveedor.php');
require_model('fs_extension.php');
require_model('proveedor.php');

class compras_facturas extends fs_controller
{
   public $agente;
   public $articulo;
   public $factura;
   public $offset;
   public $proveedor;
   public $resultados;

   public function __construct()
   {
      parent::__construct(__CLASS__, 'Facturas de proveedor', 'compras', FALSE, TRUE);
   }
   
   protected function process()
   {
      $this->factura = new factura_proveedor();
      
      $this->offset = 0;
      if( isset($_GET['offset']) )
         $this->offset = intval($_GET['offset']);
      
      if( isset($_GET['codagente']) )
      {
         $this->show_fs_toolbar = FALSE;
         $this->template = 'extension/compras_facturas_agente';
         $this->ppage = clone $this->page;
         $this->page->show_on_menu = FALSE;
         $this->page->title = 'Filtro: agente';
         
         $agente = new agente();
         $this->agente = $agente->get($_GET['codagente']);
         $this->resultados = $this->factura->all_from_agente($_GET['codagente'], $this->offset);
      }
      else if( isset($_GET['codproveedor']) )
      {
         $this->show_fs_toolbar = FALSE;
         $this->template = 'extension/compras_facturas_proveedor';
         $this->ppage = clone $this->page;
         $this->page->show_on_menu = FALSE;
         $this->page->title = 'Filtro: proveedor';
         
         $proveedor = new proveedor();
         $this->proveedor = $proveedor->get($_GET['codproveedor']);
         $this->resultados = $this->factura->all_from_proveedor($_GET['codproveedor'], $this->offset);
      }
      else if( isset($_GET['ref']) )
      {
         $this->template = 'extension/compras_facturas_articulo';
         $this->ppage = clone $this->page;
         $this->page->show_on_menu = FALSE;
         $this->page->title = 'Filtro: artículo';
         
         $articulo = new articulo();
         $this->articulo = $articulo->get($_GET['ref']);
         
         $linea = new linea_factura_proveedor();
         $this->resultados = $linea->all_from_articulo($_GET['ref'], $this->offset);
      }
      else
      {
         $this->custom_search = TRUE;
         $this->share_extension();
         
         $this->buttons[] = new fs_button('b_nueva', 'Nueva', 'index.php?page=nueva_compra&tipo=factura');
         
         if( isset($_GET['delete']) )
         {
            $fact = $this->factura->get($_GET['delete']);
            if($fact)
            {
               if( $fact->delete() )
               {
                  $this->new_message("Factura eliminada correctamente.");
               }
               else
                  $this->new_error_msg("¡Imposible eliminar la factura!");
            }
            else
               $this->new_error_msg("Factura no encontrada.");
         }
         
         if($this->query != '')
         {
            $this->resultados = $this->factura->search($this->query, $this->offset);
         }
         else if( isset($_GET['sinpagar']) )
         {
            $this->resultados = $this->factura->all_sin_pagar($this->offset);
         }
         else
            $this->resultados = $this->factura->all($this->offset);
      }
   }
   
   public function anterior_url()
   {
      $url = '';
      $extra = '';
      
      if( isset($_GET['sinpagar']) )
      {
         $extra = '&sinpagar=TRUE';
      }
      else if( isset($_GET['codagente']) )
      {
         $extra = '&codagente='.$_GET['codagente'];
      }
      else if( isset($_GET['codproveedor']) )
      {
         $extra = '&codproveedor='.$_GET['codproveedor'];
      }
      else if( isset($_GET['ref']) )
      {
         $extra = '&ref='.$_GET['ref'];
      }
      
      if($this->query!='' AND $this->offset>'0')
      {
         $url = $this->url()."&query=".$this->query."&offset=".($this->offset-FS_ITEM_LIMIT).$extra;
      }
      else if($this->query=='' AND $this->offset>'0')
      {
         $url = $this->url()."&offset=".($this->offset-FS_ITEM_LIMIT).$extra;
      }
      
      return $url;
   }
   
   public function siguiente_url()
   {
      $url = '';
      $extra = '';
      
      if( isset($_GET['sinpagar']) )
      {
         $extra = '&sinpagar=TRUE';
      }
      else if( isset($_GET['codagente']) )
      {
         $extra = '&codagente='.$_GET['codagente'];
      }
      else if( isset($_GET['codproveedor']) )
      {
         $extra = '&codproveedor='.$_GET['codproveedor'];
      }
      else if( isset($_GET['ref']) )
      {
         $extra = '&ref='.$_GET['ref'];
      }
      
      if($this->query!='' AND count($this->resultados)==FS_ITEM_LIMIT)
      {
         $url = $this->url()."&query=".$this->query."&offset=".($this->offset+FS_ITEM_LIMIT).$extra;
      }
      else if($this->query=='' AND count($this->resultados)==FS_ITEM_LIMIT)
      {
         $url = $this->url()."&offset=".($this->offset+FS_ITEM_LIMIT).$extra;
      }
      
      return $url;
   }
   
   private function share_extension()
   {
      /// cargamos la extensión para clientes
      $fsext0 = new fs_extension();
      if( !$fsext0->get_by(__CLASS__, 'compras_proveedor') )
      {
         $fsext = new fs_extension();
         $fsext->from = __CLASS__;
         $fsext->to = 'compras_proveedor';
         $fsext->type = 'button';
         $fsext->text = 'Facturas';
         $fsext->save();
      }
      
      if( !$fsext0->get_by(__CLASS__, 'admin_agente') )
      {
         $fsext = new fs_extension();
         $fsext->from = __CLASS__;
         $fsext->to = 'admin_agente';
         $fsext->type = 'button';
         $fsext->text = 'Facturas de proveedor';
         $fsext->save();
      }
      
      if( !$fsext0->get_by(__CLASS__, 'ventas_articulo') )
      {
         $fsext = new fs_extension();
         $fsext->from = __CLASS__;
         $fsext->to = 'ventas_articulo';
         $fsext->type = 'button';
         $fsext->text = 'Facturas de proveedor';
         $fsext->save();
      }
   }
}
