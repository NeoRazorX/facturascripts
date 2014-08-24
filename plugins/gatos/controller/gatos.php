<?php

class gatos extends fs_controller
{
   public function __construct() {
      /* Añade el submenú "Gatos" al menú "demo" */
      parent::__construct(__CLASS__, 'Gatos', 'demo', FALSE, TRUE);
   }
   
   protected function process() {
      /* Añade soporte para búsquedas */
      $this->custom_search = TRUE;
      
      /* Si se ha pasado un peso */
      if( isset($_POST['peso']) )
      {
         /* var2str se utiliza para evitar SQL Injection */
         $numero = $this->empresa->var2str($this->nuevo_numero());
         $peso = $this->empresa->var2str(intval($_POST['peso']));
         $entrada = $this->empresa->var2str($_POST['entrada']);
         
         /* Si se ha pasado una fecha fin */
         if($_POST['fechafin'] != '') /* Se utiliza la fecha definida*/
            $fecha_fin = $this->empresa->var2str($_POST['fechafin']);
         else /* En caso contrario, se usa un valor nulo */
            $fecha_fin = $this->empresa->var2str(NULL);
         
         /* Insertar en la base de datos */
         $this->db->exec("INSERT INTO gatos (numero,peso,entrada,fecha_fin)
                 VALUES (".$numero.",".$peso.",".$entrada.",".$fecha_fin.");");
         /* Mostramos mensaje por pantalla */
         $this->new_message('Gato '.$numero.' insertado.');
      }
      else if( isset($_GET['delete']) )
      {
         /* Borrar de la base de datos */
         $this->db->exec("DELETE FROM gatos WHERE numero = ".$this->empresa->var2str($_GET['delete']).";");
         /* Mostramos mensaje por pantalla */
         $this->new_message('Gato '.$_GET['delete'].' eliminado.');
      }
   }
   
   public function listar_gatos()
   {
      $sql = "SELECT * FROM gatos;";
      
      /* Si se recibe una búsqueda se omite la consulta anterior*/
      if( isset($_POST['query']) )
         $sql = "SELECT * FROM gatos WHERE peso = ".$this->empresa->var2str($_POST['query']).";";
      
      /* Ejecutamos la consulta SQL */
      $data = $this->db->select($sql);
      if($data) /* Se devuelven los datos de la consulta SQL */
         return $data;
      else /* Se devuelve un array vacío porque no se han devuelto datos de la consulta SQL*/
         return array();
   }
   
   public function nuevo_numero()
   {
      /* Se obtiene el último número de la tabla */
      $data = $this->db->select("SELECT max(numero) AS num FROM gatos;");
      if($data) /* Al elemento devuelto se le suma 1 para el siguiente */
         return intval($data[0]['num']) + 1;
      else /* Si no se ha devuelto nada no hay elementos, el primero es 1 */
         return 1;
   }
   
}
