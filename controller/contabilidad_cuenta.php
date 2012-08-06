<?php

require_once 'model/cuenta.php';

class contabilidad_cuenta extends fs_controller
{
   public $cuenta;
   public $ejercicio;
   
   public function __construct()
   {
      parent::__construct('contabilidad_cuenta', 'Cuenta', 'contabilidad', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->ppage = $this->page->get('contabilidad_cuentas');
      
      if( isset($_GET['id']) )
      {
         $this->cuenta = new cuenta();
         $this->cuenta = $this->cuenta->get($_GET['id']);
         if($this->cuenta)
         {
            $this->page->title = 'Cuenta: '.$this->cuenta->codcuenta;
            $this->ejercicio = $this->cuenta->get_ejercicio();
         }
      }
   }
   
   public function version() {
      return parent::version().'-1';
   }
   
   public function url()
   {
      if($this->cuenta)
         return $this->cuenta->url();
      else
         $this->page->url();
   }
}

?>
