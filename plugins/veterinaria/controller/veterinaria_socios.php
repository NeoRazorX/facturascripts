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
require_model('fbm_socio.php');

class veterinaria_socios extends fs_controller
{
   public $resultados;
   public $socio;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Socios', 'Veterinaria');
   }
   
   protected function process()
   {
      $this->show_fs_toolbar = FALSE;
      $this->socio = new fbm_socio();
      
      if( isset($_POST['nombre']) )
      {
         $cliente = new cliente();
         $cliente->codcliente = $cliente->get_new_codigo();
         $cliente->nombre = $_POST['nombre'];
         $cliente->nombrecomercial = $_POST['nombre'];
         $cliente->cifnif = $_POST['cifnif'];
         $cliente->codserie = $this->empresa->codserie;;
         if( $cliente->save() )
         {
            $dircliente = new direccion_cliente();
            $dircliente->codcliente = $cliente->codcliente;
            $dircliente->codpais = $this->empresa->codpais;;
            $dircliente->provincia = $_POST['provincia'];
            $dircliente->ciudad = $_POST['ciudad'];
            $dircliente->codpostal = $_POST['codpostal'];
            $dircliente->direccion = $_POST['direccion'];
            $dircliente->descripcion = 'Principal';
            if( $dircliente->save() )
            {
               $this->socio->codcliente = $cliente->codcliente;
               if( $this->socio->save() )
               {
                  $this->new_message('Socio creado correctamente.');
               }
               else
                  $this->new_error_msg("¡Imposible crear el socio!");
            }
            else
               $this->new_error_msg("¡Imposible guardar la dirección del cliente!");
         }
         else
            $this->new_error_msg("¡Imposible guardar los datos del cliente!");
      }
      
      $this->resultados = $this->socio->all();
   }
}