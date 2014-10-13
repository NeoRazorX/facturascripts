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
require_model('fbm_raza.php');

/**
 * Description of veterinaria_mascota
 *
 * @author carlos
 */
class veterinaria_mascota extends fs_controller
{
   public $ajustes;
   public $analisis;
   public $desparasitaciones;
   public $pesos;
   public $vacunas;
   public $mascota;
   public $raza;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Mascota...', 'veterinaria', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->show_fs_toolbar = FALSE;
      
      $mascota = new fbm_mascota();
      $this->mascota = FALSE;
      $this->raza = new fbm_raza();
      
      $analisis = new fbm_analisis();
      $this->analisis = array();
      $this->desparasitaciones = array();
      $this->pesos = array();
      $this->vacunas = array();
      
      $this->ajustes = new fbm_ajustes();
      
      if( isset($_POST['tipo']) AND $_POST['idmascota'] )
      {
         $this->mascota = $mascota->get($_POST['idmascota']);
         if($this->mascota)
         {
            $analisis2 = new fbm_analisis();
            $analisis2->idmascota = $_POST['idmascota'];
            $analisis2->tipo = $_POST['tipo'];
            
            if( isset($_POST['idtipo']) )
            {
               $ajuste = $this->ajustes->get($_POST['idtipo']);
               if($ajuste)
               {
                  $analisis2->idtipo = $ajuste->id;
                  $analisis2->nombre = $ajuste->nombre;
                  $analisis2->nueva_fecha = date('d-m-Y', time()+($ajuste->dias*86400));
               }
            }
            
            if( isset($_POST['resultado']) )
            {
               $analisis2->resultado = $_POST['resultado'];
               
               if($_POST['tipo'] == 'peso')
               {
                  $this->mascota->peso = $_POST['resultado'];
                  $this->mascota->save();
               }
            }
            
            if( isset($_POST['notas']) )
            {
               $analisis2->notas = $_POST['notas'];
            }
            
            if( $analisis2->save() )
            {
               $this->new_message('Datos guardados correctamente.');
               
               if($_POST['tipo'] != 'peso')
               {
                  header('Location: '.$analisis2->url());
               }
            }
            else
               $this->new_error_msg('Imposible guardar los datos.');
         }
      }
      else if( isset($_GET['id']) )
      {
         $this->mascota = $mascota->get($_GET['id']);
      }
      
      if($this->mascota)
      {
         if( isset($_POST['nombre']) )
         {
            $this->mascota->nombre = $_POST['nombre'];
            $this->mascota->altura = $_POST['altura'];
            $this->mascota->chip = $_POST['chip'];
            $this->mascota->color = $_POST['color'];
            $this->mascota->fecha_nac = $_POST['fecha_nac'];
            $this->mascota->idraza = $_POST['raza'];
            $this->mascota->pasaporte = $_POST['pasaporte'];
            $this->mascota->sexo = $_POST['sexo'];
            
            if( isset($_POST['esterilizado']) )
            {
               $this->mascota->esterilizado = TRUE;
               $this->mascota->fecha_esterilizado = $_POST['fecha_esterilizado'];
            }
            else
            {
               $this->mascota->esterilizado = FALSE;
               $this->mascota->fecha_esterilizado = NULL;
            }
            
            if( $this->mascota->save() )
            {
               $this->new_message('Datos guardadod correctamente.');
            }
            else
               $this->new_error_msg('Imposible guardar los datos.');
         }
         else if( isset($_GET['delete']) )
         {
            $analisis2 = $analisis->get($_GET['delete']);
            if($analisis2)
            {
               if( $analisis2->delete() )
               {
                  $this->new_message('Análisis eliminado correctamente.');
               }
               else
                  $this->new_error_msg('Imposible eliminar el análisis.');
            }
            else
               $this->new_error_msg('Análisis no encontrado.');
         }
         
         $this->page->title = $this->mascota->nombre;
         
         $this->analisis = $analisis->all_from($this->mascota->idmascota, 'analitica');
         $this->desparasitaciones = $analisis->all_from($this->mascota->idmascota, 'desparas');
         $this->pesos = $analisis->all_from($this->mascota->idmascota, 'peso');
         $this->vacunas = $analisis->all_from($this->mascota->idmascota, 'vacuna');
      }
      else
         $this->new_error_msg('Mascota no encontrada.');
   }
   
   public function url()
   {
      if( isset($this->mascota) )
      {
         if($this->mascota)
         {
            return $this->mascota->url();
         }
         else
            return parent::url();
      }
      else
         return parent::url();
   }
}
