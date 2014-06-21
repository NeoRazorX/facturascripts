<?php

require_model('presupuesto_cliente.php');

class ver_presupuesto_cli extends fs_controller
{
   public $presupuesto;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Presupuesto', 'general', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->ppage = $this->page->get('presupuestos_cliente');
      $presupuesto = new presupuesto_cliente();
      
      if( isset($_GET['id']) )
      {
         $this->presupuesto = $presupuesto->get($_GET['id']);
      }
      
      if($this->presupuesto)
      {
         $this->page->title = $this->presupuesto->codigo;
      }
   }
}