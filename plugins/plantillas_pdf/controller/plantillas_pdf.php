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

require_model('fs_extension.php');

class plantillas_pdf extends fs_controller
{
   public $plantillas;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Plantillas PDF', 'admin', TRUE, TRUE);
   }
   
   protected function process()
   {
      $fsext = new fs_extension();
      
      if( isset($_GET['delete']) )
      {
         $fsext2 = $fsext->get($_GET['delete']);
         if($fsext2)
         {
            if( $fsext2->delete() )
            {
               $this->new_message('Plantilla eliminada correctamente.');
            }
            else
               $this->new_error_msg('Imposible eliminar la plantilla.');
         }
         else
            $this->new_error_msg('Plantilla no encontrada.');
      }
      else if( isset($_POST['name']) )
      {
         $fsext2 = FALSE;
         if( isset($_POST['idp']) )
         {
            $fsext2 = $fsext->get($_POST['idp']);
         }
         
         if(!$fsext2)
            $fsext2 = new fs_extension();
         
         $fsext2->type = 'pdf';
         $fsext2->name = $_POST['name'];
         $fsext2->to = $_POST['page_to'];
         $fsext2->text = $_POST['html'];
         if( $fsext2->save() )
         {
            $this->new_message('Plantilla guardada correctamente.');
         }
         else
            $this->new_error_msg('Imposible guardar la plantilla.');
      }
      
      $this->plantillas = $fsext->all_4_type('pdf');
   }
}