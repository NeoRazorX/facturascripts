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
require_once 'model/subcuenta.php';

class contabilidad_subcuenta extends fs_controller
{
   public $subcuenta;
   public $cuenta;
   public $ejercicio;
   public $resultados;
   public $offset;
   
   public function __construct()
   {
      parent::__construct('contabilidad_subcuenta', 'Subcuenta', 'contabilidad', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->ppage = $this->page->get('contabilidad_cuentas');
      
      if( isset($_GET['id']) )
      {
         $this->subcuenta = new subcuenta();
         $this->subcuenta = $this->subcuenta->get($_GET['id']);
      }
      
      if($this->subcuenta)
      {
         $this->page->title = 'Subcuenta: '.$this->subcuenta->codsubcuenta;
         $this->cuenta = $this->subcuenta->get_cuenta();
         $this->ejercicio = $this->subcuenta->get_ejercicio();
         
         if( isset($_GET['libro_mayor']) )
            $this->libro_mayor();
         else
         {
            /// comprobamos la subcuenta
            $this->subcuenta->test();
            
            $this->buttons[] = new fs_button('b_libro_mayor', 'libro mayor',
               $this->url().'&libro_mayor=TRUE','', 'img/print.png', 'imprimir', TRUE);
            
            if( isset($_GET['offset']) )
               $this->offset = intval($_GET['offset']);
            else
               $this->offset = 0;
            
            $this->resultados = $this->subcuenta->get_partidas($this->offset);
         }
      }
      else
         $this->new_error_msg("Subcuenta no encontrada.");
   }
   
   public function version()
   {
      return parent::version().'-3';
   }
   
   public function url()
   {
      if( $this->subcuenta )
         return $this->subcuenta->url();
      else
         return $this->ppage->url();
   }

   public function anterior_url()
   {
      $url = '';
      if($this->query!='' AND $this->offset>'0')
         $url = $this->url()."&query=".$this->query."&offset=".($this->offset-FS_ITEM_LIMIT);
      else if($this->query=='' AND $this->offset>'0')
         $url = $this->url()."&offset=".($this->offset-FS_ITEM_LIMIT);
      return $url;
   }
   
   public function siguiente_url()
   {
      $url = '';
      if($this->query!='' AND count($this->resultados)==FS_ITEM_LIMIT)
         $url = $this->url()."&query=".$this->query."&offset=".($this->offset+FS_ITEM_LIMIT);
      else if($this->query=='' AND count($this->resultados)==FS_ITEM_LIMIT)
         $url = $this->url()."&offset=".($this->offset+FS_ITEM_LIMIT);
      return $url;
   }
   
   private function libro_mayor()
   {
      /// desactivamos la plantilla HTML
      $this->template = FALSE;
      
      $pdf = new Cezpdf('a4');
      
      /// cambiamos ! por el simbolo del euro
      $euro_diff = array(33 => 'Euro');
      $pdf->selectFont("ezpdf/fonts/Helvetica.afm",
              array('encoding' => 'WinAnsiEncoding', 'differences' => $euro_diff));
      
      $pdf->addInfo('Title', 'Libro mayor de ' . $this->subcuenta->codsubcuenta);
      $pdf->addInfo('Subject', 'Libro mayor de ' . $this->subcuenta->codsubcuenta);
      $pdf->addInfo('Author', $this->empresa->nombre);
      
      $partidas = $this->subcuenta->get_partidas_full();
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
                    'campos' => "<b>Empresa:</b>\n<b>Libro mayor de:</b>\n<b>Fecha:</b>",
                    'factura' => $this->empresa->nombre."\n".$this->subcuenta->codsubcuenta."\n".Date('d-m-Y')
                )
            );
            $pdf->ezTable($filas,
                    array('campos' => '', 'factura' => ''),
                    '',
                    array(
                        'cols' => array(
                            'campos' => array('justification' => 'right', 'width' => 100),
                            'factura' => array('justification' => 'left')
                        ),
                        'showLines' => 0,
                        'width' => 540
                    )
            );
            $pdf->ezText("\n", 12);
            
            /// Creamos la tabla con las lineas
            $saltos = 0;
            $filas = array();
            for($i = $linea_actual; (($linea_actual < ($lppag + $i)) AND ($linea_actual < $lineasfact));)
            {
               $filas[$linea_actual]['asiento'] = $partidas[$linea_actual]->numero;
               $filas[$linea_actual]['fecha'] = $partidas[$linea_actual]->fecha;
               $filas[$linea_actual]['concepto'] = $partidas[$linea_actual]->concepto;
               $filas[$linea_actual]['debe'] = number_format($partidas[$linea_actual]->debe, 2, '.', ' ');
               $filas[$linea_actual]['haber'] = number_format($partidas[$linea_actual]->haber, 2, '.', ' ');
               $filas[$linea_actual]['saldo'] = number_format($partidas[$linea_actual]->saldo, 2, '.', ' ');
               $saltos++;
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
      
      $pdf->ezStream();
   }
}

?>
