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

require_once 'base/fs_cache.php';
require_once 'model/partida.php';

class admin_errores extends fs_controller
{
   public $partida;
   
   public function __construct()
   {
      parent::__construct('admin_errores', 'Errores en la base de datos', 'admin', TRUE, FALSE);
   }
   
   protected function process()
   {
      $this->partida = new partida();
   }
   
   public function version()
   {
      return parent::version().'-1';
   }
}

?>
