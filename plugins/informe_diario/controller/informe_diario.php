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

class informe_diario extends fs_controller
{
   public $articulos;
   public $hoy;
   
   public function __construct()
   {
      parent::__construct('informe_diario', 'Diario', 'informes');
   }
   
   protected function process()
   {
      $articulo = new articulo();
      $this->articulos = array();
      $this->hoy = !isset($_GET['ayer']);
      
      if( isset($_GET['ayer']) )
      {
         $this->hoy = FALSE;
         $fecha = date( "d-m-Y", strtotime("-1 day") );
      }
      else
      {
         $this->hoy = TRUE;
         $fecha = date("d-m-Y");
      }
      
      /// leemos directamente de la base de datos
      $data = $this->db->select("SELECT referencia, SUM(cantidad) as cantidad, AVG(pvptotal/cantidad) as precio
         FROM lineasalbaranescli WHERE idalbaran IN (SELECT idalbaran FROM albaranescli WHERE fecha = ".$articulo->var2str($fecha).")
         GROUP BY referencia ORDER BY referencia ASC;");
      if($data)
      {
         foreach($data as $d)
         {
            $art0 = $articulo->get($d['referencia']);
            if($art0)
            {
               $this->articulos[] = array(
                   'referencia' => $d['referencia'],
                   'url' => $art0->url(),
                   'descripcion' => $art0->descripcion,
                   'cantidad' => $d['cantidad'],
                   'precio' => $d['precio']
               );
            }
         }
      }
   }
}