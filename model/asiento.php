<?php

require_once 'base/fs_model.php';
require_once 'model/partida.php';
require_once 'model/factura_cliente.php';
require_once 'model/factura_proveedor.php';

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
         $this->idconcepto = $a['idconcepto'];
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
         $this->concepto = NULL;
         $this->fecha = Date('d-m-Y');
         $this->codejercicio = NULL;
         $this->codplanasiento = NULL;
         $this->editable = TRUE;
         $this->documento = NULL;
         $this->tipodocumento = NULL;
         $this->importe = 0;
      }
   }
   
   public function show_fecha()
   {
      return Date('d-m-Y', strtotime($this->fecha));
   }
   
   public function show_importe()
   {
      return number_format($this->importe, 2, ',', '.');
   }
   
   public function url()
   {
      if( is_null($this->idasiento) )
         return 'index.php?page=contabilidad_asientos';
      else
         return 'index.php?page=contabilidad_asiento&id='.$this->idasiento;
   }
   
   public function factura_url()
   {
      if($this->tipodocumento == 'Factura de cliente')
      {
         $fac = new factura_cliente();
         $fac = $fac->get_by_codigo($this->documento);
         if($fac)
            return $fac->url();
         else
            return '';
      }
      else if($this->tipodocumento == 'Factura de proveedor')
      {
         $fac = new factura_proveedor();
         $fac = $fac->get_by_codigo($this->documento);
         if($fac)
            return $fac->url();
         else
            return '';
      }
      else
         return '';
   }

   public function get($id)
   {
      $asiento = $this->db->select("SELECT * FROM ".$this->table_name." WHERE idasiento = '".$id."';");
      if($asiento)
         return new asiento($asiento[0]);
      else
         return FALSE;
   }
   
   public function get_partidas()
   {
      $partida = new partida();
      return $partida->all_from_asiento($this->idasiento);
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
   
   public function new_idasiento()
   {
      $newid = $this->db->select("SELECT nextval('".$this->table_name."_idasiento_seq');");
      if($newid)
         $this->idasiento = intval($newid[0]['nextval']);
   }
   
   public function get_new_numero()
   {
      $num = $this->db->select("SELECT MAX(numero::integer) as num FROM ".$this->table_name."
         WHERE codejercicio = ".$this->var2str($this->codejercicio).";");
      if($num)
         return (1 + intval($num[0]['num']));
      else
         return 1;
   }

   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE ".$this->table_name." SET numero = ".$this->var2str($this->numero).",
            idconcepto = ".$this->var2str($this->idconcepto).", concepto = ".$this->var2str($this->concepto).",
            fecha = ".$this->var2str($this->fecha).", codejercicio = ".$this->var2str($this->codejercicio).",
            codplanasiento = ".$this->var2str($this->codplanasiento).", editable = ".$this->var2str($this->editable).",
            documento = ".$this->var2str($this->documento).", tipodocumento = ".$this->var2str($this->tipodocumento).",
            importe = ".$this->var2str($this->importe)." WHERE idasiento = '".$this->idasiento."';";
      }
      else
      {
         $this->new_idasiento();
         if( is_null($this->numero) )
            $this->numero = $this->get_new_numero();
         
         $sql = "INSERT INTO ".$this->table_name." (idasiento,numero,idconcepto,concepto,fecha,codejercicio,codplanasiento,editable,
            documento,tipodocumento,importe) VALUES (".$this->var2str($this->idasiento).",".$this->var2str($this->numero).",
            ".$this->var2str($this->idconcepto).",".$this->var2str($this->concepto).",
            ".$this->var2str($this->fecha).",".$this->var2str($this->codejercicio).",
            ".$this->var2str($this->codplanasiento).",".$this->var2str($this->editable).",".$this->var2str($this->documento).",
            ".$this->var2str($this->tipodocumento).",".$this->var2str($this->importe).");";
      }
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      if( $this->editable )
         return $this->db->exec("DELETE FROM ".$this->table_name." WHERE idasiento = '".$this->idasiento."';");
      else
      {
         $this->new_error_msg("El asiento no es editable, por tanto no se puede borrar.");
         return FALSE;
      }
   }
   
   public function search($query, $offset=0)
   {
      $alist = array();
      $query = strtolower($query);
      $asientos = $this->db->select_limit("SELECT * FROM ".$this->table_name." WHERE lower(concepto) ~~ '%".$query."%'
         OR lower(documento) ~~ '%".$query."%' ORDER BY fecha DESC", FS_ITEM_LIMIT, $offset);
      if($asientos)
      {
         foreach($asientos as $a)
            $alist[] = new asiento($a);
      }
      return $alist;
   }
   
   public function all($offset=0)
   {
      $alist = array();
      $asientos = $this->db->select_limit("SELECT * FROM ".$this->table_name." ORDER BY fecha DESC", FS_ITEM_LIMIT, $offset);
      if($asientos)
      {
         foreach($asientos as $a)
            $alist[] = new asiento($a);
      }
      return $alist;
   }
}

?>
