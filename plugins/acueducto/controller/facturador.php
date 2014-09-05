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

class facturador extends fs_controller
{
    
   private $total_clientes;
   private $total_facturas;
   private $total_lecturas;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Facturador', 'acueducto', FALSE, TRUE);
   }
   
   protected function process()
   {
       
      if( isset($_GET['start']) )
      {
         $this->total_clientes = 0;
         $this->total_facturas = 0;
         $this->total_lecturas = 0;
         /// leo clientes
         $cliente = new cliente();
         foreach($cliente->all_full() as $cli)
         {
            $this->generar_factura_cliente( array($cli) );
            
         }
         $this->new_message($this->total_clientes.' clientes facturados. Facturas emitidas '.$this->total_facturas.' y Total lecturas procesadas '.$this->total_lecturas);
        
      }
   }
   private function generar_factura_cliente($clientefact)
   {
       /// leo contadores del cliente
       $contador = new contador();
       foreach($contador->all_cli($clientefact[0]->codcliente) as $cont)
         {
           /// leo lecturas de cada contador
           $lectura = new lectura();
           foreach($lectura->all_cli_cont($clientefact[0]->codcliente,$cont->idcontador) as $lect)
           {
              $this->total_lecturas++;
           }
           $this->total_facturas++;           
         }
       $this->total_clientes++;
   }
   
}

?>
