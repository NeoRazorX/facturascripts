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

require_model('cobrador.php');

class cobradores extends fs_controller
{
   public $cobrador;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Cobradores', 'creditos', FALSE, TRUE);
   }
   
   protected function process()
   {
      $this->custom_search = TRUE;
      $this->cobrador = new cobrador();
      
      /// desactivamos la barra de botones
      $this->show_fs_toolbar = FALSE;
      
      if( isset($_POST['nombre']) )
      {
         /// si tenemos el id, buscamos el cobrador y así lo modificamos
         if( isset($_POST['idcobrador']) )
         {
            $cobr0 = $this->cobrador->get($_POST['idcobrador']);
         }
         else /// si no está el id, seguimos como si fuese nuevo
         {
            $cobr0 = new cobrador();
            $cobr0->idcobrador = $this->cobrador->nuevo_numero();
         }
         
         $cobr0->nombre = $_POST['nombre'];
         $cobr0->telefono = $_POST['telefono'];
         
         if( $cobr0->save() )
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
         $cobr0 = $this->cobrador->get($_GET['delete']);
         if($cobr0)
         {
            if( $cobr0->delete() )
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
   
   public function listar_cobradores()
   {
      if( isset($_POST['query']) )
      {
         return $this->cobrador->buscar($_POST['query']);
      }
      else
      {
         return $this->cobrador->listar();
      }
   }
}
