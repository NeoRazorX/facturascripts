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

require_once 'ezpdf/class.ezpdf.php';
require_once 'model/factura_cliente.php';
require_once 'model/factura_proveedor.php';

class informe_facturas extends fs_controller
{
   public $desde;
   public $factura_cli;
   public $factura_pro;
   public $hasta;
   
   public function __construct()
   {
      parent::__construct('informe_facturas', 'Facturas', 'informes', FALSE, TRUE);
   }
   
   protected function process()
   {
      $this->desde = Date('1-m-Y');
      $this->factura_cli = new factura_cliente();
      $this->factura_pro = new factura_proveedor();
      $this->hasta = Date('d-m-Y', mktime(0, 0, 0, date("m")+1, date("1")-1, date("Y")));
      
      if( isset($_POST['listado']) )
      {
         if($_POST['listado'] == 'facturascli')
            $this->listar_facturas_cli();
         else
            $this->listar_facturas_prov();
      }
   }
   
   public function version()
   {
      return parent::version().'-2';
   }
   
   private function listar_facturas_cli()
   {
      /// desactivamos el motor de plantillas
      $this->template = FALSE;
      
      $pdf = new Cezpdf('a4', 'landscape');
      
      /// cambiamos ! por el simbolo del euro
      $euro_diff = array(33 => 'Euro');
      $pdf->selectFont("ezpdf/fonts/Courier.afm",
              array('encoding' => 'WinAnsiEncoding', 'differences' => $euro_diff));
      
      $pdf->addInfo('Title', 'Facturas emitidas del '.$_POST['dfecha'].' al '.$_POST['hfecha'] );
      $pdf->addInfo('Subject', 'Facturas emitidas del '.$_POST['dfecha'].' al '.$_POST['hfecha'] );
      $pdf->addInfo('Author', $this->empresa->nombre);
      
      $facturas = $this->factura_cli->all_desde($_POST['dfecha'], $_POST['hfecha']);
      if($facturas)
      {
         $total_lineas = count( $facturas );
         $linea_actual = 0;
         $lppag = 33;
         $total = $base = $re = 0;
         $impuestos = array();
         $pagina = 1;

         while($linea_actual < $total_lineas)
         {
            if($linea_actual > 0)
            {
               $pdf->ezNewPage();
               $pagina++;
            }
            
            $pdf->ezText($this->empresa->nombre." - Facturas emitidas del ".$_POST['dfecha']." al ".$_POST['hfecha'].":\n\n", 14);
            
            $lineas = Array();
            for($i = 0; $i < $lppag AND $linea_actual < $total_lineas; $i++)
            {
               $linea = array(
                   'serie' => $facturas[$linea_actual]->codserie,
                   'factura' => $facturas[$linea_actual]->numero,
                   'asiento' => '-',
                   'fecha' => $facturas[$linea_actual]->fecha,
                   'subcuenta' => '-',
                   'descripcion' => $facturas[$linea_actual]->nombrecliente,
                   'cifnif' => $facturas[$linea_actual]->cifnif,
                   'base' => $facturas[$linea_actual]->show_neto(),
                   'iva' => 0,
                   'totaliva' => 0,
                   're' => $facturas[$linea_actual]->recfinanciero,
                   'totalre' => $facturas[$linea_actual]->totalrecargo,
                   'total' => $facturas[$linea_actual]->show_total()
               );
               $asiento = $facturas[$linea_actual]->get_asiento();
               if($asiento)
               {
                  $linea['asiento'] = $asiento->numero;
                  $partidas = $asiento->get_partidas();
                  if($partidas)
                     $linea['subcuenta'] = $partidas[0]->codsubcuenta;
               }
               $linivas = $facturas[$linea_actual]->get_lineas_iva();
               if($linivas)
               {
                  foreach($linivas as $liva)
                  {
                     $linea['iva'] = $liva->iva;
                     $linea['totaliva'] = $liva->show_totaliva();
                     if( !isset($impuestos[$liva->iva]) )
                        $impuestos[$liva->iva] = $liva->totaliva;
                     else
                        $impuestos[$liva->iva] += $liva->totaliva;
                  }
               }
               $lineas[$i] = $linea;
               
               $base += $facturas[$linea_actual]->neto;
               $re += $facturas[$linea_actual]->recfinanciero;
               $total += $facturas[$linea_actual]->total;
               $linea_actual++;
            }
            $titulo = array(
                'serie' => '<b>S</b>',
                'factura' => '<b>Fact.</b>',
                'asiento' => '<b>Asi.</b>',
                'fecha' => '<b>Fecha</b>',
                'subcuenta' => '<b>Subcuenta</b>',
                'descripcion' => '<b>Descripción</b>',
                'cifnif' => '<b>CIF/NIF</b>',
                'base' => '<b>Base Im.</b>',
                'iva' => '<b>% IVA</b>',
                'totaliva' => '<b>IVA</b>',
                're' => '<b>% RE</b>',
                'totalre' => '<b>RE</b>',
                'total' => '<b>Total</b>'
            );
            $opciones = array(
                'fontSize' => 8,
                'cols' => array(
                    'base' => array('justification' => 'right'),
                    'iva' => array('justification' => 'right'),
                    'totaliva' => array('justification' => 'right'),
                    're' => array('justification' => 'right'),
                    'totalre' => array('justification' => 'right'),
                    'total' => array('justification' => 'right')
                ),
                'shaded' => 0,
                'width' => 750
            );
            $pdf->ezTable($lineas, $titulo, '', $opciones);
            $pdf->ezText("\n", 10);
            
            /*
             * Rellenamos la última tabla
             */
            $titulo = array();
            $titulo['pagina'] = '<b>Suma y sigue</b>';
            $titulo['base'] = '<b>Base im.</b>';
            $filas = array();
            $filas[0] = array();
            $filas[0]['pagina'] = $pagina . '/' . ceil($total_lineas / $lppag);
            $filas[0]['base'] = number_format($base, 2) . ' !';
            $opciones = array();
            $opciones['cols'] = array();
            $opciones['cols']['base'] = array('justification' => 'right');
            foreach($impuestos as $i => $value)
            {
               $titulo['iva'.$i] = '<b>IVA '.$i.'%</b>';
               $filas[0]['iva'.$i] = number_format($value, 2) . ' !';
               $opciones['cols']['iva'.$i] = array('justification' => 'right');
            }
            $titulo['re'] = '<b>RE</b>';
            $titulo['total'] = '<b>Total</b>';
            $filas[0]['re'] = number_format($re, 2) . ' !';
            $filas[0]['total'] = number_format($total, 2) . ' !';
            $opciones['cols']['re'] = array('justification' => 'right');
            $opciones['cols']['total'] = array('justification' => 'right');
            $opciones['showLines'] = 0;
            $opciones['width'] = 750;
            $pdf->ezTable($filas, $titulo, '', $opciones);
         }
      }
      else
      {
         $pdf->ezText($this->empresa->nombre." - Facturas emitidas del ".$_POST['dfecha']." al ".$_POST['hfecha'].":\n\n", 14);
         $pdf->ezText("Ninguna.\n\n", 14);
      }
      
      $pdf->ezStream();
   }
   
