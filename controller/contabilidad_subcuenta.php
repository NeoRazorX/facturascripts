<?php

require_once 'model/subcuenta.php';

class contabilidad_subcuenta extends fs_controller
{
   public $subcuenta;
   public $cuenta;
   public $ejercicio;
   public $resultados;
   public $offset;
   
   public function __construct()
   {
      parent::__construct('contabilidad_subcuenta', 'Subcuenta', 'contabilidad', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->ppage = $this->page->get('contabilidad_cuentas');
      
      if( isset($_GET['id']) )
      {
         $this->subcuenta = new subcuenta();
         $this->subcuenta = $this->subcuenta->get($_GET['id']);
      }
      
      if($this->subcuenta)
      {
         $this->page->title = 'Subcuenta: '.$this->subcuenta->codsubcuenta;
         $this->cuenta = $this->subcuenta->get_cuenta();
         $this->ejercicio = $this->subcuenta->get_ejercicio();
         
         if( !$this->subcuenta->test() )
            $this->new_error_msg( $this->subcuenta->error_msg );
         
         if( isset($_GET['offset']) )
            $this->offset = intval($_GET['offset']);
         else
            $this->offset = 0;
         
         $this->resultados = $this->subcuenta->get_partidas($this->offset);
      }
      else
         $this->new_error_msg("Subcuenta no encontrada.");
   }
   
   public function version() {
      return parent::version().'-2';
   }
   
   public function url()
   {
      if( $this->subcuenta )
         return $this->subcuenta->url();
      else
         return $this->ppage->url();
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
