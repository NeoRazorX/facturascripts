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

require_once 'base/fs_cache.php';
require_once 'model/articulo.php';
require_once 'model/familia.php';

class family_data
{
   public $codfamilia;
   public $codimpuesto;
   public $sufijo;
   public $action;
   public $num_articulos;
   public $lineas;
   public $lineas_procesadas;
   public $articulos_nuevos;
   public $articulos_actualizados;
   public $articulos_sin_modificar;
   public $pvp_suben;
   public $pvp_bajan;
   public $pvp_igual;
   public $pvp_diferencia;
   public $pvp_sum_diferencias;
   
   public function __construct($codfamilia, $codimpuesto=NULL, $sufijo='', $num_articulos=0)
   {
      $this->codfamilia = $codfamilia;
      $this->codimpuesto = $codimpuesto;
      $this->sufijo = $sufijo;
      $this->action = 'test';
      $this->num_articulos = $num_articulos;
      $this->lineas = -1;
      $this->lineas_procesadas = 0;
      $this->articulos_nuevos = 0;
      $this->articulos_actualizados = 0;
      $this->articulos_sin_modificar = 0;
      $this->pvp_suben = 0;
      $this->pvp_bajan = 0;
      $this->pvp_igual = 0;
      $this->pvp_diferencia = 0;
      $this->pvp_sum_diferencias = 0;
   }
   
   public function set_action($action)
   {
      if($action == $this->action)
         $this->action = $action;
      else if($action == 'test')
         $this->action = $action;
      else if($action == 'start')
      {
         $this->action = $action;
         $this->lineas_procesadas = 0;
         $this->articulos_nuevos = 0;
         $this->articulos_actualizados = 0;
         $this->articulos_sin_modificar = 0;
         $this->pvp_suben = 0;
         $this->pvp_bajan = 0;
         $this->pvp_igual = 0;
         $this->pvp_diferencia = 0;
         $this->pvp_sum_diferencias = 0;
      }
      else
         $this->action = FALSE;
   }
   
   public function check()
   {
      if($this->lineas_procesadas >= $this->lineas)
      {
         $this->articulos_sin_modificar = $this->num_articulos - $this->articulos_actualizados;
         if($this->articulos_actualizados > 0)
            $this->pvp_diferencia = $this->pvp_sum_diferencias/$this->articulos_actualizados;
      }
   }
   
   public function ready2start()
   {
      return ($this->action == 'test' AND $this->lineas_procesadas >= $this->lineas);
   }
}

class general_importar_familia extends fs_controller
{
   public $articulo;
   private $cache;
   public $family_data;
   public $familia;
   public $ready;
   