   private function listar_facturas_prov()
   {
      /// desactivamos el motor de plantillas
      $this->template = FALSE;
      
      $pdf = new Cezpdf('a4', 'landscape');
      
      /// cambiamos ! por el simbolo del euro
      $euro_diff = array(33 => 'Euro');
      $pdf->selectFont("ezpdf/fonts/Courier.afm",
              array('encoding' => 'WinAnsiEncoding', 'differences' => $euro_diff));
      
      $pdf->addInfo('Title', 'Facturas emitidas del '.$_POST['dfecha'].' al '.$_POST['hfecha'] );
      $pdf->addInfo('Subject', 'Facturas emitidas del '.$_POST['dfecha'].' al '.$_POST['hfecha'] );
      $pdf->addInfo('Author', $this->empresa->nombre);
      
      $facturas = $this->factura_pro->all_desde($_POST['dfecha'], $_POST['hfecha']);
      if($facturas)
      {
         $total_lineas = count( $facturas );
         $linea_actual = 0;
         $lppag = 33;
         $total = $base = $re = 0;
         $impuestos = array();
         $pagina = 1;

         while($linea_actual < $total_lineas)
         {
            if($linea_actual > 0)
            {
               $pdf->ezNewPage();
               $pagina++;
            }
            
            $pdf->ezText($this->empresa->nombre." - Facturas recibidas del ".$_POST['dfecha'].' al '.$_POST['hfecha'].":\n\n", 14);
            
            $lineas = Array();
            for($i = 0; $i < $lppag AND $linea_actual < $total_lineas; $i++)
            {
               $linea = array(
                   'serie' => $facturas[$linea_actual]->codserie,
                   'factura' => $facturas[$linea_actual]->numero,
                   'asiento' => '-',
                   'fecha' => $facturas[$linea_actual]->fecha,
                   'subcuenta' => '-',
                   'descripcion' => $facturas[$linea_actual]->nombre,
                   'cifnif' => $facturas[$linea_actual]->cifnif,
                   'base' => $facturas[$linea_actual]->show_neto(),
                   'iva' => 0,
                   'totaliva' => 0,
                   're' => $facturas[$linea_actual]->recfinanciero,
                   'totalre' => $facturas[$linea_actual]->totalrecargo,
                   'total' => $facturas[$linea_actual]->show_total()
               );
               $asiento = $facturas[$linea_actual]->get_asiento();
               if($asiento)
               {
                  $linea['asiento'] = $asiento->numero;
                  $partidas = $asiento->get_partidas();
                  if($partidas)
                     $linea['subcuenta'] = $partidas[0]->codsubcuenta;
               }
               $linivas = $facturas[$linea_actual]->get_lineas_iva();
               if($linivas)
               {
                  foreach($linivas as $liva)
                  {
                     $linea['iva'] = $liva->iva;
                     $linea['totaliva'] = $liva->show_totaliva();
                     if( !isset($impuestos[$liva->iva]) )
                        $impuestos[$liva->iva] = $liva->totaliva;
                     else
                        $impuestos[$liva->iva] += $liva->totaliva;
                  }
               }
               $lineas[$i] = $linea;
               
               $base += $facturas[$linea_actual]->neto;
               $re += $facturas[$linea_actual]->recfinanciero;
               $total += $facturas[$linea_actual]->total;
               $linea_actual++;
            }
            $titulo = array(
                'serie' => '<b>S</b>',
                'factura' => '<b>Fact.</b>',
                'asiento' => '<b>Asi.</b>',
                'fecha' => '<b>Fecha</b>',
                'subcuenta' => '<b>Subcuenta</b>',
                'descripcion' => '<b>Descripción</b>',
                'cifnif' => '<b>CIF/NIF</b>',
                'base' => '<b>Base Im.</b>',
                'iva' => '<b>% IVA</b>',
                'totaliva' => '<b>IVA</b>',
                're' => '<b>% RE</b>',
                'totalre' => '<b>RE</b>',
                'total' => '<b>Total</b>'
            );
            $opciones = array(
                'fontSize' => 8,
                'cols' => array(
                    'base' => array('justification' => 'right'),
                    'iva' => array('justification' => 'right'),
                    'totaliva' => array('justification' => 'right'),
                    're' => array('justification' => 'right'),
                    'totalre' => array('justification' => 'right'),
                    'total' => array('justification' => 'right')
                ),
                'shaded' => 0,
                'width' => 750
            );
            $pdf->ezTable($lineas, $titulo, '', $opciones);
            $pdf->ezText("\n", 10);
            
            /*
             * Rellenamos la última tabla
             */
            $titulo = array();
            $titulo['pagina'] = '<b>Suma y sigue</b>';
            $titulo['base'] = '<b>Base im.</b>';
            $filas = array();
            $filas[0] = array();
            $filas[0]['pagina'] = $pagina . '/' . ceil($total_lineas / $lppag);
            $filas[0]['base'] = number_format($base, 2) . ' !';
            $opciones = array();
            $opciones['cols'] = array();
            $opciones['cols']['base'] = array('justification' => 'right');
            foreach($impuestos as $i => $value)
            {
               $titulo['iva'.$i] = '<b>IVA '.$i.'%</b>';
               $filas[0]['iva'.$i] = number_format($value, 2) . ' !';
               $opciones['cols']['iva'.$i] = array('justification' => 'right');
            }
            $titulo['re'] = '<b>RE</b>';
            $titulo['total'] = '<b>Total</b>';
            $filas[0]['re'] = number_format($re, 2) . ' !';
            $filas[0]['total'] = number_format($total, 2) . ' !';
            $opciones['cols']['re'] = array('justification' => 'right');
            $opciones['cols']['total'] = array('justification' => 'right');
            $opciones['showLines'] = 0;
            $opciones['width'] = 750;
            $pdf->ezTable($filas, $titulo, '', $opciones);
         }
      }
      else
      {
         $pdf->ezText($this->empresa->nombre." - Facturas recibidas del ".$_POST['dfecha'].' al '.$_POST['hfecha'].":\n\n", 14);
         $pdf->ezText("Ninguna.\n\n", 14);
      }
      
      $pdf->ezStream();
   }
}

?>
