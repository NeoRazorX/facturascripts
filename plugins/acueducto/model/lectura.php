<?php

class lectura extends fs_model
{
   public $idlectura;
   public $codcliente;
   public $idcontador;
   public $fecha;
   public $lectura;
   public $tecnico;
   public $verificada;
   public $imputacion;
   public $usuario;


   public function __construct($g = FALSE)
   {
      parent::__construct('lecturas', 'plugins/acueducto/');
      
      if($g)
      {
         $this->idlectura = $g['idlectura'];
         $this->codcliente = $g['codcliente'];
         $this->idcontador = $g['idcontador'];
         $this->fecha = date('d-m-Y', strtotime($g['fecha']));
         $this->lectura = intval($g['lectura']);
         $this->tecnico = $g['tecnico'];
         
         /// str2bool() para leer un valor lógico de la tabla
         $this->verificada = $this->str2bool($g['verificada']);
         
         $this->imputacion = date('d-m-Y', strtotime($g['imputacion']));
         $this->usuario = $g['usuario'];
      }
      else
      {
         $this->idlectura = NULL;
         $this->codcliente = "";
         $this->idcontador = "";
         $this->fecha = date('d-m-Y');
         $this->lectura = 0;
         $this->tecnico = "";
         $this->verificada = 0;
         $this->imputacion = date('d-m-Y');
         $this->usuario = "";
         
      }
   }
   
   protected function install() {
      ;
   }
   
   public function get($id)
   {
      $data = $this->db->select("select * from lecturas where idlectura = ".$this->var2str($id).";");
      if($data)
      {
         return new lectura($data[0]);
      }
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->idlectura) )
      {
         return FALSE;
      }
      else
      {
         return $this->db->select("select * from lecturas where idlectura = ".$this->var2str($this->idlectura).";");
      }
   }
   
   public function test() {
      ;
   }
   
   public function nuevo_numero()
   {
      $data = $this->db->select("select max(idlectura) as num from lecturas;");
      if($data)
         return intval($data[0]['num']) + 1;
      else
         return 1;
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE lecturas set codcliente = ".$this->var2str($this->codcliente).
                 ", idcontador = ".$this->var2str($this->idcontador).
                 ", fecha = ".$this->var2str($this->fecha).
                 ", lectura = ".$this->var2str($this->lectura). 
                 ", tecnico = ".$this->var2str($this->tecnico).
                 ", verificada = ".$this->var2str($this->verificada).
                 ", imputacion = ".$this->var2str($this->imputacion).
                 ", usuario = ".$this->var2str($this->usuario).
                 " where idlectura = ".$this->var2str($this->idlectura).";";
                 
      }
      else
      {
         $sql = "INSERT into lecturas (idlectura,codcliente,idcontador,fecha,lectura,tecnico,verificada,imputacion,usuario) VALUES ("
                 .$this->var2str($this->idlectura).","
                 .$this->var2str($this->codcliente).","
                 .$this->var2str($this->idcontador).","
                 .$this->var2str($this->fecha).","
                 .$this->var2str($this->lectura).","
                 .$this->var2str($this->tecnico).","
                 .$this->var2str($this->verificada).","
                 .$this->var2str($this->imputacion).","
                 .$this->var2str($this->usuario).");";
      }
      
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      return $this->db->exec("delete from lecturas where idlectura = ".$this->var2str($this->idlectura).";");
   }
   
   public function listar()
   {
      $listag = array();
      
      $data = $this->db->select("select * from lecturas order by idlectura desc;");
      if($data)
      {
         foreach($data as $d)
         {
            $listag[] = new lectura($d);
         }
      }
      
      return $listag;
   }
   
   public function buscar($texto = '')
   {
      $listag = array();
      
      $data = $this->db->select("select * from lecturas where codcliente like '%".$texto."%' order by idlectura desc;");
      if($data)
      {
         foreach($data as $d)
         {
            $listag[] = new lectura($d);
         }
      }
      
      return $listag;
   }

 }