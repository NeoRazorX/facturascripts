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
require_model('balance.php');
require_model('cuenta.php');
require_model('ejercicio.php');
require_model('empresa.php');
require_model('partida.php');

class inventarios_balances
{
   private $balance;
   private $balance_cuenta_a;
   private $empresa;
   
   public function __construct()
   {
      $this->balance = new balance();
      $this->balance_cuenta_a = new balance_cuenta_a();
      $this->empresa = new empresa();
   }
   
   public function cron_job()
   {
      $ejercicio = new ejercicio();
      foreach($ejercicio->all() as $eje)
         $this->generar_libro($eje);
   }
   
   private function generar_libro(&$eje)
   {
      if($eje)
      {
         if( !file_exists('tmp/'.FS_TMP_NAME.'inventarios_balances') )
            mkdir('tmp/'.FS_TMP_NAME.'inventarios_balances');
         
         if( !file_exists('tmp/'.FS_TMP_NAME.'inventarios_balances/'.$eje->codejercicio.'.pdf') )
         {
            echo '.';
            
            $pdf_doc = new fs_pdf();
            $pdf_doc->pdf->addInfo('Title', 'Libro de inventarios y balances de ' . $this->empresa->nombre);
            $pdf_doc->pdf->addInfo('Subject', 'Libro de inventarios y balances de ' . $this->empresa->nombre);
            $pdf_doc->pdf->addInfo('Author', $this->empresa->nombre);
            $pdf_doc->pdf->ezStartPageNumbers(580, 10, 10, 'left', '{PAGENUM} de {TOTALPAGENUM}');
            
            if( isset($eje->idasientocierre) AND isset($eje->idasientopyg) )
               $excluir = array($eje->idasientocierre, $eje->idasientopyg);
            else
               $excluir = FALSE;
            
            $this->sumas_y_saldos($pdf_doc, $eje, 'de apertura a cierre', $eje->fechainicio, $eje->fechafin, $excluir, FALSE);
            $this->sumas_y_saldos($pdf_doc, $eje, 'de apertura a apertura', $eje->fechainicio, $eje->fechainicio);
            $this->sumas_y_saldos($pdf_doc, $eje, 'del 1º trimestre', $eje->fechainicio, '31-3-'.$eje->year());
            $this->sumas_y_saldos($pdf_doc, $eje, 'del 2º trimestre', '1-4-'.$eje->year(), '30-6-'.$eje->year());
            $this->sumas_y_saldos($pdf_doc, $eje, 'del 3º trimestre', '1-7-'.$eje->year(), '30-9-'.$eje->year());
            $this->sumas_y_saldos($pdf_doc, $eje, 'del 4º trimestre', '1-10-'.$eje->year(), $eje->fechafin);
            $this->perdidas_y_ganancias($pdf_doc, $eje);
            $this->situacion($pdf_doc, $eje);
            
            $pdf_doc->save('tmp/'.FS_TMP_NAME.'inventarios_balances/'.$eje->codejercicio.'.pdf');
         }
      }
   }
   
   public function generar_pyg($codeje)
   {
      $ejercicio = new ejercicio();
      
      $eje0 = $ejercicio->get($codeje);
      if($eje0)
      {
         $pdf_doc = new fs_pdf();
         $pdf_doc->pdf->addInfo('Title', 'Balance de pérdidas y ganancias de ' . $this->empresa->nombre);
         $pdf_doc->pdf->addInfo('Subject', 'Balance de pérdidas y ganancias de ' . $this->empresa->nombre);
         $pdf_doc->pdf->addInfo('Author', $this->empresa->nombre);
         $pdf_doc->pdf->ezStartPageNumbers(580, 10, 10, 'left', '{PAGENUM} de {TOTALPAGENUM}');
         
         $this->perdidas_y_ganancias($pdf_doc, $eje0, FALSE);
         
         $pdf_doc->show();
      }
   }
   
