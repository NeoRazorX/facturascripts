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

class new_fs_controller extends fs_controller
{
   public function __construct()
   {
      parent::__construct('general_familias', 'Familias', 'general', FALSE, TRUE);
   }
   
   protected function process()
   {
      if( isset($_POST['ncodfamilia']) )
      {
         $fam = new familia();
         $fam->codfamilia = $_POST['ncodfamilia'];
         $fam->descripcion = $_POST['ndescripcion'];
         if( $fam->save() )
            Header('location: ' . $fam->url());
      }
      else if( isset($_GET['delete']) )
      {
         $fam = new familia();
         $fam->codfamilia = $_GET['delete'];
         if( $fam->delete() )
            $this->new_message("Familia ".$_GET['delete']." eliminada correctamente");
      }
   }

   public function all()
   {
      $fam = new familia();
      return $fam->all();
   }
}

?>
