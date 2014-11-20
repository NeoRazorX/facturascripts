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

require_model('ruta.php');
require_model('cobrador.php');

class rutas extends fs_controller
{
   public $ruta;
   public $cobrador;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Rutas', 'creditos', FALSE, TRUE);
   }
   
   protected function process()
   {
      $this->ruta = new ruta();
      $this->cobrador = new cobrador();
      
      /// desactivamos la barra de botones
      $this->show_fs_toolbar = FALSE;
      
      if( isset($_POST['descripcion']) )
      {
         /// si tenemos el id, buscamos la ruta y así lo modificamos
         if( isset($_POST['idruta']) )
         {
            $ruta0 = $this->ruta->get($_POST['idruta']);
         }
         else /// si no está el id, seguimos como si fuese nuevo
         {
            $ruta0 = new ruta();
            $ruta0->idruta = $this->ruta->nuevo_numero();
         }
         
         $ruta0->descripcion = $_POST['descripcion'];
         $ruta0->idcobrador = $_POST['idcobrador'];
         
         if( $ruta0->save() )
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
         $ruta0 = $this->ruta->get($_GET['delete']);
         if($ruta0)
         {
            if( $ruta0->delete() )
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
   
   public function listar_rutas()
   {
      if( isset($_POST['query']) )
      {
         return $this->ruta->buscar($_POST['query']);
      }
      else
      {
         return $this->ruta->listar();
      }
   }
}
