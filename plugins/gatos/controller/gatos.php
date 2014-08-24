<?php

class gatos extends fs_controller
{
    public function __construct() {
        parent::__construct(__CLASS__, 'Gatos', 'demo', FALSE, TRUE);
    }
    
    protected function process() {
       /* Añade soporte para búsquedas */
       $this->custom_search = TRUE;
       
        if( isset($_POST['peso']) )
        {
            $numero = $this->empresa->var2str($this->nuevo_numero());
            $peso = $this->empresa->var2str(intval($_POST['peso']));
            $entrada = $this->empresa->var2str($_POST['entrada']);
            
            if($_POST['fechafin'] != '')
               $fecha_fin = $this->empresa->var2str($_POST['fechafin']);
            else
               $fecha_fin = $this->empresa->var2str(NULL);
            
            $this->db->exec("INSERT INTO gatos (numero,peso,entrada,fecha_fin)
                    VALUES (".$numero.",".$peso.",".$entrada.",".$fecha_fin.");");
        }
        else if( isset($_GET['delete']) )
        {
           $this->db->exec("delete from gatos where numero = ".$this->empresa->var2str($_GET['delete']).";");
           $this->new_message('Gato '.$_GET['delete'].' eliminado.');
        }
    }
    
    public function listar_gatos()
    {
       $sql = "select * from gatos;";
       if( isset($_POST['query']) )
          $sql = "select * from gatos where peso = ".$this->empresa->var2str($_POST['query']).";";
       
        $data = $this->db->select($sql);
        if($data)
            return $data;
        else
            return array();
    }
    
    public function nuevo_numero()
    {
        $data = $this->db->select("select max(numero) as num from gatos;");
        if($data)
            return intval($data[0]['num']) + 1;
        else
            return 1;
    }
}
