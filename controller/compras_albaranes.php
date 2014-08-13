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
require_model('albaran_proveedor.php');
require_model('articulo.php');
require_model('fs_extension.php');
require_model('proveedor.php');

class compras_albaranes extends fs_controller
{
   public $agente;
   public $articulo;
   public $buscar_lineas;
   public $lineas;
   public $offset;
   public $proveedor;
   public $resultados;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, FS_ALBARANES.' de proveedor', 'compras', FALSE, TRUE, TRUE);
   }
   
   protected function process()
   {
      $albaran = new albaran_proveedor();
      
      $this->offset = 0;
      if( isset($_GET['offset']) )
         $this->offset = intval($_GET['offset']);
      
      if( isset($_POST['buscar_lineas']) )
      {
         $this->buscar_lineas();
      }
      else if( isset($_GET['codagente']) )
      {
         $this->template = 'extension/compras_albaranes_agente';
         $this->ppage = clone $this->page;
         $this->page->show_on_menu = FALSE;
         $this->page->title = 'Filtro: agente';
         
         $agente = new agente();
         $this->agente = $agente->get($_GET['codagente']);
         $this->resultados = $albaran->all_from_agente($_GET['codagente'], $this->offset);
      }
      else if( isset($_GET['codproveedor']) )
      {
         $this->template = 'extension/compras_albaranes_proveedor';
         $this->ppage = clone $this->page;
         $this->page->show_on_menu = FALSE;
         $this->page->title = 'Filtro: proveedor';
         
         $this->buttons[] = new fs_button('b_buscar_lineas', 'Líneas');
         
         $proveedor = new proveedor();
         $this->proveedor = $proveedor->get($_GET['codproveedor']);
         $this->resultados = $albaran->all_from_proveedor($_GET['codproveedor'], $this->offset);
      }
      else if( isset($_GET['ref']) )
      {
         $this->template = 'extension/compras_albaranes_articulo';
         $this->ppage = clone $this->page;
         $this->page->show_on_menu = FALSE;
         $this->page->title = 'Filtro: artículo';
         
         $articulo = new articulo();
         $this->articulo = $articulo->get($_GET['ref']);
         
         $linea = new linea_albaran_proveedor();
         $this->resultados = $linea->all_from_articulo($_GET['ref'], $this->offset);
      }
      else
      {
         $this->custom_search = TRUE;
         $this->share_extension();
         
         $this->buttons[] = new fs_button('b_nuevo_albaran', 'Nuevo', 'index.php?page=nueva_compra&tipo=albaran');
         $this->buttons[] = new fs_button('b_agrupar_albaranes', 'Agrupar', 'index.php?page=compras_agrupar_albaranes');
         $this->buttons[] = new fs_button('b_buscar_lineas', 'Líneas');
         
         if( !isset($_GET['ptefactura']) )
         {
            $this->buttons[] = new fs_button('b_pendientes', 'Pendientes', $this->url()."&amp;ptefactura=TRUE");
         }
         
         if( isset($_POST['delete']) )
         {
            $this->delete_albaran();
         }
         
         if($this->query != '')
         {
            $this->resultados = $albaran->search($this->query, $this->offset);
         }
         else if( isset($_GET['ptefactura']) )
         {
            $this->new_advice('Estos son los '.FS_ALBARANES.' pendientes de facturar. Haz clic <a href="'.$this->url().
                 '">aquí</a> para volver a la vista normal.');
            $this->resultados = $albaran->all_ptefactura($this->offset);
         }
         else
            $this->resultados = $albaran->all($this->offset);
      }
   }
   
   public function anterior_url()
   {
      $url = '';
      $extra = '';
      
      if( isset($_GET['ptefactura']) )
      {
         $extra = '&ptefactura=TRUE';
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
      
      if( isset($_GET['ptefactura']) )
      {
         $extra = '&ptefactura=TRUE';
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
   
   public function buscar_lineas()
   {
      /// cambiamos la plantilla HTML
      $this->template = 'ajax/compras_lineas_albaranes';
      
      $this->buscar_lineas = $_POST['buscar_lineas'];
      $linea = new linea_albaran_proveedor();
      
      if( isset($_POST['codproveedor']) )
      {
         $this->lineas = $linea->search_from_proveedor($_POST['codproveedor'], $this->buscar_lineas);
      }
      else
      {
         $this->lineas = $linea->search($this->buscar_lineas);
      }
   }
   
   private function delete_albaran()
   {
      $alb1 = new albaran_proveedor();
      $alb1 = $alb1->get($_POST['delete']);
      if($alb1)
      {
         /// ¿Actualizamos el stock de los artículos?
         if( isset($_POST['stock']) )
         {
            $articulo = new articulo();
            
            foreach($alb1->get_lineas() as $linea)
            {
               $art0 = $articulo->get($linea->referencia);
               if($art0)
               {
                  $art0->sum_stock($alb1->codalmacen, 0 - $linea->cantidad);
                  $art0->save();
               }
            }
         }
         
         if( $alb1->delete() )
            $this->new_message(FS_ALBARAN." ".$alb1->codigo." borrado correctamente.");
         else
            $this->new_error_msg("¡Imposible borrar el ".FS_ALBARAN."!");
      }
      else
         $this->new_error_msg("¡".FS_ALBARAN." no encontrado!");
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
         $fsext->text = ucfirst(FS_ALBARANES);
         $fsext->save();
      }
      
      if( !$fsext0->get_by(__CLASS__, 'admin_agente') )
      {
         $fsext = new fs_extension();
         $fsext->from = __CLASS__;
         $fsext->to = 'admin_agente';
         $fsext->type = 'button';
         $fsext->text = ucfirst(FS_ALBARANES).' de proveedores';
         $fsext->save();
      }
      
      if( !$fsext0->get_by(__CLASS__, 'ventas_articulo') )
      {
         $fsext = new fs_extension();
         $fsext->from = __CLASS__;
         $fsext->to = 'ventas_articulo';
         $fsext->type = 'button';
         $fsext->text = ucfirst(FS_ALBARANES).' de proveedores';
         $fsext->save();
      }
   }
}
