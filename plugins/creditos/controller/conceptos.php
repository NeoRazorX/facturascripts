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

require_model('concepto.php');

class conceptos extends fs_controller
{
   public $concepto;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Conceptos', 'tesoreria', FALSE, TRUE);
   }
   
   protected function process()
   {
      $this->custom_search = TRUE;
      $this->concepto = new concepto();
      
      /// desactivamos la barra de botones
      $this->show_fs_toolbar = FALSE;
      
      if( isset($_POST['descripcion']) )
      {
         /// si tenemos el id, buscamos el concepto y así lo modificamos
         if( isset($_POST['idconcepto']) )
         {
            $coms0 = $this->concepto->get($_POST['idconcepto']);
         }
         else /// si no está el id, seguimos como si fuese nuevo
         {
            $coms0 = new concepto();
            $coms0->idconcepto = $this->concepto->nuevo_numero();
         }
         
         $coms0->descripcion = $_POST['descripcion'];
         $coms0->precio = intval($_POST['precio']);
         
         if( $coms0->save() )
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
         $coms0 = $this->concepto->get($_GET['delete']);
         if($coms0)
         {
            if( $coms0->delete() )
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
   
   public function listar_conceptos()
   {
      if($this->query != '')
      {
         return $this->concepto->buscar($_POST['query']);
      }
      else
      {
         return $this->concepto->listar();
      }
   }
}