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

require_model('cuenta_banco_proveedor.php');
require_model('divisa.php');
require_model('forma_pago.php');
require_model('pais.php');
require_model('proveedor.php');
require_model('serie.php');

class compras_proveedor extends fs_controller
{
   public $cuenta_banco;
   public $divisa;
   public $forma_pago;
   public $pais;
   public $proveedor;
   public $serie;

   public function __construct()
   {
      parent::__construct(__CLASS__, 'Proveedor', 'compras', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->show_fs_toolbar = FALSE;
      $this->ppage = $this->page->get('compras_proveedores');
      $this->cuenta_banco = new cuenta_banco_proveedor();
      $this->divisa = new divisa();
      $this->forma_pago = new forma_pago();
      $this->pais = new pais();
      $this->serie = new serie();
      
      /// cargamos el proveedor
      $proveedor = new proveedor();
      $this->proveedor = FALSE;
      if( isset($_POST['codproveedor']) )
      {
         $this->proveedor = $proveedor->get($_POST['codproveedor']);
      }
      else if( isset($_GET['cod']) )
         $this->proveedor = $proveedor->get($_GET['cod']);
      
      
      /// ¿Hay que hacer algo más?
      if( isset($_GET['delete_cuenta']) ) /// eliminar una cuenta bancaria
      {
         $cuenta = $this->cuenta_banco->get($_GET['delete_cuenta']);
         if($cuenta)
         {
            if( $cuenta->delete() )
            {
               $this->new_message('Cuenta bancaria eliminada correctamente.');
            }
            else
               $this->new_error_msg('Imposible eliminar la cuenta bancaria.');
         }
         else
            $this->new_error_msg('Cuenta bancaria no encontrada.');
      }
      else if( isset($_GET['delete_dir']) ) /// eliminar una dirección
      {
         $dir = new direccion_proveedor();
         $dir0 = $dir->get($_GET['delete_dir']);
         if($dir0)
         {
            if( $dir0->delete() )
            {
               $this->new_message('Dirección eliminada correctamente.');
            }
            else
               $this->new_error_msg('Imposible eliminar la dirección.');
         }
         else
            $this->new_error_msg('Dirección no encontrada.');
      }
      else if( isset($_POST['coddir']) ) /// añadir/modificar una dirección
      {
         $direccion = new direccion_proveedor();
         if($_POST['coddir'] != '')
            $direccion = $direccion->get($_POST['coddir']);
         $direccion->apartado = $_POST['apartado'];
         $direccion->ciudad = $_POST['ciudad'];
         $direccion->codpais = $_POST['pais'];
         $direccion->codpostal = $_POST['codpostal'];
         $direccion->codproveedor = $this->proveedor->codproveedor;
         $direccion->descripcion = $_POST['descripcion'];
         $direccion->direccion = $_POST['direccion'];
         $direccion->direccionppal = isset($_POST['direccionppal']);
         $direccion->provincia = $_POST['provincia'];
         if( $direccion->save() )
         {
            $this->new_message("Dirección guardada correctamente.");
         }
         else
            $this->new_error_msg("¡Imposible guardar la dirección!");
      }
      else if( isset($_POST['iban']) ) /// añadir/modificar una cuenta bancaria
      {
         if( isset($_POST['codcuenta']) )
         {
            $cuentab = $this->cuenta_banco->get($_POST['codcuenta']);
         }
         else
         {
            $cuentab = new cuenta_banco_proveedor();
            $cuentab->codproveedor = $this->proveedor->codproveedor;
         }
         $cuentab->descripcion = $_POST['descripcion'];
         
         if($_POST['ciban'] != '')
         {
            $cuentab->iban = $this->calcular_iban($_POST['ciban']);
         }
         else
            $cuentab->iban = $_POST['iban'];
         
         if( $cuentab->save() )
         {
            $this->new_message('Cuenta bancaria guardada correctamente.');
         }
         else
            $this->new_error_msg('Imposible guardar la cuenta bancaria.');
      }
      else if( isset($_POST['codproveedor']) ) /// modificar el proveedor
      {
         $this->proveedor->nombre = $_POST['nombre'];
         $this->proveedor->nombrecomercial = $_POST['nombrecomercial'];
         $this->proveedor->cifnif = $_POST['cifnif'];
         $this->proveedor->telefono1 = $_POST['telefono1'];
         $this->proveedor->telefono2 = $_POST['telefono2'];
         $this->proveedor->fax = $_POST['fax'];
         $this->proveedor->email = $_POST['email'];
         $this->proveedor->web = $_POST['web'];
         $this->proveedor->observaciones = $_POST['observaciones'];
         $this->proveedor->codserie = $_POST['codserie'];
         $this->proveedor->codpago = $_POST['codpago'];
         $this->proveedor->coddivisa = $_POST['coddivisa'];
         $this->proveedor->regimeniva = $_POST['regimeniva'];
         if( $this->proveedor->save() )
         {
            $this->new_message('Datos del proveedor modificados correctamente.');
         }
         else
            $this->new_error_msg('¡Imposible modificar los datos del proveedor!');
      }
      
      if($this->proveedor)
      {
         $this->page->title = $this->proveedor->codproveedor;
      }
      else
         $this->new_error_msg("¡Proveedor no encontrado!");
   }
   
   public function url()
   {
      if( !isset($this->proveedor) )
      {
         return parent::url();
      }
      else if($this->proveedor)
      {
         return $this->proveedor->url();
      }
      else
         return $this->ppage->url();
   }
   
   public function this_year($previous = 0)
   {
      return intval(Date('Y')) - $previous;
   }
   
   private function calcular_iban($ccc)
   {
      $codpais = substr($this->empresa->codpais, 0, 2);
      
      foreach($this->proveedor->get_direcciones() as $dir)
      {
         if($dir->direccionppal)
         {
            $codpais = substr($dir->codpais, 0, 2);
            break;
         }
      }
      
      $pesos = array('A' => '10', 'B' => '11', 'C' => '12', 'D' => '13', 'E' => '14', 'F' => '15',
          'G' => '16', 'H' => '17', 'I' => '18', 'J' => '19', 'K' => '20', 'L' => '21', 'M' => '22',
          'N' => '23', 'O' => '24', 'P' => '25', 'Q' => '26', 'R' => '27', 'S' => '28', 'T' => '29',
          'U' => '30', 'V' => '31', 'W' => '32', 'X' => '33', 'Y' => '34', 'Z' => '35'
      );
      
      $dividendo = $ccc.$pesos[substr($codpais, 0 , 1)].$pesos[substr($codpais, 1 , 1)].'00';	
      $digitoControl =  98 - bcmod($dividendo, '97');
      
      if( strlen($digitoControl) == 1 )
         $digitoControl = '0'.$digitoControl;
      
      return $codpais.$digitoControl.$ccc;
   }
   
   /*
    * Devuelve un array con los datos estadísticos de las compras al proveedor
    * en los cinco últimos años.
    */
   public function stats_from_prov()
   {
      $stats = array();
      $years = array();
      for($i=4; $i>=0; $i--)
         $years[] = intval(Date('Y')) - $i;
      
      $meses = array('Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic');
      
      foreach($years as $year)
      {
         for($i = 1; $i <= 12; $i++)
         {
            $stats[$year.'-'.$i]['mes'] = $meses[$i-1].' '.$year;
            $stats[$year.'-'.$i]['compras'] = 0;
         }
         
         if( strtolower(FS_DB_TYPE) == 'postgresql')
            $sql_aux = "to_char(fecha,'FMMM')";
         else
            $sql_aux = "DATE_FORMAT(fecha, '%m')";
         
         $data = $this->db->select("SELECT ".$sql_aux." as mes, sum(total) as total
            FROM albaranesprov WHERE fecha >= ".$this->empresa->var2str(Date('1-1-'.$year))."
            AND fecha <= ".$this->empresa->var2str(Date('31-12-'.$year))." AND codproveedor = ".$this->empresa->var2str($this->proveedor->codproveedor)."
            GROUP BY ".$sql_aux." ORDER BY mes ASC;");
         if($data)
         {
            foreach($data as $d)
               $stats[$year.'-'.intval($d['mes'])]['compras'] = number_format($d['total'], FS_NF0, '.', '');
         }
      }
      
      return $stats;
   }
}
