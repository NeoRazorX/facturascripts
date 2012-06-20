<?php

require_once 'base/fs_model.php';
require_once 'model/ejercicio.php';
require_once 'model/serie.php';

class secuencia extends fs_model
{
   public $idsec; /// pkey
   public $id;
   public $valorout;
   public $valor;
   public $descripcion;
   public $nombre;
   
   public function __construct($s = FALSE)
   {
      parent::__construct('secuencias');
      if($s)
      {
         $this->idsec = intval($s['idsec']);
         $this->id = intval($s['id']);
         $this->valorout = intval($s['valorout']);
         $this->valor = intval($s['valor']);
         $this->descripcion = $s['descripcion'];
         $this->nombre = $s['nombre'];
      }
      else
      {
         $this->idsec = NULL;
         $this->id = NULL;
         $this->valorout = 0;
         $this->valor = 1;
         $this->descripcion = NULL;
         $this->nombre = NULL;
      }
   }
   
   protected function install()
   {
      return "";
   }
   
   public function get($idsec)
   {
      $sec = $this->db->select("SELECT * FROM ".$this->table_name." WHERE idsec = '".$idsec."';");
      if($sec)
         return new secuencia($sec[0]);
      else
         return FALSE;
   }
   
   public function get_by_params($id, $nombre)
   {
      $sec = $this->db->select("SELECT * FROM ".$this->table_name." WHERE id = '".$id."' AND nombre = '".$nombre."';");
      if($sec)
         return new secuencia($sec[0]);
      else
         return FALSE;
   }
   
   public function get_by_params2($eje, $serie, $nombre)
   {
      $sece = new secuencia_ejercicio();
      $sece->check();
      $aux = $sece->get_by_params($eje, $serie);
      if($aux)
      {
         $sec = $this->get_by_params($aux->id, $nombre);
         if( $sec )
            return $sec;
         else
         {
            $newsec = new secuencia();
            $newsec->id = $aux->id;
            $newsec->nombre = $nombre;
            return $newsec;
         }
      }
      else
      {
         $this->new_error_msg("¡Secuencia de ejercicio no encontrada!");
         return FALSE;
      }
   }

   public function exists()
   {
      if( is_null($this->idsec) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE idsec = '".$this->idsec."';");
   }
   
   public function new_idsec()
   {
      $newid = $this->db->select("SELECT nextval('".$this->table_name."_idsec_seq');");
      if($newid)
         $this->idsec = intval($newid[0]['nextval']);
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE ".$this->table_name." SET id = ".$this->var2str($this->id).",
            valorout = ".$this->var2str($this->valorout).", valor = ".$this->var2str($this->valor).",
            descripcion = ".$this->var2str($this->descripcion).", nombre = ".$this->var2str($this->nombre)."
            WHERE idsec = ".$this->var2str($this->idsec).";";
      }
      else
      {
         $this->new_idsec();
         $sql = "INSERT INTO ".$this->table_name." (idsec,id,valorout,valor,descripcion,nombre) VALUES
            (".$this->var2str($this->idsec).",".$this->var2str($this->id).",".$this->var2str($this->valorout).",
            ".$this->var2str($this->valor).",".$this->var2str($this->descripcion).",".$this->var2str($this->nombre).");";
      }
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE idsec = '".$this->idsec."';");
   }
}

class secuencia_ejercicio extends fs_model
{
   public $id; /// pkey
   public $nfacturacli;
   public $nalbarancli;
   public $npedidocli;
   public $npresupuestocli;
   public $nfacturaprov;
   public $nalbaranprov;
   public $npedidoprov;
   public $codejercicio;
   public $codserie;
   
