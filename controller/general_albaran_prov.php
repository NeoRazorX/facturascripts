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

require_once 'model/albaran_proveedor.php';
require_once 'model/articulo.php';
require_once 'model/asiento.php';
require_once 'model/ejercicio.php';
require_once 'model/factura_proveedor.php';
require_once 'model/familia.php';
require_once 'model/impuesto.php';
require_once 'model/partida.php';
require_once 'model/proveedor.php';
require_once 'model/serie.php';
require_once 'model/subcuenta.php';

class general_albaran_prov extends fs_controller
{
   public $agente;
   public $albaran;
   public $ejercicio;
   public $familia;
   public $impuesto;
   public $nuevo_albaran_url;
   public $proveedor;
   public $serie;
   
   public function __construct()
   {
      parent::__construct('general_albaran_prov', 'Albaran de proveedor', 'general', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->ppage = $this->page->get('general_albaranes_prov');
      $this->ejercicio = new ejercicio();
      $this->familia = new familia();
      $this->impuesto = new impuesto();
      $this->proveedor = new proveedor();
      $this->serie = new serie();
      
      /// comprobamos si el usuario tiene acceso a general_nuevo_albaran
      $this->nuevo_albaran_url = FALSE;
      if( $this->user->have_access_to('general_nuevo_albaran', FALSE) )
      {
         $nuevoalbp = $this->page->get('general_nuevo_albaran');
         if($nuevoalbp)
            $this->nuevo_albaran_url = $nuevoalbp->url();
      }
      
      if( isset($_POST['idalbaran']) )
      {
         $this->albaran = new albaran_proveedor();
         $this->albaran = $this->albaran->get($_POST['idalbaran']);
         $this->modificar();
      }
      else if( isset($_GET['id']) )
      {
         $this->albaran = new albaran_proveedor();
         $this->albaran = $this->albaran->get($_GET['id']);
      }
      
      if($this->albaran)
      {
         $this->page->title = $this->albaran->codigo;
         $this->agente = $this->albaran->get_agente();
         
         /// comprobamos el albarán
         $this->albaran->full_test();
         
         if( isset($_GET['facturar']) AND isset($_GET['petid']) AND $this->albaran->ptefactura )
         {
            if( $this->duplicated_petition($_GET['petid']) )
               $this->new_error_msg('Petición duplicada. Evita hacer doble clic sobre los botones.');
            else
               $this->generar_factura();
         }
         
         if( isset($_POST['actualizar_precios']) )
            $this->actualizar_precios();
         
         if( $this->albaran->ptefactura )
         {
            $this->buttons[] = new fs_button('b_facturar', 'generar factura',
                    $this->url()."&facturar=TRUE&petid=".$this->random_string());
         }
         else
         {
            $this->buttons[] = new fs_button('b_ver_factura', 'factura',
                    $this->albaran->factura_url(), 'button', 'img/zoom.png');
         }
         $this->buttons[] = new fs_button('b_precios', 'precios', '#', '', 'img/tools.png');
         $this->buttons[] = new fs_button('b_eliminar', 'eliminar', '#', 'remove', 'img/remove.png');
      }
      else
         $this->new_error_msg("¡Albarán de proveedor no encontrado!");
   }
   
   public function version()
   {
      return parent::version().'-18';
   }
   
   public function url()
   {
      if($this->albaran)
         return $this->albaran->url();
      else
         return $this->page->url();
   }
   
   private function modificar()
   {
      $this->albaran->numproveedor = $_POST['numproveedor'];
      $this->albaran->observaciones = $_POST['observaciones'];
      
      if( $this->albaran->ptefactura )
      {
         /// obtenemos los datos del ejercicio para acotar la fecha
         $eje0 = $this->ejercicio->get( $this->albaran->codejercicio );
         if($eje0)
            $this->albaran->fecha = $eje0->get_best_fecha($_POST['fecha'], TRUE);
         else
            $this->new_error_msg('No se encuentra el ejercicio asociado al albarán.');
         
         /// ¿Cambiamos el proveedor?
         if($_POST['proveedor'] != $this->albaran->codproveedor)
         {
            $proveedor = $this->proveedor->get($_POST['proveedor']);
            if($proveedor)
            {
               $this->albaran->codproveedor = $proveedor->codproveedor;
               $this->albaran->nombre = $proveedor->nombre;
               $this->albaran->cifnif = $proveedor->cifnif;
            }
         }
         
         $serie = $this->serie->get($this->albaran->codserie);
         
         /// ¿cambiamos la serie?
         if($_POST['serie'] != $this->albaran->codserie)
         {
            $this->albaran->codserie = $_POST['serie'];
            $this->albaran->new_codigo();
         }
         
         if( isset($_POST['lineas']) )
         {
            $this->albaran->neto = 0;
            $this->albaran->totaliva = 0;
            $lineas = $this->albaran->get_lineas();
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
                  if( !$l->delete() )
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
                           $this->albaran->neto += ($value->cantidad*$value->pvpunitario*(100-$value->dtopor)/100);
                           $this->albaran->totaliva += ($value->cantidad*$value->pvpunitario*(100-$value->dtopor)/100*$value->iva/100);
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
                        $linea = new linea_albaran_proveedor();
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
                        
                        $linea->idalbaran = $this->albaran->idalbaran;
                        $linea->cantidad = floatval($_POST['cantidad_'.$num]);
                        $linea->pvpunitario = floatval($_POST['pvp_'.$num]);
                        $linea->dtopor = floatval($_POST['dto_'.$num]);
                        $linea->pvpsindto = ($linea->cantidad * $linea->pvpunitario);
                        $linea->pvptotal = ($linea->cantidad * $linea->pvpunitario * (100 - $linea->dtopor)/100);
                        
                        if( $linea->save() )
                        {
                           $this->albaran->neto += ($linea->cantidad*$linea->pvpunitario*(100-$linea->dtopor)/100);
                           $this->albaran->totaliva += ($linea->cantidad*$linea->pvpunitario*(100-$linea->dtopor)/100*$linea->iva/100);
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
            $this->albaran->neto = round($this->albaran->neto, 2);
            $this->albaran->totaliva = round($this->albaran->totaliva, 2);
            $this->albaran->total = $this->albaran->neto + $this->albaran->totaliva;
         }
      }
      
      if( $this->albaran->save() )
         $this->new_message("Albarán modificado correctamente.");
      else
         $this->new_error_msg("¡Imposible modificar el albarán!");
   }
   
   private function generar_factura()
   {
      $factura = new factura_proveedor();
      $factura->automatica = TRUE;
      $factura->editable = FALSE;
      $factura->cifnif = $this->albaran->cifnif;
      $factura->codalmacen = $this->albaran->codalmacen;
      $factura->coddivisa = $this->albaran->coddivisa;
      $factura->tasaconv = $this->albaran->tasaconv;
      $factura->codejercicio = $this->albaran->codejercicio;
      $factura->codpago = $this->albaran->codpago;
      $factura->codproveedor = $this->albaran->codproveedor;
      $factura->codserie = $this->albaran->codserie;
      $factura->irpf = $this->albaran->irpf;
      $factura->neto = $this->albaran->neto;
      $factura->nombre = $this->albaran->nombre;
      $factura->numproveedor = $this->albaran->numproveedor;
      $factura->observaciones = $this->albaran->observaciones;
      $factura->recfinanciero = $this->albaran->recfinanciero;
      $factura->total = $this->albaran->total;
      $factura->totalirpf = $this->albaran->totalirpf;
      $factura->totaliva = $this->albaran->totaliva;
      $factura->totalrecargo = $this->albaran->totalrecargo;
      if( $factura->save() )
      {
         $continuar = TRUE;
         foreach($this->albaran->get_lineas() as $l)
         {
            $linea = new linea_factura_proveedor();
            $linea->cantidad = $l->cantidad;
            $linea->codimpuesto = $l->codimpuesto;
            $linea->descripcion = $l->descripcion;
            $linea->dtolineal = $l->dtolineal;
            $linea->dtopor = $l->dtopor;
            $linea->idalbaran = $l->idalbaran;
            $linea->idfactura = $factura->idfactura;
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
               $this->new_error_msg("¡Imposible guardar la línea el artículo ".$linea->referencia."! ");
               break;
            }
         }
         
         if( $continuar )
         {
            $this->albaran->idfactura = $factura->idfactura;
            $this->albaran->ptefactura = FALSE;
            if( $this->albaran->save() )
               $this->generar_asiento($factura);
            else
            {
               $this->new_error_msg("¡Imposible vincular el albarán con la nueva factura!");
               if( $factura->delete() )
                  $this->new_error_msg("La factura se ha borrado.");
               else
                  $this->new_error_msg("¡Imposible borrar la factura!");
            }
         }
         else
         {
            if( $factura->delete() )
               $this->new_error_msg("La factura se ha borrado.");
            else
               $this->new_error_msg("¡Imposible borrar la factura!");
         }
      }
      else
         $this->new_error_msg("¡Imposible guardar la factura!");
   }
   
   private function generar_asiento($factura)
   {
      $proveedor = $this->proveedor->get($factura->codproveedor);
      $subcuenta_prov = $proveedor->get_subcuenta($factura->codejercicio);
      
      if( !$this->empresa->contintegrada )
         $this->new_message("<a href='".$factura->url()."'>Factura</a> generada correctamente.");
      else if( !$subcuenta_prov )
         $this->new_message("El proveedor no tiene asociada una subcuenta, y por tanto no se generará
            un asiento. Aun así la <a href='".$factura->url()."'>factura</a> se ha generado correctamente.");
      else
      {
         $asiento = new asiento();
         $asiento->codejercicio = $factura->codejercicio;
         $asiento->concepto = "Su factura ".$factura->codigo." - ".$factura->nombre;
         $asiento->documento = $factura->codigo;
         $asiento->editable = FALSE;
         $asiento->fecha = $factura->fecha;
         $asiento->importe = $factura->total;
         $asiento->tipodocumento = "Factura de proveedor";
         if( $asiento->save() )
         {
            $asiento_correcto = TRUE;
            $subcuenta = new subcuenta();
            $partida0 = new partida();
            $partida0->idasiento = $asiento->idasiento;
            $partida0->concepto = $asiento->concepto;
            $partida0->idsubcuenta = $subcuenta_prov->idsubcuenta;
            $partida0->codsubcuenta = $subcuenta_prov->codsubcuenta;
            $partida0->haber = $factura->total;
            $partida0->coddivisa = $factura->coddivisa;
            $partida0->tasaconv = $factura->tasaconv;
            if( !$partida0->save() )
            {
               $asiento_correcto = FALSE;
               $this->new_error_msg("¡Imposible generar la partida para la subcuenta ".$partida0->codsubcuenta."!");
            }
            
            /// generamos una partida por cada impuesto
            $subcuenta_iva = $subcuenta->get_by_codigo('4720000000', $asiento->codejercicio);
            foreach($factura->get_lineas_iva() as $li)
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
                  $partida1->codserie = $factura->codserie;
                  $partida1->factura = $factura->numero;
                  $partida1->baseimponible = $li->neto;
                  $partida1->iva = $li->iva;
                  $partida1->coddivisa = $factura->coddivisa;
                  $partida1->tasaconv = $factura->tasaconv;
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
               $partida2->debe = $factura->neto;
               $partida2->coddivisa = $factura->coddivisa;
               $partida2->tasaconv = $factura->tasaconv;
               if( !$partida2->save() )
               {
                  $asiento_correcto = FALSE;
                  $this->new_error_msg("¡Imposible generar la partida para la subcuenta ".$partida2->codsubcuenta."!");
               }
            }
            
            if( $asiento_correcto )
            {
               $factura->idasiento = $asiento->idasiento;
               if( $factura->save() )
                  $this->new_message("<a href='".$factura->url()."'>Factura</a> generada correctamente.");
               else
                  $this->new_error_msg("¡Imposible añadir el asiento a la factura!");
            }
            else
            {
               if( $asiento->delete() )
               {
                  $this->new_message("El asiento se ha borrado.");
                  if( $factura->delete() )
                     $this->new_message("La factura se ha borrado.");
                  else
                     $this->new_error_msg("¡Imposible borrar la factura!");
               }
               else
                  $this->new_error_msg("¡Imposible borrar el asiento!");
            }
         }
         else
         {
            $this->new_error_msg("¡Imposible guardar el asiento!");
            if( $factura->delete() )
               $this->new_error_msg("La factura se ha borrado.");
            else
               $this->new_error_msg("¡Imposible borrar la factura!");
         }
      }
   }
   
   private function actualizar_precios()
   {
      $articulo = new articulo();
      $num_lineas = count($this->albaran->get_lineas());
      
      for($i=0; $i<$num_lineas; $i++)
      {
         if( isset($_POST['pvp_'.$i]) )
         {
            $art0 = $articulo->get($_POST['referencia_'.$i]);
            if( $art0 )
            {
               $art0->set_pvp($_POST['pvp_'.$i]);
               if( !$art0->save() )
                  $this->new_error_msg('Imposible actualizar el artículo '.$art0->referencia);
            }
         }
      }
      
      $this->new_message('Precios actualizados.');
   }
}

?>