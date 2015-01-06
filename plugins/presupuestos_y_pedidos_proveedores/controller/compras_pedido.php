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

require_model('articulo.php');
require_model('proveedor.php');
require_model('ejercicio.php');
require_model('albaran_proveedor.php');
require_model('familia.php');
require_model('impuesto.php');
require_model('linea_pedido_proveedor.php');
require_model('pedido_proveedor.php');
require_model('regularizacion_iva.php');
require_model('serie.php');

class compras_pedido extends fs_controller
{
   public $agente;
   public $proveedor;
   public $proveedor_s;
   public $ejercicio;
   public $familia;
   public $impuesto;
   public $nuevo_pedido_url;
   public $pedido;
   public $serie;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, ucfirst(FS_PEDIDO), 'compras', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->ppage = $this->page->get('compras_pedidos');
      $this->agente = FALSE;
      
      /// desactivamos la barra de botones
      $this->show_fs_toolbar = FALSE;
      
      $pedido = new pedido_proveedor();
      $this->pedido = FALSE;
      $this->proveedor = new proveedor();
      $this->proveedor_s = FALSE;
      $this->ejercicio = new ejercicio();
      $this->familia = new familia();
      $this->impuesto = new impuesto();
      $this->nuevo_pedido_url = FALSE;
      $this->serie = new serie();
      
      /**
       * Comprobamos si el usuario tiene acceso a nueva_compra,
       * necesario para poder añadir líneas.
       */
      if( $this->user->have_access_to('nueva_compra', FALSE) )
      {
         $nuevopedp = $this->page->get('nueva_compra');
         if($nuevopedp)
            $this->nuevo_pedido_url = $nuevopedp->url();
      }
      
      if( isset($_POST['idpedido']) )
      {
         $this->pedido = $pedido->get($_POST['idpedido']);
         $this->modificar();
      }
      else if( isset($_GET['id']) )
      {
         $this->pedido = $pedido->get($_GET['id']);
      }
      
