<?php


class concepto extends fs_model
{
   public $idconcepto;
   public $descripcion;
   public $precio;
   
   public function __construct($g = FALSE)
   {
      parent::__construct('conceptos', 'plugins/acueducto/');
      
      if($g)
      {
         $this->idconcepto = $g['idconcepto'];
         $this->descripcion = $g['descripcion'];
         $this->precio = $g['precio'];
      }
      else
      {
         $this->idconcepto = NULL;
         $this->descripcion = "";
         $this->precio = 0;
         
      }
   }
   
   protected function install() {
      ;
   }
   
   public function get($id)
   {
      $data = $this->db->select("select * from conceptos where idconcepto = ".$this->var2str($id).";");
      if($data)
      {
         return new concepto($data[0]);
      }
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->idconcepto) )
      {
         return FALSE;
      }
      else
      {
         return $this->db->select("select * from conceptos where idconcepto = ".$this->var2str($this->idconcepto).";");
      }
   }
   
   public function test() {
      ;
   }
   
   public function nuevo_numero()
   {
      $data = $this->db->select("select max(idconcepto) as num from conceptos;");
      if($data)
         return intval($data[0]['num']) + 1;
      else
         return 1;
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE conceptos set descripcion = ".$this->var2str($this->descripcion).
                 ", precio = ".$this->var2str($this->precio).
                 " where idconcepto = ".$this->var2str($this->idconcepto).";";
      }
      else
      {
         $sql = "INSERT into conceptos (idconcepto,descripcion,precio) VALUES ("
                 .$this->var2str($this->idconcepto).","
                 .$this->var2str($this->descripcion).","
                 .$this->var2str($this->precio).");";
      }
      
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      return $this->db->exec("delete from conceptos where idconcepto = ".$this->var2str($this->idconcepto).";");
   }
   
   public function listar()
   {
      $listag = array();
      
      $data = $this->db->select("select * from conceptos;");
      if($data)
      {
         foreach($data as $d)
         {
            $listag[] = new concepto($d);
         }
      }
      
      return $listag;
   }
   
   public function buscar($texto = '')
   {
      $listag = array();
      
      $data = $this->db->select("select * from conceptos where descripcion like '%".$texto."%';");
      if($data)
      {
         foreach($data as $d)
         {
            $listag[] = new concepto($d);
         }
      }
      
      return $listag;
   }
   
   public function all()
   {
      $todos = array();

      $data = $this->db->select("SELECT * FROM conceptos");
      if($data)
      {
         foreach($data as $d)
             $todos[] = new concepto($d);
      }

      return $todos;
   }

 }