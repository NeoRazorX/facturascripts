<?php

require_once 'base/fs_model.php';

class divisa extends fs_model
{
   public $coddivisa;
   public $descripcion;
   public $tasaconv;
   public $bandera;
   public $fecha;
   public $codiso;

   public function __construct($d=FALSE)
   {
      parent::__construct('divisas');
      if($d)
      {
         $this->coddivisa = $d['coddivisa'];
         $this->descripcion = $d['descripcion'];
         $this->tasaconv = floatval($d['tasaconv']);
         $this->bandera = $d['bandera'];
         $this->fecha = $d['fecha'];
         $this->codiso = $d['codiso'];
      }
      else
      {
         $this->coddivisa = NULL;
         $this->descripcion = '';
         $this->tasaconv = 1;
         $this->bandera = '';
         $this->fecha = Date('j-n-Y');
         $this->codiso = NULL;
      }
   }
   
   protected function install()
   {
      return "INSERT INTO ".$this->table_name." (coddivisa,descripcion,tasaconv,bandera,fecha,codiso)
         VALUES ('EUR','EUROS','1','','".Date('j-n-Y')."','978');";
   }
   
   public function get($cod)
   {
      $divisa = $this->db->select("SELECT * FROM ".$this->table_name." WHERE coddivisa = '".$cod."';");
      if($divisa)
         return new divisa($divisa[0]);
   }
   
   public function exists()
   {
      return $this->db->select("SELECT * FROM ".$this->table_name." WHERE coddivisa = '".$this->coddivisa."';");
   }
   
   public function save()
   {
      ;
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE coddivisa = '".$this->coddivisa."';");
   }
   
   public function all()
   {
      $listad = array();
      $divisas = $this->db->select("SELECT * FROM ".$this->table_name." ORDER BY coddivisa ASC;");
      if($divisas)
      {
         foreach($divisas as $d)
         {
            $listad[] = new divisa($d);
         }
      }
      return $listad;
   }
}

?>
