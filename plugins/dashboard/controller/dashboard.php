<?php
/*
 * This file is part of FacturaScripts
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

class dashboard extends fs_controller
{
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Dashboard', 'ventas', FALSE, TRUE);
   }
   
   protected function process()
   {
      /// Guardamos las extensiones
      $extensiones = array(
          array(
              'name' => 'docs.min.css',
              'page_from' => __CLASS__,
              'page_to' => __CLASS__,
              'type' => 'head',
              'text' => '<link href="plugins/dashboard/view/css/docs.min.css" rel="stylesheet" type="text/css" />',
              'params' => ''
          ),
          array(
              'name' => 'carousel.css',
              'page_from' => __CLASS__,
              'page_to' => __CLASS__,
              'type' => 'head',
              'text' => '<link href="plugins/dashboard/view/css/carousel.css" rel="stylesheet" type="text/css" />',
              'params' => ''
          )
      );
      foreach($extensiones as $ext)
      {
         $fsext0 = new fs_extension($ext);
         if( !$fsext0->save() )
         {
            $this->new_error_msg('Imposible guardar los datos de la extensión '.$ext['name'].'.');
         }
      }
   }
   
   /* Devuelve el número total de presupuestos hechos */
   public function num_presupuestos()
   {
      $data = $this->db->select("SELECT COUNT( DISTINCT(codigo)) AS total
            FROM `presupuestoscli`");
      return $data;
   }
   
   /* Devuelve el número total de presupuestos sin aprobar */
   public function num_presupuestos_pendientes()
   {
      $data = $this->db->select("SELECT COUNT( DISTINCT(codigo)) AS total
            FROM `presupuestoscli`
            WHERE idpedido IS NULL");
      return $data;
   }
   
   /* Devuelve el número total de pedidos hechos */
   public function num_pedidos()
   {
      $data = $this->db->select("SELECT COUNT( DISTINCT(codigo)) AS total
            FROM `pedidoscli`");
      return $data;
   }
   
   /* Devuelve el número total de pedidos sin aprobar */
   public function num_pedidos_pendientes()
   {
      $data = $this->db->select("SELECT COUNT( DISTINCT(codigo)) AS total
            FROM `pedidoscli`
            WHERE idalbaran IS NULL");
      return $data;
   }
   
   /* Devuelve el número total de albaranes hechos */
   public function num_albaranes()
   {
      $data = $this->db->select("SELECT COUNT( DISTINCT(codigo)) AS total
            FROM `albaranescli`");
      return $data;
   }
   
   /* Devuelve el número total de albaranes sin aprobar */
   public function num_albaranes_pendientes()
   {
      $data = $this->db->select("SELECT COUNT( DISTINCT(codigo)) AS total
            FROM `albaranescli`
            WHERE idfactura IS NULL");
      return $data;
   }
   
   /* Devuelve el número total de facturas hechas */
   public function num_facturas()
   {
      $data = $this->db->select("SELECT COUNT( DISTINCT(codigo)) AS total
            FROM `facturascli`");
      return $data;
   }
   
   /* Devuelve el número total de facturas sin cobrar */
   public function num_facturas_pendientes()
   {
      $data = $this->db->select("SELECT COUNT( DISTINCT(codigo)) AS total
            FROM `facturascli`
            WHERE pagada=FALSE");
      return $data;
   }
   
   public function num_articulos()
   {
      $data = $this->db->select("SELECT COUNT( DISTINCT(referencia)) AS total
            FROM `articulos`");
      return $data;
   }
   
   public function num_clientes()
   {
      $data = $this->db->select("SELECT COUNT( DISTINCT(codcliente)) AS total
            FROM `clientes`");
      return $data;
   }
   
}
