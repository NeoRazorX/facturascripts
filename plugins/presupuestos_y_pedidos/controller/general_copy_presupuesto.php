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

require_model('presupuesto_cliente.php');
require_model('presupuesto_proveedor.php');
require_model('almacen.php');
require_model('articulo.php');
require_model('cliente.php');
require_model('divisa.php');
require_model('ejercicio.php');
require_model('forma_pago.php');
require_model('impuesto.php');
require_model('proveedor.php');
require_model('serie.php');

class general_copy_presupuesto extends fs_controller
{
   public $presupuesto;
   public $almacen;
   public $cliente;
   public $divisa;
   public $ejercicio;
   public $forma_pago;
   public $proveedor;
   public $serie;
   public $tipo_presupuesto;
   
   public function __construct()
   {
      parent::__construct('general_copy_presupuesto', 'Copiar presupuesto', 'general', FALSE, FALSE);
   }
   
   protected function process()
   {
      $presupuesto_cliente = new presupuesto_cliente();
      $presupuesto_proveedor = new presupuesto_proveedor();
      $this->almacen = new almacen();
      $this->cliente = new cliente();
      $this->divisa = new divisa();
      $this->ejercicio = new ejercicio();
      $this->forma_pago = new forma_pago();
      $this->proveedor = new proveedor();
      $this->serie = new serie();
      
      if( isset($_GET['idprecli']) )
      {
         $this->presupuesto = $presupuesto_cliente->get($_GET['idprecli']);
         $this->tipo_presupuesto = 'cliente';
      }
      else if( isset($_GET['idprepro']) )
      {
         $this->presupuesto = $presupuesto_proveedor->get($_GET['idprepro']);
         $this->tipo_presupuesto = 'proveedor';
      }
      else
      {
         $this->presupuesto = FALSE;
         $this->tipo_presupuesto = 'cliente';
         $this->new_error_msg('Ningún presupuesto seleccionado.');
      }
      
      if($this->presupuesto)
      {
         if($this->tipo_presupuesto == 'cliente')
            $this->ppage = $this->page->get('general_presupuesto_cli');
         else
            $this->ppage = $this->page->get('general_presupuesto_prov');
         
         if($this->ppage)
         {
            $this->ppage->title = $this->presupuesto->codigo;
            $this->ppage->extra_url = '&id='.$this->presupuesto->idpresupuesto;
         }
         
         if( isset($_POST['tipo']) )
         {
            if($_POST['tipo'] == 'cliente')
               $this->nuevo_presupuesto_cliente();
            else
               $this->nuevo_presupuesto_proveedor();
         }
      }
   }
   
