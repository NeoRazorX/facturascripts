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
require_model('ejercicio.php');
require_model('factura_cliente.php');
require_model('factura_proveedor.php');
require_model('albaran_cliente.php');
require_model('albaran_proveedor.php');

class informe_errores extends fs_controller
{
   public $ajax;
   public $ejercicio;
   public $errores;
   public $informe;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Errores', 'informes', FALSE, TRUE);
   }
   
   protected function process()
   {
      $this->ajax = FALSE;
      $this->ejercicio = new ejercicio();
      $this->errores = array();
      $this->informe = array(
          'model' => 'asiento',
          'duplicados' => isset($_POST['duplicados']),
          'offset' => 0,
          'pages' => 0,
          'show_page' => 0,
          'started' => FALSE,
          'all' => FALSE,
          'ejercicio' => ''
      );
      
      if( isset($_GET['cancelar']) )
      {
         unlink('tmp/'.FS_TMP_NAME.'informe_errores.txt');
      }
      else if( file_exists('tmp/'.FS_TMP_NAME.'informe_errores.txt') ) /// continua examinando
      {
         $file = fopen('tmp/'.FS_TMP_NAME.'informe_errores.txt', 'r+');
         if($file)
         {
            /*
             * leemos el archivo tmp/informe_errores.txt donde guardamos los datos
             * y extraemos la configuración y los errores de la "página" seleccionada
             */
            $linea = explode( ';', trim(fgets($file)) );
            if( count($linea) == 8 )
            {
               $this->informe['model'] = $linea[0];
               $this->informe['duplicados'] = ($linea[1]==1);
               $this->informe['offset'] = intval($linea[2]);
               $this->informe['pages'] = intval($linea[3]);
               
               if( isset($_POST['show_page']) )
                  $this->informe['show_page'] = intval($_POST['show_page']);
               else if( isset($_GET['show_page']) )
                  $this->informe['show_page'] = intval($_GET['show_page']);
               else
                  $this->informe['show_page'] = intval($linea[4]);
               
               $this->informe['started'] = ($linea[5]==1);
               $this->informe['all'] = ($linea[6]==1);
               $this->informe['ejercicio'] = $linea[7];
            }
            
            if( isset($_POST['ajax']) )
            {
               $this->ajax = TRUE;
               
               /// leemos los errores de la "página" seleccionada
               $numlinea = 0;
               while( !feof($file) )
               {
                  $linea = explode( ';', trim(fgets($file)) );
                  if( count($linea) == 6 )
                  {
                     if($numlinea > $this->informe['show_page']*FS_ITEM_LIMIT AND $numlinea <= (1+$this->informe['show_page'])*FS_ITEM_LIMIT)
                     {
                        $this->errores[] = array(
                            'model' => $linea[0],
                            'ejercicio' => $linea[1],
                            'id' => $linea[2],
                            'url' => $linea[3],
                            'fecha' => $linea[4],
                            'fix' => ($linea[5]==1)
                        );
                     }
                     
                     $numlinea++;
                  }
               }
               
               $new_results = $this->test_models();
               if($new_results)
               {
                  foreach($new_results as $nr)
                  {
                     fwrite($file, join(';', $nr)."\n" );
                     $numlinea++;
                  }
               }
               
               $this->informe['pages'] = intval($numlinea/FS_ITEM_LIMIT);
               
               /// guardamos la configuración
               rewind($file);
               fwrite($file, join(';', $this->informe)."\n------\n" );
            }
            else
               $this->buttons[] = new fs_button_img('b_cancelar', 'Cancelar', 'remove.png', $this->url().'&cancelar=TRUE', TRUE);
            
            fclose($file);
         }
      }
      else if( isset($_POST['modelo']) ) /// empieza a examinar
      {
         $file = fopen('tmp/'.FS_TMP_NAME.'informe_errores.txt', 'w');
         if($file)
         {
            $this->buttons[] = new fs_button_img('b_cancelar', 'Cancelar', 'remove.png', $this->url().'&cancelar=TRUE', TRUE);
            
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
            
            if( isset($_POST['ejercicio']) )
               $this->informe['ejercicio'] = $_POST['ejercicio'];
            
            if( isset($_GET['show_page']) )
               $this->informe['show_page'] = intval($_GET['show_page']);
            
            /// guardamos esta configuración
            fwrite($file, join(';', $this->informe)."\n------\n" );
            fclose($file);
         }
      }
   }
   
   private function test_models()
   {
      $mpp = 100;
      $last_errores = array();
      
      switch( $this->informe['model'] )
      {
         default:
            $asiento = new asiento();
            $asientos = $asiento->all($this->informe['offset'], $mpp);
            if($asientos)
            {
               foreach($asientos as $asi)
               {
                  if($asi->codejercicio == $this->informe['ejercicio'])
                  {
                     if($this->informe['all'])
                        $this->informe['model'] = 'factura cliente';
                     else
                        $this->informe['model'] = 'fin';
                     $this->informe['offset'] = 0;
                     break;
                  }
                  else if( !$asi->full_test($this->informe['duplicados']) )
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
                  if($fac->codejercicio == $this->informe['ejercicio'])
                  {
                     if($this->informe['all'])
                        $this->informe['model'] = 'factura proveedor';
                     else
                        $this->informe['model'] = 'fin';
                     $this->informe['offset'] = 0;
                     break;
                  }
                  else if( !$fac->full_test($this->informe['duplicados']) )
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
                  if($fac->codejercicio == $this->informe['ejercicio'])
                  {
                     if($this->informe['all'])
                        $this->informe['model'] = 'albaran cliente';
                     else
                        $this->informe['model'] = 'fin';
                     $this->informe['offset'] = 0;
                     break;
                  }
                  else if( !$fac->full_test($this->informe['duplicados']) )
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
                  if($alb->codejercicio == $this->informe['ejercicio'])
                  {
                     if($this->informe['all'])
                        $this->informe['model'] = 'albaran proveedor';
                     else
                        $this->informe['model'] = 'fin';
                     $this->informe['offset'] = 0;
                     break;
                  }
                  else if( !$alb->full_test($this->informe['duplicados']) )
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
                  if($alb->codejercicio == $this->informe['ejercicio'])
                  {
                     $this->informe['model'] = 'fin';
                     $this->informe['offset'] = 0;
                     break;
                  }
                  else if( !$alb->full_test($this->informe['duplicados']) )
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
      
      return $last_errores;
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
