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

/**
 * Botón de la cabecera.
 */
class fs_button
{
   public $id;
   public $value;
   public $href;
   public $newpage;
   
   public function __construct($id, $value, $href='#', $np=FALSE)
   {
      $this->id = $id;
      $this->value = $value;
      $this->href = $href;
      $this->newpage = $np;
   }
   
   public function HTML()
   {
      if($this->newpage)
         $target = " target='_blank'";
      else
         $target = '';
      
      return "<a id='".$this->id."' class='btn'".$target." href='".$this->href."'>".$this->value."</a>";
   }
}


/**
 * Botón de la cabecera con imagen.
 */
class fs_button_img extends fs_button
{
   public $img;
   public $remove;
   
   public function __construct($id, $value, $img='add.png', $href='#', $remove=FALSE, $np=FALSE)
   {
      parent::__construct($id, $value, $href, $np);
      
      $this->img = $img;
      $this->remove = $remove;
   }
   
   public function HTML()
   {
      if($this->remove)
         $class = " class='remove'";
      else
         $class = " class='btn_img'";
      
      if($this->newpage)
         $target = " target='_blank'";
      else
         $target = '';
      
      return "<a id='".$this->id."'".$class.$target." href='".$this->href."'>".
              "<img src='view/img/".$this->img."' alt='".$this->img."'/> ".$this->value."</a>";
   }
}
