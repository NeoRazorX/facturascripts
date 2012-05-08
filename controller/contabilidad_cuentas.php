<?php

require_once 'model/cuenta.php';
require_once 'model/ejercicio.php';

class contabilidad_cuentas extends fs_controller
{
   public $cuenta;
   public $ejercicio;
   public $resultados;
   
   public function __construct()
   {
      parent::__construct('contabilidad_cuentas', 'Cuentas', 'contabilidad', FALSE, TRUE);
   }
   
   protected function process()
   {
      $this->cuenta = new cuenta();
      $this->ejercicio = new ejercicio();
      $this->custom_search = TRUE;
      
      if($this->query != '')
         $this->resultados = $this->cuenta->search($this->query);
      else if( isset($_POST['ejercicio']) )
         $this->resultados = $this->cuenta->all_from_ejercicio($_POST['ejercicio']);
      else
         $this->resultados = $this->cuenta->all();
   }
}

?>
