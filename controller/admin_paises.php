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
   public function __construct()
   {
      parent::__construct('admin_paises', 'Paises', 'admin', TRUE, TRUE);
   }
   
   protected function process()
   {
      if( isset($_POST['scodpais']) AND isset($_POST['snombre']))
      {
         $pais = new pais();
         $pais->codpais = $_POST['scodpais'];
         $pais->nombre = $_POST['snombre'];
         $pais->save();
      }
      else if( isset($_GET['delete']) )
      {
         $pais = new pais();
         $pais->codpais = $_GET['delete'];
         $pais->delete();
      }
   }

   public function all()
   {
      $pais = new pais();
      return $pais->all();
   }
}

?>