   public function __construct($s = FALSE)
   {
      parent::__construct('secuenciasejercicios');
      if($s)
      {
         $this->id = intval($s['id']);
         $this->codejercicio = $s['codejercicio'];
         $this->codserie = $s['codserie'];
         $this->nalbarancli = intval($s['nalbarancli']);
         $this->nalbaranprov = intval($s['nalbaranprov']);
         $this->nfacturacli = intval($s['nfacturacli']);
         $this->nfacturaprov = intval($s['nfacturaprov']);
         $this->npedidocli = intval($s['npedidocli']);
         $this->npedidoprov = intval($s['npedidoprov']);
         $this->npresupuestocli = intval($s['npresupuestocli']);
      }
      else
      {
         $this->id = NULL;
         $this->codejercicio = NULL;
         $this->codserie = NULL;
         $this->nalbarancli = 1;
         $this->nalbaranprov = 1;
         $this->nfacturacli = 1;
         $this->nfacturaprov = 1;
         $this->npedidocli = 1;
         $this->npedidoprov = 1;
         $this->npresupuestocli = 1;
      }
   }
   
   protected function install()
   {
      return "";
   }
   
   public function get($id)
   {
      $secs = $this->db->select("SELECT * FROM ".$this->table_name." WHERE id = ".$id.";");
      if($secs)
         return new secuencia_ejercicio($secs[0]);
      else
         return FALSE;
   }
   
   public function get_by_params($eje, $serie)
   {
      $secs = $this->db->select("SELECT * FROM ".$this->table_name." WHERE codejercicio = ".$eje."
                                 AND codserie = ".$serie.";");
      if($secs)
         return new secuencia_ejercicio($secs[0]);
      else
         return FALSE;
   }
   
   public function check()
   {
      $eje = new ejercicio();
      $serie = new serie();
      foreach($eje->all() as $e)
      {
         $secs = $this->all_from_ejercicio($e);
         foreach($serie->all() as $serie)
         {
            $encontrada = FALSE;
            foreach($secs as $s)
            {
               if($s->codserie == $serie->codserie)
                  $encontrada = TRUE;
            }
            if( !$encontrada )
            {
               $aux = new secuencia_ejercicio();
               $aux->codejercicio = $e->codejercicio;
               $aux->codserie = $serie->codserie;
               if( !$aux->save() )
               {
                  $this->new_error_msg("¡Imposible crear la secuencia para el ejercicio ".$aux->codejercicio."
                                          y la serie ".$aux->codserie."!");
               }
            }
         }
      }
   }

   public function exists()
   {
      if( is_null($this->id) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE id = '".$this->id."';");
   }
   
   public function new_id()
   {
      $newid = $this->db->select("SELECT nextval('".$this->table_name."_id_seq');");
      if($newid)
         $this->id = intval($newid[0]['nextval']);
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE ".$this->table_name." SET codejercicio = ".$this->var2str($this->codejercicio).",
            codserie = ".$this->var2str($this->codserie).", nalbarancli = ".$this->var2str($this->nalbarancli).",
            nalbaranprov = ".$this->var2str($this->nalbaranprov).", nfacturacli = ".$this->var2str($this->nfacturacli).",
            nfacturaprov = ".$this->var2str($this->nfacturaprov).", npedidocli = ".$this->var2str($this->npedidocli).",
            npedidoprov = ".$this->var2str($this->npedidoprov).", npresupuestocli =".$this->var2str($this->npresupuestocli)." 
            WHERE id = ".$this->var2str($this->id).";";
      }
      else
      {
         $this->new_id();
         $sql = "INSERT INTO ".$this->table_name." (id,codejercicio,codserie,nalbarancli,nalbaranprov,nfacturacli,
            nfacturaprov,npedidocli,npedidoprov,npresupuestocli) VALUES (".$this->var2str($this->id).",
            ".$this->var2str($this->codejercicio).",".$this->var2str($this->codserie).",".$this->var2str($this->nalbarancli).",
            ".$this->var2str($this->nalbaranprov).",".$this->var2str($this->nfacturacli).",".$this->var2str($this->nfacturaprov).",
            ".$this->var2str($this->npedidocli).",".$this->var2str($this->npedidoprov).",".$this->var2str($this->npresupuestocli).");";
      }
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE id = '".$this->id."';");
   }
   
   public function all_from_ejercicio($eje)
   {
      $seclist = array();
      $secs = $this->db->select("SELECT * FROM ".$this->table_name." WHERE codejercicio = ".$eje.";");
      if($secs)
      {
         foreach($secs as $s)
            $seclist[] = new secuencia_ejercicio($s);
      }
      return $seclist;
   }
}

?>