      if( $this->pedido )
      {
         $this->page->title = $this->pedido->codigo;
         
         /// cargamos el agente
         if( !is_null($this->pedido->codagente) )
         {
            $agente = new agente();
            $this->agente = $agente->get($this->pedido->codagente);
         }
         
         /// cargamos el proveedor
         $this->proveedor_s = $this->proveedor->get($this->pedido->codproveedor);
         
         /// comprobamos el pedido
         if( $this->pedido->full_test() )
         {
            if( isset($_GET['albaranar']) AND isset($_GET['petid']) AND is_null($this->pedido->idalbaran) )
            {
               if( $this->duplicated_petition($_GET['petid']) )
               {
                  $this->new_error_msg('Petición duplicada. Evita hacer doble clic sobre los botones.');
               }
               else
                  $this->generar_albaran();
            }
         }
      }
      else
         $this->new_error_msg("¡".ucfirst(FS_PEDIDO)." de proveedor no encontrado!");
   }
   
   public function url()
   {
      if( !isset($this->pedido) )
      {
         return parent::url();
      }
      else if($this->pedido)
      {
         return $this->pedido->url();
      }
      else
         return $this->page->url();
   }
   
   private function modificar()
   {
      $this->pedido->observaciones = $_POST['observaciones'];
      $this->pedido->numero2 = $_POST['numero2'];
      
      if( is_null($this->pedido->idalbaran) )
      {
         /// obtenemos los datos del ejercicio para acotar la fecha
         $eje0 = $this->ejercicio->get( $this->pedido->codejercicio );
         if($eje0)
         {
            $this->pedido->fecha = $eje0->get_best_fecha($_POST['fecha'], TRUE);
            $this->pedido->hora = $_POST['hora'];
         }
         else
            $this->new_error_msg('No se encuentra el ejercicio asociado al '.FS_PEDIDO);
         
         /// ¿cambiamos el proveedor?
         if($_POST['proveedor'] != $this->pedido->codproveedor)
         {
            $proveedor = $this->proveedor->get($_POST['proveedor']);
            if($proveedor)
            {
               foreach($proveedor->get_direcciones() as $d)
               {
                  if($d->domfacturacion)
                  {
                     $this->pedido->codproveedor = $proveedor->codproveedor;
                     $this->pedido->cifnif = $proveedor->cifnif;
                     $this->pedido->proveedor = $proveedor->nombrecomercial;
                     $this->pedido->apartado = $d->apartado;
                     $this->pedido->ciudad = $d->ciudad;
                     $this->pedido->coddir = $d->id;
                     $this->pedido->codpais = $d->codpais;
                     $this->pedido->codpostal = $d->codpostal;
                     $this->pedido->direccion = $d->direccion;
                     $this->pedido->provincia = $d->provincia;
                     break;
                  }
               }
            }
            
            else
               die('No se ha encontrado el proveedor.');
         }
         else
            $proveedor = $this->proveedor->get($this->pedido->codproveedor);
         
         $serie = $this->serie->get($this->pedido->codserie);
         
         /// ¿cambiamos la serie?
         if($_POST['serie'] != $this->pedido->codserie)
         {
            $serie2 = $this->serie->get($_POST['serie']);
            if($serie2)
            {
               $this->pedido->codserie = $serie2->codserie;
               $this->pedido->irpf = $serie2->irpf;
               $this->pedido->new_codigo();
               
               $serie = $serie2;
            }
         }
         
         if( isset($_POST['numlineas']) )
         {
            $numlineas = intval($_POST['numlineas']);
            
            $this->pedido->neto = 0;
            $this->pedido->totaliva = 0;
            $this->pedido->totalirpf = 0;
            $this->pedido->totalrecargo = 0;
            $lineas = $this->pedido->get_lineas();
            $articulo = new articulo();
            
            /// eliminamos las líneas que no encontremos en el $_POST
            foreach($lineas as $l)
            {
               $encontrada = FALSE;
               for($num = 0; $num <= $numlineas; $num++)
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
                  if( !$l->delete() )
                     $this->new_error_msg("¡Imposible eliminar la línea del artículo ".$l->referencia."!");
               }
            }
            
            /// modificamos y/o añadimos las demás líneas
            for($num = 0; $num <= $numlineas; $num++)
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
                        $lineas[$k]->cantidad = floatval($_POST['cantidad_'.$num]);
                        $lineas[$k]->pvpunitario = floatval($_POST['pvp_'.$num]);
                        $lineas[$k]->dtopor = floatval($_POST['dto_'.$num]);
                        $lineas[$k]->dtolineal = 0;
                        $lineas[$k]->pvpsindto = ($value->cantidad * $value->pvpunitario);
                        $lineas[$k]->pvptotal = ($value->cantidad * $value->pvpunitario * (100 - $value->dtopor)/100);
                        $lineas[$k]->descripcion = $_POST['desc_'.$num];
                        
                        $lineas[$k]->codimpuesto = NULL;
                        $lineas[$k]->iva = 0;
                        $lineas[$k]->recargo = 0;
                        $lineas[$k]->irpf = $this->pedido->irpf;
                        if( !$serie->siniva AND $proveedor->regimeniva != 'Exento' )
                        {
                           $imp0 = $this->impuesto->get_by_iva($_POST['iva_'.$num]);
                           if($imp0)
                              $lineas[$k]->codimpuesto = $imp0->codimpuesto;
                           
                           $lineas[$k]->iva = floatval($_POST['iva_'.$num]);
                           $lineas[$k]->recargo = floatval($_POST['recargo_'.$num]);
                        }
                        
                        if( $lineas[$k]->save() )
                        {
                           $this->pedido->neto += $value->pvptotal;
                           $this->pedido->totaliva += $value->pvptotal * $value->iva/100;
                           $this->pedido->totalirpf += $value->pvptotal * $value->irpf/100;
                           $this->pedido->totalrecargo += $value->pvptotal * $value->recargo/100;
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
                        $linea->descripcion = $_POST['desc_'.$num];
                        $linea->irpf = $this->pedido->irpf;
                        
                        if( !$serie->siniva AND $proveedor->regimeniva != 'Exento' )
                        {
                           $imp0 = $this->impuesto->get_by_iva($_POST['iva_'.$num]);
                           if($imp0)
                              $linea->codimpuesto = $imp0->codimpuesto;
                           
                           $linea->iva = floatval($_POST['iva_'.$num]);
                           $linea->recargo = floatval($_POST['recargo_'.$num]);
                        }
                        
                        $linea->idpedido = $this->pedido->idpedido;
                        $linea->cantidad = floatval($_POST['cantidad_'.$num]);
                        $linea->pvpunitario = floatval($_POST['pvp_'.$num]);
                        $linea->dtopor = floatval($_POST['dto_'.$num]);
                        $linea->pvpsindto = ($linea->cantidad * $linea->pvpunitario);
                        $linea->pvptotal = ($linea->cantidad * $linea->pvpunitario * (100 - $linea->dtopor)/100);
                        
                        if( $linea->save() )
                        {
                           $this->pedido->neto += $linea->pvptotal;
                           $this->pedido->totaliva += $linea->pvptotal * $linea->iva/100;
                           $this->pedido->totalirpf += $linea->pvptotal * $linea->irpf/100;
                           $this->pedido->totalrecargo += $linea->pvptotal * $linea->recargo/100;
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
            $this->pedido->neto = round($this->pedido->neto, FS_NF0);
            $this->pedido->totaliva = round($this->pedido->totaliva, FS_NF0);
            $this->pedido->totalirpf = round($this->pedido->totalirpf, FS_NF0);
            $this->pedido->totalrecargo = round($this->pedido->totalrecargo, FS_NF0);
            $this->pedido->total = $this->pedido->neto + $this->pedido->totaliva - $this->pedido->totalirpf + $this->pedido->totalrecargo;
            
            if( abs(floatval($_POST['atotal']) - $this->pedido->total) > .01 )
            {
               $this->new_error_msg("El total difiere entre el controlador y la vista (".$this->pedido->total.
                       " frente a ".$_POST['atotal']."). Debes informar del error.");
            }
         }
      }
      
      if( $this->pedido->save() )
      {
         $this->new_message(ucfirst(FS_PEDIDO)." modificado correctamente.");
         $this->new_change(ucfirst(FS_PEDIDO).' proveedor '.$this->pedido->codigo, $this->pedido->url());
      }
      else
         $this->new_error_msg("¡Imposible modificar el ".FS_PEDIDO."!");
   }
   
   private function generar_albaran()
   {
      $albaran = new albaran_proveedor();
      $albaran->apartado = $this->pedido->apartado;
      $albaran->automatica = TRUE;
      $albaran->cifnif = $this->pedido->cifnif;
      $albaran->ciudad = $this->pedido->ciudad;
      $albaran->codagente = $this->pedido->codagente;
      $albaran->codalmacen = $this->pedido->codalmacen;
      $albaran->codproveedor = $this->pedido->codproveedor;
      $albaran->coddir = $this->pedido->coddir;
      $albaran->coddivisa = $this->pedido->coddivisa;
      $albaran->tasaconv = $this->pedido->tasaconv;
      $albaran->codpago = $this->pedido->codpago;
      $albaran->codpais = $this->pedido->codpais;
      $albaran->codpostal = $this->pedido->codpostal;
      $albaran->codserie = $this->pedido->codserie;
      $albaran->direccion = $this->pedido->direccion;
      $albaran->editable = TRUE;
      $albaran->neto = $this->pedido->neto;
      $albaran->proveedor = $this->pedido->proveedor;
      $albaran->observaciones = $this->pedido->observaciones;
      $albaran->provincia = $this->pedido->provincia;
      $albaran->total = $this->pedido->total;
      $albaran->totaliva = $this->pedido->totaliva;
      $albaran->numero2 = $this->pedido->numero2;
      $albaran->irpf = $this->pedido->irpf;
      $albaran->porcomision = $this->pedido->porcomision;
      $albaran->recfinanciero = $this->pedido->recfinanciero;
      $albaran->totalirpf = $this->pedido->totalirpf;
      $albaran->totalrecargo = $this->pedido->totalrecargo;
      
      /**
       * Obtenemos el ejercicio para la fecha de hoy (puede que
       * no sea el mismo ejercicio que el del pedido, por ejemplo
       * si hemos cambiado de año)
       */
      $eje0 = $this->ejercicio->get_by_fecha($albaran->fecha);
      $albaran->codejercicio = $eje0->codejercicio;
      
      $regularizacion = new regularizacion_iva();
      
      if( !$eje0->abierto() )
      {
         $this->new_error_msg("El ejercicio está cerrado.");
      }
      else if( $regularizacion->get_fecha_inside($albaran->fecha) )
      {
         $this->new_error_msg("El IVA de ese periodo ya ha sido regularizado. No se pueden añadir más ".FS_ALBARANES." en esa fecha.");
      }
      else if( $albaran->save() )
      {
         $continuar = TRUE;
         $art0 = new articulo();
         
         foreach($this->pedido->get_lineas() as $l)
         {
            $n = new linea_albaran_proveedor();
            $n->idpedido = $l->idpedido;
            $n->idalbaran = $albaran->idalbaran;
            $n->cantidad = $l->cantidad;
            $n->codimpuesto = $l->codimpuesto;
            $n->descripcion = $l->descripcion;
            $n->dtolineal = $l->dtolineal;
            $n->dtopor = $l->dtopor;
            $n->irpf = $l->irpf;
            $n->iva = $l->iva;
            $n->pvpsindto = $l->pvpsindto;
            $n->pvptotal = $l->pvptotal;
            $n->pvpunitario = $l->pvpunitario;
            $n->recargo = $l->recargo;
            $n->referencia = $l->referencia;
            
            if( $n->save() )
            {
               /// descontamos del stock
               if( !is_null($n->referencia) )
               {
                  $articulo = $art0->get($n->referencia);
                  $articulo->sum_stock($albaran->codalmacen, 0 - $l->cantidad);
               }
            }
            else
            {
               $continuar = FALSE;
               $this->new_error_msg("¡Imposible guardar la línea el artículo ".$n->referencia."! ");
               break;
            }
         }
         
         if($continuar)
         {
            $this->pedido->idalbaran = $albaran->idalbaran;
            $this->pedido->editable = FALSE;
            if( $this->pedido->save() )
            {
               $this->new_message("<a href='".$albaran->url()."'>".ucfirst(FS_ALBARAN).'</a> generado correctamente.');
            }
            else
            {
               $this->new_error_msg("¡Imposible vincular el pedido con el nuevo ".FS_ALBARAN."!");
               if( $albaran->delete() )
               {
                  $this->new_error_msg("El ".FS_ALBARAN." se ha borrado.");
               }
               else
                  $this->new_error_msg("¡Imposible borrar el ".FS_ALBARAN."!");
            }
         }
         else
         {
            if( $albaran->delete() )
            {
               $this->new_error_msg("El ".FS_ALBARAN." se ha borrado.");
            }
            else
               $this->new_error_msg("¡Imposible borrar el ".FS_ALBARAN."!");
         }
      }
      else
         $this->new_error_msg("¡Imposible guardar el ".FS_ALBARAN."!");
   }
}
