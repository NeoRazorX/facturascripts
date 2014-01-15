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

require_model('cliente.php');
require_model('ejercicio.php');
require_model('factura_cliente.php');
require_model('factura_proveedor.php');
require_model('proveedor.php');

class informe_347 extends fs_controller
{
   public $datos_cli;
   public $datos_pro;
   public $ejercicio;
   public $sejercicio;
   
   public function __construct()
   {
      parent::__construct('informe_347', 'Modelo 347', 'informes', FALSE, TRUE);
   }
   
   protected function process()
   {
      $this->ejercicio = new ejercicio();
      
      if( isset($_POST['ejercicio']) )
         $this->sejercicio = $_POST['ejercicio'];
      else
         $this->sejercicio = Date('Y');
      
      $this->datos_cli = $this->informe_clientes();
      $this->datos_pro = $this->informe_proveedores();
   }
   
   private function informe_clientes()
   {
      $informe = array(
          'filas' => array(),
          'totales' => array(0, 0, 0, 0, 0)
      );
      
      if( strtolower(FS_DB_TYPE) == 'postgresql')
      {
         $data = $this->db->select("SELECT codcliente, to_char(fecha,'FMMM') as mes, sum(total) as total
            FROM facturascli WHERE to_char(fecha,'FMYYYY') = ".$this->ejercicio->var2str($this->sejercicio)."
            GROUP BY codcliente, to_char(fecha,'FMMM') ORDER BY codcliente;");
      }
      else
      {
         $data = $this->db->select("SELECT codcliente, DATE_FORMAT(fecha, '%m') as mes, sum(total) as total
            FROM facturascli WHERE DATE_FORMAT(fecha, '%Y') = ".$this->ejercicio->var2str($this->sejercicio)."
            GROUP BY codcliente, DATE_FORMAT(fecha, '%m') ORDER BY codcliente;");
      }
      
      $fila = array(
          'codcliente' => '',
          'cifnif' => '',
          'url' => '',
          't1' => 0,
          't2' => 0,
          't3' => 0,
          't4' => 0,
          'total' => 0
      );
      
      if($data)
      {
         foreach($data as $d)
         {
            if($fila['codcliente'] == '')
               $fila['codcliente'] = $d['codcliente'];
            else if($d['codcliente'] != $fila['codcliente'])
            {
               if($fila['total'] > 3005.06)
                  $informe['filas'][] = $fila;
               
               $fila['codcliente'] = $d['codcliente'];
               $fila['t1'] = 0;
               $fila['t2'] = 0;
               $fila['t3'] = 0;
               $fila['t4'] = 0;
               $fila['total'] = 0;
            }
            
            if( in_array($d['mes'], array('1', '2','3','01','02','03')) )
               $fila['t1'] += floatval($d['total']);
            else if( in_array($d['mes'], array('4','5','6','04','05','06')) )
               $fila['t2'] += floatval($d['total']);
            else if( in_array($d['mes'], array('7','8','9','07','08','09')) )
               $fila['t3'] += floatval($d['total']);
            else
               $fila['t4'] += floatval($d['total']);
            
            $fila['total'] = $fila['t1'] + $fila['t2'] + $fila['t3'] + $fila['t4'];
         }
         if($fila['total'] > 3005.06)
            $informe['filas'][] = $fila;
         
         $cliente = new cliente();
         foreach($informe['filas'] as $i => $value)
         {
            $cli0 = $cliente->get($value['codcliente']);
            if($cli0)
            {
               $informe['filas'][$i]['cifnif'] = $cli0->cifnif;
               $informe['filas'][$i]['url'] = $cli0->url();
            }
            
            $informe['totales'][0] += $value['t1'];
            $informe['totales'][1] += $value['t2'];
            $informe['totales'][2] += $value['t3'];
            $informe['totales'][3] += $value['t4'];
         }
         
         $informe['totales'][4] = $informe['totales'][0] + $informe['totales'][1] + $informe['totales'][2] + $informe['totales'][3];
      }
      
      return $informe;
   }
   
   private function informe_proveedores()
   {
      $informe = array(
          'filas' => array(),
          'totales' => array(0, 0, 0, 0, 0)
      );
      
      if( strtolower(FS_DB_TYPE) == 'postgresql')
      {
         $data = $this->db->select("SELECT codproveedor, to_char(fecha,'FMMM') as mes, sum(total) as total
            FROM facturasprov WHERE to_char(fecha,'FMYYYY') = ".$this->ejercicio->var2str($this->sejercicio)."
            GROUP BY codproveedor, to_char(fecha,'FMMM') ORDER BY codproveedor;");
      }
      else
      {
         $data = $this->db->select("SELECT codproveedor, DATE_FORMAT(fecha, '%m') as mes, sum(total) as total
            FROM facturasprov WHERE DATE_FORMAT(fecha, '%Y') = ".$this->ejercicio->var2str($this->sejercicio)."
            GROUP BY codproveedor, DATE_FORMAT(fecha, '%m') ORDER BY codproveedor;");
      }
      
      $fila = array(
          'codproveedor' => '',
          'cifnif' => '',
          'url' => '',
          't1' => 0,
          't2' => 0,
          't3' => 0,
          't4' => 0,
          'total' => 0
      );
      
      if($data)
      {
         foreach($data as $d)
         {
            if($fila['codproveedor'] == '')
               $fila['codproveedor'] = $d['codproveedor'];
            else if($d['codproveedor'] != $fila['codproveedor'])
            {
               if($fila['total'] > 3005.06)
                  $informe['filas'][] = $fila;
               
               $fila['codproveedor'] = $d['codproveedor'];
               $fila['t1'] = 0;
               $fila['t2'] = 0;
               $fila['t3'] = 0;
               $fila['t4'] = 0;
               $fila['total'] = 0;
            }
            
            if( in_array($d['mes'], array('1','2','3','01','02','03')) )
               $fila['t1'] += floatval($d['total']);
            else if( in_array($d['mes'], array('4','5','6','04','05','06')) )
               $fila['t2'] += floatval($d['total']);
            else if( in_array($d['mes'], array('7','8','9','07','08','09')) )
               $fila['t3'] += floatval($d['total']);
            else
               $fila['t4'] += floatval($d['total']);
            
            $fila['total'] = $fila['t1'] + $fila['t2'] + $fila['t3'] + $fila['t4'];
         }
         if($fila['total'] > 3005.06)
            $informe['filas'][] = $fila;
         
         $proveedor = new proveedor();
         foreach($informe['filas'] as $i => $value)
         {
            $pro0 = $proveedor->get($value['codproveedor']);
            if($pro0)
            {
               $informe['filas'][$i]['cifnif'] = $pro0->cifnif;
               $informe['filas'][$i]['url'] = $pro0->url();
            }
            
            $informe['totales'][0] += $value['t1'];
            $informe['totales'][1] += $value['t2'];
            $informe['totales'][2] += $value['t3'];
            $informe['totales'][3] += $value['t4'];
         }
         $informe['totales'][4] = $informe['totales'][0] + $informe['totales'][1] + $informe['totales'][2] + $informe['totales'][3];
      }
      
      return $informe;
   }
   
   public function show_float($num)
   {
      return number_format($num, FS_NF0, FS_NF1, FS_NF2);
   }
}

?>