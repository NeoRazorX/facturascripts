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
require_model('fbm_analisis.php');
require_model('fbm_mascota.php');

class veterinaria_analisis extends fs_controller
{
   public $ajustes;
   public $analisis;
   public $mascota;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Análisis', 'veterinaria', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->show_fs_toolbar = FALSE;
      
      $analisis = new fbm_analisis();
      $mascota = new fbm_mascota();
      $this->ajustes = new fbm_ajustes();
      
      if( isset($_GET['id']) )
      {
         $this->analisis = $analisis->get($_GET['id']);
      }
      
      if($this->analisis)
      {
         $this->mascota = $mascota->get($this->analisis->idmascota);
         
         if( isset($_POST['fecha']) )
         {
            $this->analisis->fecha = $_POST['fecha'];
            $this->analisis->resultado = $_POST['resultado'];
            $this->analisis->notas = $_POST['notas'];
            
            if( isset($_POST['idtipo']) )
            {
               $ajuste = $this->ajustes->get($_POST['idtipo']);
               if($ajuste)
               {
                  $this->analisis->idtipo = $ajuste->id;
                  $this->analisis->nombre = $ajuste->nombre;
               }
               
               $this->analisis->nueva_fecha = $_POST['nueva_fecha'];
            }
            
            if( $this->analisis->save() )
            {
               $this->new_message('Datos modificados correctamente.');
            }
            else
               $this->new_error_msg('Error al guardar los datos.');
         }
      }
      else
         $this->new_error_msg('Análisis no encontrado.');
   }
   
   public function url()
   {
      if( !isset($this->analisis) )
      {
         return parent::url();
      }
      else if($this->analisis)
      {
         return $this->analisis->url();
      }
      else
         return parent::url();
   }
}
