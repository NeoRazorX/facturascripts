<?php

require_once 'base/fs_cache.php';
require_once 'model/articulo.php';
require_once 'model/albaran_cliente.php';
require_once 'model/albaran_proveedor.php';
require_once 'model/familia.php';

class informe_articulos extends fs_controller
{
   public $resultados;
   public $top_ventas;
   public $top_compras;

   public function __construct() {
      parent::__construct('informe_articulos', 'informe de artículos', 'informes', FALSE, TRUE);
   }
   
   protected function process()
   {
      $articulo = new articulo();
      $linea_alb_cli = new linea_albaran_cliente();
      $linea_alb_pro = new linea_albaran_proveedor();
      $stock = new stock();
      
      $this->resultados = array(
          'articulos_total' => $articulo->count(),
          'articulos_stock' => $stock->count_by_articulo(),
          'articulos_vendidos' => $linea_alb_cli->count_by_articulo(),
          'articulos_comprados' => $linea_alb_pro->count_by_articulo()
      );
      
      $this->top_ventas = $linea_alb_cli->top_by_articulo();
      $this->top_compras = $linea_alb_pro->top_by_articulo();
   }
   
   public function version() {
      return parent::version().'-1';
   }
}

?>
