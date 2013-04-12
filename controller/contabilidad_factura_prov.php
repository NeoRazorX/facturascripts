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

require_once 'model/asiento.php';
require_once 'model/ejercicio.php';
require_once 'model/factura_proveedor.php';
require_once 'model/partida.php';
require_once 'model/proveedor.php';
require_once 'model/subcuenta.php';
require_once 'extras/ezpdf/Cezpdf.php';

class contabilidad_factura_prov extends fs_controller
{
   public $ejercicio;
   public $factura;
   
   public function __construct()
   {
      parent::__construct('contabilidad_factura_prov', 'Factura de proveedor', 'contabilidad', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->ppage = $this->page->get('contabilidad_facturas_prov');
      $this->ejercicio = new ejercicio();
      
      if( isset($_POST['idfactura']) )
      {
         $this->factura = new factura_proveedor();
         $this->factura = $this->factura->get($_POST['idfactura']);
         $this->factura->numproveedor = $_POST['numproveedor'];
         $this->factura->observaciones = $_POST['observaciones'];
         
         /// obtenemos el ejercicio para poder acotar la fecha
         $eje0 = $this->ejercicio->get( $this->factura->codejercicio );
         if( $eje0 )
            $this->factura->fecha = $eje0->get_best_fecha($_POST['fecha'], TRUE);
         else
            $this->new_error_msg('No se encuentra el ejercicio asociado a la factura.');
         
         if( $this->factura->save() )
         {
            $asiento = $this->factura->get_asiento();
            if($asiento)
            {
               $asiento->fecha = $_POST['fecha'];
               if( !$asiento->save() )
                  $this->new_error_msg("Imposible modificar la fecha del asiento.");
            }
            $this->new_message("Factura modificada correctamente.");
         }
         else
            $this->new_error_msg("¡Imposible modificar la factura!");
      }
      else if( isset($_GET['id']) )
      {
         $this->factura = new factura_proveedor();
         $this->factura = $this->factura->get($_GET['id']);
      }
      
      if($this->factura)
      {
         if( isset($_GET['imprimir']) )
            $this->generar_pdf();
         else
         {
            if( isset($_GET['gen_asiento']) )
               $this->generar_asiento();
            
            /// comprobamos la factura
            $this->factura->full_test();
            
            $this->page->title = $this->factura->codigo;
            $this->buttons[] = new fs_button('b_imprimir', 'imprimir', $this->url()."&imprimir=TRUE",
                    'button', 'img/print.png', '[]', TRUE);
            
            if($this->factura->idasiento)
               $this->buttons[] = new fs_button('b_ver_asiento', 'asiento',
                       $this->factura->asiento_url(), 'button', 'img/zoom.png');
            else
               $this->buttons[] = new fs_button('b_gen_asiento', 'generar asiento',
                       $this->url().'&gen_asiento=TRUE', 'button', 'img/tools.png');
            
            $this->buttons[] = new fs_button('b_eliminar', 'eliminar', '#', 'remove', 'img/remove.png');
         }
      }
      else
         $this->new_error_msg("¡Factura de proveedor no encontrada!");
   }
   
   public function version()
   {
      return parent::version().'-8';
   }
   
   public function url()
   {
      if($this->factura)
         return $this->factura->url();
      else
         return $this->page->url();
   }
   
   private function generar_pdf()
   {
      /// desactivamos la plantilla HTML
      $this->template = FALSE;
      
      $pdf = new Cezpdf('a4');
      
      /// cambiamos ! por el simbolo del euro
      $euro_diff = array(33 => 'Euro');
      $pdf->selectFont("extras/ezpdf/fonts/Helvetica.afm",
              array('encoding' => 'WinAnsiEncoding', 'differences' => $euro_diff));
      
      $pdf->addInfo('Title', 'Factura ' . $this->factura->codigo);
      $pdf->addInfo('Subject', 'Factura de cliente ' . $this->factura->codigo);
      $pdf->addInfo('Author', $this->empresa->nombre);
      
      $lineas = $this->factura->get_lineas();
      $lineas_iva = $this->factura->get_lineas_iva();
      if( $lineas )
      {
         $lineasfact = count($lineas);
         $linea_actual = 0;
         $lppag = 42;
         $pagina = 1;
         
         // Imprimimos las páginas necesarias
         while($linea_actual < $lineasfact)
         {
            /// salto de página
            if($linea_actual > 0)
               $pdf->ezNewPage();
            
            $pdf->ezText("<b>".$this->empresa->nombre."</b>", 16, array('justification' => 'center'));
            $pdf->ezText("CIF: ".$this->empresa->cifnif, 8, array('justification' => 'center'));
            
            $direccion = $this->empresa->direccion;
            if($this->empresa->codpostal)
               $direccion .= ' - ' . $this->empresa->codpostal;
            if($this->empresa->ciudad)
               $direccion .= ' - ' . $this->empresa->ciudad;
            if($this->empresa->provincia)
               $direccion .= ' (' . $this->empresa->provincia . ')';
            if($this->empresa->telefono)
               $direccion .= ' - Teléfono: ' . $this->empresa->telefono;
            if($this->empresa->email)
               $direccion .= ' - email: '.$this->empresa->email;
            
            $pdf->ezText($direccion, 9, array('justification' => 'center'));
            
            /// Creamos la tabla del encabezado
            $filas = array(
                array(
                    'campo1' => "<b>Factura:</b>",
                    'dato1' => $this->factura->codigo,
                    'campo2' => "<b>Proveedor:</b>",
                    'dato2' => $this->factura->nombre
                ),
                array(
                    'campo1' => "<b>Fecha:</b>",
                    'dato1' => $this->factura->fecha,
                    'campo2' => "<b>CIF/NIF:</b>",
                    'dato2' => $this->factura->cifnif
                )
            );
            $pdf->ezTable($filas,
                    array('campo1' => '', 'dato1' => '', 'campo2' => '', 'dato2' => ''),
                    '',
                    array(
                        'cols' => array(
                            'campo1' => array('justification' => 'right'),
                            'dato1' => array('justification' => 'left'),
                            'campo2' => array('justification' => 'right'),
                            'dato2' => array('justification' => 'left')
                        ),
                        'showLines' => 0,
                        'width' => 540,
                        'shaded' => 0
                    )
            );
            
            $pdf->ezText("\n", 10);
            
            /// Creamos la tabla con las lineas de la factura
            $saltos = 0;
            $filas = array();
            for($i = $linea_actual; (($linea_actual < ($lppag + $i)) AND ($linea_actual < $lineasfact));)
            {
               $filas[$linea_actual]['albaran'] = $lineas[$linea_actual]->albaran_numero();
               
               if($lineas[$linea_actual]->referencia != '0')
                  $filas[$linea_actual]['descripcion'] = substr($lineas[$linea_actual]->referencia." - ".$lineas[$linea_actual]->descripcion, 0, 40);
               else
                  $filas[$linea_actual]['descripcion'] = substr($lineas[$linea_actual]->descripcion, 0, 45);
               
               $filas[$linea_actual]['pvp'] = number_format($lineas[$linea_actual]->pvpunitario, 2) . " !";
               $filas[$linea_actual]['dto'] = number_format($lineas[$linea_actual]->dtopor, 0) . " %";
               $filas[$linea_actual]['cantidad'] = $lineas[$linea_actual]->cantidad;
               $filas[$linea_actual]['importe'] = number_format($lineas[$linea_actual]->pvptotal, 2) . " !";
               $saltos++;
               $linea_actual++;
            }
            $pdf->ezTable($filas,
                    array(
                        'albaran' => '<b>Albarán</b>',
                        'descripcion' => '<b>Descripción</b>',
                        'pvp' => '<b>PVP</b>',
                        'dto' => '<b>DTO</b>',
                        'cantidad' => '<b>Cantidad</b>',
                        'importe' => '<b>Importe</b>'
                    ),
                    '',
                    array(
                        'fontSize' => 8,
                        'cols' => array(
                            'albaran' => array('justification' => 'center'),
                            'pvp' => array('justification' => 'right'),
                            'dto' => array('justification' => 'right'),
                            'cantidad' => array('justification' => 'right'),
                            'importe' => array('justification' => 'right')
                        ),
                        'width' => 540,
                        'shaded' => 0
                    )
            );
            
            /// Rellenamos el hueco que falta hasta donde debe aparecer la última tabla
            if($this->factura->observaciones == '')
               $salto = '';
            else
            {
               $salto = "\n<b>Observaciones</b>: " . $this->factura->observaciones;
               $saltos += count( explode("\n", $this->factura->observaciones) ) - 1;
            }
            
            if($saltos < $lppag)
            {
               for(;$saltos < $lppag; $saltos++)
                  $salto .= "\n";
               $pdf->ezText($salto, 11);
            }
            else if($linea_actual >= $lineasfact)
               $pdf->ezText($salto, 11);
            else
               $pdf->ezText("\n", 11);
            
            /// Rellenamos la última tabla
            $titulo = array('pagina' => '<b>Página</b>', 'neto' => '<b>Neto</b>',);
            $filas = array(
                array(
                    'pagina' => $pagina . '/' . ceil(count($lineas) / $lppag),
                    'neto' => number_format($this->factura->neto, 2) . ' !',
                )
            );
            $opciones = array(
                'cols' => array(
                    'neto' => array('justification' => 'right'),
                ),
                'showLines' => 0,
                'width' => 540
            );
            foreach($lineas_iva as $li)
            {
               $titulo['iva'.$li->iva] = '<b>IVA'.$li->iva.'%</b>';
               $filas[0]['iva'.$li->iva] = number_format($li->totaliva, 2) . ' !';
               $opciones['cols']['iva'.$li->iva] = array('justification' => 'right');
            }
            $titulo['liquido'] = '<b>Total</b>';
            $filas[0]['liquido'] = number_format($this->factura->total, 2) . ' !';
            $opciones['cols']['liquido'] = array('justification' => 'right');
            $pdf->ezTable($filas, $titulo, '', $opciones);
            $pagina++;
         }
      }
      
      $pdf->ezStream();
   }
   
   private function generar_asiento()
   {
      if( $this->factura->get_asiento() )
         $this->new_error_msg('Ya hay un asiento asociado a esta factura.');
      else
      {
         $proveedor = new proveedor();
         $proveedor = $proveedor->get($this->factura->codproveedor);
         $subcuenta_prov = $proveedor->get_subcuenta($this->factura->codejercicio);
         
         if( !$subcuenta_prov )
         {
            $this->new_message("El proveedor no tiene asociada una subcuenta
               y por tanto no se generará un asiento.");
         }
         else
         {
            $asiento = new asiento();
            $asiento->codejercicio = $this->factura->codejercicio;
            $asiento->concepto = "Su factura ".$this->factura->codigo." - ".$this->factura->nombre;
            $asiento->documento = $this->factura->codigo;
            $asiento->editable = FALSE;
            $asiento->fecha = $this->factura->fecha;
            $asiento->importe = $this->factura->total;
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
               $partida0->haber = $this->factura->total;
               $partida0->coddivisa = $this->factura->coddivisa;
               if( !$partida0->save() )
               {
                  $asiento_correcto = FALSE;
                  $this->new_error_msg("¡Imposible generar la partida para la subcuenta ".$partida0->codsubcuenta."!");
               }
               
               /// generamos una partida por cada impuesto
               $subcuenta_iva = $subcuenta->get_by_codigo('4720000000', $asiento->codejercicio);
               foreach($this->factura->get_lineas_iva() as $li)
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
                     $partida1->codserie = $this->factura->codserie;
                     $partida1->factura = $this->factura->numero;
                     $partida1->baseimponible = $li->neto;
                     $partida1->iva = $li->iva;
                     $partida1->coddivisa = $this->factura->coddivisa;
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
                  $partida2->debe = $this->factura->neto;
                  $partida2->coddivisa = $this->factura->coddivisa;
                  if( !$partida2->save() )
                  {
                     $asiento_correcto = FALSE;
                     $this->new_error_msg("¡Imposible generar la partida para la subcuenta ".$partida2->codsubcuenta."!");
                  }
               }
               
               if( $asiento_correcto )
               {
                  $this->factura->idasiento = $asiento->idasiento;
                  if( $this->factura->save() )
                     $this->new_message("<a href='".$asiento->url()."'>Asiento</a> generado correctamente.");
                  else
                     $this->new_error_msg("¡Imposible añadir el asiento a la factura!");
               }
               else
               {
                  if( $asiento->delete() )
                     $this->new_message("El asiento se ha borrado.");
                  else
                     $this->new_error_msg("¡Imposible borrar el asiento!");
               }
            }
            else
               $this->new_error_msg("¡Imposible guardar el asiento!");
         }
      }
   }
}

?>