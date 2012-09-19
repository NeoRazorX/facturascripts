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
require_once 'model/factura_proveedor.php';

class contabilidad_factura_prov extends fs_controller
{
   public $factura;
   
   public function __construct()
   {
      parent::__construct('contabilidad_factura_prov', 'Factura de proveedor', 'contabilidad', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->ppage = $this->page->get('contabilidad_facturas_prov');
      
      if( isset($_POST['idfactura']) )
      {
         $this->factura = new factura_proveedor();
         $this->factura = $this->factura->get($_POST['idfactura']);
         $this->factura->numproveedor = $_POST['numproveedor'];
         $this->factura->fecha = $_POST['fecha'];
         $this->factura->observaciones = $_POST['observaciones'];
         if( $this->factura->save() )
            $this->new_message("Factura modificada correctamente.");
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
         $this->page->title = $this->factura->codigo;
         
         /// comprobamos la factura
         if( !$this->factura->test() )
            $this->new_error_msg( $this->factura->error_msg );
         
         $this->buttons[] = new fs_button('b_imprimir', 'imprimir', $this->url()."&imprimir=TRUE", 'button', 'img/print.png');
         if($this->factura->idasiento)
            $this->buttons[] = new fs_button('b_ver_asiento', 'ver asiento', $this->factura->asiento_url(), 'button', 'img/zoom.png');
         $this->buttons[] = new fs_button('b_eliminar', 'eliminar', '#', 'remove', 'img/remove.png');
         
         if( isset($_GET['imprimir']) )
            $this->generar_pdf();
      }
      else
         $this->new_error_msg("¡Factura de proveedor no encontrada!");
   }
   
   public function version() {
      return parent::version().'-1';
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
      
      $pdf =& new Cezpdf('a4');
      
      /// cambiamos ! por el simbolo del euro
      $euro_diff = array(33 => 'Euro');
      $pdf->selectFont("ezpdf/fonts/Helvetica.afm",
              array('encoding' => 'WinAnsiEncoding', 'differences' => $euro_diff));
      
      $pdf->addInfo('Title', 'Factura ' . $this->factura->codigo);
      $pdf->addInfo('Subject', 'Factura de cliente ' . $this->factura->codigo);
      $pdf->addInfo('Author', $this->get_empresa_name());
      
      $lineas = $this->factura->get_lineas();
      $lineas_iva = $this->factura->get_lineas_iva();
      if( $lineas )
      {
         $lineasfact = count($lineas);
         $linea_actual = 0;
         $lppag = 35;
         $pagina = 1;
         
         // Imprimimos las páginas necesarias
         while($linea_actual < $lineasfact)
         {
            /// salto de página
            if($linea_actual > 0)
               $pdf->ezNewPage();
            
            $pdf->ezText("\n\n\n\n", 12);
            
            /// Creamos la tabla del encabezado
            $filas = array(
                array(
                    'campos' => "<b>Factura:</b>\n<b>Fecha:</b>\n<b>CIF/NIF:</b>",
                    'factura' => $this->factura->codigo."\n".$this->factura->fecha."\n".$this->factura->cifnif,
                    'cliente' => $this->factura->nombre."\n"
                )
            );
            $pdf->ezTable($filas,
                    array('campos' => '', 'factura' => '', 'cliente' => ''),
                    '',
                    array(
                        'cols' => array(
                            'campos' => array('justification' => 'right', 'width' => 60),
                            'factura' => array('justification' => 'left'),
                            'cliente' => array('justification' => 'right')
                        ),
                        'showLines' => 0,
                        'width' => 540
                    )
            );
            $pdf->ezText("\n", 12);
            
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
               $pdf->ezText($salto, 10);
            }
            else if($linea_actual >= $lineasfact)
               $pdf->ezText($salto, 10);
            else
               $pdf->ezText("\n", 10);
            
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
}

?>
