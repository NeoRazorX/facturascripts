<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2014  Valentín González    valengon@hotmail.com
 * Copyright (C) 2014  Carlos Garcia Gomez  neorazorx@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'plugins/factura_detallada/fpdf17/fs_fpdf.php';
define('FPDF_FONTPATH', 'plugins/factura_detallada/fpdf17/font/');

require_once 'base/fs_pdf.php';
require_model('cliente.php');
require_model('factura_cliente.php');
require_model('articulo.php');
require_model('divisa.php');
require_model('pais.php');
require_model('forma_pago.php');
require_once 'extras/phpmailer/class.phpmailer.php';
require_once 'extras/phpmailer/class.smtp.php';

class factura_detallada extends fs_controller {

   public $cliente;
   public $factura;

   public function __construct() {
      parent::__construct(__CLASS__, 'Factura Detallada', 'ventas', FALSE, FALSE);
   }

   protected function process()
   {
      $this->share_extensions();
      
      $this->factura = FALSE;
      if (isset($_GET['id']))
      {
         $factura = new factura_cliente();
         $this->factura = $factura->get($_GET['id']);
      }
      
      if ($this->factura)
      {
         $cliente = new cliente();
         $this->cliente = $cliente->get($this->factura->codcliente);
         $this->generar_pdf();
      }
      else
      {
         $this->new_error_msg("¡Factura de cliente no encontrada!");
      }
   }

   // Corregir el Bug de fpdf con el Simbolo del Euro ---> €
   public function ckeckEuro($cadena)
   {
      $mostrar = $this->show_precio($cadena, $this->factura->coddivisa);
      $pos = strpos($mostrar, '€');
      if ($pos !== false)
      {
         if (FS_POS_DIVISA == 'right')
         {
            return number_format($cadena, FS_NF0, FS_NF1, FS_NF2) . ' ' . EEURO;
         }
         else
         {
            return EEURO . ' ' . number_format($cadena, FS_NF0, FS_NF1, FS_NF2);
         }
      }
      return $mostrar;
   }

