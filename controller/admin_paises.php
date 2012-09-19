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

require_once 'model/pais.php';

class admin_paises extends fs_controller
{
   public $pais;

   public function __construct()
   {
      parent::__construct('admin_paises', 'Paises', 'admin', TRUE, TRUE);
   }
   
   protected function process()
   {
      $this->pais = new pais();
      $this->buttons[] = new fs_button('b_nuevo_pais', 'nuevo');
      
      if( isset($_POST['scodpais']) )
      {
         $pais = $this->pais->get($_POST['scodpais']);
         if( !$pais )
         {
            $pais = new pais();
            $pais->codpais = $_POST['scodpais'];
         }
         $pais->nombre = $_POST['snombre'];
         if( $pais->save() )
            $this->new_message("País ".$pais->nombre." modificado correctamente.");
         else
            $this->new_error_msg("¡Imposible modificar el país ".$pais->nombre."!");
      }
      else if( isset($_GET['delete']) )
      {
         $pais = $this->pais->get($_GET['delete']);
         if( $pais )
         {
            if( $pais->delete() )
               $this->new_message("País ".$pais->nombre." eliminado correctamente.");
            else
               $this->new_error_msg("¡Imposible eliminar el país ".$pais->nombre."!");
         }
         else
            $this->new_error_msg("¡País no encontrado!");
      }
   }
   
   public function version() {
      return parent::version().'-2';
   }
}

?>
