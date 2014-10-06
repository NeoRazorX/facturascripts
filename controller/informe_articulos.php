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

require_model('articulo.php');
require_model('linea_albaran_cliente.php');
require_model('linea_albaran_proveedor.php');

class informe_articulos extends fs_controller
{
   public $articulo;
   public $stats;
   public $top_ventas;
   public $top_compras;

   public function __construct()
   {
      parent::__construct(__CLASS__, 'Artículos', 'informes', FALSE, TRUE);
   }
   
   protected function process()
   {
      $this->show_fs_toolbar = FALSE;
      
      $this->articulo = new articulo();
      $this->stats = $this->stats();
      $linea_alb_cli = new linea_albaran_cliente();
      $linea_alb_pro = new linea_albaran_proveedor();
      $this->top_ventas = $this->top_articulo_albcli();
      $this->top_compras = $this->top_articulo_albpro();
   }
   
   public function stats()
   {
      $stats = array(
          'total' => 0,
          'con_stock' => 0,
          'bloqueados' => 0,
          'publicos' => 0,
          'factualizado' => Date('d-m-Y', strtotime(0) )
      );
      
      $aux = $this->db->select("SELECT GREATEST( COUNT(referencia), 0) as art,
         GREATEST( SUM(case when stockfis > 0 then 1 else 0 end), 0) as stock,
         GREATEST( SUM(".$this->db->sql_to_int('bloqueado')."), 0) as bloq,
         GREATEST( SUM(".$this->db->sql_to_int('publico')."), 0) as publi,
         MAX(factualizado) as factualizado FROM articulos;");
      if($aux)
      {
         $stats['total'] = intval($aux[0]['art']);
         $stats['con_stock'] = intval($aux[0]['stock']);
         $stats['bloqueados'] = intval($aux[0]['bloq']);
         $stats['publicos'] = intval($aux[0]['publi']);
         $stats['factualizado'] = Date('d-m-Y', strtotime($aux[0]['factualizado']) );
      }
      
      return $stats;
   }
   
   public function top_articulo_albcli()
   {
      $toplist = $this->cache->get_array('albcli_top_articulos');
      if( !$toplist )
      {
         $articulo = new articulo();
         $lineas = $this->db->select_limit("SELECT referencia, SUM(cantidad) as ventas
            FROM lineasalbaranescli GROUP BY referencia ORDER BY ventas DESC", FS_ITEM_LIMIT, 0);
         if($lineas)
         {
            foreach($lineas as $l)
            {
               $art0 = $articulo->get($l['referencia']);
               if($art0)
                  $toplist[] = array($art0, intval($l['ventas']));
            }
         }
         $this->cache->set('albcli_top_articulos', $toplist);
      }
      return $toplist;
   }
   
   public function top_articulo_albpro()
   {
      $toplist = $this->cache->get('albpro_top_articulos');
      if( !$toplist )
      {
         $articulo = new articulo();
         $lineas = $this->db->select_limit("SELECT referencia, SUM(cantidad) as compras
            FROM lineasalbaranesprov GROUP BY referencia ORDER BY compras DESC", FS_ITEM_LIMIT, 0);
         if($lineas)
         {
            foreach($lineas as $l)
            {
               $art0 = $articulo->get($l['referencia']);
               if($art0)
                  $toplist[] = array($art0, intval($l['compras']));
            }
         }
         $this->cache->set('albpro_top_articulos', $toplist);
      }
      return $toplist;
   }
}
