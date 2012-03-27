<?php

require_once 'base/fs_model.php';
require_once 'model/agente.php';

class caja extends fs_model
{
   public $id;
   public $fs_id;
   public $codagente;
   public $fecha_inicial;
   public $dinero_inicial;
   public $fecha_fin;
   public $dinero_fin;
   public $agente;

   public function __construct($c=FALSE)
   {
      parent::__construct('cajas');
      if($c)
      {
         $this->id = intval($c['id']);
         $this->fs_id = $c['fs_id'];
         $this->fecha_inicial = $c['f_inicio'];
         $this->dinero_inicial = floatval($c['d_inicio']);
         $this->fecha_fin = $c['f_fin'];
         $this->dinero_fin = floatval($c['d_fin']);
         $this->codagente = $c['codagente'];
         $this->agente = new agente();
         $this->agente = $this->agente->get($this->codagente);
      }
      else
      {
         $this->id = NULL;
         $this->fs_id = FS_ID;
         $this->codagente = NULL;
         $this->fecha_inicial = Date('Y-n-j H:i:s');
         $this->dinero_inicial = 0;
         $this->fecha_fin = NULL;
         $this->dinero_fin = 0;
      }
   }
   
   public function show_fecha_inicial()
   {
      return $this->fecha_inicial;
   }
   
   public function show_fecha_fin()
   {
      if( isset($this->fecha_fin) )
         return $this->fecha_fin;
      else
         return '-';
   }
   
   public function show_dinero_inicial()
   {
      return number_format($this->dinero_inicial, 2, ',', '.');
   }
   
   public function show_dinero_fin()
   {
      if( isset($this->fecha_fin) )
         return number_format($this->dinero_fin, 2, ',', '.');
      else
         return '-';
   }
   
   public function show_diferencia()
   {
      if( isset($this->fecha_fin) )
         return number_format ($this->dinero_fin - $this->dinero_inicial, 2, ',', '.');
      else
         return '-';
   }
   
   protected function install()
   {
      return "";
   }
   
   public function exists()
   {
      if( isset($this->id) )
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE id = '".$this->id."';");
      else
         return FALSE;
   }
   
   public function get($id)
   {
      if( isset($id) )
      {
         $caja = $this->db->select("SELECT * FROM ".$this->table_name." WHERE id = '".$id."';");
         if($caja)
            return new caja($caja[0]);
         else
            return FALSE;
      }
      else
         return FALSE;
   }
   
   public function get_last_from_this_server()
   {
      $caja = $this->db->select("SELECT * FROM ".$this->table_name." WHERE fs_id = '".FS_ID."' AND f_fin IS NULL;");
      if($caja)
         return new caja($caja[0]);
      else
         return FALSE;
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE ".$this->table_name." SET fs_id = ".$this->var2str($this->fs_id).", codagente = ".$this->var2str($this->codagente).",
            f_inicio = ".$this->var2str($this->fecha_inicial).", d_inicio = ".$this->var2str($this->dinero_inicial).",
            f_fin = ".$this->var2str($this->fecha_fin).", d_fin = ".$this->var2str($this->dinero_fin)." WHERE id = '".$this->id."';";
      }
      else
      {
         $sql = "INSERT INTO ".$this->table_name." (fs_id,codagente,f_inicio,d_inicio,f_fin,d_fin) VALUES
            (".$this->var2str($this->fs_id).",".$this->var2str($this->codagente).",".$this->var2str($this->fecha_inicial).",
            ".$this->var2str($this->dinero_inicial).",".$this->var2str($this->fecha_fin).",".$this->var2str($this->dinero_fin).");";
      }
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE id = '".$this->id."';");
   }
   
   public function all($offset=0, $limit=FS_ITEM_LIMIT)
   {
      $cajalist = array();
      $cajas = $this->db->select_limit("SELECT * FROM ".$this->table_name." ORDER BY id DESC", $limit, $offset);
      if($cajas)
      {
         foreach($cajas as $c)
         {
            $cajalist[] = new caja($c);
         }
      }
      return $cajalist;
   }
}

?>
