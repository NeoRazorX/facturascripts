<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2014  Carlos Garcia Gomez  neorazorx@gmail.com
 *                     GISBEL JOSE          gpg841@gmail.com
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

require_model('periodo.php');

class periodos extends fs_controller
{
   public $periodo;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Periodos', 'tesoreria');
   }
   
   protected function process()
   {
      $this->periodo = new periodo();
      
      /// desactivamos la barra de botones
      $this->show_fs_toolbar = FALSE;
      
      if( isset($_POST['codperiodo']) )
      {
         $pe0 = $this->periodo->get($_POST['codperiodo']);
         if( !$pe0 )
         {
            $pe0 = new periodo();
            $pe0->codperiodo = $_POST['codperiodo'];
         }
         $pe0->descripcion = $_POST['descripcion'];
         $pe0->cadencia = $_POST['cadencia'];
         
         if( $pe0->save() )
            $this->new_message('Periodo '.$pe0->codperiodo.' guardado correctamente.');
         else
            $this->new_error_msg('Error al guardar el Periodo.');
      }
      else
      {
         for($i = 0; isset($_POST['codperiodo_'.$i]); $i++)
         {
            $pe0 = $this->periodo->get($_POST['codperiodo_'.$i]);
            if($pe0)
            {
               if( isset($_POST['delete_'.$i]) )
               {
                  if(FS_DEMO)
                  {
                     $this->new_error_msg('En el modo demo no puedes eliminar Periodos.
                        Otro usuario podría necesitarlas.');
                  }
                  else if( $pe0->delete() )
                     $this->new_message('Periodo '.$pe0->codperiodo.' eliminado correctamente.');
                  else
                     $this->new_error_msg('Error al eliminar el Periodo '.$pe0->codperiodo.'.');
               }
               else
               {
                  $pe0->descripcion = $_POST['descripcion_'.$i];
                  $pe0->cadencia = $_POST['cadencia_'.$i];
                  if( !$pe0->save() )
                     $this->new_error_msg('Error al guardar el Periodo.');
               }
            }
            else
               $this->new_error_msg('Periodo '.$_POST['codperiodo_'.$i].' no encontrado.');
         }
      }
   }
}
