<?php

require_once 'base/fs_model.php';

class linea_pedido_cliente extends fs_model
{
   public $pvptotal;
   public $idpedido;
   public $cantidad;
   public $descripcion;
   public $idlinea;
   public $codimpuesto;
   public $iva;
   public $dtopor;
   public $pvpsindto;
   public $pvpunitario;
   public $referencia;
   
   public function __construct($l = FALSE)
   {
      parent::__construct('lineaspedidoscli', 'plugins/pedidos/');
      
      if($l)
      {
         $this->cantidad = floatval($l['cantidad']);
         $this->codimpuesto = $l['codimpuesto'];
         $this->descripcion = $l['descripcion'];
         $this->dtopor = floatval($l['dtopor']);
         $this->idlinea = $l['idlinea'];
         $this->idpedido = $l['idpedido'];
         $this->iva = floatval($l['iva']);
         $this->pvpsindto = floatval($l['pvpsindto']);
         $this->pvptotal = floatval($l['pvptotal']);
         $this->pvpunitario = floatval($l['pvpunitario']);
         $this->referencia = $l['referencia'];
      }
      else
      {
         $this->cantidad = 0;
         $this->codimpuesto = NULL;
         $this->descripcion = NULL;
         $this->dtopor = 0;
         $this->idlinea = NULL;
         $this->idpedido = NULL;
         $this->iva = 0;
         $this->pvpsindto = 0;
         $this->pvptotal = 0;
         $this->pvpunitario = 0;
         $this->referencia = NULL;
      }
   }
   
   protected function install()
   {
      return '';
   }
   
   public function url()
   {
      if( is_null($this->idpedido) )
         return 'index.php?page=ver_pedido_cli';
      else
         return 'index.php?page=ver_pedido_cli&id='.$this->idpedido;
   }
   
   public function exists()
   {
      
   }
   
   public function test()
   {
      
   }
   
   public function save()
   {
      
   }
   
   public function delete()
   {
      
   }
   
   public function all_from_pedido($idp)
   {
      $plist = array();
      
      $data = $this->db->select("SELECT * FROM ".$this->table_name.
              " WHERE idpedido = ".$this->var2str($idp)." ORDER BY referencia ASC;");
      if($data)
      {
         foreach($data as $d)
            $plist[] = new linea_pedido_cliente($d);
      }
      
      return $plist;
   }
}
