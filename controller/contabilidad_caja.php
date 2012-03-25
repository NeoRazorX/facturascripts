<?php

require_once 'model/albaran_cliente.php';

class contabilidad_caja extends fs_controller
{
   public $albaran;
   public $resultados;
   
   public function __construct()
   {
      parent::__construct('contabilidad_caja', 'Caja', 'contabilidad', FALSE, TRUE);
   }
   
   protected function process()
   {
      $this->albaran = new albaran_cliente();
      $this->resultados = $this->albaran->all_from_day();
   }
}

?>
