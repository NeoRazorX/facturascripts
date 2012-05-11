<?php

require_once 'core/fs_model.php';

class asiento extends fs_model
{
   public $idasiento;
   public $numero;
   public $idconcepto;
   public $concepto;
   public $fecha;
   public $codejercicio;
   public $codplanasiento;
   public $editable;
   public $documento;
   public $tipodocumento;
   public $importe;
   
   public function __construct($a = FALSE)
   {
      parent::__construct('co_asientos');
      if($a)
      {
         $this->idasiento = intval($a['idasiento']);
         $this->numero = $this->intval($a['numero']);
         $this->idconcepto = $this->intval($a['idconcepto']);
         $this->concepto = $a['concepto'];
         $this->fecha = $a['fecha'];
         $this->codejercicio = $a['codejercicio'];
         $this->codplanasiento = $a['codplanasiento'];
         $this->editable = ($a['editable'] == 't');
         $this->documento = $a['documento'];
         $this->tipodocumento = $a['tipodocumento'];
         $this->importe = floatval($a['importe']);
      }
      else
      {
         $this->idasiento = NULL;
         $this->numero = NULL;
         $this->idconcepto = NULL;
         $this->concepto = '';
         $this->fecha = Date('d-m-Y');
         $this->codejercicio = NULL;
         $this->codplanasiento = NULL;
         $this->editable = TRUE;
         $this->documento = '';
         $this->tipodocumento = NULL;
         $this->importe = 0;
      }
   }
   
   protected function install()
   {
      return '';
   }
   
   public function exists()
   {
      if( is_null($this->idasiento) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE idasiento = '".$this->idasiento."';");
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE ".$this->table_name." SET numero = ".$this->var2str($this->numero).", idconcepto = ".$this->var2str($this->idconcepto).",
            concepto = ".$this->var2str($this->concepto).", fecha = ".$this->var2str($this->fecha).", codejercicio = ".$this->var2str($this->codejercicio).",
            codplanasiento = ".$this->var2str($this->codplanasiento).", editable = ".$this->var2str($this->editable).", documento = ".$this->var2str($this->documento).",
            tipodocumento = ".$this->var2str($this->tipodocumento).", importe = ".$this->var2str($this->importe)." WHERE idasiento = '".$this->idasiento."';";
      }
      else
      {
         $sql = "INSERT INTO ".$this->table_name." (numero,idconcepto,concepto,fecha,codejercicio,codplanasiento,editable,
            documento,tipodocumento,importe) VALUES (".$this->var2str($this->numero).",".$this->var2str($this->idconcepto).",".$this->var2str($this->concepto).",
            ".$this->var2str($this->fecha).",".$this->var2str($this->codejercicio).",".$this->var2str($this->codplanasiento).",".$this->var2str($this->editable).",
            ".$this->var2str($this->documento).",".$this->var2str($this->tipodocumento).",".$this->var2str($this->importe).");";
      }
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE idasiento = '".$this->idasiento."';");
   }
}

?>
