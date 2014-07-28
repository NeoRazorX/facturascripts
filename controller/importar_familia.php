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
require_model('familia.php');
require_model('impuesto.php');

class family_data
{
   public $codfamilia;
   public $codimpuesto;
   public $iva;
   public $con_iva;
   public $sufijo;
   public $pvp_max;
   public $bloquear;
   public $no_nuevos;
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
   
   public function __construct($codfamilia)
   {
      $this->codfamilia = $codfamilia;
      $this->codimpuesto = NULL;
      $this->iva = 0;
      $this->con_iva = FALSE;
      $this->sufijo = '';
      $this->pvp_max = FALSE;
      $this->bloquear = FALSE;
      $this->no_nuevos = FALSE;
      $this->action = 'test';
      $this->num_articulos = 0;
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
   
   public function set_impuesto($cod)
   {
      $impuesto = new impuesto();
      $imp0 = $impuesto->get($cod);
      if( $imp0 )
      {
         $this->codimpuesto = $imp0->codimpuesto;
         $this->iva = $imp0->iva;
      }
      else
      {
         $this->codimpuesto = NULL;
         $this->iva = 0;
      }
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

class importar_familia extends fs_controller
{
   public $articulo;
   public $family_data;
   public $familia;
   public $ready;
   public $error;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'importar familia', 'ventas', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->ppage = $this->page->get('ventas_familias');
      
      $this->articulo = new articulo();
      $this->ready = TRUE;
      $this->error = FALSE;
      
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
      
      if( $this->cache->error() )
      {
         $this->new_error_msg( 'Memcache está deshabilitado y es necesario para continuar. '.
                 $this->cache->error_msg() );
      }
      else if( $this->familia )
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
               
               /// nos guardamos la configuración
               $this->family_data = new family_data($this->familia->codfamilia);
               $this->family_data->set_impuesto($_POST['impuesto']);
               $this->family_data->sufijo = $_POST['sufijo'];
               $this->family_data->num_articulos = $this->articulo->count($this->familia->codfamilia);
               
               if( isset($_POST['con_iva']) )
                  $this->family_data->con_iva = TRUE;
               
               if( isset($_POST['pvp_max']) )
                  $this->family_data->pvp_max = TRUE;
               
               if( isset($_POST['bloquear']) )
                  $this->family_data->bloquear = TRUE;
               
               if( isset($_POST['nnuevos']) )
                  $this->family_data->no_nuevos = TRUE;
               
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
               $this->test_csv_file();
               
               if($this->ready)
               {
                  if($this->error)
                  {
                     $this->new_error_msg('Comprobación fallida.');
                  }
                  else
                  {
                     $this->new_message("Comprobación finalizada. Pulsa el botón <b>procesar</b> para comenzar.");
                     $this->buttons[] = new fs_button_img('b_start', 'Procesar', 'play.png', $this->url().'&action=start');
                  }
               }
               else
               {
                  $this->new_message("Comprobando ... ".$this->family_data->lineas_procesadas.
                          "/".$this->family_data->lineas);
                  $this->buttons[] = new fs_button_img('b_pause_import', 'Pausar', 'pause.png');
               }
               
               $this->buttons[] = new fs_button_img('b_cancelar', 'Cancelar', 'remove.png', $this->url().'&cancelar=TRUE', TRUE);
            }
            else if($this->family_data->action == 'start')
            {
               $this->csv2articulos();
               
               if($this->ready)
               {
                  if($this->error)
                     $this->new_message("Proceso fallido.");
                  else
                     $this->new_message("Proceso finalizado.");
               }
               else
               {
                  $this->new_message("Procesando ... ".$this->family_data->lineas_procesadas.
                          "/".$this->family_data->lineas);
                  
                  $this->buttons[] = new fs_button_img('b_pause_import', 'Pausar', 'pause.png');
                  $this->buttons[] = new fs_button_img('b_cancelar', 'Cancelar', 'remove.png', $this->url().'&cancelar=TRUE', TRUE);
               }
            }
         }
         else
            $this->new_error_msg("¡No se ha encontrado el archivo ".$this->familia->codfamilia.".csv!
               Comprueba los permisos de la carpeta facturascripts/tmp/familias");
      }
      else
         $this->new_error_msg("¡Ninguna familia seleccionada!");
   }
   
