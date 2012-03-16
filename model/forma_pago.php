<?php

require_once 'base/fs_model.php';

class forma_pago extends fs_model
{
   public $codpago;
   public $descripcion;
   public $genrecibos;
   public $codcuenta;
   public $domiciliado;

   public function __construct($f=FALSE)
   {
      parent::__construct('formaspago');
      if( $f )
      {
         $this->codpago = $f['codpago'];
         $this->descripcion = $f['descripcion'];
         $this->genrecibos = $f['genrecibos'];
         $this->codcuenta = $f['codcuenta'];
         $this->domiciliado = ($f['domiciliado'] == 't');
      }
      else
      {
         $this->codpago = NULL;
         $this->descripcion = '';
         $this->genrecibos = '';
         $this->codcuenta = '';
         $this->domiciliado = FALSE;
      }
   }
   
   protected function install()
   {
      return "INSERT INTO ".$this->table_name." (codpago,descripcion,genrecibos,codcuenta,domiciliado) VALUES
            ('CONT','CONTADO','Emitidos',NULL,FALSE);";
   }
   
   public function get($cod)
   {
      $pago = $this->db->select("SELECT * FROM ".$this->table_name." WHERE codpago = '".$cod."';");
      if($pago)
         return new forma_pago($pago[0]);
      else
         return FALSE;
   }

   public function exists()
   {
      return $this->db->select("SELECT * FROM ".$this->table_name." WHERE codpago = '".$this->codpago."';");
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE ".$this->table_name." SET descripcion = ".$this->var2str($this->descripcion).",
            genrecibos = ".$this->var2str($this->genrecibos).", codcuenta = ".$this->var2str($this->codcuenta).",
            domiciliado = ".$this->var2str($this->domiciliado)." WHERE codpago = '".$this->codpago."';";
      }
      else
      {
         $sql = "INSERT INTO ".$this->table_name." (codpago,descripcion,genrecibos,codcuenta,domiciliado) VALUES
            (".$this->codpago.",".$this->var2str($this->descripcion).",".$this->var2str($this->genrecibos).",
            ".$this->var2str($this->codcuenta).",".$this->var2str($this->domiciliado).");";
      }
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE codpago = '".$this->codpago."';");
   }
   
   public function all()
   {
      $listaformas = array();
      $formas = $this->db->select("SELECT * FROM ".$this->table_name." ORDER BY codpago ASC;");
      if($formas)
      {
         foreach($formas as $f)
         {
            $listaformas[] = new forma_pago($f);
         }
      }
      return $listaformas;
   }
}

?>
