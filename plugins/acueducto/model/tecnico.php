<?php


class tecnico extends fs_model
{
   public $idtecnico;
   public $nombre;
   public $telefono;
   
   public function __construct($g = FALSE)
   {
      parent::__construct('tecnicos', 'plugins/acueducto/');
      
      if($g)
      {
         $this->idtecnico = $g['idtecnico'];
         $this->nombre = $g['nombre'];
         $this->telefono = $g['telefono'];
      }
      else
      {
         $this->idtecnico = NULL;
         $this->nombre = "";
         $this->telefono = 0;
         
      }
   }
   
   protected function install() {
      ;
   }
   
   public function get($id)
   {
      $data = $this->db->select("select * from tecnicos where idtecnico = ".$this->var2str($id).";");
      if($data)
      {
         return new tecnico($data[0]);
      }
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->idtecnico) )
      {
         return FALSE;
      }
      else
      {
         return $this->db->select("select * from tecnicos where idtecnico = ".$this->var2str($this->idtecnico).";");
      }
   }
   
   public function test() {
      ;
   }
   
   public function nuevo_numero()
   {
      $data = $this->db->select("select max(idtecnico) as num from tecnicos;");
      if($data)
         return intval($data[0]['num']) + 1;
      else
         return 1;
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE tecnicos set nombre = ".$this->var2str($this->nombre).
                 ", telefono = ".$this->var2str($this->telefono).
                 " where idtecnico = ".$this->var2str($this->idtecnico).";";
      }
      else
      {
         $sql = "INSERT into tecnicos (idtecnico,nombre,telefono) VALUES ("
                 .$this->var2str($this->idtecnico).","
                 .$this->var2str($this->nombre).","
                 .$this->var2str($this->telefono).");";
      }
      
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      return $this->db->exec("delete from tecnicos where idtecnico = ".$this->var2str($this->idtecnico).";");
   }
   
   public function listar()
   {
      $listag = array();
      
      $data = $this->db->select("select * from tecnicos;");
      if($data)
      {
         foreach($data as $d)
         {
            $listag[] = new tecnico($d);
         }
      }
      
      return $listag;
   }
   
   public function buscar($texto = '')
   {
      $listag = array();
      
      $data = $this->db->select("select * from tecnicos where nombre like '%".$texto."%';");
      if($data)
      {
         foreach($data as $d)
         {
            $listag[] = new tecnico($d);
         }
      }
      
      return $listag;
   }
   
   public function all()
   {
      $todos = array();

      $data = $this->db->select("SELECT * FROM tecnicos");
      if($data)
      {
         foreach($data as $d)
             $todos[] = new tecnico($d);
      }

      return $todos;
   }

 }