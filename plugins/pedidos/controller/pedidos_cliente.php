<?php

require_model('pedido_cliente.php');

class pedidos_cliente extends fs_controller
{
   public $pedido;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Pedidos de cliente', 'general');
   }
   
   protected function process()
   {
      $this->pedido = new pedido_cliente();
   }
}