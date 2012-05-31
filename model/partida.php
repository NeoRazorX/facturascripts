<?php

require_once 'base/fs_model.php';
require_once 'model/subcuenta.php';

class partida extends fs_model
{
   public $idpartida;
   public $idasiento;
   public $idsubcuenta;
   public $codsubcuenta;
   public $idconcepto;
   public $concepto;
   public $idcontrapartida;
   public $codcontrapartida;
   public $punteada;
   public $tasaconv;
   public $coddivisa;
   public $haberme;
   public $debeme;
   public $recargo;
   public $iva;
   public $baseimponible;
   public $factura;
   public $codserie;
   public $tipodocumento;
   public $documento;
   public $cifnif;
   public $haber;
   public $debe;
   
   public function __construct($p=FALSE)
   {
      parent::__construct('co_partidas');
      if($p)
      {
         $this->idpartida = intval($p['idpartida']);
         $this->idasiento = $this->intval($p['idasiento']);
         $this->idsubcuenta = $this->intval($p['idsubcuenta']);
         $this->codsubcuenta = $p['codsubcuenta'];
         $this->idconcepto = $this->intval($p['idconcepto']);
         $this->concepto = $p['concepto'];
         $this->idcontrapartida = $this->intval($p['idcontrapartida']);
         $this->codcontrapartida = $p['codcontrapartida'];
         $this->punteada = ($p['punteada'] == 't');
         $this->debe = floatval($p['debe']);
         $this->haber = floatval($p['haber']);
      }
      else
      {
         $this->idpartida = NULL;
         $this->idasiento = NULL;
         $this->idsubcuenta = NULL;
         $this->codsubcuenta = NULL;
         $this->idconcepto = NULL;
         $this->concepto = '';
         $this->idcontrapartida = NULL;
         $this->codcontrapartida = NULL;
         $this->punteada = FALSE;
         $this->debe = 0;
         $this->haber = 0;
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
   
   public function url()
   {
      return 'index.php?page=contabilidad_asiento&id='.$this->idasiento;
   }
   
   public function subcuenta_url()
   {
      $subc = new subcuenta();
      $subc = $subc->get($this->idsubcuenta);
      if($subc)
         return $subc->url();
      else
         return $this->url();
   }

   protected function install()
   {
      return '';
   }
   
   public function exists()
   {
      if( is_null($this->idpartida) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE idpartida = '".$this->idpartida."';");
   }
   
   public function save()
   {
      ;
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE idpartida = '".$this->idpartida."';");
   }
   
   public function all_from_subcuenta($id, $offset=0)
   {
      $plist = array();
      $partidas = $this->db->select_limit("SELECT * FROM ".$this->table_name." WHERE idsubcuenta = '".$id."'",
              FS_ITEM_LIMIT, $offset);
      if($partidas)
      {
         foreach($partidas as $p)
            $plist[] = new partida($p);
      }
      return $plist;
   }
   
   public function all_from_asiento($id)
   {
      $plist = array();
      $partidas = $this->db->select("SELECT * FROM ".$this->table_name." WHERE idasiento = '".$id."';");
      if($partidas)
      {
         foreach($partidas as $p)
            $plist[] = new partida($p);
      }
      return $plist;
   }
}

?>
