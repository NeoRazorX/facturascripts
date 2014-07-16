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

require_model('agente.php');
require_model('presupuesto_cliente.php');
require_model('presupuesto_proveedor.php');
require_model('almacen.php');
require_model('articulo.php');
require_model('cliente.php');
require_model('divisa.php');
require_model('ejercicio.php');
require_model('familia.php');
require_model('forma_pago.php');
require_model('impuesto.php');
require_model('proveedor.php');
require_model('serie.php');

class general_nuevo_presupuesto extends fs_controller
{
   public $agente;
   public $almacen;
   public $articulo;
   public $cliente;
   public $divisa;
   public $ejercicio;
   public $familia;
   public $forma_pago;
   public $impuesto;
   public $proveedor;
   public $results;
   public $serie;
   
   public function __construct()
   {
      parent::__construct('general_nuevo_presupuesto', 'nuevo presupuesto', 'general', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->articulo = new articulo();
      $this->familia = new familia();
      $this->impuesto = new impuesto();
      $this->results = array();
      
      if( isset($_GET['new_articulo']) )
         $this->new_articulo();
      else if( $this->query != '' )
         $this->new_search();
      else if( isset($_POST['referencia4precios']) )
         $this->get_precios_articulo();
      else
      {
         if( isset($_POST['codagente']) )
         {
            $agente = new agente();
            $this->agente = $agente->get($_POST['codagente']);
         }
         else
            $this->agente = $this->user->get_agente();
         
         $this->almacen = new almacen();
         $this->cliente = new cliente();
         $this->divisa = new divisa();
         $this->ejercicio = new ejercicio();
         $this->forma_pago = new forma_pago();
         $this->proveedor = new proveedor();
         $this->serie = new serie();
         
         if( !$this->agente )
         {
            $this->new_error_msg('No tienes un <a href="'.$this->user->url().'">agente asociado</a>
               a tu usuario, y por tanto no puedes hacer tickets.');
         }
         else if( isset($_POST['tipopresupuesto']) )
         {
            if($_POST['tipopresupuesto'] == 'cliente')
               $this->nuevo_presupuesto_cliente();
            else if($_POST['tipopresupuesto'] == 'proveedor')
               $this->nuevo_presupuesto_proveedor();
         }
      }
   }
   
   private function new_articulo()
   {
      $art0 = new articulo();
      $art0->referencia = $_POST['referencia'];
      $art0->descripcion = $_POST['referencia'];
      $art0->codfamilia = $_POST['codfamilia'];
      $art0->set_impuesto($_POST['codimpuesto']);
      
      if( $art0->save() )
         $this->results[] = $art0;
      
      /// cambiamos la plantilla HTML
      $this->template = 'ajax/general_nuevo_presupuesto';
   }
   
   private function new_search()
   {
      /// cambiamos la plantilla HTML
      $this->template = 'ajax/general_nuevo_presupuesto';
      
      $codfamilia = '';
      if( isset($_POST['codfamilia']) )
         $codfamilia = $_POST['codfamilia'];
      
      $con_stock = isset($_POST['con_stock']);
      $this->results = $this->articulo->search($this->query, 0, $codfamilia, $con_stock);
   }
   
   private function nuevo_presupuesto_cliente()
   {
      $continuar = TRUE;
      
      $cliente = $this->cliente->get($_POST['cliente']);
      if( $cliente )
         $this->save_codcliente( $cliente->codcliente );
      else
      {
         $this->new_error_msg('Cliente no encontrado.');
         $continuar = FALSE;
      }
      
      $almacen = $this->almacen->get($_POST['almacen']);
      if( $almacen )
         $this->save_codalmacen( $almacen->codalmacen );
      else
      {
         $this->new_error_msg('Almacén no encontrado.');
         $continuar = FALSE;
      }
      
      $ejercicio = $this->ejercicio->get_by_fecha($_POST['fecha']);
      if( $ejercicio )
         $this->save_codejercicio( $ejercicio->codejercicio );
      else
      {
         $this->new_error_msg('Ejercicio no encontrado.');
         $continuar = FALSE;
      }
      
      $serie = $this->serie->get($_POST['serie']);
      if( $serie )
         $this->save_codserie( $serie->codserie );
      else
      {
         $this->new_error_msg('Serie no encontrada.');
         $continuar = FALSE;
      }
      
      $forma_pago = $this->forma_pago->get($_POST['forma_pago']);
      if( $forma_pago )
         $this->save_codpago( $forma_pago->codpago );
      else
      {
         $this->new_error_msg('Forma de pago no encontrada.');
         $continuar = FALSE;
      }
      
      $divisa = $this->divisa->get($_POST['divisa']);
      if( $divisa )
         $this->save_coddivisa( $divisa->coddivisa );
      else
      {
         $this->new_error_msg('Divisa no encontrada.');
         $continuar = FALSE;
      }
      
      $presupuesto = new presupuesto_cliente();
      
      if( $this->duplicated_petition($_POST['petition_id']) )
      {
         $this->new_error_msg('Petición duplicada. Has hecho doble clic sobre el botón guadar
               y se han enviado dos peticiones. Mira en <a href="'.$presupuesto->url().'">presupuestos</a>
               para ver si el presupuesto se ha guardado correctamente.');
         $continuar = FALSE;
      }
      
      if( $continuar )
      {
         $presupuesto->fecha = $_POST['fecha'];
         $presupuesto->hora = $_POST['hora'];
         $presupuesto->codalmacen = $almacen->codalmacen;
         $presupuesto->codejercicio = $ejercicio->codejercicio;
         $presupuesto->codserie = $serie->codserie;
         $presupuesto->codpago = $forma_pago->codpago;
         $presupuesto->coddivisa = $divisa->coddivisa;
         $presupuesto->tasaconv = $divisa->tasaconv;
         $presupuesto->codagente = $this->agente->codagente;
         if( isset($_POST['numero2']) )
            $presupuesto->numero2 = $_POST['numero2'];
         $presupuesto->observaciones = $_POST['observaciones'];
         
         foreach($cliente->get_direcciones() as $d)
         {
            if($d->dompedidocion)
            {
               $presupuesto->codcliente = $cliente->codcliente;
               $presupuesto->cifnif = $cliente->cifnif;
               $presupuesto->nombrecliente = $cliente->nombrecomercial;
               $presupuesto->apartado = $d->apartado;
               $presupuesto->ciudad = $d->ciudad;
               $presupuesto->coddir = $d->id;
               $presupuesto->codpais = $d->codpais;
               $presupuesto->codpostal = $d->codpostal;
               $presupuesto->direccion = $d->direccion;
               $presupuesto->provincia = $d->provincia;
               break;
            }
         }
         
         if( is_null($presupuesto->codcliente) )
         {
            $this->new_error_msg("No hay ninguna dirección asociada al cliente.");
         }
         else if( $presupuesto->save() )
         {
            $n = floatval($_POST['numlineas']);
            for($i = 1; $i <= $n; $i++)
            {
               if( isset($_POST['referencia_'.$i]) )
               {
                  $articulo = $this->articulo->get($_POST['referencia_'.$i]);
                  if($articulo)
                  {
                     $linea = new linea_presupuesto_cliente();
                     $linea->idpresupuesto = $presupuesto->idpresupuesto;
                     $linea->referencia = $articulo->referencia;
                     
                     if( isset($_POST['desc_'.$i]) )
                        $linea->descripcion = $_POST['desc_'.$i];
                     else
                        $linea->descripcion = $articulo->descripcion;
                     
                     if( $serie->siniva )
                     {
                        $linea->codimpuesto = NULL;
                        $linea->iva = 0;
                     }
                     else
                     {
                        $imp0 = $this->impuesto->get_by_iva($_POST['iva_'.$i]);
                        if($imp0)
                        {
                           $linea->codimpuesto = $imp0->codimpuesto;
                           $linea->iva = $imp0->iva;
                        }
                        else
                        {
                           $linea->codimpuesto = NULL;
                           $linea->iva = floatval($_POST['iva_'.$i]);
                        }
                     }
                     
                     $linea->pvpunitario = floatval($_POST['pvp_'.$i]);
                     $linea->cantidad = floatval($_POST['cantidad_'.$i]);
                     $linea->dtopor = floatval($_POST['dto_'.$i]);
                     $linea->pvpsindto = ($linea->pvpunitario * $linea->cantidad);
                     $linea->pvptotal = floatval($_POST['total_'.$i]);
                     
                     if( $linea->save() )
                     {
                        /// descontamos del stock
                        $articulo->sum_stock($presupuesto->codalmacen, 0 - $linea->cantidad);
                        
                        $presupuesto->neto += $linea->pvptotal;
                        $presupuesto->totaliva += ($linea->pvptotal * $linea->iva/100);
                     }
                     else
                     {
                        $this->new_error_msg("¡Imposible guardar la linea con referencia: ".$linea->referencia);
                        $continuar = FALSE;
                     }
                  }
                  else
                  {
                     $this->new_error_msg("Artículo no encontrado: ".$_POST['referencia_'.$i]);
                     $continuar = FALSE;
                  }
               }
            }
            
            if( $continuar )
            {
               /// redondeamos
               $presupuesto->neto = round($presupuesto->neto, 2);
               $presupuesto->totaliva = round($presupuesto->totaliva, 2);
               $presupuesto->total = $presupuesto->neto + $presupuesto->totaliva;
               
               if( $presupuesto->save() )
               {
                  $this->new_message("<a href='".$presupuesto->url()."'>Presupuesto</a> guardado correctamente.");
                  $this->new_change('Presupuesto Cliente '.$presupuesto->codigo, $presupuesto->url(), TRUE);
               }
               else
                  $this->new_error_msg("¡Imposible actualizar el <a href='".$presupuesto->url()."'>presupuesto</a>!");
            }
            else if( $presupuesto->delete() )
            {
               $this->new_message("Presupuesto eliminado correctamente.");
            }
            else
               $this->new_error_msg("¡Imposible eliminar el <a href='".$presupuesto->url()."'>presupuesto</a>!");
         }
         else
            $this->new_error_msg("¡Imposible guardar el presupuesto!");
      }
   }
   
   private function nuevo_presupuesto_proveedor()
   {
      $continuar = TRUE;
      
      $proveedor = $this->proveedor->get($_POST['proveedor']);
      if( $proveedor )
         $this->save_codproveedor( $proveedor->codproveedor );
      else
      {
         $this->new_error_msg('Proveedor no encontrado.');
         $continuar = FALSE;
      }
      
      $almacen = $this->almacen->get($_POST['almacen']);
      if( $almacen )
         $this->save_codalmacen( $almacen->codalmacen );
      else
      {
         $this->new_error_msg('Almacén no encontrado.');
         $continuar = FALSE;
      }
      
      $ejercicio = $this->ejercicio->get_by_fecha($_POST['fecha']);
      if( $ejercicio )
         $this->save_codejercicio( $ejercicio->codejercicio );
      else
      {
         $this->new_error_msg('Ejercicio no encontrado.');
         $continuar = FALSE;
      }
      
      $serie = $this->serie->get($_POST['serie']);
      if( $serie )
         $this->save_codserie( $serie->codserie );
      else
      {
         $this->new_error_msg('Serie no encontrada.');
         $continuar = FALSE;
      }
      
      $forma_pago = $this->forma_pago->get($_POST['forma_pago']);
      if( $forma_pago )
         $this->save_codpago( $forma_pago->codpago );
      else
      {
         $this->new_error_msg('Forma de pago no encontrada.');
         $continuar = FALSE;
      }
      
      $divisa = $this->divisa->get($_POST['divisa']);
      if( $divisa )
         $this->save_coddivisa( $divisa->coddivisa );
      else
      {
         $this->new_error_msg('Divisa no encontrada.');
         $continuar = FALSE;
      }
      
      $presupuesto = new presupuesto_proveedor();
      
      if( $this->duplicated_petition($_POST['petition_id']) )
      {
         $this->new_error_msg('Petición duplicada. Has hecho doble clic sobre el botón guadar
               y se han enviado dos peticiones. Mira en <a href="'.$presupuesto->url().'">presupuestos</a>
               para ver si el presupuesto se ha guardado correctamente.');
         $continuar = FALSE;
      }
      
      if( $continuar )
      {
         $presupuesto->fecha = $_POST['fecha'];
         $presupuesto->hora = $_POST['hora'];
         $presupuesto->codproveedor = $proveedor->codproveedor;
         $presupuesto->nombre = $proveedor->nombrecomercial;
         $presupuesto->cifnif = $proveedor->cifnif;
         $presupuesto->codalmacen = $almacen->codalmacen;
         $presupuesto->codejercicio = $ejercicio->codejercicio;
         $presupuesto->codserie = $serie->codserie;
         $presupuesto->codpago = $forma_pago->codpago;
         $presupuesto->coddivisa = $divisa->coddivisa;
         $presupuesto->tasaconv = $divisa->tasaconv;
         $presupuesto->codagente = $this->agente->codagente;
         if( isset($_POST['numproveedor']) )
            $presupuesto->numproveedor = $_POST['numproveedor'];
         $presupuesto->observaciones = $_POST['observaciones'];
         
         if( $presupuesto->save() )
         {
            $n = floatval($_POST['numlineas']);
            for($i = 1; $i <= $n; $i++)
            {
               if( isset($_POST['referencia_'.$i]) )
               {
                  $articulo = $this->articulo->get($_POST['referencia_'.$i]);
                  if($articulo)
                  {
                     $linea = new linea_presupuesto_proveedor();
                     $linea->idpresupuesto = $presupuesto->idpresupuesto;
                     $linea->referencia = $articulo->referencia;
                     
                     if( isset($_POST['desc_'.$i]) )
                        $linea->descripcion = $_POST['desc_'.$i];
                     else
                        $linea->descripcion = $articulo->descripcion;
                     
                     if( $serie->siniva )
                     {
                        $linea->codimpuesto = NULL;
                        $linea->iva = 0;
                     }
                     else
                     {
                        $imp0 = $this->impuesto->get_by_iva($_POST['iva_'.$i]);
                        if($imp0)
                        {
                           $linea->codimpuesto = $imp0->codimpuesto;
                           $linea->iva = $imp0->iva;
                        }
                        else
                        {
                           $linea->codimpuesto = NULL;
                           $linea->iva = floatval($_POST['iva_'.$i]);
                        }
                     }
                     
                     $linea->pvpunitario = floatval($_POST['pvp_'.$i]);
                     $linea->cantidad = floatval($_POST['cantidad_'.$i]);
                     $linea->dtopor = floatval($_POST['dto_'.$i]);
                     $linea->pvpsindto = ($linea->pvpunitario * $linea->cantidad);
                     $linea->pvptotal = floatval($_POST['total_'.$i]);
                     
                     if( $linea->save() )
                     {
                        /// sumamos al stock
                        $articulo->sum_stock($presupuesto->codalmacen, $linea->cantidad);
                        
                        $presupuesto->neto += $linea->pvptotal;
                        $presupuesto->totaliva += ($linea->pvptotal * $linea->iva/100);
                     }
                     else
                     {
                        $this->new_error_msg("¡Imposible guardar la linea con referencia: ".$linea->referencia);
                        $continuar = FALSE;
                     }
                  }
                  else
                  {
                     $this->new_error_msg("Artículo no encontrado: ".$_POST['referencia_'.$i]);
                     $continuar = FALSE;
                  }
               }
            }
            
            if($continuar)
            {
               /// redondeamos
               $presupuesto->neto = round($presupuesto->neto, 2);
               $presupuesto->totaliva = round($presupuesto->totaliva, 2);
               $presupuesto->total = $presupuesto->neto + $presupuesto->totaliva;
               
               if( $presupuesto->save() )
               {
                  $this->new_message("<a href='".$presupuesto->url()."'>Presupuesto</a> guardado correctamente.");
                  $this->new_change('Presupuesto Proveedor '.$presupuesto->codigo, $presupuesto->url(), TRUE);
               }
               else
                  $this->new_error_msg("¡Imposible actualizar el <a href='".$presupuesto->url()."'>presupuesto</a>!");
            }
            else if( $presupuesto->delete() )
            {
               $this->new_message("Presupuesto eliminado correctamente.");
            }
            else
               $this->new_error_msg("¡Imposible eliminar el <a href='".$presupuesto->url()."'>presupuesto</a>!");
         }
         else
            $this->new_error_msg("¡Imposible guardar el presupuesto!");
      }
   }
   
   private function get_precios_articulo()
   {
      /// cambiamos la plantilla HTML
      $this->template = 'ajax/general_nuevo_presupuesto_precios';
      $this->articulo = $this->articulo->get($_POST['referencia4precios']);
   }
}
