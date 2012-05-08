<?php

require_once 'core/fs_model.php';

class subcuenta extends fs_model
{
   public $codejercicio;
   public $coddivisa;
   public $recargo;
   public $iva;
   public $codimpuesto;
   public $codcuenta;
   public $idcuenta;
   public $saldo;
   public $haber;
   public $debe;
   public $descripcion;
   public $codsubcuenta;
   public $idsubcuenta;
   
   public function __construct($s=FALSE)
   {
      parent::__construct('co_subcuentas');
   }
   
   protected function install()
   {
      return "";
   }
   
   public function exists()
   {
      ;
   }
   
   public function save()
   {
      ;
   }
   
   public function delete()
   {
      ;
   }
   
   public function all($offset=0)
   {
      
   }
   
   public function all_from_cuenta($codcuenta, $offset=0)
   {
      
   }
   
   public function search($query, $offset=0)
   {
      
   }
}

?>
