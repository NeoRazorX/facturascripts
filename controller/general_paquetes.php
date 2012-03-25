<?php

require_once 'model/articulo.php';
require_once 'model/paquete.php';

class general_paquetes extends fs_controller
{
   public $paquete;
   public $cache_paquete;
   
   public function __construct()
   {
      parent::__construct('general_paquetes', 'Paquetes', 'general', FALSE, TRUE);
   }
   
   protected function process()
   {
      $this->paquete = new paquete();
      $this->cache_paquete = new cache_paquete();
      
      if( isset($_GET['add2cache']) )
      {
         $this->cache_paquete->add($_GET['add2cache']);
      }
      else if( isset($_GET['cleancache']) )
      {
         $this->cache_paquete->clean();
      }
      else if( isset($_GET['fillcache']) )
      {
         $art = new articulo();
         foreach($art->all() as $a)
         {
            $this->cache_paquete->add($a->referencia);
         }
      }
   }
}

?>
