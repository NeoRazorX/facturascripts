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

require_model('pais.php');

class admin_paises extends fs_controller
{
   public $pais;

   public function __construct()
   {
      parent::__construct(__CLASS__, 'Paises', 'admin', FALSE, TRUE);
   }
   
   protected function process()
   {
      $this->pais = new pais();
      
      if( isset($_POST['scodpais']) )
      {
         $pais = $this->pais->get($_POST['scodpais']);
         if( !$pais )
         {
            $pais = new pais();
            $pais->codpais = $_POST['scodpais'];
         }
         
         $pais->codiso = $_POST['scodiso'];
         $pais->nombre = $_POST['snombre'];
         
         if( $pais->save() )
         {
            $this->new_message("País ".$pais->nombre." guardado correctamente.");
         }
         else
            $this->new_error_msg("¡Imposible guardar el país!");
      }
      else if( isset($_GET['delete']) )
      {
         if(FS_DEMO)
         {
            $this->new_error_msg('En el modo demo no puedes eliminar paises. Otro usuario podría necesitarlo.');
         }
         else
         {
            $pais = $this->pais->get($_GET['delete']);
            if( $pais )
            {
               if( $pais->delete() )
               {
                  $this->new_message("País ".$pais->nombre." eliminado correctamente.");
               }
               else
                  $this->new_error_msg("¡Imposible eliminar el país!");
            }
            else
               $this->new_error_msg("¡País no encontrado!");
         }
      }
   }
}
