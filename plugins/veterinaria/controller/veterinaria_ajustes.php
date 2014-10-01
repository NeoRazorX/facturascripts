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

require_model('fbm_ajustes.php');
require_model('fbm_raza.php');

class veterinaria_ajustes extends fs_controller
{
   public $ajustes;
   public $raza;

   public function __construct()
   {
      parent::__construct(__CLASS__, 'Ajustes', 'Veterinaria', TRUE, TRUE);
   }
   
   protected function process()
   {
      $this->show_fs_toolbar = FALSE;
      
      $this->ajustes = new fbm_ajustes();
      $this->raza = new fbm_raza();
      
      if( isset($_POST['especie']) ) /// crear/modificar especie/raza
      {
         if( isset($_POST['id']) )
         {
            $raza0 = $this->raza->get($_POST['id']);
            
            if(!$raza0)
            {
               $raza0 = new fbm_raza();
            }
         }
         else
            $raza0 = new fbm_raza();
         
         $raza0->especie = $_POST['especie'];
         $raza0->nombre = $_POST['nombre'];
         if( $raza0->save() )
         {
            $this->new_message('Raza guardada correctamente.');
         }
         else
            $this->new_error_msg('Imposible guardar la raza.');
      }
      if( isset($_POST['tipo']) ) /// crear modificar analitica/desparasitación/vacuna
      {
         if( isset($_POST['id']) )
         {
            $ajuste0 = $this->ajustes->get($_POST['id']);
            
            if(!$ajuste0)
            {
               $ajuste0 = new fbm_ajustes();
            }
         }
         else
            $ajuste0 = new fbm_ajustes();
         
         $ajuste0->dias = intval($_POST['dias']);
         $ajuste0->nombre = $_POST['nombre'];
         $ajuste0->tipo = $_POST['tipo'];
         
         if( $ajuste0->save() )
         {
            $this->new_message('Datos guardados correctamente.');
         }
         else
            $this->new_error_msg('Error al guardar los datos.');
      }
      else if( isset($_GET['delete']) )
      {
         $ajuste0 = $this->ajustes->get($_GET['delete']);
         if($ajuste0)
         {
            if( $ajuste0->delete() )
            {
               $this->new_message('Datos liminados correctamente.');
            }
            else
               $this->new_error_msg('Error al eliminar los datos.');
         }
         else
            $this->new_error_msg('Datos no encontrados.');
      }
      else if( isset($_GET['delete_raza']) ) /// eliminar una especie/raza
      {
         $raza0 = $this->raza->get($_GET['delete_raza']);
         if($raza0)
         {
            if( $raza0->delete() )
            {
               $this->new_message('Raza eliminada correctamente.');
            }
            else
               $this->new_error_msg('Error al eliminar la raza.');
         }
         else
            $this->new_error_msg('Raza no encontrada.');
      }
   }
}
