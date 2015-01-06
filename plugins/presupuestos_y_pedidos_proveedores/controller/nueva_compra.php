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

require_model('almacen.php');
require_model('asiento_factura.php');
require_model('forma_pago.php');

class nueva_compra extends fs_controller
{
   public $agente;
   public $almacen;
   public $articulo;
   public $divisa;
   public $familia;
   public $forma_pago;
   public $impuesto;
   public $proveedor;
   public $proveedor_s;
   public $results;
   public $serie;
   public $tipo;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'nueva compra', 'compras', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->show_fs_toolbar = FALSE;
      
      $this->proveedor = new proveedor();
      $this->proveedor_s = FALSE;
      $this->familia = new familia();
      $this->impuesto = new impuesto();
      $this->results = array();
      
      if( isset($_REQUEST['tipo']) )
      {
         $this->tipo = $_REQUEST['tipo'];
      }
      else
      {
         foreach($this->tipos_a_guardar() as $t)
         {
            $this->tipo = $t['tipo'];
            break;
         }
      }
      
      if( isset($_REQUEST['buscar_proveedor']) )
      {
         $this->buscar_proveedor();
      }
      else if( isset($_REQUEST['datosproveedor']) )
      {
         $this->datos_proveedor();
      }
      else if( isset($_GET['new_articulo']) )
      {
         $this->new_articulo();
      }
      else if( $this->query != '' )
      {
         $this->new_search();
      }
      else if( isset($_POST['referencia4precios']) )
      {
         $this->get_precios_articulo();
      }
      else if( isset($_POST['proveedor']) )
      {
         $this->proveedor_s = $this->proveedor->get($_POST['proveedor']);
         
         if( isset($_POST['codagente']) )
         {
            $agente = new agente();
            $this->agente = $agente->get($_POST['codagente']);
         }
         else
            $this->agente = $this->user->get_agente();
         
         $this->almacen = new almacen();
         $this->serie = new serie();
         $this->forma_pago = new forma_pago();
         $this->divisa = new divisa();
         
         if( isset($_POST['tipo']) )
         {
            if($_POST['tipo'] == 'presupuesto')
            {
               $this->nuevo_presupuesto_proveedor();
            }
            else if($_POST['tipo'] == 'pedido')
            {
               $this->nuevo_pedido_proveedor();
            }            
            else if($_POST['tipo'] == 'albaran')
            {
               $this->nuevo_albaran_proveedor();
            }
            else if($_POST['tipo'] == 'factura')
            {
               $this->nueva_factura_proveedor();
            }
         }
      }
   }
   
   /**
    * Devuelve los tipos de documentos a guardar,
    * así para añadir tipos no hay que tocar la vista.
    * @return type
    */
   public function tipos_a_guardar()
   {
      $tipos = array();
      
      if( $this->user->have_access_to('compras_presupuesto') )
         $tipos[] = array('tipo' => 'presupuesto', 'nombre' => ucfirst(FS_PRESUPUESTO).' de proveedor');
      
      if( $this->user->have_access_to('compras_pedido') )
         $tipos[] = array('tipo' => 'pedido', 'nombre' => ucfirst(FS_PEDIDO).' a proveedor');

      if( $this->user->have_access_to('compras_albaran') )
         $tipos[] = array('tipo' => 'albaran', 'nombre' => ucfirst(FS_ALBARAN).' de proveedor');
      
      if( $this->user->have_access_to('compras_factura') )
         $tipos[] = array('tipo' => 'factura', 'nombre' => 'Factura de proveedor');
      
      return $tipos;
   }
   
   public function url()
   {
      return 'index.php?page='.__CLASS__.'&tipo='.$this->tipo;
   }
   
   private function buscar_proveedor()
   {
      /// desactivamos la plantilla HTML
      $this->template = FALSE;
      
      $json = array();
      foreach($this->proveedor->search($_REQUEST['buscar_proveedor']) as $pro)
      {
         $json[] = array('value' => $pro->nombre, 'data' => $pro->codproveedor);
      }
      
      header('Content-Type: application/json');
      echo json_encode( array('query' => $_REQUEST['buscar_proveedor'], 'suggestions' => $json) );
   }
   
   private function datos_proveedor()
   {
      /// desactivamos la plantilla HTML
      $this->template = FALSE;
      
      $json = array();
      foreach($this->proveedor->get($_REQUEST['datosproveedor']) as $pro)
      {
         $json[] = $pro;
      }
      
      header('Content-Type: application/json');
      echo json_encode($json);
   }
   
   private function new_articulo()
   {
      /// desactivamos la plantilla HTML
      $this->template = FALSE;
      
      $art0 = new articulo();
      $art0->referencia = $_POST['referencia'];
      if( $art0->exists() )
      {
         $this->results[] = $art0->get($_POST['referencia']);
      }
      else
      {
         $art0->descripcion = $_POST['descripcion'];
         $art0->codfamilia = $_POST['codfamilia'];
         $art0->set_impuesto($_POST['codimpuesto']);
         
         if( $art0->save() )
         {
            $this->results[] = $art0;
         }
      }
      
      header('Content-Type: application/json');
      echo json_encode($this->results);
   }
   
   private function new_search()
   {
      /// desactivamos la plantilla HTML
      $this->template = FALSE;
      
      $articulo = new articulo();
      $codfamilia = '';
      if( isset($_REQUEST['codfamilia']) )
      {
         $codfamilia = $_REQUEST['codfamilia'];
      }
      
      $con_stock = isset($_REQUEST['con_stock']);
      $this->results = $articulo->search($this->query, 0, $codfamilia, $con_stock);
      
      /// añadimos la busqueda
      foreach($this->results as $i => $value)
      {
         $this->results[$i]->query = $this->query;
         $this->results[$i]->dtopor = 0;
      }
      
      /// buscamos el grupo de proveedores y la tarifa
      if( isset($_REQUEST['codproveedor']) )
      {
         $proveedor = $this->proveedor->get($_REQUEST['codproveedor']);
         if($proveedor->codgrupo)
         {
            $grupo0 = new grupo_proveedores();
            $grupo = $grupo0->get($proveedor->codgrupo);
            if($grupo)
            {
               $tarifa0 = new tarifa();
               $tarifa = $tarifa0->get($grupo->codtarifa);
               if($tarifa)
               {
                  foreach($this->results as $i => $value)
                  {
                     $this->results[$i]->dtopor = 0 - $tarifa->incporcentual;
                  }
               }
            }
         }
      }
      
      header('Content-Type: application/json');
      echo json_encode($this->results);
   }
   
   private function get_precios_articulo()
   {
      /// cambiamos la plantilla HTML
      $this->template = 'ajax/nueva_compra_precios';
      
      $articulo = new articulo();
      $this->articulo = $articulo->get($_POST['referencia4precios']);
   }
   
   private function nuevo_albaran_proveedor()
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
      
      $eje0 = new ejercicio();
      $ejercicio = $eje0->get_by_fecha($_POST['fecha']);
      if( $ejercicio )
         $this->save_codejercicio( $ejercicio->codejercicio );
      else
      {
         $this->new_error_msg('Ejercicio no encontrado.');
         $continuar = FALSE;
      }
      
      $serie = $this->serie->get($_POST['serie']);
      if( !$serie )
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
      
      $albaran = new albaran_proveedor();
      
      if( $this->duplicated_petition($_POST['petition_id']) )
      {
         $this->new_error_msg('Petición duplicada. Has hecho doble clic sobre el botón guardar
               y se han enviado dos peticiones. Mira en <a href="'.$albaran->url().'">'.FS_ALBARANES.'</a>
               para ver si el '.FS_ALBARAN.' se ha guardado correctamente.');
         $continuar = FALSE;
      }
      
      if( $continuar )
      {
         $albaran->fecha = $_POST['fecha'];
         $albaran->hora = $_POST['hora'];
         $albaran->codproveedor = $proveedor->codproveedor;
         $albaran->nombre = $proveedor->nombrecomercial;
         $albaran->cifnif = $proveedor->cifnif;
         $albaran->codalmacen = $almacen->codalmacen;
         $albaran->codejercicio = $ejercicio->codejercicio;
         $albaran->codserie = $serie->codserie;
         $albaran->codpago = $forma_pago->codpago;
         $albaran->coddivisa = $divisa->coddivisa;
         $albaran->tasaconv = $divisa->tasaconv;
         $albaran->codagente = $this->agente->codagente;
         $albaran->numproveedor = $_POST['numproveedor'];
         $albaran->observaciones = $_POST['observaciones'];
         $albaran->irpf = $serie->irpf;
         
         if( $albaran->save() )
         {
            $art0 = new articulo();
            $n = floatval($_POST['numlineas']);
            for($i = 0; $i < $n; $i++)
            {
               if( isset($_POST['referencia_'.$i]) )
               {
                  $articulo = $art0->get($_POST['referencia_'.$i]);
                  if($articulo)
                  {
                     $linea = new linea_albaran_proveedor();
                     $linea->idalbaran = $albaran->idalbaran;
                     $linea->referencia = $articulo->referencia;
                     $linea->descripcion = $_POST['desc_'.$i];
                     $linea->irpf = $albaran->irpf;
                     
                     if( !$serie->siniva AND $proveedor->regimeniva != 'Exento' )
                     {
                        $imp0 = $this->impuesto->get_by_iva($_POST['iva_'.$i]);
                        if($imp0)
                        {
                           $linea->codimpuesto = $imp0->codimpuesto;
                           $linea->iva = floatval($_POST['iva_'.$i]);
                           $linea->recargo = floatval($_POST['recargo_'.$i]);
                        }
                        else
                        {
                           $linea->iva = floatval($_POST['iva_'.$i]);
                           $linea->recargo = floatval($_POST['recargo_'.$i]);
                        }
                     }
                     
                     $linea->pvpunitario = floatval($_POST['pvp_'.$i]);
                     $linea->cantidad = floatval($_POST['cantidad_'.$i]);
                     $linea->dtopor = floatval($_POST['dto_'.$i]);
                     $linea->pvpsindto = ($linea->pvpunitario * $linea->cantidad);
                     $linea->pvptotal = floatval($_POST['neto_'.$i]);
                     
                     if( $linea->save() )
                     {
                        /// sumamos al stock
                        $articulo->sum_stock($albaran->codalmacen, $linea->cantidad);
                        
                        $albaran->neto += $linea->pvptotal;
                        $albaran->totaliva += ($linea->pvptotal * $linea->iva/100);
                        $albaran->totalirpf += ($linea->pvptotal * $linea->irpf/100);
                        $albaran->totalrecargo += ($linea->pvptotal * $linea->recargo/100);
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
               $albaran->neto = round($albaran->neto, FS_NF0);
               $albaran->totaliva = round($albaran->totaliva, FS_NF0);
               $albaran->totalirpf = round($albaran->totalirpf, FS_NF0);
               $albaran->totalrecargo = round($albaran->totalrecargo, FS_NF0);
               $albaran->total = $albaran->neto + $albaran->totaliva - $albaran->totalirpf + $albaran->totalrecargo;
               
               if( abs(floatval($_POST['atotal']) - $albaran->total) > .01 )
               {
                  $this->new_error_msg("El total difiere entre la vista y el controlador (".
                          $_POST['atotal']." frente a ".$albaran->total."). Debes informar del error.");
                  $albaran->delete();
               }
               else if( $albaran->save() )
               {
                  $this->new_message("<a href='".$albaran->url()."'>".ucfirst(FS_ALBARAN)."</a> guardado correctamente.");
                  $this->new_change(ucfirst(FS_ALBARAN).' Proveedor '.$albaran->codigo, $albaran->url(), TRUE);
               }
               else
                  $this->new_error_msg("¡Imposible actualizar el <a href='".$albaran->url()."'>".FS_ALBARAN."</a>!");
            }
            else if( $albaran->delete() )
            {
               $this->new_message(FS_ALBARAN." eliminado correctamente.");
            }
            else
               $this->new_error_msg("¡Imposible eliminar el <a href='".$albaran->url()."'>".FS_ALBARAN."</a>!");
         }
         else
            $this->new_error_msg("¡Imposible guardar el ".FS_ALBARAN."!");
      }
   }
   
   private function nueva_factura_proveedor()
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
      
      $eje0 = new ejercicio();
      $ejercicio = $eje0->get_by_fecha($_POST['fecha']);
      if( $ejercicio )
         $this->save_codejercicio( $ejercicio->codejercicio );
      else
      {
         $this->new_error_msg('Ejercicio no encontrado.');
         $continuar = FALSE;
      }
      
      $serie = $this->serie->get($_POST['serie']);
      if( !$serie )
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
      
      $factura = new factura_proveedor();
      
      if( $this->duplicated_petition($_POST['petition_id']) )
      {
         $this->new_error_msg('Petición duplicada. Has hecho doble clic sobre el botón guardar
               y se han enviado dos peticiones. Mira en <a href="'.$factura->url().'">Facturas</a>
               para ver si la factura se ha guardado correctamente.');
         $continuar = FALSE;
      }
      
      if( $continuar )
      {
         $factura->fecha = $_POST['fecha'];
         $factura->hora = $_POST['hora'];
         $factura->codproveedor = $proveedor->codproveedor;
         $factura->nombre = $proveedor->nombrecomercial;
         $factura->cifnif = $proveedor->cifnif;
         $factura->codalmacen = $almacen->codalmacen;
         $factura->codejercicio = $ejercicio->codejercicio;
         $factura->codserie = $serie->codserie;
         $factura->codpago = $forma_pago->codpago;
         $factura->coddivisa = $divisa->coddivisa;
         $factura->tasaconv = $divisa->tasaconv;
         $factura->codagente = $this->agente->codagente;
         $factura->numproveedor = $_POST['numproveedor'];
         $factura->observaciones = $_POST['observaciones'];
         $factura->irpf = $serie->irpf;
         
         if($forma_pago->genrecibos == 'Pagados')
         {
            $factura->pagada = TRUE;
         }
         
         foreach($proveedor->get_direcciones() as $d)
         {
            if($d->domfacturacion)
            {
               $factura->codproveedor = $proveedor->codproveedor;
               $factura->cifnif = $proveedor->cifnif;
               $factura->nombre = $proveedor->nombrecomercial;
               $factura->apartado = $d->apartado;
               $factura->ciudad = $d->ciudad;
               $factura->coddir = $d->id;
               $factura->codpais = $d->codpais;
               $factura->codpostal = $d->codpostal;
               $factura->direccion = $d->direccion;
               $factura->provincia = $d->provincia;
               break;
            }
         }
         
         if( is_null($factura->codproveedor) )
         {
            $this->new_error_msg("No hay ninguna dirección asociada al proveedor.");
         }
         else if( $factura->save() )
         {
            $art0 = new articulo();
            $n = floatval($_POST['numlineas']);
            for($i = 0; $i < $n; $i++)
            {
               if( isset($_POST['referencia_'.$i]) )
               {
                  $articulo = $art0->get($_POST['referencia_'.$i]);
                  if($articulo)
                  {
                     $linea = new linea_factura_proveedor();
                     $linea->idfactura = $factura->idfactura;
                     $linea->referencia = $articulo->referencia;
                     $linea->descripcion = $_POST['desc_'.$i];
                     $linea->irpf = $factura->irpf;
                     
                     if( !$serie->siniva AND $proveedor->regimeniva != 'Exento' )
                     {
                        $imp0 = $this->impuesto->get_by_iva($_POST['iva_'.$i]);
                        if($imp0)
                        {
                           $linea->codimpuesto = $imp0->codimpuesto;
                           $linea->iva = floatval($_POST['iva_'.$i]);
                           $linea->recargo = floatval($_POST['recargo_'.$i]);
                        }
                        else
                        {
                           $linea->iva = floatval($_POST['iva_'.$i]);
                           $linea->recargo = floatval($_POST['recargo_'.$i]);
                        }
                     }
                     
                     $linea->pvpunitario = floatval($_POST['pvp_'.$i]);
                     $linea->cantidad = floatval($_POST['cantidad_'.$i]);
                     $linea->dtopor = floatval($_POST['dto_'.$i]);
                     $linea->pvpsindto = ($linea->pvpunitario * $linea->cantidad);
                     $linea->pvptotal = floatval($_POST['neto_'.$i]);
                     
                     if( $linea->save() )
                     {
                        /// sumamos al stock
                        $articulo->sum_stock($factura->codalmacen, $linea->cantidad);
                        
                        if($articulo->costemedio == 0)
                        {
                           $articulo->costemedio = $linea->pvptotal/$linea->cantidad;
                           $articulo->save();
                        }
                        
                        $factura->neto += $linea->pvptotal;
                        $factura->totaliva += ($linea->pvptotal * $linea->iva/100);
                        $factura->totalirpf += ($linea->pvptotal * $linea->irpf/100);
                        $factura->totalrecargo += ($linea->pvptotal * $linea->recargo/100);
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
               $factura->neto = round($factura->neto, FS_NF0);
               $factura->totaliva = round($factura->totaliva, FS_NF0);
               $factura->totalirpf = round($factura->totalirpf, FS_NF0);
               $factura->totalrecargo = round($factura->totalrecargo, FS_NF0);
               $factura->total = $factura->neto + $factura->totaliva - $factura->totalirpf + $factura->totalrecargo;
               
               if( abs(floatval($_POST['atotal']) - $factura->total) > .01 )
               {
                  $this->new_error_msg("El total difiere entre el controlador y la vista (".
                          $factura->total." frente a ".$_POST['atotal']."). Debes informar del error.");
                  $factura->delete();
               }
               else if( $factura->save() )
               {
                  $this->generar_asiento($factura);
                  $this->new_message("<a href='".$factura->url()."'>Factura</a> guardada correctamente.");
                  $this->new_change('Factura Proveedor '.$factura->codigo, $factura->url(), TRUE);
               }
               else
                  $this->new_error_msg("¡Imposible actualizar la <a href='".$factura->url()."'>factura</a>!");
            }
            else if( $factura->delete() )
            {
               $this->new_message("Factura eliminada correctamente.");
            }
            else
               $this->new_error_msg("¡Imposible eliminar la <a href='".$factura->url()."'>factura</a>!");
         }
         else
            $this->new_error_msg("¡Imposible guardar la factura!");
      }
   }
   
   private function generar_asiento($factura)
   {
      if($this->empresa->contintegrada)
      {
         $asiento_factura = new asiento_factura();
         $asiento_factura->generar_asiento_compra($factura);
         
         foreach($asiento_factura->errors as $err)
         {
            $this->new_error_msg($err);
         }
         
         foreach($asiento_factura->messages as $msg)
         {
            $this->new_message($msg);
         }
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
      
      $eje0 = new ejercicio();
      $ejercicio = $eje0->get_by_fecha($_POST['fecha']);
      if( $ejercicio )
         $this->save_codejercicio( $ejercicio->codejercicio );
      else
      {
         $this->new_error_msg('Ejercicio no encontrado.');
         $continuar = FALSE;
      }
      
      $serie = $this->serie->get($_POST['serie']);
      if( !$serie )
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
         $this->new_error_msg('Petición duplicada. Has hecho doble clic sobre el botón guardar
               y se han enviado dos peticiones. Mira en <a href="'.$presupuesto->url().'">Presupuestos</a>
               para ver si el presupuesto se ha guardado correctamente.');
         $continuar = FALSE;
      }
      
      if($continuar)
      {
         $presupuesto->fecha = $_POST['fecha'];
         $presupuesto->finoferta = date("Y-m-d", strtotime($_POST['fecha']." +30 days"));
         $presupuesto->codalmacen = $almacen->codalmacen;
         $presupuesto->codejercicio = $ejercicio->codejercicio;
         $presupuesto->codserie = $serie->codserie;
         $presupuesto->codpago = $forma_pago->codpago;
         $presupuesto->coddivisa = $divisa->coddivisa;
         $presupuesto->tasaconv = $divisa->tasaconv;
         $presupuesto->codagente = $this->agente->codagente;
         $presupuesto->observaciones = $_POST['observaciones'];
         $presupuesto->numero2 = $_POST['numero2'];
         $presupuesto->irpf = $serie->irpf;
         $presupuesto->porcomision = $this->agente->porcomision;
         
         foreach($proveedor->get_direcciones() as $d)
         {
            if($d->domfacturacion)
            {
               $presupuesto->codproveedor = $proveedor->codproveedor;
               $presupuesto->cifnif = $proveedor->cifnif;
               $presupuesto->nombre = $proveedor->nombrecomercial;
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
         
         if( is_null($presupuesto->codproveedor) )
         {
            $this->new_error_msg("No hay ninguna dirección asociada al proveedor.");
         }
         else if( $presupuesto->save() )
         {
            $art0 = new articulo();
            $n = floatval($_POST['numlineas']);
            for($i = 0; $i <= $n; $i++)
            {
               if( isset($_POST['referencia_'.$i]) )
               {
                  $articulo = $art0->get($_POST['referencia_'.$i]);
                  if($articulo)
                  {
                     $linea = new linea_presupuesto_proveedor();
                     $linea->idpresupuesto = $presupuesto->idpresupuesto;
                     $linea->referencia = $articulo->referencia;
                     $linea->descripcion = $_POST['desc_'.$i];
                     $linea->irpf = $presupuesto->irpf;
                     
                     if( !$serie->siniva AND $proveedor->regimeniva != 'Exento' )
                     {
                        $imp0 = $this->impuesto->get_by_iva($_POST['iva_'.$i]);
                        if($imp0)
                        {
                           $linea->codimpuesto = $imp0->codimpuesto;
                           $linea->iva = floatval($_POST['iva_'.$i]);
                           $linea->recargo = floatval($_POST['recargo_'.$i]);
                        }
                        else
                        {
                           $linea->iva = floatval($_POST['iva_'.$i]);
                           $linea->recargo = floatval($_POST['recargo_'.$i]);
                        }
                     }
                     
                     $linea->pvpunitario = floatval($_POST['pvp_'.$i]);
                     $linea->cantidad = floatval($_POST['cantidad_'.$i]);
                     $linea->dtopor = floatval($_POST['dto_'.$i]);
                     $linea->pvpsindto = ($linea->pvpunitario * $linea->cantidad);
                     $linea->pvptotal = floatval($_POST['neto_'.$i]);
                     
                     if( $linea->save() )
                     {
                        $presupuesto->neto += $linea->pvptotal;
                        $presupuesto->totaliva += ($linea->pvptotal * $linea->iva/100);
                        $presupuesto->totalirpf += ($linea->pvptotal * $linea->irpf/100);
                        $presupuesto->totalrecargo += ($linea->pvptotal * $linea->recargo/100);
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
               $presupuesto->neto = round($presupuesto->neto, FS_NF0);
               $presupuesto->totaliva = round($presupuesto->totaliva, FS_NF0);
               $presupuesto->totalirpf = round($presupuesto->totalirpf, FS_NF0);
               $presupuesto->totalrecargo = round($presupuesto->totalrecargo, FS_NF0);
               $presupuesto->total = $presupuesto->neto + $presupuesto->totaliva - $presupuesto->totalirpf + $presupuesto->totalrecargo;
               
               if( abs(floatval($_POST['atotal']) - $presupuesto->total) > .01 )
               {
                  $this->new_error_msg("El total difiere entre el controlador y la vista (".
                          $presupuesto->total." frente a ".$_POST['atotal']."). Debes informar del error.");
                  $presupuesto->delete();
               }
               else if( $presupuesto->save() )
               {
                  $this->new_message("<a href='".$presupuesto->url()."'>".ucfirst(FS_PRESUPUESTO)."</a> guardado correctamente.");
                  $this->new_change(ucfirst(FS_PRESUPUESTO).' a Proveedor '.$presupuesto->codigo, $presupuesto->url(), TRUE);
               }
               else
                  $this->new_error_msg("¡Imposible actualizar el <a href='".$presupuesto->url()."'>".FS_PRESUPUESTO."</a>!");
            }
            else if( $presupuesto->delete() )
            {
               $this->new_message(ucfirst(FS_PRESUPUESTO)." eliminado correctamente.");
            }
            else
               $this->new_error_msg("¡Imposible eliminar el <a href='".$presupuesto->url()."'>".FS_PRESUPUESTO."</a>!");
         }
         else
            $this->new_error_msg("¡Imposible guardar el ".FS_PRESUPUESTO."!");
      }
   }
   
   private function nuevo_pedido_proveedor()
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
      
      $eje0 = new ejercicio();
      $ejercicio = $eje0->get_by_fecha($_POST['fecha']);
      if( $ejercicio )
         $this->save_codejercicio( $ejercicio->codejercicio );
      else
      {
         $this->new_error_msg('Ejercicio no encontrado.');
         $continuar = FALSE;
      }
      
      $serie = $this->serie->get($_POST['serie']);
      if( !$serie )
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
      
      $pedido = new pedido_proveedor();
      
      if( $this->duplicated_petition($_POST['petition_id']) )
      {
         $this->new_error_msg('Petición duplicada. Has hecho doble clic sobre el botón guardar
               y se han enviado dos peticiones. Mira en <a href="'.$pedido->url().'">Pedidos</a>
               para ver si el pedido se ha guardado correctamente.');
         $continuar = FALSE;
      }
      
      if($continuar)
      {
         $pedido->fecha = $_POST['fecha'];
         $pedido->codalmacen = $almacen->codalmacen;
         $pedido->codejercicio = $ejercicio->codejercicio;
         $pedido->codserie = $serie->codserie;
         $pedido->codpago = $forma_pago->codpago;
         $pedido->coddivisa = $divisa->coddivisa;
         $pedido->tasaconv = $divisa->tasaconv;
         $pedido->codagente = $this->agente->codagente;
         $pedido->observaciones = $_POST['observaciones'];
         $pedido->numero2 = $_POST['numero2'];
         $pedido->irpf = $serie->irpf;
         $pedido->porcomision = $this->agente->porcomision;
         
         foreach($proveedor->get_direcciones() as $d)
         {
            if($d->domfacturacion)
            {
               $pedido->codproveedor = $proveedor->codproveedor;
               $pedido->cifnif = $proveedor->cifnif;
               $pedido->nombre = $proveedor->nombrecomercial;
               $pedido->apartado = $d->apartado;
               $pedido->ciudad = $d->ciudad;
               $pedido->coddir = $d->id;
               $pedido->codpais = $d->codpais;
               $pedido->codpostal = $d->codpostal;
               $pedido->direccion = $d->direccion;
               $pedido->provincia = $d->provincia;
               break;
            }
         }
         
         if( is_null($pedido->codproveedor) )
         {
            $this->new_error_msg("No hay ninguna dirección asociada al proveedor.");
         }
         else if( $pedido->save() )
         {
            $art0 = new articulo();
            $n = floatval($_POST['numlineas']);
            for($i = 0; $i <= $n; $i++)
            {
               if( isset($_POST['referencia_'.$i]) )
               {
                  $articulo = $art0->get($_POST['referencia_'.$i]);
                  if($articulo)
                  {
                     $linea = new linea_pedido_proveedor();
                     $linea->idpedido = $pedido->idpedido;
                     $linea->referencia = $articulo->referencia;
                     $linea->descripcion = $_POST['desc_'.$i];
                     $linea->irpf = $pedido->irpf;
                     
                     if( !$serie->siniva AND $proveedor->regimeniva != 'Exento' )
                     {
                        $imp0 = $this->impuesto->get_by_iva($_POST['iva_'.$i]);
                        if($imp0)
                        {
                           $linea->codimpuesto = $imp0->codimpuesto;
                           $linea->iva = floatval($_POST['iva_'.$i]);
                           $linea->recargo = floatval($_POST['recargo_'.$i]);
                        }
                        else
                        {
                           $linea->iva = floatval($_POST['iva_'.$i]);
                           $linea->recargo = floatval($_POST['recargo_'.$i]);
                        }
                     }
                     
                     $linea->pvpunitario = floatval($_POST['pvp_'.$i]);
                     $linea->cantidad = floatval($_POST['cantidad_'.$i]);
                     $linea->dtopor = floatval($_POST['dto_'.$i]);
                     $linea->pvpsindto = ($linea->pvpunitario * $linea->cantidad);
                     $linea->pvptotal = floatval($_POST['neto_'.$i]);
                     
                     if( $linea->save() )
                     {
                        $pedido->neto += $linea->pvptotal;
                        $pedido->totaliva += ($linea->pvptotal * $linea->iva/100);
                        $pedido->totalirpf += ($linea->pvptotal * $linea->irpf/100);
                        $pedido->totalrecargo += ($linea->pvptotal * $linea->recargo/100);
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
               $pedido->neto = round($pedido->neto, FS_NF0);
               $pedido->totaliva = round($pedido->totaliva, FS_NF0);
               $pedido->totalirpf = round($pedido->totalirpf, FS_NF0);
               $pedido->totalrecargo = round($pedido->totalrecargo, FS_NF0);
               $pedido->total = $pedido->neto + $pedido->totaliva - $pedido->totalirpf + $pedido->totalrecargo;
               
               if( abs(floatval($_POST['atotal']) - $pedido->total) > .01 )
               {
                  $this->new_error_msg("El total difiere entre el controlador y la vista (".
                          $pedido->total." frente a ".$_POST['atotal']."). Debes informar del error.");
                  $pedido->delete();
               }
               else if( $pedido->save() )
               {
                  $this->new_message("<a href='".$pedido->url()."'>".ucfirst(FS_PEDIDO)."</a> guardado correctamente.");
                  $this->new_change(ucfirst(FS_PEDIDO)." a Proveedor ".$pedido->codigo, $pedido->url(), TRUE);
               }
               else
                  $this->new_error_msg("¡Imposible actualizar el <a href='".$pedido->url()."'>".FS_PEDIDO."</a>!");
            }
            else if( $pedido->delete() )
            {
               $this->new_message(ucfirst(FS_PEDIDO)." eliminado correctamente.");
            }
            else
               $this->new_error_msg("¡Imposible eliminar el <a href='".$pedido->url()."'>".FS_PEDIDO."</a>!");
         }
         else
            $this->new_error_msg("¡Imposible guardar el ".FS_PEDIDO."!");
      }
   }
}
