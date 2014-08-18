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
   public $cantidad;
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
      
      if( isset($_POST['cantidad']) )
         $this->cantidad = floatval($_POST['cantidad']);
      else if( isset($_GET['cantidad']) )
         $this->cantidad = floatval($_GET['cantidad']);
      else
         $this->cantidad = 3005.06;
      
      if( isset($_POST['ejercicio']) )
         $this->sejercicio = $_POST['ejercicio'];
      else if( isset($_GET['eje']) )
         $this->sejercicio = $_GET['eje'];
      else
         $this->sejercicio = Date('Y');
      
      $this->datos_cli = $this->informe_clientes();
      $this->datos_pro = $this->informe_proveedores();
      
      if( isset($_GET['eje']) )
         $this->excel();
      else
         $this->buttons[] = new fs_button('b_download', 'Descargar', $this->url().'&eje='.$this->sejercicio.'&cantidad='.$this->cantidad);
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
          'cliente' => '',
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
               if($fila['total'] > $this->cantidad)
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
         if($fila['total'] > $this->cantidad)
            $informe['filas'][] = $fila;
         
         $cliente = new cliente();
         foreach($informe['filas'] as $i => $value)
         {
            $cli0 = $cliente->get($value['codcliente']);
            if($cli0)
               $informe['filas'][$i]['cliente'] = $cli0;
            
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
          'proveedor' => '',
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
               if($fila['total'] > $this->cantidad)
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
         if($fila['total'] > $this->cantidad)
            $informe['filas'][] = $fila;
         
         $proveedor = new proveedor();
         foreach($informe['filas'] as $i => $value)
         {
            $pro0 = $proveedor->get($value['codproveedor']);
            if($pro0)
               $informe['filas'][$i]['proveedor'] = $pro0;
            
            $informe['totales'][0] += $value['t1'];
            $informe['totales'][1] += $value['t2'];
            $informe['totales'][2] += $value['t3'];
            $informe['totales'][3] += $value['t4'];
         }
         $informe['totales'][4] = $informe['totales'][0] + $informe['totales'][1] + $informe['totales'][2] + $informe['totales'][3];
      }
      
      return $informe;
   }
   
   private function excel()
   {
      $this->template = FALSE;
      header("Content-Disposition: attachment; filename=\"modelo_347_".$this->sejercicio.".xls\"");
      header("Content-Type: application/vnd.ms-excel");
      
      echo "<table>
         <tr>
            <td colspan='6'>Clientes que han comprado mas de ".$this->cantidad." euros en el ejercicio ".$this->sejercicio.".</td>
         </tr>
         <tr>
            <td>Cliente</td>
            <td>T.1</td>
            <td>T.2</td>
            <td>T.3</td>
            <td>T.4</td>
            <td>Total</td>
         </tr>";
      
      foreach($this->datos_cli['filas'] as $d)
      {
         echo "<tr>
            <td>".$d['cliente']->nombre."</td>
            <td>".number_format($d['t1'], 2, ',', '')."</td>
            <td>".number_format($d['t2'], 2, ',', '')."</td>
            <td>".number_format($d['t3'], 2, ',', '')."</td>
            <td>".number_format($d['t4'], 2, ',', '')."</td>
            <td>".number_format($d['total'], 2, ',', '')."</td>
         </tr>";
      }
      
      echo "<tr>
            <td></td>
            <td>".number_format($this->datos_cli['totales'][0], 2, ',', '')."</td>
            <td>".number_format($this->datos_cli['totales'][1], 2, ',', '')."</td>
            <td>".number_format($this->datos_cli['totales'][2], 2, ',', '')."</td>
            <td>".number_format($this->datos_cli['totales'][3], 2, ',', '')."</td>
            <td>".number_format($this->datos_cli['totales'][4], 2, ',', '')."</td>
         </tr>";
      
      echo "<tr><td></td></tr>
         <tr>
            <td colspan='6'>Proveedores que nos han vendido mas de 3 005.06 euros en el ejercicio ".$this->sejercicio.".</td>
         </tr>
         <tr>
            <td>Proveedor</td>
            <td>T.1</td>
            <td>T.2</td>
            <td>T.3</td>
            <td>T.4</td>
            <td>Total</td>
         </tr>";
      
      foreach($this->datos_pro['filas'] as $d)
      {
         echo "<tr>
            <td>".$d['proveedor']->nombre."</td>
            <td>".number_format($d['t1'], 2, ',', '')."</td>
            <td>".number_format($d['t2'], 2, ',', '')."</td>
            <td>".number_format($d['t3'], 2, ',', '')."</td>
            <td>".number_format($d['t4'], 2, ',', '')."</td>
            <td>".number_format($d['total'], 2, ',', '')."</td>
         </tr>";
      }
      
      echo "<tr>
            <td></td>
            <td>".number_format($this->datos_pro['totales'][0], 2, ',', '')."</td>
            <td>".number_format($this->datos_pro['totales'][1], 2, ',', '')."</td>
            <td>".number_format($this->datos_pro['totales'][2], 2, ',', '')."</td>
            <td>".number_format($this->datos_pro['totales'][3], 2, ',', '')."</td>
            <td>".number_format($this->datos_pro['totales'][4], 2, ',', '')."</td>
         </tr>";
      
      echo "</table>";
   }
}
