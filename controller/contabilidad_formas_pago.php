<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2014  Carlos Garcia Gomez  neorazorx@gmail.com
 *                     GISBEL JOSE          gpg841@gmail.com
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

require_model('forma_pago.php');

class contabilidad_formas_pago extends fs_controller
{
   public $forma_pago;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Formas de Pago', 'contabilidad');
   }
   
   protected function process()
   {
      $this->forma_pago = new forma_pago();
      
      if( isset($_POST['codpago']) )
      {
         $fp0 = $this->forma_pago->get($_POST['codpago']);
         if(!$fp0)
         {
            $fp0 = new forma_pago();
            $fp0->codpago = $_POST['codpago'];
         }
         $fp0->descripcion = $_POST['descripcion'];
         $fp0->genrecibos = $_POST['genrecibos'];
         $fp0->domiciliado = isset($_POST['domiciliado']);
         
         if( $fp0->save() )
         {
            $this->new_message('Forma pago '.$fp0->codpago.' guardada correctamente.');
         }
         else
            $this->new_error_msg('Error al guardar la forma pago.');
      }
      else if( isset($_GET['delete']) )
      {
         $fp0 = $this->forma_pago->get($_GET['delete']);
         if($fp0)
         {
            if(FS_DEMO)
            {
               $this->new_error_msg('En el modo demo no puedes eliminar forma de pago. Otro usuario podría necesitarlas.');
            }
            else if( $fp0->delete() )
            {
               $this->new_message('Forma de pago '.$fp0->codpago.' eliminada correctamente.');
            }
            else
               $this->new_error_msg('Error al eliminar la forma de pago '.$fp0->codpago.'.');
         }
         else
            $this->new_error_msg('Forma de pago no encontrada.');
      }
   }
}
