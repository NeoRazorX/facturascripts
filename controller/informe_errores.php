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

require_model('asiento.php');
require_model('factura_cliente.php');
require_model('factura_proveedor.php');
require_model('albaran_cliente.php');
require_model('albaran_proveedor.php');

class informe_errores extends fs_controller
{
   public $ajax;
   public $informe;
   public $errores;
   
   public function __construct()
   {
      parent::__construct('informe_errores', 'Errores', 'informes', FALSE, TRUE);
   }
   
   protected function process()
   {
      if( $this->cache->error() )
         $this->new_error_msg( 'Memcache está deshabilitado y es necesario para continuar. '.$this->cache->error_msg() );
      else
         $this->process2();
   }
   
   private function process2()
   {
      $this->ajax = FALSE;
      
      $this->informe = $this->cache->get('informe_errores');
      if( !$this->informe OR isset($_GET['cancelar']) OR isset($_POST['modelo']) )
      {
         $this->cache->delete('informe_errores');
         
         /// borramos todas las páginas de errores
         for($i=0; $this->cache->get('informe_errores_'.$i); $i++)
            $this->cache->delete('informe_errores_'.$i);
         
         $this->informe = array(
             'model' => 'asiento',
             'duplicados' => isset($_POST['duplicados']),
             'offset' => 0,
             'pages' => 0,
             'show_page' => 0,
             'started' => FALSE,
             'all' => FALSE
         );
         
         if( isset($_POST['modelo']) )
         {
            if($_POST['modelo'] == 'todo')
            {
               $this->informe['model'] = 'asiento';
               $this->informe['started'] = TRUE;
               $this->informe['all'] = TRUE;
            }
            else if($_POST['modelo'] != '')
            {
               $this->informe['model'] = $_POST['modelo'];
               $this->informe['started'] = TRUE;
            }
         }
      }
      
      if( $this->informe['started'] )
      {
         $mpp = 75;
         
         $this->buttons[] = new fs_button_img('b_cancelar', 'cancelar', 'remove.png', $this->url().'&cancelar=TRUE', TRUE);
         
         if( isset($_GET['show_page']) )
            $this->informe['show_page'] = intval($_GET['show_page']);
         
         if( isset($_POST['ajax']) )
         {
            $this->ajax = TRUE;
            $this->informe['show_page'] = intval($_POST['show_page']);
            
            $last_errores = $this->get_errores_page( $this->informe['pages'] );
            switch( $this->informe['model'] )
            {
               default:
                  $asiento = new asiento();
                  $asientos = $asiento->all($this->informe['offset'], $mpp);
                  if($asientos)
                  {
                     foreach($asientos as $asi)
                     {
                        if( !$asi->full_test($this->informe['duplicados']) )
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
                     $this->informe['offset'] += $mpp;
                  }
                  else if($this->informe['all'])
                  {
                     $this->informe['model'] = 'factura cliente';
                     $this->informe['offset'] = 0;
                  }
                  else
                  {
                     $this->informe['model'] = 'fin';
                     $this->informe['offset'] = 0;
                  }
                  break;
                  
               case 'factura cliente':
                  $factura = new factura_cliente();
                  $facturas = $factura->all($this->informe['offset'], $mpp);
                  if($facturas)
                  {
                     foreach($facturas as $fac)
                     {
                        if( !$fac->full_test($this->informe['duplicados']) )
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
                     $this->informe['offset'] += $mpp;
                  }
                  else if($this->informe['all'])
                  {
                     $this->informe['model'] = 'factura proveedor';
                     $this->informe['offset'] = 0;
                  }
                  else
                  {
                     $this->informe['model'] = 'fin';
                     $this->informe['offset'] = 0;
                  }
                  break;
                  
               case 'factura proveedor':
                  $factura = new factura_proveedor();
                  $facturas = $factura->all($this->informe['offset'], $mpp);
                  if($facturas)
                  {
                     foreach($facturas as $fac)
                     {
                        if( !$fac->full_test($this->informe['duplicados']) )
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
                     $this->informe['offset'] += $mpp;
                  }
                  else if($this->informe['all'])
                  {
                     $this->informe['model'] = 'albaran cliente';
                     $this->informe['offset'] = 0;
                  }
                  else
                  {
                     $this->informe['model'] = 'fin';
                     $this->informe['offset'] = 0;
                  }
                  break;

               case 'albaran cliente':
                  $albaran = new albaran_cliente();
                  $albaranes = $albaran->all($this->informe['offset'], $mpp);
                  if($albaranes)
                  {
                     foreach($albaranes as $alb)
                     {
                        if( !$alb->full_test($this->informe['duplicados']) )
                        {
                           $last_errores[] = array(
                               'model' => $this->informe['model'],
                               'ejercicio' => $alb->codejercicio,
                               'id' => $alb->codigo,
                               'url' => $alb->url(),
                               'fecha' => $alb->fecha,
                               'fix' => FALSE
                           );
                        }
                     }
                     $this->informe['offset'] += $mpp;
                  }
                  else if($this->informe['all'])
                  {
                     $this->informe['model'] = 'albaran proveedor';
                     $this->informe['offset'] = 0;
                  }
                  else
                  {
                     $this->informe['model'] = 'fin';
                     $this->informe['offset'] = 0;
                  }
                  break;
               
               case 'albaran proveedor':
                  $albaran = new albaran_proveedor();
                  $albaranes = $albaran->all($this->informe['offset'], $mpp);
                  if($albaranes)
                  {
                     foreach($albaranes as $alb)
                     {
                        if( !$alb->full_test($this->informe['duplicados']) )
                        {
                           $last_errores[] = array(
                               'model' => $this->informe['model'],
                               'ejercicio' => $alb->codejercicio,
                               'id' => $alb->codigo,
                               'url' => $alb->url(),
                               'fecha' => $alb->fecha,
                               'fix' => FALSE
                           );
                        }
                     }
                     $this->informe['offset'] += $mpp;
                  }
                  else
                  {
                     $this->informe['model'] = 'fin';
                     $this->informe['offset'] = 0;
                  }
                  break;

               case 'fin':
                  break;
            }
            
            /// si ya no existe informe_errores, entonces no guardamos
            if( $this->cache->get('informe_errores') )
            {
               $this->set_errores_page($this->informe['pages'], $last_errores);
               if( count($last_errores) > FS_ITEM_LIMIT )
                  $this->informe['pages']++;
               $this->cache->set('informe_errores', $this->informe, 86400);
            }
            
            $this->errores = $this->get_errores_page( $this->informe['show_page'] );
         }
         else
            $this->cache->set('informe_errores', $this->informe, 86400);
      }
   }
   
   private function get_errores_page($page)
   {
      return $this->cache->get_array('informe_errores_'.$page);
   }
   
   private function set_errores_page($page, $value)
   {
      $this->cache->set('informe_errores_'.$page, $value, 86400);
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