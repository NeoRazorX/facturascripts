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
         $this->idcontrapartida = $p['idcontrapartida'];
         $this->codcontrapartida = $p['codcontrapartida'];
         $this->punteada = ($p['punteada'] == 't');
         $this->tasaconv = $p['tasaconv'];
         $this->coddivisa = $p['coddivisa'];
         $this->haberme = floatval($p['haberme']);
         $this->debeme = floatval($p['debeme']);
         $this->recargo = floatval($p['recargo']);
         $this->iva = floatval($p['iva']);
         $this->baseimponible = floatval($p['baseimponible']);
         $this->factura = $this->intval($p['factura']);
         $this->codserie = $p['codserie'];
         $this->tipodocumento = $p['tipodocumento'];
         $this->documento = $p['documento'];
         $this->cifnif = $p['cifnif'];
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
         $this->tasaconv = 1;
         $this->coddivisa = NULL;
         $this->haberme = 0;
         $this->debeme = 0;
         $this->recargo = 0;
         $this->iva = 0;
         $this->baseimponible = 0;
         $this->factura = NULL;
         $this->codserie = NULL;
         $this->tipodocumento = NULL;
         $this->documento = NULL;
         $this->cifnif = NULL;
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
      if( is_null($this->idasiento) )
         return 'index.php?page=contabilidad_asientos';
      else
         return 'index.php?page=contabilidad_asiento&id='.$this->idasiento;
   }
   
   public function subcuenta_url()
   {
      $subc = new subcuenta();
      $subc = $subc->get($this->idsubcuenta);
      if($subc)
         return $subc->url();
      else
         return '#';
   }
   
   public function contrapartida_url()
   {
      $subc = new subcuenta();
      $subc = $subc->get($this->idcontrapartida);
      if($subc)
         return $subc->url();
      else
         return '#';
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
      if( $this->exists() )
      {
         $sql = "UPDATE ".$this->table_name." SET idasiento = ".$this->var2str($this->idasiento).",
            idsubcuenta = ".$this->var2str($this->idsubcuenta).", codsubcuenta = ".$this->var2str($this->codsubcuenta).",
            idconcepto = ".$this->var2str($this->idconcepto).", concepto = ".$this->var2str($this->concepto).",
            idcontrapartida = ".$this->var2str($this->idcontrapartida).", codcontrapartida = ".$this->var2str($this->codcontrapartida).",
            punteada = ".$this->var2str($this->punteada).", tasaconv = ".$this->var2str($this->tasaconv).",
            coddivisa = ".$this->var2str($this->coddivisa).", haberme = ".$this->var2str($this->haberme).",
            debeme = ".$this->var2str($this->debeme).", recargo = ".$this->var2str($this->recargo).",
            iva = ".$this->var2str($this->iva).", baseimponible = ".$this->var2str($this->baseimponible).",
            factura = ".$this->var2str($this->factura).", codserie = ".$this->var2str($this->codserie).",
            tipodocumento = ".$this->var2str($this->tipodocumento).", documento = ".$this->var2str($this->documento).",
            cifnif = ".$this->var2str($this->cifnif).", debe = ".$this->var2str($this->debe).",
            haber = ".$this->var2str($this->haber)." WHERE idpartida = ".$this->var2str($this->idpartida).";";
      }
      else
      {
         $sql = "INSERT INTO ".$this->table_name." (idasiento,idsubcuenta,codsubcuenta,idconcepto,
            concepto,idcontrapartida,codcontrapartida,punteada,tasaconv,coddivisa,haberme,debeme,recargo,iva,
            baseimponible,factura,codserie,tipodocumento,documento,cifnif,debe,haber) VALUES
            (".$this->var2str($this->idasiento).",".$this->var2str($this->idsubcuenta).",
            ".$this->var2str($this->codsubcuenta).",".$this->var2str($this->idconcepto).",".$this->var2str($this->concepto).",
            ".$this->var2str($this->idcontrapartida).",".$this->var2str($this->codcontrapartida).",".$this->var2str($this->punteada).",
            ".$this->var2str($this->tasaconv).",".$this->var2str($this->coddivisa).",".$this->var2str($this->haberme).",
            ".$this->var2str($this->debeme).",".$this->var2str($this->recargo).",".$this->var2str($this->iva).",
            ".$this->var2str($this->baseimponible).",".$this->var2str($this->factura).",".$this->var2str($this->codserie).",
            ".$this->var2str($this->tipodocumento).",".$this->var2str($this->documento).",".$this->var2str($this->cifnif).",
            ".$this->var2str($this->debe).",".$this->var2str($this->haber).");";
      }
      return $this->db->exec($sql);
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
