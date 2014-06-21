<?php

require_once 'base/fs_model.php';

class pedido_cliente extends fs_model
{
   public $apartado;
   public $cifnif;
   public $ciudad;
   public $codagente;
   public $codalmacen;
   public $codcliente;
   public $coddir;
   public $coddivisa;
   public $codejercicio;
   public $codigo;
   public $codpago;
   public $codpais;
   public $codpostal;
   public $codserie;
   public $direccion;
   public $editable;
   public $fecha;
   public $idpedido;
   public $neto;
   public $nombrecliente;
   public $numero;
   public $observaciones;
   public $provincia;
   public $tasaconv;
   public $total;
   public $totaleuros;
   public $totaliva;
   
   public function __construct($p = FALSE)
   {
      parent::__construct('pedidoscli', 'plugins/pedidos/');
      
      if($p)
      {
         $this->apartado = $p['apartado'];
         $this->cifnif = $p['cifnif'];
         $this->ciudad = $p['ciudad'];
         $this->codagente = $p['codagente'];
         $this->codalmacen = $p['codalmacen'];
         $this->codcliente = $p['codcliente'];
         $this->coddir = $p['coddir'];
         $this->coddivisa = $p['coddivisa'];
         $this->codejercicio = $p['codejercicio'];
         $this->codigo = $p['codigo'];
         $this->codpago = $p['codpago'];
         $this->codpais = $p['codpais'];
         $this->codpostal = $p['codpostal'];
         $this->codserie = $p['codserie'];
         $this->direccion = $p['direccion'];
         $this->editable = $p['editable'];
         $this->fecha = date('d-m-Y', strtotime($p['fecha']));
         $this->idpedido = $p['idpedido'];
         $this->neto = $p['neto'];
         $this->nombrecliente = $p['nombrecliente'];
         $this->numero = $p['numero'];
         $this->observaciones = $p['observaciones'];
         $this->provincia = $p['provincia'];
         $this->tasaconv = $p['tasaconv'];
         $this->total = $p['total'];
         $this->totaleuros = $p['totaleuros'];
         $this->totaliva = $p['totaliva'];
      }
      else
      {
         $this->apartado = NULL;
         $this->cifnif = NULL;
         $this->ciudad = NULL;
         $this->codagente = NULL;
         $this->codalmacen = NULL;
         $this->codcliente = NULL;
         $this->coddir = NULL;
         $this->coddivisa = NULL;
         $this->codejercicio = NULL;
         $this->codigo = NULL;
         $this->codpago = NULL;
         $this->codpais = NULL;
         $this->codpostal = NULL;
         $this->codserie = NULL;
         $this->direccion = NULL;
         $this->editable = TRUE;
         $this->fecha = date('d-m-Y');
         $this->idpedido = NULL;
         $this->neto = 0;
         $this->nombrecliente = NULL;
         $this->numero = 0;
         $this->observaciones = '';
         $this->provincia = NULL;
         $this->tasaconv = 1;
         $this->total = 0;
         $this->totaleuros = 0;
         $this->totaliva = 0;
      }
   }
   
   protected function install()
   {
      return '';
   }
   
   public function url()
   {
      if( is_null($this->idpedido) )
         return 'index.php?page=pedidos_cliente';
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
   
   public function all($offset = 0)
   {
      $plist = array();
      
      $data = $this->db->select_limit("SELECT * FROM ".$this->table_name." ORDER BY fecha DESC", FS_ITEM_LIMIT, $offset);
      if($data)
      {
         foreach($data as $d)
            $plist[] = new pedido_cliente($d);
      }
      
      return $plist;
   }
}
