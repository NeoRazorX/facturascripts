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
require_model('empresa.php');
require_model('subcuenta.php');

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
         if( $subc->is_outdated() )
            $subc->save();
         
         $this->libro_mayor($subc, TRUE);
      }
   }
   
   public function libro_mayor($subc=FALSE, $echos=FALSE)
   {
      if($subc)
      {
         if( !file_exists('tmp/libro_mayor') )
            mkdir('tmp/libro_mayor');
         
         if( !file_exists('tmp/libro_mayor/'.$subc->idsubcuenta.'.pdf') )
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
               $pagina = 1;
               
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
                            'concepto' => $partidas[$linea_actual]->concepto,
                            'debe' => $partidas[$linea_actual]->show_debe(),
                            'haber' => $partidas[$linea_actual]->show_haber(),
                            'saldo' => $partidas[$linea_actual]->show_saldo()
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
                            'debe' => '<b>'.$partidas[$linea_actual-1]->show_sumdebe().'</b>',
                            'haber' => '<b>'.$partidas[$linea_actual-1]->show_sumhaber().'</b>',
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
                  
                  $pagina++;
               }
            }
            
            $pdf_doc->save('tmp/libro_mayor/'.$subc->idsubcuenta.'.pdf');
         }
      }
   }
}

?>