   public function url()
   {
      if( !isset($this->familia) )
         return parent::url();
      else if($this->familia)
         return $this->page->url().'&fam='.$this->familia->codfamilia;
      else
         return $this->page->url();
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
      $this->ready = FALSE;
      $lineas_a_procesar = 300;
      
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
            while(!feof($file) AND !$this->ready)
            {
               $linea = trim( fgets($file, 1024) );
               if( $i == 0 )
               {
                  $cabecera = explode(';', $linea);
                  if($cabecera[0] != 'REF' OR $cabecera[1] != 'PVP' OR $cabecera[2] != 'DESC' OR $cabecera[3] != 'CODBAR')
                  {
                     $this->new_error_msg("¡Las columnas no concuerdan!");
                     $this->ready = TRUE;
                     $this->error = TRUE;
                  }
               }
               else if($i >= $this->family_data->lineas_procesadas AND $i < ($this->family_data->lineas_procesadas + $lineas_a_procesar))
               {
                  if( !$this->test_articulo( explode(';', $linea) ) )
                  {
                     $this->new_error_msg("¡Error al procesar la línea ".($i+1)."!");
                     $this->ready = TRUE;
                     $this->error = TRUE;
                     break;
                  }
               }
               else if($i >= ($this->family_data->lineas_procesadas + $lineas_a_procesar))
                  break;
               $i++;
            }
            $this->family_data->lineas_procesadas = $i;
            $this->family_data->check();
            $this->save_family_data();
         }
         else if( $this->family_data->lineas_procesadas >= $this->family_data->lineas )
            $this->ready = TRUE;
         fclose($file);
      }
      else
         $this->new_error_msg("¡Error al leer el archivo ".$this->familia->codfamilia.".csv!");
   }
   
   /// comprobamos la línea y el artículo, devuelve False en caso de fallo
   private function test_articulo($tarifa)
   {
      $retorno = TRUE;
      
      if(count($tarifa) >= 4)
      {
         // sustituimos las comas por puntos en el pvp
         $tarifa[1] = floatval( str_replace(',', '.', $tarifa[1]) );
         
         $articulo = $this->articulo->get( $tarifa[0] . $this->family_data->sufijo );
         if($articulo)
         {
            if(strlen($tarifa[2]) > 0)
               $articulo->descripcion = $tarifa[2];
            if(strlen($tarifa[3]) > 0)
               $articulo->codbarras = $tarifa[3];
            
            if( $this->family_data->pvp_max )
            {
               if( $this->family_data->con_iva )
                  $pvp = max( array($tarifa[1], $articulo->show_pvp_iva(FALSE)) );
               else
                  $pvp = max( array($tarifa[1], $articulo->pvp) );
            }
            else
               $pvp = $tarifa[1];
            
            if( $this->family_data->con_iva )
               $articulo->set_pvp_iva($pvp);
            else
               $articulo->set_pvp($pvp);
            
            if($articulo->codfamilia != $this->family_data->codfamilia)
            {
               $this->new_error_msg('El artículo '.$articulo->referencia.' pertenece a la
                  familia '.$articulo->codfamilia);
               $retorno = FALSE;
            }
            else if( $articulo->test() )
            {
               $this->family_data->articulos_actualizados += 1;
               
               $diff = $articulo->pvp - $articulo->pvp_ant;
               if( abs($diff) > .01 )
               {
                  if($articulo->pvp > $articulo->pvp_ant)
                     $this->family_data->pvp_suben += 1;
                  else
                     $this->family_data->pvp_bajan += 1;
                  
                  if($diff != 0 AND $articulo->pvp != 0)
                  {
                     $diff = $diff*100/$articulo->pvp;
                     $this->family_data->pvp_sum_diferencias += $diff;
                  }
               }
               else
                  $this->family_data->pvp_igual += 1;
            }
            else
            {
               $this->new_error_msg('Hay un error en el artículo '.$articulo->referencia);
               $retorno = FALSE;
            }
         }
         else if( !$this->family_data->no_nuevos )
         {
            $articulo = new articulo();
            $articulo->referencia = $tarifa[0] . $this->family_data->sufijo;
            $articulo->descripcion = $tarifa[2];
            $articulo->codbarras = $tarifa[3];
            $articulo->codfamilia = $this->familia->codfamilia;
            $articulo->set_impuesto($this->family_data->codimpuesto);
            
            if( $this->family_data->con_iva )
               $articulo->set_pvp_iva($tarifa[1]);
            else
               $articulo->set_pvp($tarifa[1]);
            
            if( $articulo->test() )
               $this->family_data->articulos_nuevos += 1;
            else
            {
               $retorno = FALSE;
               $this->new_error_msg('Hay un error en el artículo '.$articulo->referencia);
            }
         }
      }
      
      return $retorno;
   }
   
   /*
    * leemos el archivo csv y creamos o actualizamos los artículos,
    * devolvemos FALSE si queremos recargar la página y continuar
    */
   private function csv2articulos()
   {
      $this->ready = FALSE;
      $lineas_a_procesar = 100;
      
      $file = fopen("tmp/familias/".$this->familia->codfamilia.".csv", 'r');
      if($file)
      {
         if( $this->family_data->lineas_procesadas < $this->family_data->lineas )
         {
            $i = 0;
            while(!feof($file) AND !$this->ready)
            {
               $linea = trim( fgets($file, 1024) );
               if($i > 0 AND $i >= $this->family_data->lineas_procesadas AND $i < ($this->family_data->lineas_procesadas + $lineas_a_procesar))
               {
                  if( !$this->csvline2articulo( explode(';', $linea) ) )
                  {
                     $this->new_error_msg("¡Error al procesar la línea ".($i+1)."!");
                     $this->ready = TRUE;
                     $this->error = TRUE;
                     break;
                  }
               }
               else if($i >= ($this->family_data->lineas_procesadas + $lineas_a_procesar))
                  break;
               $i++;
            }
            $this->family_data->lineas_procesadas = $i;
            $this->family_data->check();
            $this->save_family_data();
         }
         else if( $this->family_data->lineas_procesadas >= $this->family_data->lineas )
         {
            /// ¿bloqueamos los articulos no actualizados?
            if( $this->family_data->bloquear )
            {
               $this->db->exec("UPDATE articulos SET bloqueado = true
                  WHERE codfamilia = ".$this->articulo->var2str( $this->family_data->codfamilia ).
                  " AND (factualizado < ".$this->articulo->var2str( $this->articulo->factualizado )."
                     OR factualizado IS NULL);");
            }
            
            $this->ready = TRUE;
         }
         
         fclose($file);
      }
      else
         $this->new_error_msg("¡Error al leer el archivo ".$this->familia->codfamilia.".csv!");
   }
   
   /// crea/actualiza un artículos en base a la información dada, devuelve FALSE en caso de error
   private function csvline2articulo($tarifa)
   {
      $retorno = FALSE;
      
      if(count($tarifa) >= 4)
      {
         // sustituimos las comas por puntos en el pvp
         $tarifa[1] = floatval( str_replace(',', '.', $tarifa[1]) );
         
         $articulo = $this->articulo->get( $tarifa[0] . $this->family_data->sufijo );
         if($articulo)
         {
            if(strlen($tarifa[2]) > 0)
               $articulo->descripcion = $tarifa[2];
            if(strlen($tarifa[3]) > 0)
               $articulo->codbarras = $tarifa[3];
            
            if( $this->family_data->pvp_max )
            {
               if( $this->family_data->con_iva )
                  $pvp = max( array($tarifa[1], $articulo->show_pvp_iva(FALSE)) );
               else
                  $pvp = max( array($tarifa[1], $articulo->pvp) );
            }
            else
               $pvp = $tarifa[1];
            
            if( $this->family_data->con_iva )
               $articulo->set_pvp_iva($pvp);
            else
               $articulo->set_pvp($pvp);
            
            if( $articulo->save() )
            {
               if( count($tarifa) >= 5 )
               {
                  /// ¿Actualizamos el stock?
                  if(strlen($tarifa[4]) > 0)
                     $articulo->set_stock($this->empresa->codalmacen, floatval($tarifa[4]));
               }
               
               $retorno = TRUE;
               $this->family_data->articulos_actualizados += 1;
               
               $diff = $articulo->pvp - $articulo->pvp_ant;
               if( abs($diff) > .01 )
               {
                  if($articulo->pvp > $articulo->pvp_ant)
                     $this->family_data->pvp_suben += 1;
                  else
                     $this->family_data->pvp_bajan += 1;
                  
                  if($diff != 0 AND $articulo->pvp != 0)
                  {
                     $diff = $diff*100/$articulo->pvp;
                     $this->family_data->pvp_sum_diferencias += $diff;
                  }
               }
               else
                  $this->family_data->pvp_igual += 1;
            }
            else
               $this->new_error_msg('Hay un error en el artículo '.$articulo->referencia);
         }
         else if( $this->family_data->no_nuevos )
         {
            $retorno = TRUE;
         }
         else
         {
            $articulo = new articulo();
            $articulo->referencia = $tarifa[0] . $this->family_data->sufijo;
            $articulo->descripcion = $tarifa[2];
            $articulo->codbarras = $tarifa[3];
            $articulo->codfamilia = $this->familia->codfamilia;
            $articulo->set_impuesto($this->family_data->codimpuesto);
            
            if( $this->family_data->con_iva )
               $articulo->set_pvp_iva($tarifa[1]);
            else
               $articulo->set_pvp($tarifa[1]);
            
            if( $articulo->save() )
            {
               if( count($tarifa) >= 5 )
               {
                  /// ¿Actualizamos el stock?
                  if(strlen($tarifa[4]) > 0)
                     $articulo->set_stock($this->empresa->codalmacen, floatval($tarifa[4]));
               }
               
               $retorno = TRUE;
               $this->family_data->articulos_nuevos += 1;
            }
            else
               $this->new_error_msg('Hay un error en el artículo '.$articulo->referencia);
         }
      }
      else
         $retorno = TRUE;
      
      return $retorno;
   }
}
