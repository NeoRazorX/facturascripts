<?php

require_once 'base/fs_model.php';

class cuenta extends fs_model
{
   public $idcuenta;
   public $codcuenta;
   public $codejercicio;
   public $idepigrafe;
   public $codepigrafe;
   public $descripcion;
   public $codbalance;
   public $idcuentaesp;
   
   public function __construct($c=FALSE)
   {
      parent::__construct('co_cuentas');
      if($c)
      {
         $this->idcuenta = $this->intval( $c['idcuenta'] );
         $this->codcuenta = $c['codcuenta'];
         $this->codejercicio = $c['codejercicio'];
         $this->idepigrafe = $this->intval($c['idepigrafe']);
         $this->codepigrafe = $c['codepigrafe'];
         $this->descripcion = $c['descripcion'];
         $this->codbalance = $c['codbalance'];
         $this->idcuentaesp = $this->intval($c['idcuentaesp']);
      }
      else
      {
         $this->idcuenta = NULL;
         $this->codcuenta = NULL;
         $this->codejercicio = NULL;
         $this->idepigrafe = NULL;
         $this->codepigrafe = NULL;
         $this->descripcion = '';
         $this->codbalance = NULL;
         $this->idcuentaesp = NULL;
      }
   }
   
   protected function install()
   {
      return "";
   }
   
   public function exists()
   {
      if( is_null($this->idcuenta) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE idcuenta = '".$this->idcuenta."';");
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE ".$this->table_name." SET codcuenta = ".$this->var2str($this->codcuenta).", codejercicio = ".$this->var2str($this->codejercicio).",
            idepigrafe = ".$this->var2str($this->idepigrafe).", codepigrafe = ".$this->var2str($this->codepigrafe).",
            descripcion = ".$this->var2str($this->descripcion).", codbalance = ".$this->var2str($this->codbalance).",
            idcuentaesp = ".$this->var2str($this->idcuentaesp)." WHERE idcuenta = '".$this->idcuenta."';";
      }
      else
      {
         $sql = "INSERT INTO ".$this->table_name." (codcuenta,codejercicio,idepigrafe,codepigrafe,descripcion,codbalance,idcuentaesp)
            VALUES (".$this->var2str($this->codcuenta).",".$this->var2str($this->codejercicio).",".$this->var2str($this->idepigrafe).",
           ".$this->var2str($this->codepigrafe).",".$this->var2str($this->descripcion).",".$this->var2str($this->codbalance).",
           ".$this->var2str($this->idcuentaesp).");";
      }
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE idcuenta = '".$this->idcuenta."';");
   }
   
   public function all($offset=0)
   {
      $cuenlist = array();
      $cuentas = $this->db->select_limit("SELECT * FROM ".$this->table_name." ORDER BY codejercicio DESC, codcuenta ASC",
              FS_ITEM_LIMIT, $offset);
      if($cuentas)
      {
         foreach($cuentas as $c)
            $cuenlist[] = new cuenta($c);
      }
      return $cuenlist;
   }
   
   public function all_from_ejercicio($codejercicio, $offset=0)
   {
      $cuenlist = array();
      $cuentas = $this->db->select_limit("SELECT * FROM ".$this->table_name." WHERE codejercicio = '".$codejercicio."'
         ORDER BY codcuenta ASC", FS_ITEM_LIMIT, $offset);
      if($cuentas)
      {
         foreach($cuentas as $c)
            $cuenlist[] = new cuenta($c);
      }
      return $cuenlist;
   }
   
   public function search($query, $offset=0)
   {
      $cuenlist = array();
      $query = strtolower($query);
      $cuentas = $this->db->select_limit("SELECT * FROM ".$this->table_name." WHERE codcuenta ~~ '%".$query."%'
         OR lower(descripcion) ~~ '%".$query."%' ORDER BY codejercicio DESC, codcuenta ASC", FS_ITEM_LIMIT, $offset);
      if($cuentas)
      {
         foreach($cuentas as $c)
            $cuenlist[] = new cuenta($c);
      }
      return $cuenlist;
   }
}

?>
