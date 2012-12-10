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

require_once 'model/articulo.php';
require_once 'model/albaran_cliente.php';
require_once 'model/albaran_proveedor.php';

class informe_articulos extends fs_controller
{
   public $articulo;
   public $resultados;
   public $top_ventas;
   public $top_compras;

   public function __construct() {
      parent::__construct('informe_articulos', 'Artículos', 'informes', FALSE, TRUE);
   }
   
   protected function process()
   {
      $this->articulo = new articulo();
      $linea_alb_cli = new linea_albaran_cliente();
      $linea_alb_pro = new linea_albaran_proveedor();
      $stock = new stock();
      
      $this->resultados = array(
          'articulos_total' => $this->articulo->count(),
          'articulos_stock' => $stock->count_by_articulo(),
          'articulos_vendidos' => $linea_alb_cli->count_by_articulo(),
          'articulos_comprados' => $linea_alb_pro->count_by_articulo()
      );
      
      $this->top_ventas = $linea_alb_cli->top_by_articulo();
      $this->top_compras = $linea_alb_pro->top_by_articulo();
   }
   
   public function version()
   {
      return parent::version().'-2';
   }
}

?>
