<?php


class cuenta_especial extends fs_model
{
   public $idcuentaesp;
   public $descripcion;
   public $codcuenta;
   public $codsubcuenta;
   
   public function __construct($g = FALSE)
   {
      parent::__construct('cuenta_especiales', 'plugins/acueducto/');
      
      if($g)
      {
         $this->idcuentaesp = $g['idcuentaesp'];
         $this->descripcion = $g['descripcion'];
         $this->codcuenta = $g['codcuenta'];
         $this->codsubcuenta = $g['codsubcuenta'];
      }
      else
      {
         $this->idcuentaesp = NULL;
         $this->descripcion = "";
         $this->codcuenta = "";
         $this->codsubcuenta = "";
         
      }
   }
   
   protected function install() {
      ;
   }
   
   public function get($id)
   {
      $data = $this->db->select("select * from co_cuentasesp where idcuentaesp = ".$this->var2str($id).";");
      if($data)
      {
         return new cuenta_especial($data[0]);
      }
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->idcuentaesp) )
      {
         return FALSE;
      }
      else
      {
         return $this->db->select("select * from co_cuentasesp where idcuentaesp = ".$this->var2str($this->idcuentaesp).";");
      }
   }
   
   public function test() {
      ;
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE co_cuentasesp set descripcion = ".$this->var2str($this->descripcion).
                 ", codcuenta = ".$this->var2str($this->codcuenta).
                 ", codsubcuenta = ".$this->var2str($this->codsubcuenta).
                 " where idcuentaesp = ".$this->var2str($this->idcuentaesp).";";
      }
      else
      {
         $sql = "INSERT into co_cuentasesp (idcuentaesp,descripcion,codcuenta,codsubcuenta) VALUES ("
                 .$this->var2str($this->idcuentaesp).","
                 .$this->var2str($this->descripcion).","
                 .$this->var2str($this->codcuenta).","
                 .$this->var2str($this->codsubcuenta).");";
      }
      
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      return $this->db->exec("delete from co_cuentasesp where idcuentaesp = ".$this->var2str($this->idcuentaesp).";");
   }
   
   public function listar()
   {
      $listag = array();
      
      $data = $this->db->select("select * from co_cuentasesp;");
      if($data)
      {
         foreach($data as $d)
         {
            $listag[] = new cuenta_especial($d);
         }
      }
      
      return $listag;
   }
   
   public function buscar($texto = '')
   {
      $listag = array();
      
      $data = $this->db->select("select * from co_cuentasesp where descripcion like '%".$texto."%';");
      if($data)
      {
         foreach($data as $d)
         {
            $listag[] = new cuenta_especial($d);
         }
      }
      
      return $listag;
   }

 }