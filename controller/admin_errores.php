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

require_once 'base/fs_cache.php';
require_once 'model/asiento.php';
require_once 'model/factura_cliente.php';
require_once 'model/factura_proveedor.php';

class admin_errores extends fs_controller
{
   private $cache;
   public $informe;
   public $errores;
   public $full_page;
   
   public function __construct()
   {
      parent::__construct('admin_errores', 'Errores en la base de datos', 'admin', TRUE, FALSE);
   }
   
   protected function process()
   {
      $this->cache = new fs_cache();
      
      $this->informe = $this->cache->get('admin_errores');
      if( !$this->informe OR isset($_GET['restart']) )
      {
         $this->cache->delete('admin_errores');
         
         /// borramos todas las páginas de errores
         for($i=0; $this->cache->get('admin_errores_'.$i); $i++)
            $this->cache->delete('admin_errores_'.$i);
         
         $this->informe = array(
             'model' => 'asiento',
             'offset' => 0,
             'pages' => 0,
             'show_page' => 0,
             'start' => TRUE
         );
      }
      else if($this->informe)
      {
         /// modificamos esta variable para indicar que no es la primera iteración del algorito
         $this->informe['start'] = FALSE;
      }
      
      $this->ppage = $this->page->get('admin_info');
      $this->buttons[] = new fs_button('b_restart_ae', 'reiniciar', $this->url().'&restart=TRUE',
              'remove', 'img/remove.png');
      
      if( isset($_POST['process']) )
      {
         $this->full_page = FALSE;
         
         if( isset($_POST['show_page']) )
            $this->informe['show_page'] = intval($_POST['show_page']);
         
         $last_errores = $this->get_errores_page( $this->informe['pages'] );
         
         switch( $this->informe['model'] )
         {
            default:
               $asiento = new asiento();
               $asientos = $asiento->all($this->informe['offset'], 100);
               if($asientos)
               {
                  foreach($asientos as $asi)
                  {
                     if( !$asi->full_test() )
                     {
                        $last_errores[] = array(
                            'model' => $this->informe['model'],
                            'ejercicio' => $asi->codejercicio,
                            'id' => $asi->numero,
                            'url' => $asi->url(),
                            'fecha' => $asi->fecha,
                            'fix' => $asi->fix()
                        );
                     }
                  }
                  $this->informe['offset'] += 100;
               }
               else
               {
                  $this->informe['model'] = 'factura cliente';
                  $this->informe['offset'] = 0;
               }
               break;
            
            case 'factura cliente':
               $factura = new factura_cliente();
               $facturas = $factura->all($this->informe['offset'], 100);
               if($facturas)
               {
                  foreach($facturas as $fac)
                  {
                     if( !$fac->full_test() )
                     {
                        $last_errores[] = array(
                            'model' => $this->informe['model'],
                            'ejercicio' => $fac->codejercicio,
                            'id' => $fac->codigo,
                            'url' => $fac->url(),
                            'fecha' => $fac->fecha,
                            'fix' => FALSE
                        );
                     }
                  }
                  $this->informe['offset'] += 100;
               }
               else
               {
                  $this->informe['model'] = 'factura proveedor';
                  $this->informe['offset'] = 0;
               }
               break;
            
            case 'factura proveedor':
               $factura = new factura_proveedor();
               $facturas = $factura->all($this->informe['offset'], 100);
               if($facturas)
               {
                  foreach($facturas as $fac)
                  {
                     if( !$fac->full_test() )
                     {
                        $last_errores[] = array(
                            'model' => $this->informe['model'],
                            'ejercicio' => $fac->codejercicio,
                            'id' => $fac->codigo,
                            'url' => $fac->url(),
                            'fecha' => $fac->fecha,
                            'fix' => FALSE
                        );
                     }
                  }
                  $this->informe['offset'] += 100;
               }
               else
               {
                  $this->informe['model'] = 'caca';
                  $this->informe['offset'] = 0;
               }
               break;
            
            case 'caca':
               break;
         }
         
         /*
          * Guardamos solamente si es la primera iteración o existe una previa.
          * Así nos evitamos problemas al reinciar el algoritmo.
          */
         if( $this->informe['start'] OR $this->cache->get('admin_errores') )
         {
            $this->set_errores_page($this->informe['pages'], $last_errores);
            if( count($last_errores) > FS_ITEM_LIMIT )
               $this->informe['pages']++;
            
            $this->cache->set('admin_errores', $this->informe, 86400);
         }
      }
      else
         $this->full_page = TRUE;
      
      $this->errores = $this->get_errores_page( $this->informe['show_page'] );
   }
   
   public function version()
   {
      return parent::version().'-3';
   }
   
   private function get_errores_page($page)
   {
      return $this->cache->get_array('admin_errores_'.$page);
   }
   
   private function set_errores_page($page, $value)
   {
      $this->cache->set('admin_errores_'.$page, $value, 86400);
   }
   
   public function all_pages()
   {
      $allp = array();
      $show_p = $this->informe['show_page'];
      /// cargamos todas las páginas
      for($i = 0; $i<=$this->informe['pages']; $i++)
         $allp[] = array('page' => $i, 'num' => $i+1, 'selected' => ($i==$show_p));
      /// ahora descartamos
      foreach($allp as $j => $value)
      {
         if( ($value['num']>1 AND $j<$show_p-3 AND $value['num']%10) OR ($j>$show_p+3 AND $j<$i-1 AND $value['num']%10) )
            unset($allp[$j]);
      }
      return $allp;
   }
}

?>