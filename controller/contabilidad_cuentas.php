<?php

require_once 'model/cuenta.php';
require_once 'model/ejercicio.php';

class contabilidad_cuentas extends fs_controller
{
   public $cuenta;
   public $ejercicio;
   public $resultados;
   public $resultados2;
   public $offset;

   public function __construct()
   {
      parent::__construct('contabilidad_cuentas', 'Cuentas', 'contabilidad', FALSE, TRUE);
   }
   
   protected function process()
   {
      $this->cuenta = new cuenta();
      $this->ejercicio = new ejercicio();
      $this->custom_search = TRUE;
      
      if( isset($_GET['offset']) )
         $this->offset = intval($_GET['offset']);
      else
         $this->offset = 0;
      
      if($this->query != '')
      {
         $this->resultados = $this->cuenta->search($this->query);
         $subc = new subcuenta();
         $this->resultados2 = $subc->search($this->query);
      }
      else if( isset($_POST['ejercicio']) )
      {
         $eje = $this->ejercicio->get($_POST['ejercicio']);
         if($eje)
            $eje->set_default();
         $this->resultados = $this->cuenta->all_from_ejercicio($_POST['ejercicio'], $this->offset);
      }
      else
         $this->resultados = $this->cuenta->all($this->offset);
   }
   
   public function anterior_url()
   {
      $url = '';
      if($this->query!='' AND $this->offset>'0')
         $url = $this->url()."&query=".$this->query."&offset=".($this->offset-FS_ITEM_LIMIT);
      else if($this->query=='' AND $this->offset>'0')
         $url = $this->url()."&offset=".($this->offset-FS_ITEM_LIMIT);
      return $url;
   }
   
   public function siguiente_url()
   {
      $url = '';
      if($this->query!='' AND count($this->resultados)==FS_ITEM_LIMIT)
         $url = $this->url()."&query=".$this->query."&offset=".($this->offset+FS_ITEM_LIMIT);
      else if($this->query=='' AND count($this->resultados)==FS_ITEM_LIMIT)
         $url = $this->url()."&offset=".($this->offset+FS_ITEM_LIMIT);
      return $url;
   }
}

?>
