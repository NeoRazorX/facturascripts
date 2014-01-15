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

require_once 'plugins/woocommerce/model/fs_mysql_x.php';
require_once 'model/fs_var.php';
require_model('articulo.php');

class admin_woocommerce extends fs_controller
{
   private $mysql;
   public $woo_setup;
   
   public function __construct()
   {
      parent::__construct('admin_woocommerce', 'WooCommerce', 'admin', TRUE, TRUE);
   }
   
   protected function process()
   {
      $this->mysql = new fs_mysql_x();
      $fs_var = new fs_var();
      $this->woo_setup = array(
          'woo_server' => '',
          'woo_port' => '',
          'woo_dbname' => '',
          'woo_user' => '',
          'woo_password' => '',
          'connected' => FALSE
      );
      
      if( isset($_POST['woo_server']) )
      {
         $this->woo_setup['woo_server'] = $_POST['woo_server'];
         $this->woo_setup['woo_port'] = $_POST['woo_port'];
         $this->woo_setup['woo_dbname'] = $_POST['woo_dbname'];
         $this->woo_setup['woo_user'] = $_POST['woo_user'];
         $this->woo_setup['woo_password'] = $_POST['woo_password'];
         
         if( $fs_var->multi_save($this->woo_setup) )
            $this->new_message("Datos guardados correctamente.");
         else
            $this->new_error_msg("Error al guardar los datos.");
      }
      
      $num = 0;
      foreach($fs_var->multi_get(array('woo_server','woo_port','woo_dbname','woo_user','woo_password')) as $fv)
      {
         if($fv->varchar != '')
         {
            if($fv->name == 'woo_server')
            {
               $this->woo_setup['woo_server'] = $fv->varchar;
               $num++;
            }
            else if($fv->name == 'woo_port')
            {
               $this->woo_setup['woo_port'] = $fv->varchar;
               $num++;
            }
            else if($fv->name == 'woo_dbname')
            {
               $this->woo_setup['woo_dbname'] = $fv->varchar;
               $num++;
            }
            else if($fv->name == 'woo_user')
            {
               $this->woo_setup['woo_user'] = $fv->varchar;
               $num++;
            }
            else if($fv->name == 'woo_password')
            {
               $this->woo_setup['woo_password'] = $fv->varchar;
               $num++;
            }
         }
      }
      
      if($num == 5)
      {
         $this->mysql->connect($this->woo_setup['woo_server'], $this->woo_setup['woo_port'],
                 $this->woo_setup['woo_user'], $this->woo_setup['woo_password'], $this->woo_setup['woo_dbname']);
         
         if( $this->mysql->connected )
         {
            $this->woo_setup['connected'] = TRUE;
            
            if( isset($_GET['sync']) )
               $this->woo_sync();
            else
               $this->buttons[] = new fs_button('b_woo_sync', 'Sincronizar', $this->url().'&sync=TRUE');
         }
         else
            $this->new_error_msg('Error al conectar. '.$this->mysql->last_error());
      }
      else
      {
         $this->new_advice('<a target="_blank" href="http://www.woothemes.com/woocommerce/">WooCommerce</a>
            es el plugin para wordpress que sirve para montar una pequeña tienda online.
            Introduce los datos de la base de datos de wordpress para empezar a sincronizar.');
      }
   }
   
   private function woo_sync()
   {
      $articulo = new articulo();
      
      foreach($articulo->all_publico() as $art)
      {
         $post_id = 0;
         if( !$this->woo_add_product($art, $post_id) )
         {
            $this->new_error_msg('Error al sincronizar el artículo '.$art->referencia);
            break;
         }
         else if( !$this->woo_add_product_info($art, $post_id) )
         {
            $this->new_error_msg('Error al sincronizar los datos del artículo '.$art->referencia);
            break;
         }
      }
      
      $this->new_message("Sincronización finalizada.");
   }
   
   private function woo_add_product($articulo, &$post_id)
   {
      $done = TRUE;
      $referencia = $this->sanitize_title( $articulo->referencia );
      $product = $this->mysql->select("SELECT * FROM wp_posts
         WHERE post_name = ".$articulo->var2str($referencia).";");
      if($product)
      {
         $sql = "UPDATE wp_posts SET post_content = ".$articulo->var2str($articulo->descripcion)."
            WHERE post_name = ".$articulo->var2str($referencia).";";
         if( $this->mysql->exec($sql) )
         {
            $done = TRUE;
            $post_id = $product[0]['ID'];
         }
      }
      else
      {
         $sql = "INSERT INTO wp_posts (post_name,post_title,post_content,post_author,post_date,
            post_date_gmt,post_status,comment_status,ping_status,post_modified,post_modified_gmt,
            post_parent,menu_order,post_type,comment_count) VALUES
            (".$articulo->var2str($referencia).",".$articulo->var2str($articulo->referencia).",
            ".$articulo->var2str($articulo->descripcion).",'1','".Date('Y-m-d H:m:i')."',
            '".Date('Y-m-d H:m:i')."','publish','open','closed','".Date('Y-m-d H:m:i')."',
            '".Date('Y-m-d H:m:i')."','0','0','product','0');";
         if( $this->mysql->exec($sql) )
         {
            $done = TRUE;
            $post_id = $this->mysql->lastval();
         }
      }
      return $done;
   }
   
   private function sanitize_title($title)
   {
      $title = str_replace('+', 'M', strtolower($title) );
      $title = str_replace('/', 'B', $title);
      $title = str_replace('*', 'A', $title);
      return str_replace('.', 'P', $title);
   }
   
   private function woo_add_product_info($articulo, $post_id)
   {
      $done = TRUE;
      $data = array(
          array('meta_key'=>'_manage_stock', 'meta_value'=>'no'),
          array('meta_key'=>'_backorders', 'meta_value'=>'no'),
          array('meta_key'=>'_price', 'meta_value'=>$articulo->pvp),
          array('meta_key'=>'_sale_price_dates_from', 'meta_value'=>'no'),
          array('meta_key'=>'_product_attributes', 'meta_value'=>'a:0:{}'),
          array('meta_key'=>'_sku', 'meta_value'=>$articulo->referencia),
          array('meta_key'=>'_featured', 'meta_value'=>'no'),
          array('meta_key'=>'_regular_price', 'meta_value'=>$articulo->pvp),
          array('meta_key'=>'_virtual', 'meta_value'=>'no'),
          array('meta_key'=>'_downloadable', 'meta_value'=>'no'),
          array('meta_key'=>'total_sales', 'meta_value'=>0),
          array('meta_key'=>'_stock_status', 'meta_value'=>'instock'),
          array('meta_key'=>'_visibility', 'meta_value'=>'visible')
      );
      
      foreach($data as $d)
      {
         $meta = $this->mysql->select("SELECT * FROM wp_postmeta WHERE post_id = ".$articulo->var2str($post_id)."
            AND meta_key = ".$articulo->var2str($d['meta_key']).";");
         if($meta)
         {
            $sql = "UPDATE wp_postmeta SET meta_value = ".$articulo->var2str($d['meta_value']).
                    " WHERE meta_id = ".$articulo->var2str($meta[0]['meta_id']).";";
         }
         else
         {
            $sql = "INSERT INTO wp_postmeta (post_id,meta_key,meta_value) VALUES
               (".$articulo->var2str($post_id).",".$articulo->var2str($d['meta_key']).
               ",".$articulo->var2str($d['meta_value']).");";
         }
         
         if( !$this->mysql->exec($sql) )
         {
            $done = FALSE;
            break;
         }
      }
      
      return $done;
   }
}

?>