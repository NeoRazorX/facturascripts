<?php

class contador extends fs_model
{
   public $idcontador;
   public $codcliente;
   public $numero;
   public $ubicacion;
   public $alta;
   public $lectura;
 
   public function __construct($g = FALSE)
   {
      parent::__construct('contadores', 'plugins/acueducto/');
      
      if($g)
      {
         $this->idcontador = $g['idcontador'];
         $this->codcliente = $g['codcliente'];
         $this->numero = $g['numero'];
         $this->ubicacion = $g['ubicacion'];
         $this->alta = date('d-m-Y', strtotime($g['alta']));
         $this->lectura = date('d-m-Y', strtotime($g['lectura']));
      }
      else
      {
         $this->idlectura = NULL;
         $this->codcliente = "";
         $this->numero = "";
         $this->ubicacion = "";
         $this->fecha = date('d-m-Y');
         $this->imputacion = date('d-m-Y');
         $this->usuario = "";
         
      }
   }
   
   protected function install() {
      ;
   }
   
   public function get($id)
   {
      $data = $this->db->select("select * from contadores where idcontador = ".$this->var2str($id).";");
      if($data)
      {
         return new lectura($data[0]);
      }
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->idcontador) )
      {
         return FALSE;
      }
      else
      {
         return $this->db->select("select * from contadores where idcontador = ".$this->var2str($this->idcontador).";");
      }
   }
   
   public function test() {
      ;
   }
   
   public function nuevo_numero()
   {
      $data = $this->db->select("select max(idcontador) as num from contadores;");
      if($data)
         return intval($data[0]['num']) + 1;
      else
         return 1;
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE contadores set codcliente = ".$this->var2str($this->codcliente).
                 ", numero = ".$this->var2str($this->numero).
                 ", ubicacion = ".$this->var2str($this->ubicacion).
                 ", alta = ".$this->var2str($this->alta).
                 ", lectura = ".$this->var2str($this->lectura).
                 " where idcontador = ".$this->var2str($this->idcontador).";";
                 
      }
      else
      {
         $sql = "INSERT into contadores (idcontador,codcliente,numero,ubicacion,alta,lectura) VALUES ("
                 .$this->var2str($this->idlectura).","
                 .$this->var2str($this->codcliente).","
                 .$this->var2str($this->numero).","
                 .$this->var2str($this->ubicacion).","
                 .$this->var2str($this->fecha).","
                 .$this->var2str($this->lectura).");";
      }
      
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      return $this->db->exec("delete from contadores where idcontador = ".$this->var2str($this->idcontador).";");
   }
   
   public function listar()
   {
      $listag = array();
      
      $data = $this->db->select("select * from contadores;");
      if($data)
      {
         foreach($data as $d)
         {
            $listag[] = new contador($d);
         }
      }
      
      return $listag;
   }
   
   public function buscar($texto = '')
   {
      $listag = array();
      
      $data = $this->db->select("select * from contadores where codcliente like '%".$texto."%';");
      if($data)
      {
         foreach($data as $d)
         {
            $listag[] = new contador($d);
         }
      }
      
      return $listag;
   }

 }