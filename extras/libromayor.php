<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2014  Carlos Garcia Gomez  neorazorx@gmail.com
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

require_once 'base/fs_pdf.php';
require_model('ejercicio.php');
require_model('empresa.php');
require_model('partida.php');
require_model('subcuenta.php');

class libro_mayor
{
   private $ejercicio;
   private $empresa;
   private $subcuenta;
   
   public function __construct()
   {
      $this->ejercicio = new ejercicio();
      $this->empresa = new empresa();
      $this->subcuenta = new subcuenta();
   }
   
   public function cron_job()
   {
      foreach($this->subcuenta->all() as $subc)
      {
         if( $subc->is_outdated() )
         {
            $subc->save();
         }
         
         $this->libro_mayor($subc, TRUE);
      }
      
      foreach($this->ejercicio->all() as $eje)
         $this->libro_diario($eje);
   }
   
   public function libro_mayor(&$subc, $echos=FALSE)
   {
      if($subc)
      {
         if( !file_exists('tmp/'.FS_TMP_NAME.'libro_mayor') )
            mkdir('tmp/'.FS_TMP_NAME.'libro_mayor');
         
         if( !file_exists('tmp/'.FS_TMP_NAME.'libro_mayor/'.$subc->idsubcuenta.'.pdf') )
         {
            if($echos)
               echo '.';
            
            $pdf_doc = new fs_pdf();
            $pdf_doc->pdf->addInfo('Title', 'Libro mayor de ' . $subc->codsubcuenta);
            $pdf_doc->pdf->addInfo('Subject', 'Libro mayor de ' . $subc->codsubcuenta);
            $pdf_doc->pdf->addInfo('Author', $this->empresa->nombre);
            $pdf_doc->pdf->ezStartPageNumbers(590, 10, 10, 'left', '{PAGENUM} de {TOTALPAGENUM}');
            
            $partidas = $subc->get_partidas_full();
            if($partidas)
            {
               $lineasfact = count($partidas);
               $linea_actual = 0;
               $lppag = 49;
               
               // Imprimimos las páginas necesarias
               while($linea_actual < $lineasfact)
               {
                  /// salto de página
                  if($linea_actual > 0)
                     $pdf_doc->pdf->ezNewPage();
                  
                  /// Creamos la tabla del encabezado
                  $pdf_doc->new_table();
                  $pdf_doc->add_table_row(
                     array(
                         'campos' => "<b>Empresa:</b>\n<b>Subcuenta:</b>\n<b>Fecha:</b>",
                         'factura' => $this->empresa->nombre."\n".$subc->codsubcuenta."\n".Date('d-m-Y')
                     )
                  );
                  $pdf_doc->save_table(
                     array(
                         'cols' => array(
                             'campos' => array('justification' => 'right', 'width' => 70),
                             'factura' => array('justification' => 'left')
                         ),
                         'showLines' => 0,
                         'width' => 540
                     )
                  );
                  $pdf_doc->pdf->ezText("\n", 10);
                  
                  
                  /// Creamos la tabla con las lineas
                  $pdf_doc->new_table();
                  $pdf_doc->add_table_header(
                     array(
                         'asiento' => '<b>Asiento</b>',
                         'fecha' => '<b>Fecha</b>',
                         'concepto' => '<b>Concepto</b>',
                         'debe' => '<b>Debe</b>',
                         'haber' => '<b>Haber</b>',
                         'saldo' => '<b>Saldo</b>'
                     )
                  );
                  for($i = $linea_actual; (($linea_actual < ($lppag + $i)) AND ($linea_actual < $lineasfact));)
                  {
                     $pdf_doc->add_table_row(
                        array(
                            'asiento' => $partidas[$linea_actual]->numero,
                            'fecha' => $partidas[$linea_actual]->fecha,
                            'concepto' => substr($partidas[$linea_actual]->concepto, 0, 60),
                            'debe' => $this->show_numero($partidas[$linea_actual]->debe),
                            'haber' => $this->show_numero($partidas[$linea_actual]->haber),
                            'saldo' => $this->show_numero($partidas[$linea_actual]->saldo)
                        )
                     );
                     
                     $linea_actual++;
                  }
                  /// añadimos las sumas de la línea actual
                  $pdf_doc->add_table_row(
                        array(
                            'asiento' => '',
                            'fecha' => '',
                            'concepto' => '',
                            'debe' => '<b>'.$this->show_numero($partidas[$linea_actual-1]->sum_debe).'</b>',
                            'haber' => '<b>'.$this->show_numero($partidas[$linea_actual-1]->sum_haber).'</b>',
                            'saldo' => ''
                        )
                  );
                  $pdf_doc->save_table(
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
               }
            }
            
            $pdf_doc->save('tmp/'.FS_TMP_NAME.'libro_mayor/'.$subc->idsubcuenta.'.pdf');
         }
      }
   }
   
   private function libro_diario(&$eje)
   {
      if($eje)
      {
         if( !file_exists('tmp/'.FS_TMP_NAME.'libro_diario') )
            mkdir('tmp/'.FS_TMP_NAME.'libro_diario');
         
         if( !file_exists('tmp/'.FS_TMP_NAME.'libro_diario/'.$eje->codejercicio.'.pdf') )
         {
            echo ' '.$eje->codejercicio;
            
            $pdf_doc = new fs_pdf('a4', 'landscape', 'Courier');
            $pdf_doc->pdf->addInfo('Title', 'Libro diario de ' . $eje->codejercicio);
            $pdf_doc->pdf->addInfo('Subject', 'Libro mayor de ' . $eje->codejercicio);
            $pdf_doc->pdf->addInfo('Author', $this->empresa->nombre);
            $pdf_doc->pdf->ezStartPageNumbers(800, 10, 10, 'left', '{PAGENUM} de {TOTALPAGENUM}');
            
            $partida = new partida();
            $sum_debe = 0;
            $sum_haber = 0;
            
            /// leemos todas las partidas del ejercicio
            $lppag = 33;
            $lactual = 0;
            $lineas = $partida->full_from_ejercicio($eje->codejercicio, $lactual, $lppag);
            while( count($lineas) > 0 )
            {
               if($lactual > 0)
               {
                  $pdf_doc->pdf->ezNewPage();
                  echo '+';
               }
               
               $pdf_doc->pdf->ezText($this->empresa->nombre." - libro diario ".$eje->year()."\n\n", 12);
               
               /// Creamos la tabla con las lineas
               $pdf_doc->new_table();
               $pdf_doc->add_table_header(
                  array(
                      'asiento' => '<b>Asiento</b>',
                      'fecha' => '<b>Fecha</b>',
                      'subcuenta' => '<b>Subcuenta</b>',
                      'concepto' => '<b>Concepto</b>',
                      'debe' => '<b>Debe</b>',
                      'haber' => '<b>Haber</b>'
                  )
               );
               
               foreach($lineas as $linea)
               {
                  $pdf_doc->add_table_row(
                     array(
                         'asiento' => $linea['numero'],
                         'fecha' => $linea['fecha'],
                         'subcuenta' => $linea['codsubcuenta'].' '.substr($linea['descripcion'], 0, 35),
                         'concepto' => substr($linea['concepto'], 0, 45),
                         'debe' => $this->show_numero($linea['debe']),
                         'haber' => $this->show_numero($linea['haber'])
                     )
                  );
                  
                  $sum_debe += floatval($linea['debe']);
                  $sum_haber += floatval($linea['haber']);
                  $lactual++;
               }
               
               /// añadimos las sumas de la línea actual
               $pdf_doc->add_table_row(
                  array(
                      'asiento' => '',
                      'fecha' => '',
                      'subcuenta' => '',
                      'concepto' => '',
                      'debe' => '<b>'.$this->show_numero($sum_debe).'</b>',
                      'haber' => '<b>'.$this->show_numero($sum_haber).'</b>'
                  )
               );
               $pdf_doc->save_table(
                  array(
                      'fontSize' => 9,
                      'cols' => array(
                          'debe' => array('justification' => 'right'),
                          'haber' => array('justification' => 'right')
                      ),
                      'width' => 780,
                      'shaded' => 0
                  )
               );
               
               $lineas = $partida->full_from_ejercicio($eje->codejercicio, $lactual, $lppag);
            }
            
            $pdf_doc->save('tmp/'.FS_TMP_NAME.'libro_diario/'.$eje->codejercicio.'.pdf');
         }
      }
   }
   
   private function show_numero($num)
   {
      return number_format($num, FS_NF0, FS_NF1, FS_NF2);
   }
}
