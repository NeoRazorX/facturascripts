<?php

class cargo extends fs_model
{
   public $idcargo;
   public $codcliente;
   public $idcontador;
   public $idconcepto;
   public $precio;
   public $cantidad;
   public $total;
   public $fecha;
   public $facturado;
   public $numero;
   public $imputacion;
   public $usuario;


   public function __construct($g = FALSE)
   {
      parent::__construct('cargos', 'plugins/acueducto/');
      
      if($g)
      {
         $this->idcargo = $g['idcargo'];
         $this->codcliente = $g['codcliente'];
         $this->idcontador = $g['idcontador'];
         $this->idconcepto = $g['idconcepto'];
         $this->precio = $g['precio'];
         $this->cantidad = $g['cantidad'];
         $this->total = $g['total'];
         $this->fecha = date('d-m-Y', strtotime($g['fecha']));
         $this->facturado = $this->str2bool($g['facturado']);
         $this->numero = $g['numero'];
         $this->imputacion = date('d-m-Y', strtotime($g['imputacion']));
         $this->usuario = $g['usuario'];
      }
      else
      {
         $this->idcargo = NULL;
         $this->codcliente = "";
         $this->idcontador = "";
         $this->idconcepto = "";
         $this->precio = 0;
         $this->cantidad = 0;
         $this->total = 0;
         $this->fecha = date('d-m-Y');
         $this->facturado = 0;
         $this->numero = 0;
         $this->imputacion = date('d-m-Y');
         $this->usuario = "";
         
      }
   }
   
   protected function install() {
      ;
   }
   
   public function get($id)
   {
      $data = $this->db->select("select * from cargos where idcargo = ".$this->var2str($id).";");
      if($data)
      {
         return new cargo($data[0]);
      }
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->idcargo) )
      {
         return FALSE;
      }
      else
      {
         return $this->db->select("select * from cargos where idcargo = ".$this->var2str($this->idcargo).";");
      }
   }
   
   public function test() {
      ;
   }
   
   public function nuevo_numero()
   {
      $data = $this->db->select("select max(idcargo) as num from cargos;");
      if($data)
         return intval($data[0]['num']) + 1;
      else
         return 1;
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE cargos set codcliente = ".$this->var2str($this->codcliente).
                 ", idcontador = ".$this->var2str($this->idcontador).
                 ", idconcepto = ".$this->var2str($this->idconcepto).
                 ", precio = ".$this->var2str($this->precio).
                 ", cantidad = ".$this->var2str($this->cantidad). 
                 ", total = ".$this->var2str($this->total).
                 ", fecha = ".$this->var2str($this->fecha).
                 ", facturado = ".$this->var2str($this->facturado).
                 ", numero = ".$this->var2str($this->numero).
                 ", imputacion = ".$this->var2str($this->imputacion).
                 ", usuario = ".$this->var2str($this->usuario).
                 " where idcargo = ".$this->var2str($this->idcargo).";";
                 
      }
      else
      {
         $sql = "INSERT into cargos (idcargo,codcliente,idcontador,idconcepto,precio,cantidad,total,fecha,facturado,numero,imputacion,usuario) VALUES ("
                 .$this->var2str($this->idcargo).","
                 .$this->var2str($this->codcliente).","
                 .$this->var2str($this->idcontador).","
                 .$this->var2str($this->idconcepto).","
                 .$this->var2str($this->precio).","
                 .$this->var2str($this->cantidad).","
                 .$this->var2str($this->total).","
                 .$this->var2str($this->fecha).","
                 .$this->var2str($this->facturado).","
                 .$this->var2str($this->numero).","
                 .$this->var2str($this->imputacion).","
                 .$this->var2str($this->usuario).");";
      }
      
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      return $this->db->exec("delete from cargos where idcargo = ".$this->var2str($this->idcargo).";");
   }
   
   public function listar()
   {
      $listag = array();
      
      $data = $this->db->select("select * from cargos order by idcargo desc;");
      if($data)
      {
         foreach($data as $d)
         {
            $listag[] = new cargo($d);
         }
      }
      
      return $listag;
   }
   
   public function buscar($texto = '')
   {
      $listag = array();
      
      $data = $this->db->select("select * from cargos where codcliente like '%".$texto."%' order by idcargo desc;");
      if($data)
      {
         foreach($data as $d)
         {
            $listag[] = new cargo($d);
         }
      }
      
      return $listag;
   }

 }