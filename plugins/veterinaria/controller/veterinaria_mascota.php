<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_model('mascota.php');

/**
 * Description of veterinaria_mascota
 *
 * @author carlos
 */
class veterinaria_mascota extends fs_controller
{
   public $mascota;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Mascota...', 'veterinaria', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->show_fs_toolbar = FALSE;
      
      $mascota = new mascota();
      $this->mascota = FALSE;
      
      if( isset($_GET['id']) )
      {
         $this->mascota = $mascota->get($_GET['id']);
      }
      
      if($this->mascota)
      {
         $this->page->title = $this->mascota->nombre;
      }
      else
         $this->new_error_msg('Mascota no encontrada.');
   }
}
