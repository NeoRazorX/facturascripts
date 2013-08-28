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

require_once 'model/serie.php';

class contabilidad_series extends fs_controller
{
   public $serie;
   
   public function __construct()
   {
      parent::__construct('contabilidad_series', 'Series', 'contabilidad', FALSE, TRUE);
   }
   
   protected function process()
   {
      $this->serie = new serie();
      $this->buttons[] = new fs_button_img('b_nueva_serie', 'nueva');
      
      if( isset($_POST['codserie']) )
      {
         $serie = $this->serie->get($_POST['codserie']);
         if( !$serie )
         {
            $serie = new serie();
            $serie->codserie = $_POST['codserie'];
         }
         $serie->descripcion = $_POST['descripcion'];
         $serie->siniva = isset($_POST['siniva']);
         if( $serie->save() )
            $this->new_message("Serie ".$serie->codserie." guardada correctamente");
         else
            $this->new_error_msg("¡Imposible guardar la serie!");
      }
   }
   
   public function version() {
      return parent::version().'-3';
   }
}

?>
