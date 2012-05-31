<?php

require_once 'base/fs_model.php';
require_once 'model/cuenta.php';
require_once 'model/ejercicio.php';
require_once 'model/partida.php';

class subcuenta extends fs_model
{
   public $idsubcuenta;
   public $codsubcuenta;
   public $idcuenta;
   public $codcuenta;
   public $codejercicio;
   public $coddivisa;
   public $codimpuesto;
   public $descripcion;
   public $haber;
   public $debe;
   public $saldo;
   public $recargo;
   public $iva;
   
   public function __construct($s=FALSE)
   {
      parent::__construct('co_subcuentas');
      if($s)
      {
         $this->idsubcuenta = $this->intval($s['idsubcuenta']);
         $this->codsubcuenta = $s['codsubcuenta'];
         $this->idcuenta = $this->intval($s['idcuenta']);
         $this->codcuenta = $s['codcuenta'];
         $this->codejercicio = $s['codejercicio'];
         $this->coddivisa = $s['coddivisa'];
         $this->codimpuesto = $s['codimpuesto'];
         $this->descripcion = $s['descripcion'];
         $this->debe = floatval($s['debe']);
         $this->haber = floatval($s['haber']);
         $this->saldo = floatval($s['saldo']);
         $this->recargo = floatval($s['recargo']);
         $this->iva = floatval($s['iva']);
      }
      else
      {
         $this->idsubcuenta = NULL;
         $this->codsubcuenta = NULL;
         $this->idcuenta = NULL;
         $this->codcuenta = NULL;
         $this->codejercicio = NULL;
         $this->coddivisa = NULL;
         $this->codimpuesto = NULL;
         $this->descripcion = '';
         $this->debe = 0;
         $this->haber = 0;
         $this->saldo = 0;
         $this->recargo = 0;
         $this->iva = 0;
      }
   }
   
   public function show_debe()
   {
      return number_format($this->debe, 2, ',', '.');
   }
   
   public function show_haber()
   {
      return number_format($this->haber, 2, ',', '.');
   }
   
   public function show_saldo()
   {
      return number_format($this->saldo, 2, ',', '.');
   }
   
   public function url()
   {
      return 'index.php?page=contabilidad_subcuenta&id='.$this->idsubcuenta;
   }
   
   public function get_cuenta()
   {
      $cuenta = new cuenta();
      return $cuenta->get($this->idcuenta);
   }
   
   public function get_ejercicio()
   {
      $eje = new ejercicio();
      return $eje->get($this->codejercicio);
   }
   
   public function get_partidas($offset=0)
   {
      $part = new partida();
      return $part->all_from_subcuenta($this->idsubcuenta, $offset);
   }
   
   protected function install()
   {
      return "";
   }
   
   public function get($id)
   {
      $subc = $this->db->select("SELECT * FROM ".$this->table_name." WHERE idsubcuenta = '".$id."';");
      if($subc)
         return new subcuenta($subc[0]);
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->idsubcuenta) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE idsubcuenta = '".$this->idsubcuenta."';");
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE ".$this->table_name." SET codsubcuenta = ".$this->var2str($this->codsubcuenta).", idcuenta = ".$this->var2str($this->idcuenta).",
            codcuenta = ".$this->var2str($this->codcuenta).", codejercicio = ".$this->var2str($this->codejercicio).", coddivisa = ".$this->var2str($this->coddivisa).",
            codimpuesto = ".$this->var2str($this->codimpuesto).", descripcion = ".$this->var2str($this->descripcion).", debe = ".$this->var2str($this->debe).",
            haber = ".$this->var2str($this->haber).", saldo = ".$this->var2str($this->saldo).", recargo = ".$this->var2str($this->recargo).",
            iva = ".$this->var2str($this->iva)." WHERE idsubcuenta = '".$this->idsubcuenta."';";
      }
      else
      {
         $sql = "INSERT INTO ".$this->table_name." (codsubcuenta,idcuenta,codcuenta,codejercicio,coddivisa,codimpuesto,descripcion,
            debe,haber,saldo,recargo,iva) VALUES (".$this->var2str($this->codsubcuenta).",".$this->var2str($this->idcuenta).",".$this->var2str($this->codcuenta).",
            ".$this->var2str($this->codejercicio).",".$this->var2str($this->coddivisa).",".$this->var2str($this->codimpuesto).",
            ".$this->var2str($this->descripcion).",".$this->var2str($this->debe).",".$this->var2str($this->haber).",
            ".$this->var2str($this->saldo).",".$this->var2str($this->recargo).",".$this->var2str($this->iva).");";
      }
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE idsubcuenta = '".$this->idsubcuenta."';");
   }
   
   public function all_from_cuenta($idcuenta)
   {
      $sublist = array();
      $subcuentas = $this->db->select("SELECT * FROM ".$this->table_name." WHERE idcuenta = '".$idcuenta."';");
      if($subcuentas)
      {
         foreach($subcuentas as $s)
            $sublist[] = new subcuenta($s);
      }
      return $sublist;
   }
   
   public function search($query)
   {
      $sublist = array();
      $subcuentas = $this->db->select("SELECT * FROM ".$this->table_name." WHERE codcuenta ~~ '%".$query."%'
         OR lower(descripcion) ~~ '%".$query."%' ORDER BY codejercicio DESC, codcuenta ASC;");
      if($subcuentas)
      {
         foreach($subcuentas as $s)
            $sublist[] = new subcuenta($s);
      }
      return $sublist;
   }
}

?>
