<?php

require_model('presupuesto_cliente.php');

class presupuestos_cliente extends fs_controller
{
   public $presupuesto;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Presupuestos cliente', 'general');
   }
   
   protected function process()
   {
      $this->presupuesto = new presupuesto_cliente();
   }
}