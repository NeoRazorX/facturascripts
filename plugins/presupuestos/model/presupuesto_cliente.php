<?php

require_once 'base/fs_model.php';

class presupuesto_cliente extends fs_model
{
   public $codigo;
   public $totaleuros;
   public $idpresupuesto;
   public $direccion;
   public $codpago;
   public $tasaconv;
   public $codejercicio;
   public $total;
   public $nombrecliente;
   public $observaciones;
   public $codcliente;
   public $codpais;
   public $editable;
   public $codalmacen;
   public $coddir;
   public $cifnif;
   public $provincia;
   public $codagente;
   public $fecha;
   public $neto;
   public $apartado;
   public $codserie;
   public $codpostal;
   public $totaliva;
   public $ciudad;
   public $numero;
   public $coddivisa;
   
   public function __construct($p = FALSE)
   {
      parent::__construct('presupuestoscli', 'plugins/presupuestos/');
      
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
         $this->fecha = Date('d-m-Y', strtotime($p['fecha']));
         $this->idpresupuesto = $this->intval($p['idpresupuesto']);
         $this->neto = floatval($p['neto']);
         $this->nombrecliente = $p['nombrecliente'];
         $this->numero = $this->intval($p['numero']);
         $this->observaciones = $p['observaciones'];
         $this->provincia = $p['provincia'];
         $this->tasaconv = floatval($p['tasaconv']);
         $this->total = floatval($p['total']);
         $this->totaleuros = floatval($p['totaleuros']);
         $this->totaliva = floatval($p['totaliva']);
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
         $this->editable = NULL;
         $this->fecha = Date('d-m-Y');
         $this->idpresupuesto = NULL;
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
      if( is_null($this->idpresupuesto) )
         return 'index.php?page=ver_presupuesto_cli';
      else
         return 'index.php?page=ver_presupuesto_cli&id='.$this->idpresupuesto;
   }
   
   public function get($id)
   {
      $data = $this->db->select("SELECT * FROM ".$this->table_name." WHERE idpresupuesto = ".$this->var2str($id).";");
      if($data)
         return new presupuesto_cliente($data[0]);
      else
         return FALSE;
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
   
   public function all()
   {
      $plist = array();
      
      $data = $this->db->select("SELECT * FROM ".$this->table_name." ORDER BY fecha DESC;");
      if($data)
      {
         foreach($data as $d)
            $plist[] = new presupuesto_cliente($d);
      }
      
      return $plist;
   }
}