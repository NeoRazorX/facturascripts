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
   
   protected function install() {
      ;
   }
   
   public function get($id)
   {
      $data = $this->db->select("select * from documentos where iddocumento = ".$this->var2str($id).";");
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
         return $this->db->select("select * from documentos where iddocumento = ".$this->var2str($this->iddocumento).";");
      }
   }
   
   public function test() {
      ;
   }
   
   public function nuevo_numero()
   {
      $data = $this->db->select("select max(iddocumento) as num from documentos;");
      if($data)
         return intval($data[0]['num']) + 1;
      else
         return 1;
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE documentos set descripcion = ".$this->var2str($this->descripcion).
                 " where iddocumento = ".$this->var2str($this->iddocumento).";";
      }
      else
      {
         $sql = "INSERT into documentos (iddocumento,descripcion) VALUES ("
                 .$this->var2str($this->iddocumento).","
                 .$this->var2str($this->descripcion).");";
      }
      
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      return $this->db->exec("delete from documentos where iddocumento = ".$this->var2str($this->iddocumento).";");
   }
   
   public function listar()
   {
      $listag = array();
      
      $data = $this->db->select("select * from documentos;");
      if($data)
      {
         foreach($data as $d)
         {
            $listag[] = new documento($d);
         }
      }
      
      return $listag;
   }
   
   public function buscar($texto = '')
   {
      $listag = array();
      
      $data = $this->db->select("select * from documentos where nombre like '%".$texto."%';");
      if($data)
      {
         foreach($data as $d)
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
         foreach($data as $d)
             $todos[] = new documento($d);
      }

      return $todos;
   }

 }