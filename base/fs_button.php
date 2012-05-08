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

class fs_button
{
   public $id;
   public $value;
   public $href;
   public $class;
   public $img;
   public $alt;
   
   public function __construct($id, $value, $href='#', $class='', $img='img/add.png', $alt='+')
   {
      $this->id = $id;
      $this->value = $value;
      $this->href = $href;
      $this->class = $class;
      $this->img = $img;
      $this->alt = $alt;
   }
}

?>
