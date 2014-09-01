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

require_model('cuenta_especial.php');

class cuentas_especiales extends fs_controller
{
   public $cuenta_especial;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Cuentas Especiales', 'contabilidad', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->ppage = $this->page->get('contabilidad_cuentas');
      $this->custom_search = TRUE;
      $this->cuenta_especial = new cuenta_especial();
      
      if( isset($_POST['descripcion']) )
      {
         /// si tenemos el id, buscamos el cuenta_especial y así lo modificamos
         if( isset($_POST['idcuentaesp']) )
         {
            $cesp0 = $this->cuenta_especial->get($_POST['idcuentaesp']);
         }
         
         if(!$cesp0)
         {
            $cesp0 = new cuenta_especial();
            $cesp0->idcuentaesp = $_POST['idcuentaesp'];
         }
         
         $cesp0->descripcion = $_POST['descripcion'];
         
         if( $cesp0->save() )
         {
            $this->new_message('Datos guardados correctamente.');
         }
         else
         {
            $this->new_error_msg('Imposible guardar los datos.');
         }
      }
      else if( isset($_GET['delete']) )
      {
         $cesp0 = $this->cuenta_especial->get($_GET['delete']);
         if($cesp0)
         {
            if( $cesp0->delete() )
            {
               $this->new_message('Identificador '. $_GET['delete'] .' eliminado correctamente.');
            }
            else
            {
               $this->new_error_msg('Imposible eliminar los datos.');
            }
         }
      }
   }
}