   public function url()
   {
      if( !isset($this->presupuesto) )
         return parent::url();
      else if($this->presupuesto)
      {
         if($this->tipo_presupuesto == 'cliente')
            return $this->page->url().'&idprecli='.$this->presupuesto->idpresupuesto;
         else
            return $this->page->url().'&idprepro='.$this->presupuesto->idpresupuesto;
      }
      else
         return $this->page->url();
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
         $presupuesto->codagente = $this->user->codagente;
         $presupuesto->observaciones = $_POST['observaciones'];
         
         foreach($cliente->get_direcciones() as $d)
         {
            if($d->dompedidocion)
            {
               $presupuesto->codcliente = $cliente->codcliente;
               $presupuesto->cifnif = $cliente->cifnif;
               $presupuesto->nombrecliente = $cliente->nombre;
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
            $this->new_error_msg("No hay ninguna dirección asociada al cliente.");
         else if( $presupuesto->save() )
         {
            $articulo = new articulo();
            $impuesto = new impuesto();
            
            foreach($this->presupuesto->get_lineas() as $lin)
            {
               $art0 = $articulo->get($lin->referencia);
               if($art0)
               {
                  $linea = new linea_presupuesto_cliente();
                  $linea->idpresupuesto = $presupuesto->idpresupuesto;
                  $linea->referencia = $lin->referencia;
                  $linea->descripcion = $lin->descripcion;
                  
                  if( $serie->siniva )
                  {
                     $linea->codimpuesto = NULL;
                     $linea->iva = 0;
                  }
                  else
                  {
                     $imp0 = $impuesto->get_by_iva($lin->iva);
                     if($imp0)
                     {
                        $linea->codimpuesto = $imp0->codimpuesto;
                        $linea->iva = $imp0->iva;
                     }
                     else
                     {
                        $linea->codimpuesto = NULL;
                        $linea->iva = $lin->iva;
                     }
                  }
                  
                  $linea->pvpunitario = $lin->pvpunitario;
                  $linea->cantidad = $lin->cantidad;
                  $linea->dtopor = $lin->dtopor;
                  $linea->pvpsindto = $lin->pvpsindto;
                  $linea->pvptotal = $lin->pvptotal;
                  
                  if( $linea->save() )
                  {
                     /// descontamos del stock
                     $art0->sum_stock($presupuesto->codalmacen, 0 - $linea->cantidad);
                     
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
                  $this->new_error_msg("Artículo no encontrado: ".$lin->referencia);
                  $continuar = FALSE;
               }
            }
            
            if( $continuar )
            {
               /// redondeamos
               $presupuesto->neto = round($presupuesto->neto, 2);
               $presupuesto->totaliva = round($presupuesto->totaliva, 2);
               $presupuesto->total = $presupuesto->neto + $presupuesto->totaliva;
               
               if( $presupuesto->save() )
                  $this->new_message("<a href='".$presupuesto->url()."'>presupuesto</a> guardado correctamente.");
               else
                  $this->new_error_msg("¡Imposible actualizar el <a href='".$presupuesto->url()."'>presupuesto</a>!");
            }
            else if( $presupuesto->delete() )
               $this->new_message("Presupuesto eliminado correctamente.");
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
         $presupuesto->nombre = $proveedor->nombre;
         $presupuesto->cifnif = $proveedor->cifnif;
         $presupuesto->codalmacen = $almacen->codalmacen;
         $presupuesto->codejercicio = $ejercicio->codejercicio;
         $presupuesto->codserie = $serie->codserie;
         $presupuesto->codpago = $forma_pago->codpago;
         $presupuesto->coddivisa = $divisa->coddivisa;
         $presupuesto->tasaconv = $divisa->tasaconv;
         $presupuesto->codagente = $this->user->codagente;
         $presupuesto->observaciones = $_POST['observaciones'];
         
         if( $presupuesto->save() )
         {
            foreach($this->presupuesto->get_lineas() as $lin)
            {
               $articulo = new articulo();
               $impuesto = new impuesto();
               
               $art0 = $articulo->get($lin->referencia);
               if($art0)
               {
                  $linea = new linea_presupuesto_proveedor();
                  $linea->idpresupuesto = $presupuesto->idpresupuesto;
                  $linea->referencia = $art0->referencia;
                  $linea->descripcion = $lin->descripcion;
                  
                  if( $serie->siniva )
                  {
                     $linea->codimpuesto = NULL;
                     $linea->iva = 0;
                  }
                  else
                  {
                     $imp0 = $impuesto->get_by_iva($lin->iva);
                     if($imp0)
                     {
                        $linea->codimpuesto = $imp0->codimpuesto;
                        $linea->iva = $imp0->iva;
                     }
                     else
                     {
                        $linea->codimpuesto = NULL;
                        $linea->iva = $lin->iva;
                     }
                  }
                  
                  $linea->pvpunitario = $lin->pvpunitario;
                  $linea->cantidad = $lin->cantidad;
                  $linea->dtopor = $lin->dtopor;
                  $linea->pvpsindto = $lin->pvpsindto;
                  $linea->pvptotal = $lin->pvptotal;
                  
                  if( $linea->save() )
                  {
                     /// sumamos al stock
                     $art0->sum_stock($presupuesto->codalmacen, $linea->cantidad);
                     
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
                  $this->new_error_msg("Artículo no encontrado: ".$lin->referencia);
                  $continuar = FALSE;
               }
            }
            
            if($continuar)
            {
               /// redondeamos
               $presupuesto->neto = round($presupuesto->neto, 2);
               $presupuesto->totaliva = round($presupuesto->totaliva, 2);
               $presupuesto->total = $presupuesto->neto + $presupuesto->totaliva;
               
               if( $presupuesto->save() )
                  $this->new_message("<a href='".$presupuesto->url()."'>presupuesto</a> guardado correctamente.");
               else
                  $this->new_error_msg("¡Imposible actualizar el <a href='".$presupuesto->url()."'>presupuesto</a>!");
            }
            else if( $presupuesto->delete() )
               $this->new_message("Presupuesto eliminado correctamente.");
            else
               $this->new_error_msg("¡Imposible eliminar el <a href='".$presupuesto->url()."'>presupuesto</a>!");
         }
         else
            $this->new_error_msg("¡Imposible guardar el presupuesto!");
      }
   }
}

?>
