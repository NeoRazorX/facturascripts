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

require_model('clan_familiar.php');

class ventas_clanes extends fs_controller
{
   public $clan;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Clanes familiares', 'ventas');
   }
   
   protected function process()
   {
      $this->show_fs_toolbar = FALSE;
      $this->clan = new clan_familiar();
      
      if( isset($_GET['delete']) )
      {
         $clan = $this->clan->get($_GET['delete']);
         if($clan)
         {
            if( $clan->delete() )
               $this->new_message('Clan eliminado correctamente.');
            else
               $this->new_message('Ha sido imposible eliminar el clan.');
         }
         else
            $this->new_message('Clan no encontrado.');
      }
      else if( isset($_POST['nombre']) )
      {
         $this->clan->nombre = $_POST['nombre'];
         if( $this->clan->save() )
            header('Location: '.$this->clan->url());
         else
            $this->new_error_msg('Imposible guardar el clan familiar.');
      }
   }
}
