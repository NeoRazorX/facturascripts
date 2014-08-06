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

require_model('impuesto.php');

class contabilidad_impuestos extends fs_controller
{
   public $impuesto;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Impuestos', 'contabilidad', FALSE, TRUE);
   }
   
   protected function process()
   {
      $this->impuesto = new impuesto();
      
      if( isset($_GET['delete']) )
      {
         if(FS_DEMO)
         {
            $this->new_error_msg('En el modo demo no puedes eliminar impuestos.
               Otro usuario podría necesitarlo.');
         }
         else if(!$this->user->admin)
         {
            $this->new_error_msg('Sólo un administrador puede eliminar impuestos.');
         }
         else
         {
            $impuesto = $this->impuesto->get($_GET['delete']);
            if($impuesto)
            {
               if( $impuesto->delete() )
                  $this->new_message('Impuesto eliminado correctamente.');
               else
                  $this->new_error_msg('Ha sido imposible eliminar el impuesto.');
            }
            else
               $this->new_error_msg('Impuesto no encontrado.');
         }
      }
      else if( isset($_POST['codimpuesto']) )
      {
         $impuesto = $this->impuesto->get($_POST['codimpuesto']);
         if( !$impuesto )
         {
            $impuesto = new impuesto();
            $impuesto->codimpuesto = $_POST['codimpuesto'];
         }
         $impuesto->descripcion = $_POST['descripcion'];
         $impuesto->iva = floatval( $_POST['iva'] );
         $impuesto->recargo = floatval( $_POST['recargo'] );
         if( $impuesto->save() )
            $this->new_message("Impuesto ".$impuesto->codimpuesto." guardado correctamente.");
         else
            $this->new_error_msg("¡Error al guardar el impuesto!");
      }
   }
}