   public function __construct() {
      parent::__construct('general_importar_familia', 'importar familia', 'general', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->articulo = new articulo();
      $this->cache = new fs_cache();
      $this->ready = TRUE;
      
      if( isset($_GET['fam']) )
      {
         $familia = new familia();
         $this->familia = $familia->get($_GET['fam']);
      }
      else if( isset($_POST['fam']) )
      {
         $familia = new familia();
         $this->familia = $familia->get($_POST['fam']);
      }
      
      if( $this->familia )
      {
         if( isset($_POST['archivo']) )
         {
            if( is_uploaded_file($_FILES['farchivo']['tmp_name']) )
            {
               if( !file_exists("tmp/familias") )
                  mkdir("tmp/familias");
               else if( file_exists("tmp/familias/".$this->familia->codfamilia.'.csv') )
                  unlink("tmp/familias/".$this->familia->codfamilia.'.csv');
               
               copy($_FILES['farchivo']['tmp_name'], "tmp/familias/".$this->familia->codfamilia.'.csv');
               
               /// limpiamos la cache
               $this->cache->delete('family_data_'.$this->familia->codfamilia);
               $this->family_data = new family_data($this->familia->codfamilia, $_POST['impuesto'],
                    $_POST['sufijo'], $this->articulo->count($this->familia->codfamilia));
               $this->save_family_data();
            }
            else
               $this->new_error_msg("¡Imposible cargar el archivo!");
         }
         
         if( file_exists("tmp/familias/".$this->familia->codfamilia.".csv") )
         {
            if( isset($_GET['cancelar']) )
            {
               $this->cache->delete('family_data_'.$this->familia->codfamilia);
               unlink("tmp/familias/".$this->familia->codfamilia.'.csv');
               header("location: ".$this->familia->url());
            }
            else
               $this->family_data = $this->get_family_data();
            
            if( isset($_GET['action']) )
               $this->family_data->set_action($_GET['action']);
            
            if($this->family_data->action == 'test')
            {
               $this->ready = $this->test_csv_file();
               if( !$this->ready )
               {
                  $this->new_message("Comprobando ... ".$this->family_data->lineas_procesadas.
                                     "/".$this->family_data->lineas);
               }
               else
               {
                  $this->new_message("Comprobación finalizada. Pulsa el botón <b>procesar</b> para comenzar.");
                  $this->buttons[] = new fs_button('b_start', 'procesar', $this->url().'&action=start');
               }
            }
            else if($this->family_data->action == 'start')
            {
               $this->ready = $this->csv2articulos();
               if( !$this->ready )
               {
                  $this->new_message("Procesando ... ".$this->family_data->lineas_procesadas.
                                     "/".$this->family_data->lineas);
               }
               else
                  $this->new_message("Proceso finalizado");
            }
            
            $this->buttons[] = new fs_button('b_cancelar', 'cancelar', $this->url().'&cancelar=TRUE', 'remove', 'img/remove.png');
         }
         else
            $this->new_error_msg("¡No se ha encontrado el archivo ".$this->familia->codfamilia.".csv!");
      }
      else
         $this->new_error_msg("¡Ninguna familia seleccionada!");
   }
   
   public function url()
   {
      if($this->familia)
         return $this->page->url().'&fam='.$this->familia->codfamilia;
      else
         return $this->page->url();
   }
   
   public function version()
   {
      return parent::version().'-1';
   }
   
   private function get_family_data()
   {
      $data = $this->cache->get_array('family_data_'.$this->familia->codfamilia);
      if($data)
         return $data;
      else
         return new family_data($this->familia->codfamilia);
   }
   
   private function save_family_data()
   {
      $this->cache->set('family_data_'.$this->familia->codfamilia, $this->family_data);
   }
   
   /// comprobamos el archivo csv, devolvemos FALSE si queremos continuar comprobando
   private function test_csv_file()
   {
      $retorno = FALSE;
      $file = fopen("tmp/familias/".$this->familia->codfamilia.".csv", 'r');
      if($file)
      {
         if( $this->family_data->lineas < 0 )
         {
            $i = 0;
            while( !feof($file) )
            {
               fgets($file, 1024);
               $i++;
            }
            $this->family_data->lineas = $i;
            $this->save_family_data();
         }
         else if( $this->family_data->lineas_procesadas < $this->family_data->lineas )
         {
            $i = 0;
            while(!feof($file) AND !$retorno)
            {
               $linea = trim( fgets($file, 1024) );
               if( $i == 0 )
               {
                  $cabecera = explode(';', $linea);
                  if($cabecera[0] != 'REF' OR $cabecera[1] != 'PVP' OR $cabecera[2] != 'DESC' OR $cabecera[3] != 'CODBAR')
                  {
                     $this->new_error_msg("¡Las columnas no concuerdan!");
                     $retorno = TRUE;
                  }
               }
               else if($i >= $this->family_data->lineas_procesadas AND $i < ($this->family_data->lineas_procesadas + 2*FS_ITEM_LIMIT))
                  $this->test_articulo( explode(';', $linea) );
               else if($i >= ($this->family_data->lineas_procesadas + 2*FS_ITEM_LIMIT))
                  break;
               $i++;
            }
            $this->family_data->lineas_procesadas = $i;
            $this->family_data->check();
            $this->save_family_data();
         }
         else if( $this->family_data->lineas_procesadas >= $this->family_data->lineas )
            $retorno = TRUE;
         fclose($file);
      }
      else
         $this->new_error_msg("¡Error al leer el archivo ".$this->familia->codfamilia.".csv!");
      return $retorno;
   }
   
   /// comprobamos la línea y el artículo
   private function test_articulo($tarifa)
   {
      if(count($tarifa) >= 4)
      {
         $articulo = $this->articulo->get( $tarifa[0] . $this->family_data->sufijo );
         if($articulo)
         {
            $this->family_data->articulos_actualizados += 1;
            
            // sustituimos las comas por puntos en el pvp
            $tarifa[1] = str_replace(',', '.', $tarifa[1]);
            $pvp = floatval($tarifa[1]);
            if($pvp > $articulo->pvp)
               $this->family_data->pvp_suben += 1;
            else if($pvp < $articulo->pvp)
               $this->family_data->pvp_bajan += 1;
            else
               $this->family_data->pvp_igual += 1;
            
            $diff = $pvp - $articulo->pvp;
            if($diff != 0 AND $articulo->pvp != 0)
            {
               $diff = $diff*100/$articulo->pvp;
               $this->family_data->pvp_sum_diferencias += $diff;
            }
         }
         else
            $this->family_data->articulos_nuevos += 1;
      }
   }
   
   /*
    * leemos el archivo csv y creamos o actualizamos los artículos,
    * devolvemos FALSE si queremos recargar la página y continuar
    */
   private function csv2articulos()
   {
      $retorno = FALSE;
      $file = fopen("tmp/familias/".$this->familia->codfamilia.".csv", 'r');
      if($file)
      {
         if( $this->family_data->lineas_procesadas < $this->family_data->lineas )
         {
            $i = 0;
            while(!feof($file) AND !$retorno)
            {
               $linea = trim( fgets($file, 1024) );
               if( $i == 0 )
               {
                  $cabecera = explode(';', $linea);
                  if($cabecera[0] != 'REF' OR $cabecera[1] != 'PVP' OR $cabecera[2] != 'DESC' OR $cabecera[3] != 'CODBAR')
                  {
                     $this->new_error_msg("¡Las columnas no concuerdan!");
                     $retorno = TRUE;
                  }
               }
               else if($i >= $this->family_data->lineas_procesadas AND $i < ($this->family_data->lineas_procesadas + 2*FS_ITEM_LIMIT))
               {
                  if( !$this->csvline2articulo( explode(';', $linea) ) )
                  {
                     $retorno = TRUE;
                     $this->new_error_msg("¡Error al procesar la línea ".($i+1)."!");
                     break;
                  }
               }
               else if($i >= ($this->family_data->lineas_procesadas + 2*FS_ITEM_LIMIT))
                  break;
               $i++;
            }
            $this->family_data->lineas_procesadas = $i;
            $this->family_data->check();
            $this->save_family_data();
         }
         else if( $this->family_data->lineas_procesadas >= $this->family_data->lineas )
            $retorno = TRUE;
         fclose($file);
      }
      else
         $this->new_error_msg("¡Error al leer el archivo ".$this->familia->codfamilia.".csv!");
      return $retorno;
   }
   
   /// crea/actualiza un artículos en base a la información dada, devuelve FALSE en caso de error
   private function csvline2articulo($tarifa)
   {
      $retorno = FALSE;
      
      if(count($tarifa) >= 4)
      {
         $articulo = $this->articulo->get( $tarifa[0] . $this->family_data->sufijo );
         if($articulo)
         {
            // sustituimos las comas por puntos en el pvp
            $tarifa[1] = str_replace(',', '.', $tarifa[1]);
            $articulo->set_pvp($tarifa[1]);
            $articulo->descripcion = $tarifa[2];
            $articulo->codbarras = $tarifa[3];
            if( $articulo->save() )
            {
               $retorno = TRUE;
               $this->family_data->articulos_actualizados += 1;
               $pvp = floatval($tarifa[1]);
               if($pvp > $articulo->pvp)
                  $this->family_data->pvp_suben += 1;
               else if($pvp < $articulo->pvp)
                  $this->family_data->pvp_bajan += 1;
               else
                  $this->family_data->pvp_igual += 1;
               $diff = $pvp - $articulo->pvp;
               if($diff != 0 AND $articulo->pvp != 0)
               {
                  $diff = $diff*100/$articulo->pvp;
                  $this->family_data->pvp_sum_diferencias += $diff;
               }
            }
         }
         else
         {
            $articulo = new articulo();
            $articulo->referencia = $tarifa[0] . $this->family_data->sufijo;
            // sustituimos las comas por puntos en el pvp
            $tarifa[1] = str_replace(',', '.', $tarifa[1]);
            $articulo->set_pvp($tarifa[1]);
            $articulo->descripcion = $tarifa[2];
            $articulo->codbarras = $tarifa[3];
            $articulo->codfamilia = $this->familia->codfamilia;
            $articulo->codimpuesto = $this->family_data->codimpuesto;
            if( $articulo->save() )
            {
               $retorno = TRUE;
               $this->family_data->articulos_nuevos += 1;
            }
         }
      }
      else
         $retorno = TRUE;
      
      return $retorno;
   }
}

?>
