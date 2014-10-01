<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
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
      
      if( isset($_GET['id']) )
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
         
         $this->page->title = $this->mascota->nombre;
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
