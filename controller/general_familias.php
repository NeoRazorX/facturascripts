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

require_once 'model/familia.php';

class general_familias extends fs_controller
{
   public $familia;
   
   public function __construct()
   {
      parent::__construct('general_familias', 'Familias', 'general', FALSE, TRUE);
   }
   
   protected function process()
   {
      $this->familia = new familia();
      $this->buttons[] = new fs_button('b_nueva_familia', 'nueva');
      
      if( isset($_POST['ncodfamilia']) )
      {
         $fam = new familia();
         if( $fam->set_codfamilia($_POST['ncodfamilia']) )
         {
            $fam->descripcion = $_POST['ndescripcion'];
            if( $fam->save() )
               Header('location: ' . $fam->url());
            else
               $this->new_error_msg("¡Imposible guardar la familia!");
         }
         else
            $this->new_error_msg($fam->error_msg);
      }
      else if( isset($_GET['delete']) )
      {
         $fam = new familia();
         $fam->codfamilia = $_GET['delete'];
         if( $fam->delete() )
            $this->new_message("Familia ".$_GET['delete']." eliminada correctamente");
         else
            $this->new_error_msg("¡Imposible eliminar la familia ".$_GET['delete']."!");
      }
   }
   
   public function version() {
      return parent::version().'-2';
   }
}

?>
