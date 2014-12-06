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

require_model('almacen.php');
require_model('articulo.php');
require_model('familia.php');
require_model('impuesto.php');
require_model('stock.php');

class ventas_articulo extends fs_controller
{
   public $almacen;
   public $articulo;
   public $familia;
   public $impuesto;
   public $nuevos_almacenes;
   public $stocks;
   public $equivalentes;

   public function __construct()
   {
      parent::__construct(__CLASS__, 'Articulo', 'ventas', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->show_fs_toolbar = FALSE;
      $this->ppage = $this->page->get('ventas_articulos');
      $articulo = new articulo();
      
      if( isset($_POST['pvpiva']) )
      {
         $this->articulo = $articulo->get($_POST['referencia']);
         if($this->articulo)
         {
            $continuar = TRUE;
            $this->articulo->set_impuesto( $_POST['codimpuesto'] );
            $this->articulo->set_pvp_iva( $_POST['pvpiva'] );
            
            if( isset($_POST['preciocoste']) )
               $this->articulo->preciocoste = floatval($_POST['preciocoste']);
            
            if( !$this->articulo->save() )
            {
               $this->new_error_msg("¡Imposible modificar el artículo!");
               $continuar = FALSE;
            }
            if ($_POST['pvpiva']>0)  // No se guardan los precios si PVP es cero, porque se guarda 0% de descuento, independientemente de la tarifa
            {
                  $tarifa_articulo = new tarifa_articulo();
                  for($i = 0; $i < 100; $i++)
                  {
                     if( isset($_POST['codtarifa_'.$i]) )
                     {
                        if($_POST['id_'.$i] != '')
                           $ta = $tarifa_articulo->get($_POST['id_'.$i]);
                        else
                           $ta = FALSE;
                        if( !$ta )
                        {
                           $ta = new tarifa_articulo();
                           $ta->codtarifa = $_POST['codtarifa_'.$i];
                           $ta->referencia = $this->articulo->referencia;
                        }
                        $ta->pvp = $this->articulo->pvp;
                        $ta->iva = $this->articulo->get_iva();
                        $ta->set_pvp_iva($_POST['pvpiva_'.$i]);
                        if( !$ta->save() )
                        {
                           $this->new_error_msg("¡Imposible modificar la tarifa!");
                           $continuar = FALSE;
                        }
                     }
                     else
                        break;
                  }
            } else {
               $this->new_advice("Debe establecer el Precio de Venta");
            }
            if( $continuar )
               $this->new_message("Precios modificadas correctamente.");
         }
      }
      else if( isset($_POST['almacen']) )
      {
         $this->articulo = $articulo->get($_POST['referencia']);
         if($this->articulo)
         {
            if( $this->articulo->set_stock($_POST['almacen'], $_POST['cantidad']) )
               $this->new_message("Stock guardado correctamente.");
            else
               $this->new_error_msg("Error al guardar el stock.");
         }
      }
      else if( isset($_POST['imagen']) )
      {
         $this->articulo = $articulo->get($_POST['referencia']);
         if(is_uploaded_file($_FILES['fimagen']['tmp_name']) AND $_FILES['fimagen']['size'] <= 1024000)
         {
            $this->articulo->set_imagen( file_get_contents($_FILES['fimagen']['tmp_name']) );
            if( $this->articulo->save() )
               $this->new_message("Imagen del articulo modificada correctamente");
            else
               $this->new_error_msg("¡Error al guardar la imagen del articulo!");
         }
      }
      else if( isset($_GET['delete_img']) )
      {
         $this->articulo = $articulo->get($_GET['ref']);
         $this->articulo->set_imagen(NULL);
         if( $this->articulo->save() )
            $this->new_message("Imagen del articulo eliminada correctamente");
         else
            $this->new_error_msg("¡Error al eliminar la imagen del articulo!");
      }
      else if( isset($_POST['referencia']) )
      {
         $this->articulo = $articulo->get($_POST['referencia']);
         $this->articulo->descripcion = $_POST['descripcion'];
         $this->articulo->codfamilia = $_POST['codfamilia'];
         $this->articulo->codbarras = $_POST['codbarras'];
         $this->articulo->equivalencia = $_POST['equivalencia'];
         $this->articulo->destacado = isset($_POST['destacado']);
         $this->articulo->bloqueado = isset($_POST['bloqueado']);
         $this->articulo->controlstock = isset($_POST['controlstock']);
         $this->articulo->secompra = isset($_POST['secompra']);
         $this->articulo->sevende = isset($_POST['sevende']);
         $this->articulo->publico = isset($_POST['publico']);
         $this->articulo->observaciones = $_POST['observaciones'];
         $this->articulo->stockmin = $_POST['stockmin'];
         $this->articulo->stockmax = $_POST['stockmax'];
         if( $this->articulo->save() )
         {
            $this->new_message("Datos del articulo modificados correctamente");
            $this->articulo->set_referencia($_POST['nreferencia']);
         }
         else
            $this->new_error_msg("¡Error al guardar el articulo!");
      }
      else if( isset($_GET['ref']) )
      {
         $this->articulo = $articulo->get($_GET['ref']);
      }
      
      if($this->articulo)
      {
         $this->page->title = $this->articulo->referencia;
         
         if($this->articulo->bloqueado)
            $this->new_error_msg("Este artículo está bloqueado.");
         
         $this->almacen = new almacen();
         
         $this->familia = $this->articulo->get_familia();
         if(!$this->familia)
            $this->familia = new familia();
         
         $this->impuesto = new impuesto();
         $this->stocks = $this->articulo->get_stock();
         /// metemos en un array los almacenes que no tengan stock de este producto
         $this->nuevos_almacenes = array();
         foreach($this->almacen->all() as $a)
         {
            $encontrado = FALSE;
            foreach($this->stocks as $s)
            {
               if( $a->codalmacen == $s->codalmacen )
                  $encontrado = TRUE;
            }
            if( !$encontrado )
               $this->nuevos_almacenes[] = $a;
         }
         
         $this->equivalentes = $this->articulo->get_equivalentes();
      }
      else
         $this->new_error_msg("Artículo no encontrado.");
   }
   
   public function url()
   {
      if( !isset($this->articulo) )
      {
         return parent::url();
      }
      else if($this->articulo)
      {
         return $this->articulo->url();
      }
      else
         return $this->page->url();
   }
}
