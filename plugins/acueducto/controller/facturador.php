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
require_model('contador.php');
require_model('lectura.php');
require_model('factura_cliente.php');
require_model('facturacion.php');

class facturador extends fs_controller
{
    
   private $total_clientes;
   private $total_clientes_sin;
   private $total_facturas;
   private $total_lecturas;
   private $total_lecturas_auto;
   private $cont_con;
   private $cont_lec;
   
   public $fecha_ultima;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Facturador', 'acueducto', FALSE, TRUE);
   }
   
   protected function process()
   {
      $fact0 = new facturacion();
      $fact1 = $fact0->get_ultima();
      
      if($fact1)
      {
         $this->fecha_ultima = $fact1->fecha;
         //// $this->new_message('fecha leida '. $this->fecha_ultima);
      }
      else
      {
         $this->fecha_ultima = date('d-m-Y');
         $this->new_error_msg('No Existen fecha de facturación. '. $this->fecha_ultima);
      }
        
      if( isset($_POST['fecha']) )
      {
         $this->total_clientes = 0;
         $this->total_clientes_sin = 0;
         $this->total_facturas = 0;
         $this->total_lecturas = 0;
         $this->total_lecturas_auto = 0;
         
         /// leo clientes 
         $cliente = new cliente();
         foreach($cliente->all_full() as $cli)
         {
             /// leo contadores del cliente 
             $contador = new contador();
             $this->cont_con = FALSE;
             foreach($contador->all_cli($cli->codcliente) as $cont) 
             {
                  $this->cont_con = TRUE;
                  /// leo lecturas de cada contador
                  $lectura = new lectura();
                  $this->cont_lec = FALSE;
                  foreach($lectura->all_cli_cont($cli->codcliente,$cont->idcontador) as $lect)
                  {
                      $this->generar_factura_cliente( array($cli),array($cont),array($lect) );
                      $this->total_lecturas++;
                      $this->cont_lec = TRUE;
                      
                  }
                  if(!$this->cont_lec)
                  {
                      $lect = $lectura;
                      /// cargar campos nueva lectura
                      $lect->lectura = 40;
                      /// grabar nueva lectura
                      $this->generar_factura_cliente( array($cli),array($cont),array($lect) );
                      $this->total_lecturas_auto++;
                  }
                  $this->total_facturas++;           
              }
              if(!$this->cont_con)
              { 
                  $this->total_clientes_sin++;
              }
              $this->total_clientes++;

         }
         $this->new_message($this->total_clientes.' clientes facturados, '.$this->total_clientes_sin. ' clientes sin Contador. Facturas emitidas '.$this->total_facturas.' , Total lecturas procesadas '.$this->total_lecturas.' y Total lecturas auto generadas '.$this->total_lecturas_auto);
        
         /// grabo nueva facturacion;
         $fact0 = new facturacion();
         
         $fact0->fecha = $_POST['fecha'];
         $fact0->imputacion = date('d-m-Y');
         $fact0->usuario = $this->user->nick;
         
         if( $fact0->save() )
         {
            $this->new_message('Datos Facturacion guardados correctamente.');
         }
         else
         {
            $this->new_error_msg('Imposible guardar los datos de Facturacion.');
         }
         
      }
   }
   
   private function generar_factura_cliente($cliefact,$contfact,$lectfact)
   {
       $this->new_message('Cliente facturado '. $cliefact[0]->codcliente. ' contador ' . $contfact[0]->numero . ' lectura ' . $lectfact[0]->idlectura);
   }
   
}

?>