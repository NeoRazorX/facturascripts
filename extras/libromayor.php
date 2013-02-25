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
require_once 'model/empresa.php';
require_once 'model/subcuenta.php';

class libro_mayor
{
   private $empresa;
   private $subcuenta;
   
   public function __construct()
   {
      $this->empresa = new empresa();
      $this->subcuenta = new subcuenta();
   }
   
   public function cron_job()
   {
      foreach($this->subcuenta->all() as $subc)
      {
         /// comprobamos si hay que actualizar la subcuenta
         $totales = $subc->get_totales();
         if( abs($subc->debe - $totales['debe']) > .001 )
            $subc->save();
         else if( abs($subc->haber - $totales['haber']) > .001 )
            $subc->save();
         else if( abs($subc->saldo - $totales['saldo']) > .001 )
            $subc->save();
         
         $this->libro_mayor($subc);
      }
   }
   
   private function libro_mayor($subc=FALSE)
   {
      if( $subc )
      {
         if( !file_exists('tmp/libro_mayor') )
            mkdir('tmp/libro_mayor');
         
         if( !file_exists('tmp/libro_mayor/'.$subc->idsubcuenta.'.pdf') )
         {
            echo '.';
            
            $pdf = new Cezpdf('a4');
            
            /// cambiamos ! por el simbolo del euro
            $euro_diff = array(33 => 'Euro');
            $pdf->selectFont("ezpdf/fonts/Helvetica.afm",
              array('encoding' => 'WinAnsiEncoding', 'differences' => $euro_diff));
            
            $pdf->addInfo('Title', 'Libro mayor de ' . $subc->codsubcuenta);
            $pdf->addInfo('Subject', 'Libro mayor de ' . $subc->codsubcuenta);
            $pdf->addInfo('Author', $this->empresa->nombre);
            $pdf->ezStartPageNumbers(590, 10, 10, 'left', '{PAGENUM} de {TOTALPAGENUM}');
            
            $partidas = $subc->get_partidas_full();
            if( $partidas )
            {
               $lineasfact = count($partidas);
               $linea_actual = 0;
               $lppag = 50;
               $pagina = 1;
               
               // Imprimimos las páginas necesarias
               while($linea_actual < $lineasfact)
               {
                  /// salto de página
                  if($linea_actual > 0)
                     $pdf->ezNewPage();
                  
                  /// Creamos la tabla del encabezado
                  $filas = array(
                      array(
                          'campos' => "<b>Empresa:</b>\n<b>Subcuenta:</b>\n<b>Fecha:</b>",
                          'factura' => $this->empresa->nombre."\n".$subc->codsubcuenta."\n".Date('d-m-Y')
                      )
                  );
                  $pdf->ezTable($filas,
                          array('campos' => '', 'factura' => ''),
                          '',
                          array(
                              'cols' => array(
                                  'campos' => array('justification' => 'right', 'width' => 70),
                                  'factura' => array('justification' => 'left')
                              ),
                              'showLines' => 0,
                              'width' => 540
                          )
                  );
                  $pdf->ezText("\n", 10);
                  
                  /// Creamos la tabla con las lineas
                  $filas = array();
                  for($i = $linea_actual; (($linea_actual < ($lppag + $i)) AND ($linea_actual < $lineasfact));)
                  {
                     $filas[$linea_actual] = array(
                         'asiento' => $partidas[$linea_actual]->numero,
                         'fecha' => $partidas[$linea_actual]->fecha,
                         'concepto' => $partidas[$linea_actual]->concepto,
                         'debe' => number_format($partidas[$linea_actual]->debe, 2, '.', ' '),
                         'haber' => number_format($partidas[$linea_actual]->haber, 2, '.', ' '),
                         'saldo' => number_format($partidas[$linea_actual]->saldo, 2, '.', ' ')
                     );
                     $linea_actual++;
                  }
                  $pdf->ezTable($filas,
                          array(
                              'asiento' => '<b>Asiento</b>',
                              'fecha' => '<b>Fecha</b>',
                              'concepto' => '<b>Concepto</b>',
                              'debe' => '<b>Debe</b>',
                              'haber' => '<b>Haber</b>',
                              'saldo' => '<b>Saldo</b>'
                          ),
                          '',
                          array(
                              'fontSize' => 8,
                              'cols' => array(
                                  'debe' => array('justification' => 'right'),
                                  'haber' => array('justification' => 'right'),
                                  'saldo' => array('justification' => 'right')
                              ),
                              'width' => 540,
                              'shaded' => 0
                          )
                  );
                  
                  $pagina++;
               }
            }
            
            $file = fopen('tmp/libro_mayor/'.$subc->idsubcuenta.'.pdf', 'a');
            fwrite($file, $pdf->ezOutput());
            fclose($file);
         }
      }
   }
}

?>