   public function generar_pdf($archivo = FALSE)
   {
      ///// INICIO - Factura Detallada
      /// Creamos el PDF y escribimos sus metadatos
      ob_end_clean();
      $pdf_doc = new PDF_MC_Table('P', 'mm', 'A4');
      define('EEURO', chr(128));

      $pdf_doc->SetTitle('Factura: ' . $this->factura->codigo . " " . $this->factura->numero2);
      $pdf_doc->SetSubject('Factura del cliente: ' . $this->factura->nombrecliente);
      $pdf_doc->SetAuthor($this->empresa->nombre);
      $pdf_doc->SetCreator('FacturaSctipts V_' . $this->version());

      $pdf_doc->Open();
      $pdf_doc->AliasNbPages();
      $pdf_doc->SetAutoPageBreak(true, 40);

      // Definimos el color de relleno (gris, rojo, verde, azul)
      $pdf_doc->SetColorRelleno('verde');

      /// Definimos todos los datos de la cabecera de la factura
      /// Datos de la empresa
      $pdf_doc->fde_nombre = $this->empresa->nombre;
      $pdf_doc->fde_FS_CIFNIF = FS_CIFNIF;
      $pdf_doc->fde_cifnif = $this->empresa->cifnif;
      $pdf_doc->fde_direccion = $this->empresa->direccion;
      $pdf_doc->fde_codpostal = $this->empresa->codpostal;
      $pdf_doc->fde_ciudad = $this->empresa->ciudad;
      $pdf_doc->fde_provincia = $this->empresa->provincia;
      $pdf_doc->fde_telefono = 'Teléfono: ' . $this->empresa->telefono;
      $pdf_doc->fde_fax = 'Fax: ' . $this->empresa->fax;
      $pdf_doc->fde_email = $this->empresa->email;
      $pdf_doc->fde_web = $this->empresa->web;
      $pdf_doc->fde_piefactura = $this->empresa->pie_factura;

      /// Insertamos el Logo y Marca de Agua
      if (file_exists('tmp/' . FS_TMP_NAME . 'logo.png'))
      {
         $pdf_doc->fdf_verlogotipo = '1'; // 1/0 --> Mostrar Logotipo
         $pdf_doc->fdf_Xlogotipo = '15'; // Valor X para Logotipo
         $pdf_doc->fdf_Ylogotipo = '35'; // Valor Y para Logotipo
         $pdf_doc->fdf_vermarcaagua = '1'; // 1/0 --> Mostrar Marca de Agua
         $pdf_doc->fdf_Xmarcaagua = '25'; // Valor X para Marca de Agua
         $pdf_doc->fdf_Ymarcaagua = '110'; // Valor Y para Marca de Agua
      }
      else
      {
         $pdf_doc->fdf_verlogotipo = '0';
         $pdf_doc->fdf_Xlogotipo = '0';
         $pdf_doc->fdf_Ylogotipo = '0';
         $pdf_doc->fdf_vermarcaagua = '0';
         $pdf_doc->fdf_Xmarcaagua = '0';
         $pdf_doc->fdf_Ymarcaagua = '0';
      }

      // Tipo de Documento
      $pdf_doc->fdf_tipodocumento = 'FACTURA'; // (FACTURA, FACTURA PROFORMA, ¿ALBARAN, PRESUPUESTO?...)
      $pdf_doc->fdf_codigo = $this->factura->codigo . " " . $this->factura->numero2;

      // Fecha, Codigo Cliente y observaciones de la factura
      $pdf_doc->fdf_fecha = $this->factura->fecha;
      $pdf_doc->fdf_codcliente = $this->factura->codcliente;
      $pdf_doc->fdf_observaciones = utf8_decode(str_replace(array('“', '”', '"', '&quot;', '&#39;'), array("'", "'", "'", "'", "'"), $this->factura->observaciones));

      // Datos del Cliente
      $pdf_doc->fdf_nombrecliente = $this->factura->nombrecliente;
      $pdf_doc->fdf_FS_CIFNIF = FS_CIFNIF;
      $pdf_doc->fdf_cifnif = $this->factura->cifnif;
      $pdf_doc->fdf_direccion = $this->factura->direccion;
      $pdf_doc->fdf_codpostal = $this->factura->codpostal;
      $pdf_doc->fdf_ciudad = $this->factura->ciudad;
      $pdf_doc->fdf_provincia = $this->factura->provincia;
      $pdf_doc->fdc_telefono1 = $this->cliente->telefono1;
      $pdf_doc->fdc_telefono2 = $this->cliente->telefono2;
      $pdf_doc->fdc_fax = $this->cliente->fax;
      $pdf_doc->fdc_email = $this->cliente->email;

      $pdf_doc->fdf_epago = $pdf_doc->fdf_divisa = $pdf_doc->fdf_pais = '';

      // Forma de Pago de la Factura
      $pago = new forma_pago();
      $epago = $pago->get($this->factura->codpago);
      if ($epago) {
         $pdf_doc->fdf_epago = $epago->descripcion;
      }

      // Divisa de la Factura
      $divisa = new divisa();
      $edivisa = $divisa->get($this->factura->coddivisa);
      if ($edivisa) {
         $pdf_doc->fdf_divisa = $edivisa->descripcion;
      }

      // Pais de la Factura
      $pais = new pais();
      $epais = $pais->get($this->factura->codpais);
      if ($epais) {
         $pdf_doc->fdf_pais = $epais->nombre;
      }

      // Cabecera Titulos Columnas
      $pdf_doc->Setdatoscab(array('ALB', 'DESCRIPCION', 'CANT', 'PRECIO', 'DTO', 'IVA', 'IMPORTE'));
      $pdf_doc->SetWidths(array(16, 102, 10, 20, 10, 10, 22));
      $pdf_doc->SetAligns(array('C', 'L', 'R', 'R', 'R', 'R', 'R'));
      $pdf_doc->SetColors(array('6|47|109', '6|47|109', '6|47|109', '6|47|109', '6|47|109', '6|47|109', '6|47|109'));

      /// Definimos todos los datos del PIE de la factura
      // Lineas de IVA
      $lineas_iva = $this->factura->get_lineas_iva();
      if (count($lineas_iva) > 3) {
         $pdf_doc->fdf_lineasiva = $lineas_iva;
      } else {
         $i = 0;
         foreach ($lineas_iva as $li) {
            $i++;
            $filaiva[$i][0] = ($li->iva) ? 'IVA' . $li->iva : '';
            $filaiva[$i][1] = ($li->neto) ? $this->ckeckEuro($li->neto) : '';
            $filaiva[$i][2] = ($li->iva) ? $li->iva . "%" : '';
            $filaiva[$i][3] = ($li->totaliva) ? $this->ckeckEuro($li->totaliva) : '';
            $filaiva[$i][4] = ($li->recargo) ? $li->recargo . "%" : '';
            $filaiva[$i][5] = ($li->totalrecargo) ? $this->ckeckEuro($li->totalrecargo) : '';
            // $filaiva[$i][6] = ($li->irpf)?$li->irpf . "%":''; //// POR CREARRRRRR
            // $filaiva[$i][7] = ($li->totalirpf)?$this->ckeckEuro($li->totalirpf):''; //// POR CREARRRRRR
            $filaiva[$i][6] = ''; //// POR CREARRRRRR
            $filaiva[$i][7] = ''; //// POR CREARRRRRR
            $filaiva[$i][8] = ($li->totallinea) ? $this->ckeckEuro($li->totallinea) : '';
         }
         $pdf_doc->fdf_lineasiva = $filaiva;
      }

      // Total factura numerico
      $pdf_doc->fdf_numtotal = $this->ckeckEuro($this->factura->total);

      // Total factura numeros a texto
      $pdf_doc->fdf_textotal = $this->factura->total;

      /// Agregamos la pagina inicial de la factura
      $pdf_doc->AddPage();

      // Lineas de la Factura
      $lineas = $this->factura->get_lineas();

      if ($lineas) {
         $neto = 0;
         for ($i = 0; $i < count($lineas); $i++) {
            $neto += $lineas[$i]->pvptotal;
            $pdf_doc->neto = $this->ckeckEuro($neto);

            $articulo = new articulo();
            $art = $articulo->get($lineas[$i]->referencia);
            if ($art) {
               $observa = "\n" . utf8_decode(str_replace(array('“', '”', '"', '&quot;'), array("'", "'", "'", "'"), $art->observaciones));
            } else {
               // $observa = null; // No mostrar mensaje de error
               $observa = "\n" . '******* ERROR: Descripcion de Articulo no Localizada *******';
            }

            $lafila = array(
                // '0' => utf8_decode($lineas[$i]->albaran_codigo() . '-' . $lineas[$i]->albaran_numero()),
                '0' => utf8_decode($lineas[$i]->albaran_numero()),
                '1' => utf8_decode(strtoupper(substr($lineas[$i]->descripcion, 0, 45))) . $observa,
                '2' => utf8_decode($lineas[$i]->cantidad),
                '3' => $this->ckeckEuro($lineas[$i]->pvpunitario),
                '4' => utf8_decode($this->show_numero($lineas[$i]->dtopor, 0) . " %"),
                '5' => utf8_decode($this->show_numero($lineas[$i]->iva, 0) . " %"),
                // '6' => $this->ckeckEuro($lineas[$i]->pvptotal), // Importe con Descuentos aplicados
                '6' => $this->ckeckEuro($lineas[$i]->total_iva())
            );
            $pdf_doc->Row($lafila, '1'); // Row(array, Descripcion del Articulo -- ultimo valor a imprimir)
         }
         $pdf_doc->piepagina = true;
      }

      // Damos salida al archivo PDF
      if ($archivo)
      {
         if (!file_exists('tmp/' . FS_TMP_NAME . 'enviar'))
         {
            mkdir('tmp/' . FS_TMP_NAME . 'enviar');
         }
         
         $pdf_doc->Output('tmp/' . FS_TMP_NAME . 'enviar/' . $archivo, 'F');
      }
      else
      {
         $pdf_doc->Output();
      }
   }
   
   private function share_extensions()
   {
      $fsext = new fs_extension(
              array(
                  'name' => 'factura_detallada',
                  'page_from' => __CLASS__,
                  'page_to' => 'ventas_factura',
                  'type' => 'pdf',
                  'text' => 'Factura detallada',
                  'params' => ''
              )
      );
      $fsext->save();
   }
}
