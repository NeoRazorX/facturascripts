<?php


class documento extends fs_model
{
   public $iddocumento;
   public $descripcion;
   
   public function __construct($g = FALSE)
   {
      parent::__construct('documentos', 'plugins/recibos_para_pagos_y_cobros/');
      
      if($g)
      {
         $this->iddocumento = $g['iddocumento'];
         $this->descripcion = $g['descripcion'];
      }
      else
      {
         $this->iddocumento = NULL;
         $this->descripcion = "";
      }
   }
   
   protected function install() 
   {        
      return "INSERT INTO documentos (iddocumento,descripcion) VALUES
            (0,'No Asignado'),
            (1,'Cotización de Cliente'),
            (2,'Pedido de Cliente'),
            (3,'Remisión de Cliente'),
            (4,'Factura de Cliente'),
            (5,'Remisión de Proveedor'),
            (6,'Factura de Proveedor');";
   }
   
   public function get($id)
   {
      $data = $this->db->select("SELECT * FROM documentos WHERE iddocumento = ".$this->var2str($id).";");
      if($data)
      {
         return new documento($data[0]);
      }
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->iddocumento) )
      {
         return FALSE;
      }
      else
      {
         return $this->db->select("SELECT * FROM documentos WHERE iddocumento = ".$this->var2str($this->iddocumento).";");
      }
   }
   
   public function test() {
      ;
   }
   
   public function nuevo_numero()
   {
      $data = $this->db->select("SELECT max(iddocumento) AS num FROM documentos;");
      if($data)
         return intval($data[0]['num']) + 1;
      else
         return 1;
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE documentos SET descripcion = ".$this->var2str($this->descripcion).
                 " WHERE iddocumento = ".$this->var2str($this->iddocumento).";";
      }
      else
      {
         $sql = "INSERT INTO documentos (iddocumento,descripcion) VALUES ("
                 .$this->var2str($this->iddocumento).","
                 .$this->var2str($this->descripcion).");";
      }
      
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      return $this->db->exec("delete FROM documentos WHERE iddocumento = ".$this->var2str($this->iddocumento).";");
   }
   
   public function listar()
   {
      $listag = array();
      
      $data = $this->db->select("SELECT * FROM documentos;");
      if($data)
      {
         foreach($data AS $d)
         {
            $listag[] = new documento($d);
         }
      }
      
      return $listag;
   }
   
   public function buscar($texto = '')
   {
      $listag = array();
      
      $data = $this->db->select("SELECT * FROM documentos WHERE descripcion LIKE '%".$texto."%';");
      if($data)
      {
         foreach($data AS $d)
         {
            $listag[] = new documento($d);
         }
      }
      
      return $listag;
   }
   
   public function all()
   {
      $todos = array();

      $data = $this->db->select("SELECT * FROM documentos");
      if($data)
      {
         foreach($data AS $d)
             $todos[] = new documento($d);
      }

      return $todos;
   }

 }