   public function generar_sit($codeje)
   {
      $ejercicio = new ejercicio();
      
      $eje0 = $ejercicio->get($codeje);
      if($eje0)
      {
         $pdf_doc = new fs_pdf();
         $pdf_doc->pdf->addInfo('Title', 'Balance de pérdidas y ganancias de ' . $this->empresa->nombre);
         $pdf_doc->pdf->addInfo('Subject', 'Balance de pérdidas y ganancias de ' . $this->empresa->nombre);
         $pdf_doc->pdf->addInfo('Author', $this->empresa->nombre);
         $pdf_doc->pdf->ezStartPageNumbers(580, 10, 10, 'left', '{PAGENUM} de {TOTALPAGENUM}');
         
         $this->situacion($pdf_doc, $eje0, FALSE);
         
         $pdf_doc->show();
      }
   }
   
   /*
    * Este informe muestra los saldos (distintos de cero) de cada cuenta y subcuenta
    * por periodos, pero siempre excluyendo los asientos de cierre y pérdidas y ganancias.
    */
   private function sumas_y_saldos(&$pdf_doc, &$eje, $titulo, $fechaini, $fechafin, $excluir=FALSE, $np=TRUE)
   {
      $cuenta = new cuenta();
      $partida = new partida();
      
      /// metemos todo en una lista
      $auxlist = array();
      $offset = 0;
      $cuentas = $cuenta->all_from_ejercicio($eje->codejercicio, $offset);
      while( count($cuentas) > 0 )
      {
         foreach($cuentas as $c)
         {
            $subcuentas = $c->get_subcuentas();
            $debe = 0;
            $haber = 0;
            
            foreach($subcuentas as $sc)
            {
               $auxt = $partida->totales_from_subcuenta_fechas($sc->idsubcuenta, $fechaini, $fechafin, $excluir);
               $debe += $auxt['debe'];
               $haber += $auxt['haber'];
            }
            
            if( $debe!=0 OR $haber!=0)
            {
               $auxlist[] = array(
                   'cuenta' => TRUE,
                   'codigo' => $c->codcuenta,
                   'descripcion' => $c->descripcion,
                   'debe' => $debe,
                   'haber' => $haber,
                   'saldo' => $debe-$haber
               );
               
               foreach($subcuentas as $sc)
               {
                  $auxt = $partida->totales_from_subcuenta_fechas($sc->idsubcuenta, $fechaini, $fechafin, $excluir);
                  if( $auxt['debe']!=0 OR $auxt['haber']!=0 )
                  {
                     $auxlist[] = array(
                         'cuenta' => FALSE,
                         'codigo' => $sc->codsubcuenta,
                         'descripcion' => $sc->descripcion,
                         'debe' => $auxt['debe'],
                         'haber' => $auxt['haber'],
                         'saldo' => $auxt['saldo']
                     );
                  }
               }
            }
            
            $offset++;
         }
         
         $cuentas = $cuenta->all_from_ejercicio($eje->codejercicio, $offset);
      }
      
      
      /// a partir de la lista generamos el documento
      $linea = 0;
      $tdebe = 0;
      $thaber = 0;
      while( $linea < count($auxlist) )
      {
         if($linea > 0 OR $np)
            $pdf_doc->pdf->ezNewPage();
         
         $pdf_doc->pdf->ezText($this->empresa->nombre." - Balance de sumas y saldos ".$eje->year().' '.$titulo.".\n\n", 12);
         
         /// Creamos la tabla con las lineas
         $pdf_doc->new_table();
         $pdf_doc->add_table_header(
            array(
                'subcuenta' => '<b>Cuenta</b>',
                'descripcion' => '<b>Descripción</b>',
                'debe' => '<b>Debe</b>',
                'haber' => '<b>Haber</b>',
                'saldo' => '<b>Saldo</b>'
            )
         );
         
         for($i=$linea; $i<min( array($linea+48, count($auxlist)) ); $i++)
         {
            if( $auxlist[$i]['cuenta'] )
            {
               $a = '<b>';
               $b = '</b>';
            }
            else
            {
               $a = $b = '';
               $tdebe += $auxlist[$i]['debe'];
               $thaber += $auxlist[$i]['haber'];
            }
            
            $pdf_doc->add_table_row(
               array(
                   'subcuenta' => $a.$auxlist[$i]['codigo'].$b,
                   'descripcion' => $a.substr($auxlist[$i]['descripcion'], 0, 50).$b,
                   'debe' => $a.$this->show_numero($auxlist[$i]['debe']).$b,
                   'haber' => $a.$this->show_numero($auxlist[$i]['haber']).$b,
                   'saldo' => $a.$this->show_numero($auxlist[$i]['saldo']).$b
               )
            );
         }
         $linea += 48;
         
         /// añadimos las sumas de la línea actual
         $pdf_doc->add_table_row(
            array(
                'subcuenta' => '',
                'descripcion' => '<b>Suma y sigue</b>',
                'debe' => '<b>'.$this->show_numero($tdebe).'</b>',
                'haber' => '<b>'.$this->show_numero($thaber).'</b>',
                'saldo' => '<b>'.$this->show_numero($tdebe-$thaber).'</b>'
            )
         );
         $pdf_doc->save_table(
            array(
                'fontSize' => 9,
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
   
   /*
    * Este informe se confecciona a partir de las cuentas que señalan los códigos
    * de balance que empiezan por PG.
    */
   private function perdidas_y_ganancias(&$pdf_doc, &$eje, $np=TRUE)
   {
      if($np)
         $pdf_doc->pdf->ezNewPage();
      
      $pdf_doc->pdf->ezText($this->empresa->nombre." - Cuenta de pérdidas y ganancias abreviada del ejercicio ".$eje->year().".\n\n", 13);
      
      /// necesitamos el ejercicio anterior
      $eje0 = $eje->get_by_fecha( '1-1-'.(intval($eje->year())-1), FALSE, FALSE );
      if($eje0)
      {
         /// creamos las cabeceras de la tabla
         $pdf_doc->new_table();
         $pdf_doc->add_table_header(
            array(
                'descripcion' => '<b>Descripción</b>',
                'actual' => '<b>'.$eje->year().'</b>',
                'anterior' => '<b>'.$eje0->year().'</b>'
            )
         );
         
         $balances = $this->balance->all();
         $num = 1;
         $continuar = TRUE;
         $totales = array(
             $eje->year() => array('a' => 0, 'b' => 0, 'c' => 0, 'd' => 0),
             $eje0->year() => array('a' => 0, 'b' => 0, 'c' => 0, 'd' => 0)
         );
         while($continuar)
         {
            if($num == 12)
            {
               $pdf_doc->add_table_row(
                  array(
                      'descripcion' => "\n<b>A) RESULTADOS DE EXPLOTACIÓN (1+2+3+4+5+6+7+8+9+10+11)</b>",
                      'actual' => "\n<b>".$this->show_numero($totales[$eje->year()]['a']).'</b>',
                      'anterior' => "\n<b>".$this->show_numero($totales[$eje0->year()]['a']).'</b>'
                  )
               );
            }
            else if($num == 17)
            {
               $pdf_doc->add_table_row(
                  array(
                      'descripcion' => "\n<b>B) RESULTADO FINANCIERO (12+13+14+15+16)</b>",
                      'actual' => "\n<b>".$this->show_numero($totales[$eje->year()]['b']).'</b>',
                      'anterior' => "\n<b>".$this->show_numero($totales[$eje0->year()]['b']).'</b>'
                  )
               );
               $pdf_doc->add_table_row(
                  array(
                      'descripcion' => "<b>C) RESULTADO ANTES DE IMPUESTOS (A+B)</b>",
                      'actual' => '<b>'.$this->show_numero($totales[$eje->year()]['c']).'</b>',
                      'anterior' => '<b>'.$this->show_numero($totales[$eje0->year()]['c']).'</b>'
                  )
               );
            }
            
            $encontrado = FALSE;
            foreach($balances as $bal)
            {
               if($bal->naturaleza == 'PG' AND strstr($bal->codbalance, 'PG-A-'.$num.'-') )
               {
                  $saldo1 = $this->get_saldo_balance('PG-A-'.$num.'-', $eje);
                  $saldo0 = $this->get_saldo_balance('PG-A-'.$num.'-', $eje0);
                  
                  /// añadimos la fila
                  $pdf_doc->add_table_row(
                     array(
                         'descripcion' => $bal->descripcion2,
                         'actual' => $this->show_numero($saldo1),
                         'anterior' => $this->show_numero($saldo0)
                     )
                  );
                  
                  /// sumamos donde corresponda
                  if($num <= 11)
                  {
                     $totales[$eje->year()]['a'] += $saldo1;
                     $totales[$eje0->year()]['a'] += $saldo0;
                  }
                  else if($num <= 16)
                  {
                     $totales[$eje->year()]['b'] += $saldo1;
                     $totales[$eje0->year()]['b'] += $saldo0;
                     
                     $totales[$eje->year()]['c'] = $totales[$eje->year()]['a'] + $totales[$eje->year()]['b'];
                     $totales[$eje0->year()]['c'] = $totales[$eje0->year()]['a'] + $totales[$eje0->year()]['b'];
                  }
                  else if($num == 17)
                  {
                     $totales[$eje->year()]['d'] = $totales[$eje->year()]['c'] + $saldo1;
                     $totales[$eje0->year()]['d'] = $totales[$eje0->year()]['c'] + $saldo0;
                  }
                  
                  $encontrado = TRUE;
                  $num++;
                  break;
               }
            }
            
            $continuar = $encontrado;
         }
         
         $pdf_doc->add_table_row(
            array(
                'descripcion' => "\n<b>D) RESULTADO DEL EJERCICIO (C+17)</b>",
                'actual' => "\n<b>".$this->show_numero($totales[$eje->year()]['d']).'</b>',
                'anterior' => "\n<b>".$this->show_numero($totales[$eje0->year()]['d']).'</b>'
            )
         );
         
         $pdf_doc->save_table(
            array(
                'fontSize' => 12,
                'cols' => array(
                    'actual' => array('justification' => 'right'),
                    'anterior' => array('justification' => 'right')
                ),
                'width' => 540,
                'shaded' => 0
            )
         );
      }
      else
      {
         /// creamos las cabeceras de la tabla
         $pdf_doc->new_table();
         $pdf_doc->add_table_header(
            array(
                'descripcion' => '<b>Descripción</b>',
                'actual' => '<b>'.$eje->year().'</b>'
            )
         );
         
         $balances = $this->balance->all();
         $num = 1;
         $continuar = TRUE;
         $totales = array( $eje->year() => array('a' => 0, 'b' => 0, 'c' => 0, 'd' => 0) );
         while($continuar)
         {
            if($num == 12)
            {
               $pdf_doc->add_table_row(
                  array(
                      'descripcion' => "\n<b>A) RESULTADOS DE EXPLOTACIÓN (1+2+3+4+5+6+7+8+9+10+11)</b>",
                      'actual' => "\n<b>".$this->show_numero($totales[$eje->year()]['a']).'</b>'
                  )
               );
            }
            else if($num == 17)
            {
               $pdf_doc->add_table_row(
                  array(
                      'descripcion' => "\n<b>B) RESULTADO FINANCIERO (12+13+14+15+16)</b>",
                      'actual' => "\n<b>".$this->show_numero($totales[$eje->year()]['b']).'</b>'
                  )
               );
               $pdf_doc->add_table_row(
                  array(
                      'descripcion' => "<b>C) RESULTADO ANTES DE IMPUESTOS (A+B)</b>",
                      'actual' => '<b>'.$this->show_numero($totales[$eje->year()]['c']).'</b>'
                  )
               );
            }
            
            $encontrado = FALSE;
            foreach($balances as $bal)
            {
               if($bal->naturaleza == 'PG' AND strstr($bal->codbalance, 'PG-A-'.$num.'-') )
               {
                  $saldo1 = $this->get_saldo_balance('PG-A-'.$num.'-', $eje);
                  
                  /// añadimos la fila
                  $pdf_doc->add_table_row(
                     array(
                         'descripcion' => $bal->descripcion2,
                         'actual' => $this->show_numero($saldo1)
                     )
                  );
                  
                  /// sumamos donde corresponda
                  if($num <= 11)
                  {
                     $totales[$eje->year()]['a'] += $saldo1;
                  }
                  else if($num <= 16)
                  {
                     $totales[$eje->year()]['b'] += $saldo1;
                     $totales[$eje->year()]['c'] = $totales[$eje->year()]['a'] + $totales[$eje->year()]['b'];
                  }
                  else if($num == 17)
                  {
                     $totales[$eje->year()]['d'] = $totales[$eje->year()]['c'] + $saldo1;
                  }
                  
                  $encontrado = TRUE;
                  $num++;
                  break;
               }
            }
            
            $continuar = $encontrado;
         }
         
         $pdf_doc->add_table_row(
            array(
                'descripcion' => "\n<b>D) RESULTADO DEL EJERCICIO (C+17)</b>",
                'actual' => "\n<b>".$this->show_numero($totales[$eje->year()]['d']).'</b>'
            )
         );
         
         $pdf_doc->save_table(
            array(
                'fontSize' => 12,
                'cols' => array(
                    'actual' => array('justification' => 'right')
                ),
                'width' => 540,
                'shaded' => 0
            )
         );
      }
   }
   
   private function get_saldo_balance($codbalance, &$ejercicio)
   {
      $total = 0;
      
      foreach($this->balance_cuenta_a->search_by_codbalance($codbalance) as $bca)
         $total += $bca->saldo($ejercicio);
      
      return $total;
   }
   
   /*
    * Para generar este informe hay que leer los códigos de balance con naturaleza A o P
    * en orden. Pero como era demasiado sencillo, los hijos de puta de facturalux decidieron
    * añadir números romanos, para que no puedas ordenarlos fácilemnte.
    */
   private function situacion(&$pdf_doc, &$eje, $np=TRUE)
   {
      /// necesitamos el ejercicio anterior
      $eje0 = $eje->get_by_fecha( '1-1-'.(intval($eje->year())-1), FALSE, FALSE );
      if($eje0)
      {
         $nivel0 = array('A', 'P');
         $nivel1 = array('A', 'B', 'C');
         $nivel2 = array('', '1', '2', '3', '4', '5', '6', '7', '8', '9');
         $nivel3 = array('', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X');
         $nivel4 = array('', '1', '2', '3', '4', '5', '6', '7', '8', '9');
         $balances = $this->balance->all();
         
         foreach($nivel0 as $nv0)
         {
            if($np)
               $pdf_doc->pdf->ezNewPage();
            else
               $np = TRUE;
            
            $pdf_doc->pdf->ezText($this->empresa->nombre." - Balance de situación del ejercicio ".$eje->year().".\n\n", 13);
            
            /// creamos las cabeceras de la tabla
            $pdf_doc->new_table();
            $pdf_doc->add_table_header(
               array(
                   'descripcion' => '<b>Descripción</b>',
                   'actual' => '<b>'.$eje->year().'</b>',
                   'anterior' => '<b>'.$eje0->year().'</b>'
               )
            );
            
            $desc1 = '';
            $desc2 = '';
            $desc3 = '';
            $desc4 = '';
            foreach($nivel1 as $nv1)
            {
               foreach($nivel2 as $nv2)
               {
                  foreach($nivel3 as $nv3)
                  {
                     foreach($nivel4 as $nv4)
                     {
                        foreach($balances as $bal)
                        {
                           if($bal->naturaleza == $nv0 AND $bal->nivel1 == $nv1 AND $bal->nivel2 == $nv2 AND $bal->nivel3 == $nv3 AND $bal->nivel4 == $nv4)
                           {
                              if($bal->descripcion1 != $desc1 AND $bal->descripcion1 != '')
                              {
                                 $pdf_doc->add_table_row(
                                    array(
                                        'descripcion' => "\n<b>".$bal->descripcion1.'</b>',
                                        'actual' => "\n<b>".$this->get_saldo_balance2($nv0.'-'.$nv1.'-', $eje, $nv0).'</b>',
                                        'anterior' => "\n<b>".$this->get_saldo_balance2($nv0.'-'.$nv1.'-', $eje0, $nv0).'</b>'
                                    )
                                 );
                                 
                                 $desc1 = $bal->descripcion1;
                              }
                              
                              if($bal->descripcion2 != $desc2 AND $bal->descripcion2 != '')
                              {
                                 $pdf_doc->add_table_row(
                                    array(
                                        'descripcion' => ' <b>'.$bal->descripcion2.'</b>',
                                        'actual' => $this->get_saldo_balance2($nv0.'-'.$nv1.'-'.$nv2.'-', $eje, $nv0),
                                        'anterior' => $this->get_saldo_balance2($nv0.'-'.$nv1.'-'.$nv2.'-', $eje0, $nv0)
                                    )
                                 );
                                 
                                 $desc2 = $bal->descripcion2;
                              }
                              
                              if($bal->descripcion3 != $desc3 AND $bal->descripcion3 != '')
                              {
                                 $pdf_doc->add_table_row(
                                    array(
                                        'descripcion' => '  '.$bal->descripcion3,
                                        'actual' => $this->get_saldo_balance2($nv0.'-'.$nv1.'-'.$nv2.'-'.$nv3.'-', $eje, $nv0),
                                        'anterior' => $this->get_saldo_balance2($nv0.'-'.$nv1.'-'.$nv2.'-'.$nv3.'-', $eje0, $nv0)
                                    )
                                 );
                                 
                                 $desc3 = $bal->descripcion3;
                              }
                           }
                        }
                     }
                  }
               }
            }
            
            if($nv0 == 'A')
            {
               $pdf_doc->add_table_row(
                  array(
                      'descripcion' => "\n<b>TOTAL ACTIVO (A+B)</b>",
                      'actual' => "\n<b>".$this->get_saldo_balance2($nv0.'-', $eje, $nv0).'</b>',
                      'anterior' => "\n<b>".$this->get_saldo_balance2($nv0.'-', $eje0, $nv0).'</b>'
                  )
               );
            }
            else if($nv0 == 'P')
            {
               $pdf_doc->add_table_row(
                  array(
                      'descripcion' => "\n<b>TOTAL PATRIMONIO NETO (A+B+C)</b>",
                      'actual' => "\n<b>".$this->get_saldo_balance2($nv0.'-', $eje, $nv0).'</b>',
                      'anterior' => "\n<b>".$this->get_saldo_balance2($nv0.'-', $eje0, $nv0).'</b>'
                  )
               );
            }
            
            $pdf_doc->save_table(
               array(
                   'fontSize' => 12,
                   'cols' => array(
                       'actual' => array('justification' => 'right'),
                       'anterior' => array('justification' => 'right')
                   ),
                   'width' => 540,
                   'shaded' => 0
               )
            );
         }
      }
      else
      {
         $nivel0 = array('A', 'P');
         $nivel1 = array('A', 'B', 'C');
         $nivel2 = array('', '1', '2', '3', '4', '5', '6', '7', '8', '9');
         $nivel3 = array('', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X');
         $nivel4 = array('', '1', '2', '3', '4', '5', '6', '7', '8', '9');
         $balances = $this->balance->all();
         
         foreach($nivel0 as $nv0)
         {
            if($np)
               $pdf_doc->pdf->ezNewPage();
            else
               $np = TRUE;
            
            $pdf_doc->pdf->ezText($this->empresa->nombre." - Balance de situación del ejercicio ".$eje->year().".\n\n", 13);
            
            /// creamos las cabeceras de la tabla
            $pdf_doc->new_table();
            $pdf_doc->add_table_header(
               array(
                   'descripcion' => '<b>Descripción</b>',
                   'actual' => '<b>'.$eje->year().'</b>'
               )
            );
            
            $desc1 = '';
            $desc2 = '';
            $desc3 = '';
            $desc4 = '';
            foreach($nivel1 as $nv1)
            {
               foreach($nivel2 as $nv2)
               {
                  foreach($nivel3 as $nv3)
                  {
                     foreach($nivel4 as $nv4)
                     {
                        foreach($balances as $bal)
                        {
                           if($bal->naturaleza == $nv0 AND $bal->nivel1 == $nv1 AND $bal->nivel2 == $nv2 AND $bal->nivel3 == $nv3 AND $bal->nivel4 == $nv4)
                           {
                              if($bal->descripcion1 != $desc1 AND $bal->descripcion1 != '')
                              {
                                 $pdf_doc->add_table_row(
                                    array(
                                        'descripcion' => "\n<b>".$bal->descripcion1.'</b>',
                                        'actual' => "\n<b>".$this->get_saldo_balance2($nv0.'-'.$nv1.'-', $eje, $nv0).'</b>'
                                    )
                                 );
                                 
                                 $desc1 = $bal->descripcion1;
                              }
                              
                              if($bal->descripcion2 != $desc2 AND $bal->descripcion2 != '')
                              {
                                 $pdf_doc->add_table_row(
                                    array(
                                        'descripcion' => ' <b>'.$bal->descripcion2.'</b>',
                                        'actual' => $this->get_saldo_balance2($nv0.'-'.$nv1.'-'.$nv2.'-', $eje, $nv0)
                                    )
                                 );
                                 
                                 $desc2 = $bal->descripcion2;
                              }
                              
                              if($bal->descripcion3 != $desc3 AND $bal->descripcion3 != '')
                              {
                                 $pdf_doc->add_table_row(
                                    array(
                                        'descripcion' => '  '.$bal->descripcion3,
                                        'actual' => $this->get_saldo_balance2($nv0.'-'.$nv1.'-'.$nv2.'-'.$nv3.'-', $eje, $nv0)
                                    )
                                 );
                                 
                                 $desc3 = $bal->descripcion3;
                              }
                           }
                        }
                     }
                  }
               }
            }
            
            if($nv0 == 'A')
            {
               $pdf_doc->add_table_row(
                  array(
                      'descripcion' => "\n<b>TOTAL ACTIVO (A+B)</b>",
                      'actual' => "\n<b>".$this->get_saldo_balance2($nv0.'-', $eje, $nv0).'</b>'
                  )
               );
            }
            else if($nv0 == 'P')
            {
               $pdf_doc->add_table_row(
                  array(
                      'descripcion' => "\n<b>TOTAL PATRIMONIO NETO (A+B+C)</b>",
                      'actual' => "\n<b>".$this->get_saldo_balance2($nv0.'-', $eje, $nv0).'</b>'
                  )
               );
            }
            
            $pdf_doc->save_table(
               array(
                   'fontSize' => 12,
                   'cols' => array(
                       'actual' => array('justification' => 'right')
                   ),
                   'width' => 540,
                   'shaded' => 0
               )
            );
         }
      }
   }
   
   private function get_saldo_balance2($codbalance, $ejercicio, $naturaleza='A')
   {
      $total = 0;
      
      foreach($this->balance_cuenta_a->search_by_codbalance($codbalance) as $bca)
         $total += $bca->saldo($ejercicio);
      
      if($naturaleza == 'A')
         return $this->show_numero(0-$total);
      else
         return $this->show_numero($total);
   }
   
   private function show_numero($num)
   {
      return number_format($num, FS_NF0, FS_NF1, FS_NF2);
   }
}

?>