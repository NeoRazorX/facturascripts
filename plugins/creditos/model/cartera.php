<?php


class cartera extends fs_model
{
   public $idcartera;
   public $descripcion;
   
   public function __construct($g = FALSE)
   {
      parent::__construct('carteras', 'plugins/creditos/');
      
      if($g)
      {
         $this->idcartera = $g['idcartera'];
         $this->descripcion = $g['descripcion'];
      }
      else
      {
         $this->idcartera = NULL;
         $this->descripcion = "";
      }
   }
   
   protected function install() 
   {        
      return "INSERT INTO carteras (idcartera,descripcion) VALUES (1,'Cartera Inicial');";
   }
   
   public function get($id)
   {
      $data = $this->db->select("SELECT * FROM carteras WHERE idcartera = ".$this->var2str($id).";");
      if($data)
      {
         return new cartera($data[0]);
      }
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->idcartera) )
      {
         return FALSE;
      }
      else
      {
         return $this->db->select("SELECT * FROM carteras WHERE idcartera = ".$this->var2str($this->idcartera).";");
      }
   }
   
   public function nuevo_numero()
   {
      $data = $this->db->select("SELECT max(idcartera) AS num FROM carteras;");
      if($data)
         return intval($data[0]['num']) + 1;
      else
         return 1;
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE carteras SET descripcion = ".$this->var2str($this->descripcion).
                 " WHERE idcartera = ".$this->var2str($this->idcartera).";";
      }
      else
      {
         $sql = "INSERT INTO carteras (idcartera,descripcion) VALUES ("
                 .$this->var2str($this->idcartera).","
                 .$this->var2str($this->descripcion).");";
      }
      
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      return $this->db->exec("delete FROM carteras WHERE idcartera = ".$this->var2str($this->idcartera).";");
   }
   
   public function listar()
   {
      $listag = array();
      
      $data = $this->db->select("SELECT * FROM carteras;");
      if($data)
      {
         foreach($data AS $d)
         {
            $listag[] = new cartera($d);
         }
      }
      
      return $listag;
   }
   
   public function buscar($texto = '')
   {
      $listag = array();
      
      $data = $this->db->select("SELECT * FROM carteras WHERE descripcion LIKE '%".$texto."%';");
      if($data)
      {
         foreach($data AS $d)
         {
            $listag[] = new cartera($d);
         }
      }
      
      return $listag;
   }
   
   public function all()
   {
      $todos = array();

      $data = $this->db->select("SELECT * FROM carteras");
      if($data)
      {
         foreach($data AS $d)
             $todos[] = new cartera($d);
      }

      return $todos;
   }
}
