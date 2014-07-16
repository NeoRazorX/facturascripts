<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2014  Carlos Garcia Gomez  neorazorx@gmail.com
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

require_model('pedido_proveedor.php');
require_model('articulo.php');
require_model('ejercicio.php');
require_model('albaran_proveedor.php');
require_model('familia.php');
require_model('impuesto.php');
require_model('proveedor.php');
require_model('regularizacion_iva.php');
require_model('serie.php');

class general_pedido_prov extends fs_controller
{
   public $agente;
   public $pedido;
   public $ejercicio;
   public $familia;
   public $impuesto;
   public $nuevo_pedido_url;
   public $proveedor;
   public $serie;
   
   public function __construct()
   {
      parent::__construct('general_pedido_prov', 'Pedido de proveedor', 'compras', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->ppage = $this->page->get('general_pedidos_prov');
      $this->ejercicio = new ejercicio();
      $this->familia = new familia();
      $this->impuesto = new impuesto();
      $this->proveedor = new proveedor();
      $this->serie = new serie();
      
      /// comprobamos si el usuario tiene acceso a general_nuevo_pedido
      $this->nuevo_pedido_url = FALSE;
      if( $this->user->have_access_to('general_nuevo_pedido', FALSE) )
      {
         $nuevoprep = $this->page->get('general_nuevo_pedido');
         if($nuevoprep)
            $this->nuevo_pedido_url = $nuevoprep->url();
      }
      
      if( isset($_POST['idpedido']) )
      {
         $this->pedido = new pedido_proveedor();
         $this->pedido = $this->pedido->get($_POST['idpedido']);
         $this->modificar();
      }
      else if( isset($_GET['id']) )
      {
         $this->pedido = new pedido_proveedor();
         $this->pedido = $this->pedido->get($_GET['id']);
      }
      
      if($this->pedido)
      {
         $this->page->title = $this->pedido->codigo;
         $this->agente = $this->pedido->get_agente();
         
         /// comprobamos el pedido
         $this->pedido->full_test();
         
         if( isset($_GET['albaranr']) AND isset($_GET['petid']) AND $this->pedido->ptealbaran )
         {
            if( $this->duplicated_petition($_GET['petid']) )
               $this->new_error_msg('Petición duplicada. Evita hacer doble clic sobre los botones.');
            else
               $this->generar_albaran();
         }
         
         if( isset($_POST['actualizar_precios']) )
            $this->actualizar_precios();
         
         $this->buttons[] = new fs_button('b_copiar', 'Copiar', 'index.php?page=general_copy_pedido&idprepro='.$this->pedido->idpedido, TRUE);
         
         if( $this->pedido->ptealbaran )
         {
            $this->buttons[] = new fs_button('b_albaranr', 'Generar '.FS_ALBARAN, $this->url()."&albaranr=TRUE&petid=".$this->random_string());
         }
         else if( isset($this->pedido->idalbaran) )
         {
            $this->buttons[] = new fs_button('b_ver_albaran', FS_ALBARAN, $this->pedido->albaran_url());
         }
         
         $this->buttons[] = new fs_button('b_precios', 'Frecios');
         $this->buttons[] = new fs_button_img('b_eliminar', 'Eliminar', 'trash.png', '#', TRUE);
      }
      else
         $this->new_error_msg("¡Pedido de proveedor no encontrado!");
   }
   
   public function url()
   {
      if( !isset($this->pedido) )
         return parent::url();
      else if($this->pedido)
         return $this->pedido->url();
      else
         return $this->page->url();
   }
   
   private function modificar()
   {
      $this->pedido->numproveedor = $_POST['numproveedor'];
      $this->pedido->observaciones = $_POST['observaciones'];
      
      if( $this->pedido->ptealbaran )
      {
         /// obtenemos los datos del ejercicio para acotar la fecha
         $eje0 = $this->ejercicio->get( $this->pedido->codejercicio );
         if($eje0)
            $this->pedido->fecha = $eje0->get_best_fecha($_POST['fecha'], TRUE);
         else
            $this->new_error_msg('No se encuentra el ejercicio asociado al pedido.');
         
         /// ¿Cambiamos el proveedor?
         if($_POST['proveedor'] != $this->pedido->codproveedor)
         {
            $proveedor = $this->proveedor->get($_POST['proveedor']);
            if($proveedor)
            {
               $this->pedido->codproveedor = $proveedor->codproveedor;
               $this->pedido->nombre = $proveedor->nombrecomercial;
               $this->pedido->cifnif = $proveedor->cifnif;
            }
         }
         
         $serie = $this->serie->get($this->pedido->codserie);
         
         /// ¿cambiamos la serie?
         if($_POST['serie'] != $this->pedido->codserie)
         {
            $this->pedido->codserie = $_POST['serie'];
            $this->pedido->new_codigo();
         }
         
         if( isset($_POST['lineas']) )
         {
            $this->pedido->neto = 0;
            $this->pedido->totaliva = 0;
            $lineas = $this->pedido->get_lineas();
            $articulo = new articulo();
            
            /// eliminamos las líneas que no encontremos en el $_POST
            foreach($lineas as $l)
            {
               $encontrada = FALSE;
               for($num = 0; $num <= 200; $num++)
               {
                  if( isset($_POST['idlinea_'.$num]) )
                  {
                     if($l->idlinea == intval($_POST['idlinea_'.$num]))
                     {
                        $encontrada = TRUE;
                        break;
                     }
                  }
               }
               if( !$encontrada )
               {
                  if( $l->delete() )
                  {
                     /// actualizamos el stock
                     $art0 = $articulo->get($l->referencia);
                     if($art0)
                        $art0->sum_stock($this->pedido->codalmacen, 0 - $l->cantidad);
                  }
                  else
                     $this->new_error_msg("¡Imposible eliminar la línea del artículo ".$l->referencia."!");
               }
            }
            
            /// modificamos y/o añadimos las demás líneas
            for($num = 0; $num <= 200; $num++)
            {
               $encontrada = FALSE;
               if( isset($_POST['idlinea_'.$num]) )
               {
                  foreach($lineas as $k => $value)
                  {
                     /// modificamos la línea
                     if($value->idlinea == intval($_POST['idlinea_'.$num]))
                     {
                        $encontrada = TRUE;
                        $cantidad_old = $value->cantidad;
                        $lineas[$k]->cantidad = floatval($_POST['cantidad_'.$num]);
                        $lineas[$k]->pvpunitario = floatval($_POST['pvp_'.$num]);
                        $lineas[$k]->dtopor = floatval($_POST['dto_'.$num]);
                        $lineas[$k]->dtolineal = 0;
                        $lineas[$k]->pvpsindto = ($value->cantidad * $value->pvpunitario);
                        $lineas[$k]->pvptotal = ($value->cantidad * $value->pvpunitario * (100 - $value->dtopor)/100);
                        
                        if( isset($_POST['desc_'.$num]) )
                           $lineas[$k]->descripcion = $_POST['desc_'.$num];
                        
                        if( $serie->siniva )
                        {
                           $lineas[$k]->codimpuesto = NULL;
                           $lineas[$k]->iva = 0;
                        }
                        else
                        {
                           $imp0 = $this->impuesto->get_by_iva($_POST['iva_'.$num]);
                           if($imp0)
                           {
                              $lineas[$k]->codimpuesto = $imp0->codimpuesto;
                              $lineas[$k]->iva = $imp0->iva;
                           }
                           else
                           {
                              $lineas[$k]->codimpuesto = NULL;
                              $lineas[$k]->iva = floatval($_POST['iva_'.$num]);
                           }
                        }
                        
                        if( $lineas[$k]->save() )
                        {
                           $this->pedido->neto += ($value->cantidad*$value->pvpunitario*(100-$value->dtopor)/100);
                           $this->pedido->totaliva += ($value->cantidad*$value->pvpunitario*(100-$value->dtopor)/100*$value->iva/100);
                           
                           /// actualizamos el stock
                           $art0 = $articulo->get($value->referencia);
                           if($art0)
                              $art0->sum_stock($this->pedido->codalmacen, $lineas[$k]->cantidad - $cantidad_old);
                        }
                        else
                           $this->new_error_msg("¡Imposible modificar la línea del artículo ".$value->referencia."!");
                        break;
                     }
                  }
                  
                  /// añadimos la línea
                  if(!$encontrada AND intval($_POST['idlinea_'.$num]) == -1 AND isset($_POST['referencia_'.$num]))
                  {
                     $art0 = $articulo->get( $_POST['referencia_'.$num] );
                     if($art0)
                     {
                        $linea = new linea_pedido_proveedor();
                        $linea->referencia = $art0->referencia;
                        
                        if( isset($_POST['desc_'.$num]) )
                           $linea->descripcion = $_POST['desc_'.$num];
                        else
                           $linea->descripcion = $art0->descripcion;
                        
                        if( $serie->siniva )
                        {
                           $linea->codimpuesto = NULL;
                           $linea->iva = 0;
                        }
                        else
                        {
                           $imp0 = $this->impuesto->get_by_iva($_POST['iva_'.$num]);
                           if($imp0)
                           {
                              $linea->codimpuesto = $imp0->codimpuesto;
                              $linea->iva = $imp0->iva;
                           }
                           else
                           {
                              $linea->codimpuesto = NULL;
                              $linea->iva = floatval($_POST['iva_'.$num]);
                           }
                        }
                        
                        $linea->idpedido = $this->pedido->idpedido;
                        $linea->cantidad = floatval($_POST['cantidad_'.$num]);
                        $linea->pvpunitario = floatval($_POST['pvp_'.$num]);
                        $linea->dtopor = floatval($_POST['dto_'.$num]);
                        $linea->pvpsindto = ($linea->cantidad * $linea->pvpunitario);
                        $linea->pvptotal = ($linea->cantidad * $linea->pvpunitario * (100 - $linea->dtopor)/100);
                        
                        if( $linea->save() )
                        {
                           $this->pedido->neto += ($linea->cantidad*$linea->pvpunitario*(100-$linea->dtopor)/100);
                           $this->pedido->totaliva += ($linea->cantidad*$linea->pvpunitario*(100-$linea->dtopor)/100*$linea->iva/100);
                           
                           /// actualizamos el stock
                           $art0->sum_stock($this->pedido->codalmacen, $linea->cantidad);
                        }
                        else
                           $this->new_error_msg("¡Imposible guardar la línea del artículo ".$linea->referencia."!");
                     }
                     else
                        $this->new_error_msg("¡Artículo ".$_POST['referencia_'.$num]." no encontrado!");
                  }
               }
            }
            
            /// redondeamos
            $this->pedido->neto = round($this->pedido->neto, 2);
            $this->pedido->totaliva = round($this->pedido->totaliva, 2);
            $this->pedido->total = $this->pedido->neto + $this->pedido->totaliva;
         }
      }
      
      if( $this->pedido->save() )
      {
         $this->new_message("Pedido modificado correctamente.");
         $this->new_change('Pedido Proveedor '.$this->pedido->codigo, $this->pedido->url());
      }
      else
         $this->new_error_msg("¡Imposible modificar el pedido!");
   }
   
   private function generar_albaran()
   {
      $albaran = new albaran_proveedor();
      $albaran->automatica = TRUE;
      $albaran->editable = FALSE;
      $albaran->cifnif = $this->pedido->cifnif;
      $albaran->codalmacen = $this->pedido->codalmacen;
      $albaran->coddivisa = $this->pedido->coddivisa;
      $albaran->tasaconv = $this->pedido->tasaconv;
      $albaran->codejercicio = $this->pedido->codejercicio;
      $albaran->codpago = $this->pedido->codpago;
      $albaran->codproveedor = $this->pedido->codproveedor;
      $albaran->codserie = $this->pedido->codserie;
      $albaran->irpf = $this->pedido->irpf;
      $albaran->neto = $this->pedido->neto;
      $albaran->nombre = $this->pedido->nombre;
      $albaran->numproveedor = $this->pedido->numproveedor;
      $albaran->observaciones = $this->pedido->observaciones;
      $albaran->recfinanciero = $this->pedido->recfinanciero;
      $albaran->total = $this->pedido->total;
      $albaran->totalirpf = $this->pedido->totalirpf;
      $albaran->totaliva = $this->pedido->totaliva;
      $albaran->totalrecargo = $this->pedido->totalrecargo;
      
      /// asignamos la mejor fecha posible, pero dentro del ejercicio
      $eje0 = $this->ejercicio->get($albaran->codejercicio);
      $albaran->fecha = $eje0->get_best_fecha($albaran->fecha);
      
      $regularizacion = new regularizacion_iva();
      
      if( !$eje0->abierto() )
      {
         $this->new_error_msg("El ejercicio está cerrado.");
      }
      else if( $regularizacion->get_fecha_inside($albaran->fecha) )
      {
         $this->new_error_msg("El IVA de ese periodo ya ha sido regularizado.
            No se pueden añadir más ".FS_ALBARANES." en esa fecha.");
      }
      else if( $albaran->save() )
      {
         $continuar = TRUE;
         foreach($this->pedido->get_lineas() as $l)
         {
            $linea = new linea_albaran_proveedor();
            $linea->cantidad = $l->cantidad;
            $linea->codimpuesto = $l->codimpuesto;
            $linea->descripcion = $l->descripcion;
            $linea->dtolineal = $l->dtolineal;
            $linea->dtopor = $l->dtopor;
            $linea->idpedido = $l->idpedido;
            $linea->idalbaran = $albaran->idalbaran;
            $linea->irpf = $l->irpf;
            $linea->iva = $l->iva;
            $linea->pvpsindto = $l->pvpsindto;
            $linea->pvptotal = $l->pvptotal;
            $linea->pvpunitario = $l->pvpunitario;
            $linea->recargo = $l->recargo;
            $linea->referencia = $l->referencia;
            if( !$linea->save() )
            {
               $continuar = FALSE;
               $this->new_error_msg("¡Imposible guardar la línea del artículo ".$linea->referencia."! ");
               break;
            }
         }
         
         if( $continuar )
         {
            $this->pedido->idalbaran = $albaran->idalbaran;
            $this->pedido->ptealbaran = FALSE;
            if( !$this->pedido->save() )
            {
               $this->new_error_msg("¡Imposible vincular el pedido con el nueva albarán!");
               if( $albaran->delete() )
                  $this->new_error_msg("El albarán se ha borrado.");
               else
                  $this->new_error_msg("¡Imposible borrar el albarán!");
            }
         }
         else
         {
            if( $albaran->delete() )
               $this->new_error_msg("El albarán se ha borrado.");
            else
               $this->new_error_msg("¡Imposible borrar el albarán!");
         }
      }
      else
         $this->new_error_msg("¡Imposible guardar el albarán!");
   }
   
   private function actualizar_precios()
   {
      $articulo = new articulo();
      $lineas = $this->pedido->get_lineas();
      
      foreach($lineas as $linea)
      {
         for($i = 0; $i < count($lineas); $i++)
         {
            if( !isset($_POST['referencia_'.$i]) )
            {
               
            }
            else if($_POST['referencia_'.$i] == $linea->referencia)
            {
               $art0 = $articulo->get($_POST['referencia_'.$i]);
               if($art0)
               {
                  if( isset($_POST['update_all']) )
                  {
                     $art0->descripcion = $linea->descripcion;
                     $art0->codbarras = $_POST['codbar_'.$i];
                  }
                  
                  $art0->set_impuesto($linea->codimpuesto);
                  $art0->set_pvp_iva($_POST['pvpiva_'.$i]);
                  if( !$art0->save() )
                     $this->new_error_msg('Imposible actualizar el artículo '.$art0->referencia);
               }
               
               break;
            }
         }
      }
      
      $this->new_message('Precios actualizados.');
   }
}
