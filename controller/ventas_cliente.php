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

require_model('agente.php');
require_model('cliente.php');
require_model('cuenta_banco_cliente.php');
require_model('direccion_cliente.php');
require_model('divisa.php');
require_model('forma_pago.php');
require_model('fs_extension.php');
require_model('grupo_clientes.php');
require_model('pais.php');
require_model('serie.php');

class ventas_cliente extends fs_controller
{
   public $agente;
   public $cliente;
   public $cuenta_banco;
   public $divisa;
   public $extensiones;
   public $forma_pago;
   public $grupo;
   public $pais;
   public $serie;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Cliente', 'ventas', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->ppage = $this->page->get('ventas_clientes');
      $this->agente = new agente();
      $this->cuenta_banco = new cuenta_banco_cliente();
      $this->divisa = new divisa();
      $this->forma_pago = new forma_pago();
      $this->grupo = new grupo_clientes();
      $this->pais = new pais();
      $this->serie = new serie();
      
      /// cargamos las extensiones
      $fs_extension = new fs_extension();
      $this->extensiones = $fs_extension->all_to(__CLASS__);
      
      /// cargamos el cliente
      $cliente = new cliente();
      $this->cliente = FALSE;
      if( isset($_POST['codcliente']) )
      {
         $this->cliente = $cliente->get( $_POST['codcliente'] );
      }
      else if( isset($_GET['cod']) )
         $this->cliente = $cliente->get($_GET['cod']);
      
      /// ¿Hay que hacer algo más?
      if( isset($_GET['delete_cuenta']) ) /// eliminar cuenta bancaria
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
      else if( isset($_GET['delete_dir']) ) /// eliminar dirección
      {
         $dir = new direccion_cliente();
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
      else if( isset($_POST['coddir']) ) /// añadir/modificar dirección
      {
         $dir = new direccion_cliente();
         if($_POST['coddir'] != '')
            $dir = $dir->get($_POST['coddir']);
         $dir->apartado = $_POST['apartado'];
         $dir->ciudad = $_POST['ciudad'];
         $dir->codcliente = $this->cliente->codcliente;
         $dir->codpais = $_POST['pais'];
         $dir->codpostal = $_POST['codpostal'];
         $dir->descripcion = $_POST['descripcion'];
         $dir->direccion = $_POST['direccion'];
         $dir->domenvio = isset($_POST['direnvio']);
         $dir->domfacturacion = isset($_POST['dirfact']);
         $dir->provincia = $_POST['provincia'];
         if( $dir->save() )
         {
            $this->new_message("Dirección guardada correctamente.");
         }
         else
            $this->new_message("¡Imposible guardar la dirección!");
      }
      else if( isset($_POST['iban']) ) /// añadir/modificar dirección
      {
         if( isset($_POST['codcuenta']) )
         {
            $cuentab = $this->cuenta_banco->get($_POST['codcuenta']);
         }
         else
         {
            $cuentab = new cuenta_banco_cliente();
            $cuentab->codcliente = $_POST['codcliente'];
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
      else if( isset($_POST['codcliente']) ) /// modificar cliente
      {
         $this->cliente->nombre = $_POST['nombre'];
         $this->cliente->nombrecomercial = $_POST['nombrecomercial'];
         $this->cliente->cifnif = $_POST['cifnif'];
         $this->cliente->telefono1 = $_POST['telefono1'];
         $this->cliente->telefono2 = $_POST['telefono2'];
         $this->cliente->fax = $_POST['fax'];
         $this->cliente->web = $_POST['web'];
         $this->cliente->email = $_POST['email'];
         $this->cliente->observaciones = $_POST['observaciones'];
         $this->cliente->codserie = $_POST['codserie'];
         $this->cliente->codpago = $_POST['codpago'];
         $this->cliente->coddivisa = $_POST['coddivisa'];
         $this->cliente->regimeniva = $_POST['regimeniva'];
         $this->cliente->recargo = isset($_POST['recargo']);
         
         $this->cliente->codagente = NULL;
         if($_POST['codagente'] != '---')
            $this->cliente->codagente = $_POST['codagente'];
         
         $this->cliente->codgrupo = NULL;
         if($_POST['codgrupo'] != '---')
            $this->cliente->codgrupo = $_POST['codgrupo'];
         
         if( $this->cliente->save() )
         {
            $this->new_message("Datos del cliente modificados correctamente.");
         }
         else
            $this->new_error_msg("¡Imposible modificar los datos del cliente!");
      }
      
      if($this->cliente)
      {
         $this->page->title = $this->cliente->codcliente;
         $this->buttons[] = new fs_button_img('b_eliminar', 'Eliminar', 'trash.png', '#', TRUE);
      }
      else
         $this->new_error_msg("¡Cliente no encontrado!");
   }
   
   public function url()
   {
      if( !isset($this->cliente) )
         return parent::url();
      else if($this->cliente)
         return $this->cliente->url();
      else
         return $this->ppage->url();
   }
   
   public function stats_last_months()
   {
      $albaran = new albaran_cliente();
      return $albaran->stats_from_cli($this->cliente->codcliente);
   }
   
   public function this_year($previous = 0)
   {
      return intval(Date('Y')) - $previous;
   }
   
   private function calcular_iban($ccc)
   {
      $codpais = substr($this->empresa->codpais, 0, 2);
      
      foreach($this->cliente->get_direcciones() as $dir)
      {
         if($dir->domfacturacion)
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
}
