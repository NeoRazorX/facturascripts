<?php


class certificado extends fs_model
{
   public $idcertificado;
   public $numero;
   public $fecha_inicio;
   public $fecha_fin;
   public $contador_inicial;
   public $contador_final;
   
   public function __construct($g = FALSE)
   {
      parent::__construct('certificados', 'plugins/acueducto/');
      
      if($g)
      {
         $this->idcertificado = $g['idcertificado'];
         $this->numero = $g['numero'];
         
         $this->fecha_inicio = date('d-m-Y', strtotime($g['fecha_inicio']));
         $this->fecha_fin = NULL;
         if( !is_null($g['fecha_fin']) )
            $this->fecha_fin = date('d-m-Y', strtotime($g['fecha_fin']));
         
         $this->contador_inicial = intval($g['contador_inicial']);
         $this->contador_final = intval($g['contador_final']);
      }
      else
      {
         $this->idcertificado = NULL;
         $this->numero = '';
         $this->fecha_inicio = date('d-m-Y');
         $this->fecha_fin = NULL;
         $this->contador_inicial = 0;
         $this->contador_final = 0;
      }
   }
   
   protected function install() {
      ;
   }
   
   public function get($id)
   {
      $data = $this->db->select("select * from certificados where idcertificado = ".$this->var2str($id).";");
      if($data)
      {
         return new certificado($data[0]);
      }
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->idcertificado) )
      {
         return FALSE;
      }
      else
      {
         return $this->db->select("select * from certificados where idcertificado = ".$this->var2str($this->idcertificado).";");
      }
   }
   
   public function test() {
      ;
   }
   
   public function nuevo_numero()
   {
      $data = $this->db->select("select max(idcertificado) as num from certificados;");
      if($data)
         return intval($data[0]['num']) + 1;
      else
         return 1;
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE certificados set numero = ".$this->var2str($this->numero).
                 ", fecha_inicio = ".$this->var2str($this->fecha_inicio).
                 ", fecha_fin = ".$this->var2str($this->fecha_fin).
                 ", contador_inicial = ".$this->var2str($this->contador_inicial).
                 ", contador_final = ".$this->var2str($this->contador_final). 
                 " where idcertificado = ".$this->var2str($this->idcertificado).";";
         return $this->db->exec($sql);
      }
      else
      {
         $sql = "INSERT into certificados (idcertificado,numero,fecha_inicio,fecha_fin,contador_inicial,contador_final) VALUES ("
                 .$this->var2str($this->idcertificado).","
                 .$this->var2str($this->numero).","
                 .$this->var2str($this->fecha_inicio).","
                 .$this->var2str($this->fecha_fin).","
                 .$this->var2str($this->contador_inicial).","
                 .$this->var2str($this->contador_final).");";
         
         if( $this->db->exec($sql) )
         {
            $this->idcertificado = $this->db->lastval();
            return TRUE;
         }
         else
            return FALSE;
      }
   }
   
   public function delete()
   {
      return $this->db->exec("delete from certificados where idcertificado = ".$this->var2str($this->idcertificado).";");
   }
   
   public function listar()
   {
      $listag = array();
      
      $data = $this->db->select("select * from certificados;");
      if($data)
      {
         foreach($data as $d)
         {
            $listag[] = new certificado($d);
         }
      }
      
      return $listag;
   }
   
   public function buscar($texto = '')
   {
      $listag = array();
      
      $data = $this->db->select("select * from certificados where numero like '%".$texto."%';");
      if($data)
      {
         foreach($data as $d)
         {
            $listag[] = new certificado($d);
         }
      }
      
      return $listag;
   }
}
