<?php

require_once 'model/caja.php';

class contabilidad_caja extends fs_controller
{
   public $caja;
   public $offset;
   public $resultados;
   
   public function __construct()
   {
      parent::__construct('contabilidad_caja', 'Caja', 'contabilidad', FALSE, TRUE);
   }
   
   protected function process()
   {
      $this->caja = new caja();
      
      if( isset($_GET['offset']) )
         $this->offset = intval($_GET['offset']);
      else
         $this->offset = 0;
      
      $this->resultados = $this->caja->all();
   }
   
   public function anterior_url()
   {
      $url = '';
      if($this->offset > '0')
         $url = $this->url()."&offset=".($this->offset-FS_ITEM_LIMIT);
      return $url;
   }
   
   public function siguiente_url()
   {
      $url = '';
      if(count($this->resultados)==FS_ITEM_LIMIT)
         $url = $this->url()."&offset=".($this->offset+FS_ITEM_LIMIT);
      return $url;
   }
}

?>
