<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2014  Carlos Garcia Gomez  neorazorx@gmail.com
 * Copyright (C) 2014  Francesc Pineda Segarra  shawe.ewahs@gmail.com
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
require_model('cliente.php');
require_model('pedido_cliente.php');

class ventas_pedidos extends fs_controller
{
   public $buscar_lineas;
   public $lineas;
   public $resultados;
   public $offset;

   public function __construct()
   {
      parent::__construct(__CLASS__, ucfirst(FS_PEDIDOS) . ' de cliente', 'ventas', FALSE, TRUE, TRUE);
   }

   protected function process()
   {
      $pedido = new pedido_cliente();

      /// desactivamos la barra de botones
      $this->show_fs_toolbar = FALSE;

      $this->offset = 0;
      if (isset($_GET['offset']))
         $this->offset = intval($_GET['offset']);

      if (isset($_POST['buscar_lineas']))
      {
         $this->buscar_lineas();
      }
      else if (isset($_GET['codagente']))
      {
         $this->template = 'extension/ventas_pedidos_agente';

         $agente = new agente();
         $this->agente = $agente->get($_GET['codagente']);
         $this->resultados = $pedido->all_from_agente($_GET['codagente'], $this->offset);
      }
      else if (isset($_GET['codcliente']))
      {
         $this->template = 'extension/ventas_pedidos_cliente';

         $cliente = new cliente();
         $this->cliente = $cliente->get($_GET['codcliente']);
         $this->resultados = $pedido->all_from_cliente($_GET['codcliente'], $this->offset);
      }
      else if (isset($_GET['ref']))
      {
         $this->template = 'extension/ventas_pedidos_articulo';

         $articulo = new articulo();
         $this->articulo = $articulo->get($_GET['ref']);

         $linea = new linea_pedido_cliente();
         $this->resultados = $linea->all_from_articulo($_GET['ref'], $this->offset);
      }
      else
      {
         $this->share_extension();

         if (isset($_POST['delete']))
         {
            $this->delete_pedido();
         }

         if ($this->query)
         {
            $this->resultados = $pedido->search($this->query, $this->offset);
         }
         else if (isset($_GET['pendientes']))
         {
            $this->resultados = $pedido->all_ptealbaran($this->offset);
         }
         else if (isset($_GET['rechazados']))
         {
            $this->resultados = $pedido->all_rechazados($this->offset);
         }
         else
            $this->resultados = $pedido->all($this->offset);
      }
   }

   public function anterior_url()
   {
      $url = '';
      $extra = '';

      if (isset($_GET['ptealbaran']))
      {
         $extra = '&ptealbaran=TRUE';
      }
      else if (isset($_GET['codagente']))
      {
         $extra = '&codagente=' . $_GET['codagente'];
      }
      else if (isset($_GET['codcliente']))
      {
         $extra = '&codcliente=' . $_GET['codcliente'];
      }
      else if (isset($_GET['ref']))
      {
         $extra = '&ref=' . $_GET['ref'];
      }

      if ($this->query != '' AND $this->offset > '0')
      {
         $url = $this->url() . "&query=" . $this->query . "&offset=" . ($this->offset - FS_ITEM_LIMIT) . $extra;
      }
      else if ($this->query == '' AND $this->offset > '0')
      {
         $url = $this->url() . "&offset=" . ($this->offset - FS_ITEM_LIMIT) . $extra;
      }

      return $url;
   }

   public function siguiente_url()
   {
      $url = '';
      $extra = '';

      if (isset($_GET['ptealbaran']))
      {
         $extra = '&ptealbaran=TRUE';
      }
      else if (isset($_GET['codagente']))
      {
         $extra = '&codagente=' . $_GET['codagente'];
      }
      else if (isset($_GET['codcliente']))
      {
         $extra = '&codcliente=' . $_GET['codcliente'];
      }
      else if (isset($_GET['ref']))
      {
         $extra = '&ref=' . $_GET['ref'];
      }

      if ($this->query != '' AND count($this->resultados) == FS_ITEM_LIMIT)
      {
         $url = $this->url() . "&query=" . $this->query . "&offset=" . ($this->offset + FS_ITEM_LIMIT) . $extra;
      }
      else if ($this->query == '' AND count($this->resultados) == FS_ITEM_LIMIT)
      {
         $url = $this->url() . "&offset=" . ($this->offset + FS_ITEM_LIMIT) . $extra;
      }

      return $url;
   }

   public function buscar_lineas()
   {
      /// cambiamos la plantilla HTML
      $this->template = 'ajax/ventas_lineas_pedidos';

      $this->buscar_lineas = $_POST['buscar_lineas'];
      $linea = new linea_pedido_cliente();
      $this->lineas = $linea->search($this->buscar_lineas);
   }

   private function delete_pedido()
   {
      $ped = new pedido_cliente();
      $ped1 = $ped->get($_POST['delete']);
      if ($ped1)
      {
         if ($ped1->delete())
         {
            $this->new_message(ucfirst(FS_PEDIDO) . ' ' . $ped1->codigo . " borrado correctamente.");
         }
         else
            $this->new_error_msg("¡Imposible borrar el " . FS_PEDIDO . "!");
      }
      else
         $this->new_error_msg("¡" . ucfirst(FS_PEDIDO) . " no encontrado!");
   }

   private function share_extension()
   {
      /// añadimos las extensiones para clientes, agentes y artículos
      $extensiones = array(
          array(
              'name' => 'pedidos_cliente',
              'page_from' => __CLASS__,
              'page_to' => 'ventas_cliente',
              'type' => 'button',
              'text' => ucfirst(FS_PEDIDOS),
              'params' => ''
          ),
          array(
              'name' => 'pedidos_agente',
              'page_from' => __CLASS__,
              'page_to' => 'admin_agente',
              'type' => 'button',
              'text' => ucfirst(FS_PEDIDOS) . ' de cliente',
              'params' => ''
          ),
          array(
              'name' => 'pedidos_articulo',
              'page_from' => __CLASS__,
              'page_to' => 'ventas_articulo',
              'type' => 'button',
              'text' => ucfirst(FS_PEDIDOS) . ' de cliente',
              'params' => ''
          ),
      );
      foreach ($extensiones as $ext)
      {
         $fsext0 = new fs_extension($ext);
         if (!$fsext0->save())
         {
            $this->new_error_msg('Imposible guardar los datos de la extensión ' . $ext['name'] . '.');
         }
      }
   }
}
