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

require_model('vacuna.php');

class veterinaria_vacunas extends fs_controller
{
   public $vacuna;

   public function __construct()
   {
      parent::__construct('veterinaria_vacunas', 'Tipos Vacunas', 'Veterinaria', TRUE, TRUE);
   }
   
   protected function process()
   {
      $this->vacuna = new vacuna();
   }
}

?>