<?php

class facturacion extends fs_model
{
   public $idfacturacion;
   public $fecha;
   public $imputacion;
   public $usuario;


   public function __construct($g = FALSE)
   {
      parent::__construct('facturaciones', 'plugins/acueducto/');
      
      if($g)
      {
         $this->idfacturacion = $g['idfacturacion'];
         $this->fecha = date('d-m-Y', strtotime($g['fecha']));
         $this->imputacion = date('d-m-Y', strtotime($g['imputacion']));
         $this->usuario = $g['usuario'];
      }
      else
      {
         $this->idfacturacion = NULL;
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
      $data = $this->db->select("select * from facturaciones where idfacturacion = ".$this->var2str($id).";");
      if($data)
      {
         return new facturacion($data[0]);
      }
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->idfacturacion) )
      {
         return FALSE;
      }
      else
      {
         return $this->db->select("select * from facturaciones where idfacturacion = ".$this->var2str($this->idfacturacion).";");
      }
   }
   
   public function test() {
      ;
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE facturaciones set fecha = ".$this->var2str($this->fecha).
                 ", imputacion = ".$this->var2str($this->imputacion).
                 ", usuario = ".$this->var2str($this->usuario).
                 " where idfacturacion = ".$this->var2str($this->idfacturacion).";";
         return $this->db->exec($sql);
      }
      else
      {
         $sql = "INSERT into facturaciones (fecha,imputacion,usuario) VALUES ("
                 .$this->var2str($this->idfacturacion).","
                 .$this->var2str($this->fecha).","
                 .$this->var2str($this->imputacion).","
                 .$this->var2str($this->usuario).");";
         
         if( $this->db->exec($sql) )
         {
            $this->idfacturacion = $this->db->lastval();
            return TRUE;
         }
         else
            return FALSE;
      }
   }
   
   public function delete()
   {
      return $this->db->exec("delete from facturaciones where idfacturacion = ".$this->var2str($this->idfacturacion).";");
   }
   
   public function listar()
   {
      $listag = array();
      
      $data = $this->db->select("select * from facturaciones order by idfacturacion desc;");
      if($data)
      {
         foreach($data as $d)
         {
            $listag[] = new facturacion($d);
         }
      }
      
      return $listag;
   }
   
   public function buscar($texto = '')
   {
      $listag = array();
      
      $data = $this->db->select("select * from facturaciones where fecha like '%".$texto."%' order by idfacturacion desc;");
      if($data)
      {
         foreach($data as $d)
         {
            $listag[] = new facturacion($d);
         }
      }
      
      return $listag;
   }
   
   public function all()
   {
      $todos = array();

      $data = $this->db->select("SELECT * FROM facturaciones order by fecha;");
      if($data)
      {
         foreach($data as $d)
             $todos[] = new facturacion($d);
      }

      return $todos;
   }
   
   public function get_ultima()
   {
      $data = $this->db->select("select * from facturaciones order by fecha desc limit 1;");
      if($data)
      {
         return new facturacion($data[0]);
      }
      else
         return FALSE;
   }
 }