<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class ver_pedido_cli extends fs_controller
{
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Pedido...', 'general', FALSE, FALSE);
   }
}