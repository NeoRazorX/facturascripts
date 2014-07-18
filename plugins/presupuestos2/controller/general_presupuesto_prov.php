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

require_model('presupuesto_proveedor.php');
require_model('articulo.php');
require_model('asiento.php');
require_model('ejercicio.php');
require_model('pedido_proveedor.php');
require_model('familia.php');
require_model('impuesto.php');
require_model('partida.php');
require_model('proveedor.php');
require_model('regularizacion_iva.php');
require_model('serie.php');
require_model('subcuenta.php');

class general_presupuesto_prov extends fs_controller
{
   public $agente;
   public $presupuesto;
   public $ejercicio;
   public $familia;
   public $impuesto;
   public $nuevo_presupuesto_url;
   public $proveedor;
   public $serie;
   
   public function __construct()
   {
      parent::__construct('general_presupuesto_prov', 'Presupuesto de proveedor', 'compras', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->ppage = $this->page->get('general_presupuestos_prov');
      $this->ejercicio = new ejercicio();
      $this->familia = new familia();
      $this->impuesto = new impuesto();
      $this->proveedor = new proveedor();
      $this->serie = new serie();
      
      /// comprobamos si el usuario tiene acceso a general_nuevo_presupuesto
      $this->nuevo_presupuesto_url = FALSE;
      if( $this->user->have_access_to('general_nuevo_presupuesto', FALSE) )
      {
         $nuevoprep = $this->page->get('general_nuevo_presupuesto');
         if($nuevoprep)
            $this->nuevo_presupuesto_url = $nuevoprep->url();
      }
      
      if( isset($_POST['idpresupuesto']) )
      {
         $this->presupuesto = new presupuesto_proveedor();
         $this->presupuesto = $this->presupuesto->get($_POST['idpresupuesto']);
         $this->modificar();
      }
      else if( isset($_GET['id']) )
      {
         $this->presupuesto = new presupuesto_proveedor();
         $this->presupuesto = $this->presupuesto->get($_GET['id']);
      }
      
      if($this->presupuesto)
      {
         $this->page->title = $this->presupuesto->codigo;
         $this->agente = $this->presupuesto->get_agente();
         
         /// comprobamos el presupuesto
         $this->presupuesto->full_test();
         
         if( isset($_GET['pedidor']) AND isset($_GET['petid']) AND $this->presupuesto->ptepedido )
         {
            if( $this->duplicated_petition($_GET['petid']) )
               $this->new_error_msg('Petición duplicada. Evita hacer doble clic sobre los botones.');
            else
               $this->generar_pedido();
         }
         
         if( isset($_POST['actualizar_precios']) )
            $this->actualizar_precios();
         
         $this->buttons[] = new fs_button('b_copiar', 'Copiar', 'index.php?page=general_copy_presupuesto&idprepro='.$this->presupuesto->idpresupuesto, TRUE);
         
         if( $this->presupuesto->ptepedido )
         {
            $this->buttons[] = new fs_button('b_pedidor', 'Generar pedido', $this->url()."&pedidor=TRUE&petid=".$this->random_string());
         }
         else if( isset($this->presupuesto->idpedido) )
         {
            $this->buttons[] = new fs_button('b_ver_pedido', 'Pedido', $this->presupuesto->pedido_url());
         }
         
         $this->buttons[] = new fs_button('b_precios', 'Frecios');
         $this->buttons[] = new fs_button_img('b_eliminar', 'Eliminar', 'trash.png', '#', TRUE);
      }
      else
         $this->new_error_msg("¡Presupuesto de proveedor no encontrado!");
   }
   
   public function url()
   {
      if( !isset($this->presupuesto) )
         return parent::url();
      else if($this->presupuesto)
         return $this->presupuesto->url();
      else
         return $this->page->url();
   }
   
   private function modificar()
   {
      $this->presupuesto->numproveedor = $_POST['numproveedor'];
      $this->presupuesto->observaciones = $_POST['observaciones'];
      
      if( $this->presupuesto->ptepedido )
      {
         /// obtenemos los datos del ejercicio para acotar la fecha
         $eje0 = $this->ejercicio->get( $this->presupuesto->codejercicio );
         if($eje0)
            $this->presupuesto->fecha = $eje0->get_best_fecha($_POST['fecha'], TRUE);
         else
            $this->new_error_msg('No se encuentra el ejercicio asociado al presupuesto.');
         
         /// ¿Cambiamos el proveedor?
         if($_POST['proveedor'] != $this->presupuesto->codproveedor)
         {
            $proveedor = $this->proveedor->get($_POST['proveedor']);
            if($proveedor)
            {
               $this->presupuesto->codproveedor = $proveedor->codproveedor;
               $this->presupuesto->nombre = $proveedor->nombrecomercial;
               $this->presupuesto->cifnif = $proveedor->cifnif;
            }
         }
         
         $serie = $this->serie->get($this->presupuesto->codserie);
         
         /// ¿cambiamos la serie?
         if($_POST['serie'] != $this->presupuesto->codserie)
         {
            $this->presupuesto->codserie = $_POST['serie'];
            $this->presupuesto->new_codigo();
         }
         
         if( isset($_POST['lineas']) )
         {
            $this->presupuesto->neto = 0;
            $this->presupuesto->totaliva = 0;
            $lineas = $this->presupuesto->get_lineas();
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
                        $art0->sum_stock($this->presupuesto->codalmacen, 0 - $l->cantidad);
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
                           $this->presupuesto->neto += ($value->cantidad*$value->pvpunitario*(100-$value->dtopor)/100);
                           $this->presupuesto->totaliva += ($value->cantidad*$value->pvpunitario*(100-$value->dtopor)/100*$value->iva/100);
                           
                           /// actualizamos el stock
                           $art0 = $articulo->get($value->referencia);
                           if($art0)
                              $art0->sum_stock($this->presupuesto->codalmacen, $lineas[$k]->cantidad - $cantidad_old);
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
                        $linea = new linea_presupuesto_proveedor();
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
                        
                        $linea->idpresupuesto = $this->presupuesto->idpresupuesto;
                        $linea->cantidad = floatval($_POST['cantidad_'.$num]);
                        $linea->pvpunitario = floatval($_POST['pvp_'.$num]);
                        $linea->dtopor = floatval($_POST['dto_'.$num]);
                        $linea->pvpsindto = ($linea->cantidad * $linea->pvpunitario);
                        $linea->pvptotal = ($linea->cantidad * $linea->pvpunitario * (100 - $linea->dtopor)/100);
                        
                        if( $linea->save() )
                        {
                           $this->presupuesto->neto += ($linea->cantidad*$linea->pvpunitario*(100-$linea->dtopor)/100);
                           $this->presupuesto->totaliva += ($linea->cantidad*$linea->pvpunitario*(100-$linea->dtopor)/100*$linea->iva/100);
                           
                           /// actualizamos el stock
                           $art0->sum_stock($this->presupuesto->codalmacen, $linea->cantidad);
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
            $this->presupuesto->neto = round($this->presupuesto->neto, 2);
            $this->presupuesto->totaliva = round($this->presupuesto->totaliva, 2);
            $this->presupuesto->total = $this->presupuesto->neto + $this->presupuesto->totaliva;
         }
      }
      
      if( $this->presupuesto->save() )
      {
         $this->new_message("Presupuesto modificado correctamente.");
         $this->new_change('Presupuesto Proveedor '.$this->presupuesto->codigo, $this->presupuesto->url());
      }
      else
         $this->new_error_msg("¡Imposible modificar el presupuesto!");
   }
   
   private function generar_pedido()
   {
      $pedido = new pedido_proveedor();
      $pedido->automatica = TRUE;
      $pedido->editable = FALSE;
      $pedido->cifnif = $this->presupuesto->cifnif;
      $pedido->codalmacen = $this->presupuesto->codalmacen;
      $pedido->coddivisa = $this->presupuesto->coddivisa;
      $pedido->tasaconv = $this->presupuesto->tasaconv;
      $pedido->codejercicio = $this->presupuesto->codejercicio;
      $pedido->codpago = $this->presupuesto->codpago;
      $pedido->codproveedor = $this->presupuesto->codproveedor;
      $pedido->codserie = $this->presupuesto->codserie;
      $pedido->irpf = $this->presupuesto->irpf;
      $pedido->neto = $this->presupuesto->neto;
      $pedido->nombre = $this->presupuesto->nombre;
      $pedido->numproveedor = $this->presupuesto->numproveedor;
      $pedido->observaciones = $this->presupuesto->observaciones;
      $pedido->recfinanciero = $this->presupuesto->recfinanciero;
      $pedido->total = $this->presupuesto->total;
      $pedido->totalirpf = $this->presupuesto->totalirpf;
      $pedido->totaliva = $this->presupuesto->totaliva;
      $pedido->totalrecargo = $this->presupuesto->totalrecargo;
      
      /// asignamos la mejor fecha posible, pero dentro del ejercicio
      $eje0 = $this->ejercicio->get($pedido->codejercicio);
      $pedido->fecha = $eje0->get_best_fecha($pedido->fecha);
      
      $regularizacion = new regularizacion_iva();
      
      if( !$eje0->abierto() )
      {
         $this->new_error_msg("El ejercicio está cerrado.");
      }
      else if( $regularizacion->get_fecha_inside($pedido->fecha) )
      {
         $this->new_error_msg("El IVA de ese periodo ya ha sido regularizado.
            No se pueden añadir más pedidos en esa fecha.");
      }
      else if( $pedido->save() )
      {
         $continuar = TRUE;
         foreach($this->presupuesto->get_lineas() as $l)
         {
            $linea = new linea_pedido_proveedor();
            $linea->cantidad = $l->cantidad;
            $linea->codimpuesto = $l->codimpuesto;
            $linea->descripcion = $l->descripcion;
            $linea->dtolineal = $l->dtolineal;
            $linea->dtopor = $l->dtopor;
            $linea->idpresupuesto = $l->idpresupuesto;
            $linea->idpedido = $pedido->idpedido;
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
            $this->presupuesto->idpedido = $pedido->idpedido;
            $this->presupuesto->ptepedido = FALSE;
            if( $this->presupuesto->save() )
            {
               $this->generar_asiento($pedido);
            }
            else
            {
               $this->new_error_msg("¡Imposible vincular el presupuesto con el nueva pedido!");
               if( $pedido->delete() )
                  $this->new_error_msg("El pedido se ha borrado.");
               else
                  $this->new_error_msg("¡Imposible borrar el pedido!");
            }
         }
         else
         {
            if( $pedido->delete() )
               $this->new_error_msg("El pedido se ha borrado.");
            else
               $this->new_error_msg("¡Imposible borrar el pedido!");
         }
      }
      else
         $this->new_error_msg("¡Imposible guardar el pedido!");
   }
   
   private function generar_asiento($pedido)
   {
      $proveedor = $this->proveedor->get($pedido->codproveedor);
      $subcuenta_prov = $proveedor->get_subcuenta($pedido->codejercicio);
      
      if( !$this->empresa->contintegrada )
      {
         $this->new_message("<a href='".$pedido->url()."'>Pedido</a> generado correctamente.");
         $this->new_change('Pedido Proveedor '.$pedido->codigo, $pedido->url(), TRUE);
      }
      else if( !$subcuenta_prov )
      {
         $eje0 = $this->ejercicio->get( $this->presupuesto->codejercicio );
         $this->new_message("No se ha podido generar una subcuenta para el proveedor
            <a href='".$eje0->url()."'>¿Has importado los datos del ejercicio?</a>
            Aun así el <a href='".$pedido->url()."'>pedido</a> se ha generado correctamente,
            pero sin asiento contable.");
      }
      else
      {
         $asiento = new asiento();
         $asiento->codejercicio = $pedido->codejercicio;
         $asiento->concepto = "Su pedido ".$pedido->codigo." - ".$pedido->nombre;
         $asiento->documento = $pedido->codigo;
         $asiento->editable = FALSE;
         $asiento->fecha = $pedido->fecha;
         $asiento->importe = $pedido->total;
         $asiento->tipodocumento = "Pedido de proveedor";
         if( $asiento->save() )
         {
            $asiento_correcto = TRUE;
            $subcuenta = new subcuenta();
            $partida0 = new partida();
            $partida0->idasiento = $asiento->idasiento;
            $partida0->concepto = $asiento->concepto;
            $partida0->idsubcuenta = $subcuenta_prov->idsubcuenta;
            $partida0->codsubcuenta = $subcuenta_prov->codsubcuenta;
            $partida0->haber = $pedido->total;
            $partida0->coddivisa = $pedido->coddivisa;
            $partida0->tasaconv = $pedido->tasaconv;
            if( !$partida0->save() )
            {
               $asiento_correcto = FALSE;
               $this->new_error_msg("¡Imposible generar la partida para la subcuenta ".$partida0->codsubcuenta."!");
            }
            
            /// generamos una partida por cada impuesto
            $subcuenta_iva = $subcuenta->get_by_codigo('4720000000', $asiento->codejercicio);
            foreach($pedido->get_lineas_iva() as $li)
            {
               if($subcuenta_iva AND $asiento_correcto)
               {
                  $partida1 = new partida();
                  $partida1->idasiento = $asiento->idasiento;
                  $partida1->concepto = $asiento->concepto;
                  $partida1->idsubcuenta = $subcuenta_iva->idsubcuenta;
                  $partida1->codsubcuenta = $subcuenta_iva->codsubcuenta;
                  $partida1->debe = $li->totaliva;
                  $partida1->idcontrapartida = $subcuenta_prov->idsubcuenta;
                  $partida1->codcontrapartida = $subcuenta_prov->codsubcuenta;
                  $partida1->cifnif = $proveedor->cifnif;
                  $partida1->documento = $asiento->documento;
                  $partida1->tipodocumento = $asiento->tipodocumento;
                  $partida1->codserie = $pedido->codserie;
                  $partida1->pedido = $pedido->numero;
                  $partida1->baseimponible = $li->neto;
                  $partida1->iva = $li->iva;
                  $partida1->coddivisa = $pedido->coddivisa;
                  $partida1->tasaconv = $pedido->tasaconv;
                  if( !$partida1->save() )
                  {
                     $asiento_correcto = FALSE;
                     $this->new_error_msg("¡Imposible generar la partida para la subcuenta ".$partida1->codsubcuenta."!");
                  }
               }
            }
            
            $subcuenta_compras = $subcuenta->get_by_codigo('6000000000', $asiento->codejercicio);
            if($subcuenta_compras AND $asiento_correcto)
            {
               $partida2 = new partida();
               $partida2->idasiento = $asiento->idasiento;
               $partida2->concepto = $asiento->concepto;
               $partida2->idsubcuenta = $subcuenta_compras->idsubcuenta;
               $partida2->codsubcuenta = $subcuenta_compras->codsubcuenta;
               $partida2->debe = $pedido->neto;
               $partida2->coddivisa = $pedido->coddivisa;
               $partida2->tasaconv = $pedido->tasaconv;
               if( !$partida2->save() )
               {
                  $asiento_correcto = FALSE;
                  $this->new_error_msg("¡Imposible generar la partida para la subcuenta ".$partida2->codsubcuenta."!");
               }
            }
            
            if($asiento_correcto)
            {
               $pedido->idasiento = $asiento->idasiento;
               if( $pedido->save() )
               {
                  $this->new_message("<a href='".$pedido->url()."'>Pedido</a> generada correctamente.");
                  $this->new_change('Pedido Proveedor '.$pedido->codigo, $pedido->url(), TRUE);
               }
               else
                  $this->new_error_msg("¡Imposible añadir el asiento a el pedido!");
            }
            else
            {
               if( $asiento->delete() )
               {
                  $this->new_message("El asiento se ha borrado.");
                  if( $pedido->delete() )
                     $this->new_message("El pedido se ha borrado.");
                  else
                     $this->new_error_msg("¡Imposible borrar el pedido!");
               }
               else
                  $this->new_error_msg("¡Imposible borrar el asiento!");
            }
         }
         else
         {
            $this->new_error_msg("¡Imposible guardar el asiento!");
            if( $pedido->delete() )
               $this->new_error_msg("El pedido se ha borrado.");
            else
               $this->new_error_msg("¡Imposible borrar el pedido!");
         }
      }
   }
   
   private function actualizar_precios()
   {
      $articulo = new articulo();
      $lineas = $this->presupuesto->get_lineas();
      
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
