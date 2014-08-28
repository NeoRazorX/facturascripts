<?php


class consumo extends fs_model
{
   public $idconsumo;
   public $descripcion;
   public $inicial;
   public $final;
   public $precio;
   
   public function __construct($g = FALSE)
   {
      parent::__construct('consumos', 'plugins/acueducto/');
      
      if($g)
      {
         $this->idconsumo = $g['idconsumo'];
         $this->descripcion = $g['descripcion'];
         $this->inicial = $g['inicial'];
         $this->final = $g['final'];
         $this->precio = $g['precio'];
      }
      else
      {
         $this->idconsumo = NULL;
         $this->descripcion = "";
         $this->inicial = 0;
         $this->final = 0;
         $this->precio = 0;
         
      }
   }
   
   protected function install() {
      ;
   }
   
   public function get($id)
   {
      $data = $this->db->select("select * from consumos where idconsumo = ".$this->var2str($id).";");
      if($data)
      {
         return new consumo($data[0]);
      }
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->idconsumo) )
      {
         return FALSE;
      }
      else
      {
         return $this->db->select("select * from consumos where idconsumo = ".$this->var2str($this->idconsumo).";");
      }
   }
   
   public function test() {
      ;
   }
   
   public function nuevo_numero()
   {
      $data = $this->db->select("select max(idconsumo) as num from consumos;");
      if($data)
         return intval($data[0]['num']) + 1;
      else
         return 1;
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE consumos set descripcion = ".$this->var2str($this->descripcion).
                 ", inicial = ".$this->var2str($this->inicial).
                 ", final = ".$this->var2str($this->final). 
                 ", precio = ".$this->var2str($this->precio).
                 " where idconsumo = ".$this->var2str($this->idconsumo).";";
      }
      else
      {
         $sql = "INSERT into consumos (idconsumo,descripcion,inicial,final,precio) VALUES ("
                 .$this->var2str($this->idconsumo).","
                 .$this->var2str($this->descripcion).","
                 .$this->var2str($this->inicial).","
                 .$this->var2str($this->final).","
                 .$this->var2str($this->precio).");";
      }
      
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      return $this->db->exec("delete from consumos where idconsumo = ".$this->var2str($this->idconsumo).";");
   }
   
   public function listar()
   {
      $listag = array();
      
      $data = $this->db->select("select * from consumos;");
      if($data)
      {
         foreach($data as $d)
         {
            $listag[] = new consumo($d);
         }
      }
      
      return $listag;
   }
   
   public function buscar($texto = '')
   {
      $listag = array();
      
      $data = $this->db->select("select * from consumos where descripcion like '%".$texto."%';");
      if($data)
      {
         foreach($data as $d)
         {
            $listag[] = new consumo($d);
         }
      }
      
      return $listag;
   }

 }