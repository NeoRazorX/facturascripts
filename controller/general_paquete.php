<?php

require_once 'model/paquete.php';

class general_paquete extends fs_controller
{
   public $paquete;
   
   public function __construct()
   {
      parent::__construct('general_paquete', 'Paquete', 'general', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->ppage = $this->page->get('general_paquetes');
      $this->paquete = new paquete();
      
      if( isset($_GET['delete']) )
      {
         $this->paquete->referencia = $_GET['delete'];
         if( $this->paquete->delete() )
            header("location: ".$this->ppage->url());
         else
            $this->new_error_msg("¡Imposible eliminar el paquete!".$this->paquete->error_msg);
      }
      else if( isset($_POST['referenciapaq']) )
      {
         $this->paquete = $this->paquete->get($_POST['referenciapaq']);
         if($this->paquete)
         {
            $this->paquete->set_grupos($_POST['grupos']);
            /// eliminamos todos los subpquetes
            foreach($this->paquete->subpaquetes as $s)
               $s->delete();
            $this->paquete->subpaquetes = array();
            /// añadimos los artículos marcados
            foreach($this->paquete->get_grupos() as $g)
            {
               if( isset($_POST['grupo_'.$g]) )
               {
                  foreach($_POST['grupo_'.$g] as $ref)
                  {
                     $subp = new subpaquete();
                     $subp->referenciapaq = $this->paquete->referencia;
                     $subp->grupo = $g;
                     $subp->referencia = $ref;
                     if( $subp->save() )
                     {
                        $subp->existe = TRUE;
                        $this->paquete->subpaquetes[] = $subp;
                     }
                     else
                        $this->new_error_msg("¡Imposible guardar el subpaquete del grupo ".$subp->grupo.
                                " con referencia ".$subp->referencia."!".$subp->error_msg);
                  }
               }
            }
         }
         else
         {
            $this->paquete = new paquete();
            $this->paquete->referencia = $_POST['referenciapaq'];
            $this->paquete->set_grupos($_POST['grupos']);
            if( !$this->paquete->save() )
               $this->new_error_msg("¡Imposible guardar los datos del paquete!".$this->paquete->error_msg);
         }
      }
      else if( isset($_GET['ref']) )
      {
         $this->paquete = $this->paquete->get($_GET['ref']);
      }
      else
      {
         $this->paquete = FALSE;
      }
   }
   
   public function url()
   {
      if($this->paquete)
         return $this->paquete->url();
      else
         return $this->ppage->url();
   }
}

?>
