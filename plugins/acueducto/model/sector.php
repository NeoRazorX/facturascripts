<?php


class sector extends fs_model
{
   public $idsector;
   public $descripcion;
   public $observacion;
   
   public function __construct($g = FALSE)
   {
      parent::__construct('sectores', 'plugins/acueducto/');
      
      if($g)
      {
         $this->idsector = $g['idsector'];
         $this->descripcion = $g['descripcion'];
         $this->observacion = $g['observacion'];
      }
      else
      {
         $this->idsector = NULL;
         $this->descripcion = "";
         $this->observacion = "";
         
      }
   }
   
   protected function install() {
      ;
   }
   
   public function get($id)
   {
      $data = $this->db->select("select * from sectores where idsector = ".$this->var2str($id).";");
      if($data)
      {
         return new sector($data[0]);
      }
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->idsector) )
      {
         return FALSE;
      }
      else
      {
         return $this->db->select("select * from sectores where idsector = ".$this->var2str($this->idsector).";");
      }
   }
   
   public function test() {
      ;
   }
   
   public function nuevo_numero()
   {
      $data = $this->db->select("select max(idsector) as num from sectores;");
      if($data)
         return intval($data[0]['num']) + 1;
      else
         return 1;
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE sectores set descripcion = ".$this->var2str($this->descripcion).
                 ", observacion = ".$this->var2str($this->observacion).
                 " where idsector = ".$this->var2str($this->idsector).";";
      }
      else
      {
         $sql = "INSERT into sectores (idsector,descripcion,observacion) VALUES ("
                 .$this->var2str($this->idsector).","
                 .$this->var2str($this->descripcion).","
                 .$this->var2str($this->observacion).");";
      }
      
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      return $this->db->exec("delete from sectores where idsector = ".$this->var2str($this->idsector).";");
   }
   
   public function listar()
   {
      $listag = array();
      
      $data = $this->db->select("select * from sectores;");
      if($data)
      {
         foreach($data as $d)
         {
            $listag[] = new sector($d);
         }
      }
      
      return $listag;
   }
   
   public function buscar($texto = '')
   {
      $listag = array();
      
      $data = $this->db->select("select * from sectores where descripcion like '%".$texto."%';");
      if($data)
      {
         foreach($data as $d)
         {
            $listag[] = new sector($d);
         }
      }
      
      return $listag;
   }
   
   public function all()
   {
      $todos = array();

      $data = $this->db->select("SELECT * FROM sectores");
      if($data)
      {
         foreach($data as $d)
             $todos[] = new sector($d);
      }

      return $todos;
   }

 }