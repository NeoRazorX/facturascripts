<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2014  Salvador Merino      salvaweb.co@gmail.com
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

require_model('diario.php');

class diarios extends fs_controller
{
   public $diario;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Diarios', 'tesoreria', FALSE, TRUE);
   }
   
   protected function process()
   {
      $this->custom_search = TRUE;
      $this->diario = new diario();
      
      /// desactivamos la barra de botones
      $this->show_fs_toolbar = FALSE;
      
      if( isset($_POST['descripcion']) )
      {
         /// si tenemos el id, buscamos la diario y así lo modificamos
         if( isset($_POST['iddiario']) )
         {
            $diar0 = $this->diario->get($_POST['iddiario']);
         }
         else /// si no está el id, seguimos como si fuese nuevo
         {
            $diar0 = new diario();
            $diar0->iddiario = $this->diario->nuevo_numero();
         }
         
         $diar0->descripcion = $_POST['descripcion'];
         
         if( $diar0->save() )
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
         $diar0 = $this->diario->get($_GET['delete']);
         if($diar0)
         {
            if( $diar0->delete() )
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
   
   public function listar_diarios()
   {
      if( isset($_POST['query']) )
      {
         return $this->diario->buscar($_POST['query']);
      }
      else
      {
         return $this->diario->listar();
      }
   }
}
