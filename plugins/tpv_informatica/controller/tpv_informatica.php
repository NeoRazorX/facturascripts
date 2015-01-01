<?php
/*
 * This file is part of FacturaSctipts
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

require_once 'base/fs_printer.php';
require_model('agente.php');
require_model('albaran_cliente.php');
require_model('almacen.php');
require_model('articulo.php');
require_model('caja.php');
require_model('cliente.php');
require_model('divisa.php');
require_model('ejercicio.php');
require_model('familia.php');
require_model('forma_pago.php');
require_model('serie.php');
require_model('grupo_clientes.php');
require_model('impuesto.php');
require_model('pedido_cliente.php');
require_model('presupuesto_cliente.php');
require_model('serie.php');
require_model('tarifa.php');
require_model('pais.php');

class tpv_informatica extends fs_controller
{
   public $agente;
   public $almacen;
   public $articulo;
   public $caja;
   public $cliente;
   public $divisa;
   public $ejercicio;
   public $equivalentes;
   public $familia;
   public $forma_pago;
   public $impuesto;
   public $imprimir_descripciones;
   public $imprimir_observaciones;
   public $results;
   public $serie;
   public $tarifas;
   public $ultimas_compras;
   public $ultimas_ventas;
   public $urlplugin="plugins/tpv_informatica/";
   public $tipo="presupuesto";
   public $pais;
   public $clienteid=0;
   public $nomcliente;
   public $codcliente;

   public function __construct()
   {
      parent::__construct(__CLASS__, 'TPV Informatica', 'TPV', FALSE, TRUE);
   }
   
   protected function process()
   {
      $this->show_fs_toolbar = FALSE;
      
      $this->articulo = new articulo();
      $this->cliente = new cliente();
      $this->familia = new familia();
      $this->impuesto = new impuesto();
      $this->pais = new pais();
      $this->results = array();
	  if(isset($_POST['nombre']))
      {
		$this->inserta_cliente();
	  }
      elseif( isset($_POST['saldo']) )
      {
         $this->template = FALSE;
         
         if(FS_LCD != '')
         {
            $fpt = new fs_printer(FS_LCD);
            $fpt->add( chr(12).'TOTAL               ' );
            $fpt->add( substr(sprintf('%20s', $this->show_precio($_POST['saldo'], FALSE, FALSE)), 0, 20) );
            $fpt->imprimir();
         }
      }
      else if( $this->query != '' )
      {
         $this->new_search();
      }
      else if( isset($_REQUEST['referencia4precios']) )
      {
         $this->get_precios_articulo();
      }
      else
      {
         $this->agente = $this->user->get_agente();
         $this->almacen = new almacen();
         $this->caja = new caja();
         $this->divisa = new divisa();
         $this->ejercicio = new ejercicio();
         $this->forma_pago = new forma_pago();
         $this->serie = new serie();
         
         $this->imprimir_descripciones = FALSE;
         if( isset($_POST['imprimir_desc']) )
            $this->imprimir_descripciones = TRUE;
         else if( isset($_COOKIE['imprimir_desc']) )
            $this->imprimir_descripciones = TRUE;
         
         $this->imprimir_observaciones = FALSE;
         if( isset($_POST['imprimir_obs']) )
            $this->imprimir_observaciones = TRUE;
         else if( isset($_COOKIE['imprimir_obs']) )
            $this->imprimir_observaciones = TRUE;
         if( $this->agente )
         {
         	
            /// obtenemos el bloqueo de caja, sin esto no se puede continuar
            $this->caja = $this->caja->get_last_from_this_server();
            if( $this->caja )
            {
            	
               if($this->caja->codagente == $this->user->codagente)
               {
               	
                  if( isset($_GET['abrir_caja']) )
                     $this->abrir_caja();
                  else if( isset($_GET['cerrar_caja']) )
                     $this->cerrar_caja();
                  else if( isset($_POST['cliente']) )
                  {
                  	$this->new_error_msg('hasta aqui llega');
                  	if( isset($_POST['tipo']) )
			         	{
            				if($_POST['tipo'] == 'presupuesto')
            				{
               				$this->nuevo_presupuesto_cliente();
            				}
            				else if($_POST['tipo'] == 'pedido')
            				{
               				$this->nuevo_pedido_cliente();
            				}            
            				else if($_POST['tipo'] == 'albaran')
            				{
            				   $this->nuevo_albaran_cliente();
            				}
            				else if($_POST['tipo'] == 'factura')
            				{
           					    $this->nueva_factura_cliente();
           					 }
         				}
                  }
               }
               else
               {
                  $this->new_error_msg("Esta caja está bloqueada por el agente ".
                     $this->caja->agente->get_fullname().". Puedes cerrarla desde TPV &gt; Caja."
                  );
               }
            }
            else if( isset($_POST['d_inicial']) )
            {
               $this->caja = new caja();
               $this->caja->codagente = $this->user->codagente;
               $this->caja->dinero_inicial = floatval($_POST['d_inicial']);
               $this->caja->dinero_fin = floatval($_POST['d_inicial']);
               if( $this->caja->save() )
               {
                  $this->new_message("Caja iniciada con ".$this->show_precio($this->caja->dinero_inicial) );
               }
               else
                  $this->new_error_msg("¡Imposible guardar los datos de caja!");
            }
            else
            {
               $fpt = new fs_printer();
               $fpt->abrir_cajon();
            }
         }
         else
         {
            $this->new_error_msg('No tienes un <a href="'.$this->user->url().'">agente asociado</a>
               a tu usuario, y por tanto no puedes hacer tickets.');
         }
      }
   }
   private function inserta_cliente()
   {
		$this->clienteid=$this->nuevo_cliente();
		$this->template="selectclientes";
   }

   public function nuevo_cliente()
   {
      //----------------------------------------------
      // agrega un cliente nuevo y retorna el id
      //----------------------------------------------
      
      if( isset($_POST['nombre']) )
      {
         $cliente = new cliente();
		 
         $cliente->codcliente = $cliente->get_new_codigo();
		 $this->codcliente=$cliente->codcliente;
         $cliente->nombre = $_POST['nombre'];
		 $this->nomcliente=$cliente->nombre;
         $cliente->nombrecomercial = $_POST['nombre'];
         $cliente->telefono1 = $_POST['telefono'];
         $cliente->cifnif = $_POST['cifnif'];
         $cliente->save();
         $dir = new direccion_cliente();
         $dir->ciudad = $_POST['ciudad'];
         $dir->codcliente = $cliente->codcliente;
         $dir->codpais = $_POST['pais'];
         $dir->codpostal = $_POST['codpostal'];
         $dir->direccion = $_POST['direccion'];
         $dir->domenvio = 1;
         $dir->domfacturacion = 1;
         $dir->descripcion = "Principal";
         $dir->provincia = $_POST['provincia'];
         $dir->save();

      }
      
      return $cliente->codcliente;
   }

   
   private function new_search()
   {
      /// cambiamos la plantilla HTML
      $this->template = 'ajax/tpv_recambios';
      
      $codfamilia = '';
      if( isset($_POST['codfamilia']) )
         $codfamilia = $_POST['codfamilia'];
      
      $con_stock = isset($_POST['con_stock']);
      $this->results = $this->articulo->search($this->query, 0, $codfamilia, $con_stock);
      
      $cliente = $this->cliente->get($_POST['codcliente']);
      if($cliente)
      {
         if($cliente->regimeniva == 'Exento')
         {
            foreach($this->results as $i => $value)
               $this->results[$i]->iva = 0;
         }
      }
   }
   
   private function get_precios_articulo()
   {
      /// cambiamos la plantilla HTML
      $this->template = 'ajax/tpv_recambios_precios';
      $this->articulo = $this->articulo->get($_REQUEST['referencia4precios']);
   }
   
   private function nuevo_albaran_cliente()
   {
      $continuar = TRUE;
      $this->new_error_msg('ha entrado por lo menos en el albaran');
      $cliente = $this->cliente->get($_POST['cliente']);
      if( $cliente )
         $this->save_codcliente( $cliente->codcliente );
      else
      {
         $this->new_error_msg('Cliente no encontrado.');
         $continuar = FALSE;
      }
      
      $almacen = $this->almacen->get($_POST['almacen']);
      if( $almacen )
         $this->save_codalmacen( $almacen->codalmacen );
      else
      {
         $this->new_error_msg('Almacén no encontrado.');
         $continuar = FALSE;
      }
      
      $ejercicio = $this->ejercicio->get_by_fecha($_POST['fecha']);
      if( $ejercicio )
         $this->save_codejercicio( $ejercicio->codejercicio );
      else
      {
         $this->new_error_msg('Ejercicio no encontrado.');
         $continuar = FALSE;
      }
      
      $serie = $this->serie->get($_POST['serie']);
      if( $serie )
         $this->save_codserie( $serie->codserie );
      else
      {
         $this->new_error_msg('Serie no encontrada.');
         $continuar = FALSE;
      }
      
      $forma_pago = $this->forma_pago->get($_POST['forma_pago']);
      if( $forma_pago )
         $this->save_codpago( $forma_pago->codpago );
      else
      {
         $this->new_error_msg('Forma de pago no encontrada.');
         $continuar = FALSE;
      }
      
      $divisa = $this->divisa->get($_POST['divisa']);
      if( $divisa )
         $this->save_coddivisa( $divisa->coddivisa );
      else
      {
         $this->new_error_msg('Divisa no encontrada.');
         $continuar = FALSE;
      }
      
      if( isset($_POST['imprimir_desc']) )
      {
         $this->imprimir_descripciones = TRUE;
         setcookie('imprimir_desc', TRUE, time()+FS_COOKIES_EXPIRE);
      }
      else
      {
         $this->imprimir_descripciones = FALSE;
         setcookie('imprimir_desc', FALSE, time()-FS_COOKIES_EXPIRE);
      }
      
      if( isset($_POST['imprimir_obs']) )
      {
         $this->imprimir_observaciones = TRUE;
         setcookie('imprimir_obs', TRUE, time()+FS_COOKIES_EXPIRE);
      }
      else
      {
         $this->imprimir_observaciones = FALSE;
         setcookie('imprimir_obs', FALSE, time()-FS_COOKIES_EXPIRE);
      }
      
      $albaran = new albaran_cliente();
      
      if( $this->duplicated_petition($_POST['petition_id']) )
      {
         $this->new_error_msg('Petición duplicada. Has hecho doble clic sobre el botón Guardar
               y se han enviado dos peticiones. Mira en <a href="'.$albaran->url().'">'.FS_ALBARANES.'</a>
               para ver si el '.FS_ALBARAN.' se ha guardado correctamente.');
         $continuar = FALSE;
      }
      
      if( $continuar )
      {
         $albaran->fecha = $_POST['fecha'];
         $albaran->codalmacen = $almacen->codalmacen;
         $albaran->codejercicio = $ejercicio->codejercicio;
         $albaran->codserie = $serie->codserie;
         $albaran->codpago = $forma_pago->codpago;
         $albaran->coddivisa = $divisa->coddivisa;
         $albaran->tasaconv = $divisa->tasaconv;
         $albaran->codagente = $this->agente->codagente;
         $albaran->observaciones = $_POST['observaciones'];
         $albaran->numero2 = $_POST['numero2'];
         $albaran->irpf = $serie->irpf;
         $albaran->porcomision = $this->agente->porcomision;
         
         foreach($cliente->get_direcciones() as $d)
         {
            if($d->domfacturacion)
            {
               $albaran->codcliente = $cliente->codcliente;
               $albaran->cifnif = $cliente->cifnif;
               $albaran->nombrecliente = $cliente->nombrecomercial;
               $albaran->apartado = $d->apartado;
               $albaran->ciudad = $d->ciudad;
               $albaran->coddir = $d->id;
               $albaran->codpais = $d->codpais;
               $albaran->codpostal = $d->codpostal;
               $albaran->direccion = $d->direccion;
               $albaran->provincia = $d->provincia;
               break;
            }
         }
         
         if( is_null($albaran->codcliente) )
         {
            $this->new_error_msg("No hay ninguna dirección asociada al cliente.");
         }
         else if( $albaran->save() )
         {
            $n = floatval($_POST['numlineas']);
            for($i = 1; $i <= $n; $i++)
            {
               if( isset($_POST['referencia_'.$i]) )
               {
                  $articulo = $this->articulo->get($_POST['referencia_'.$i]);
                  if($articulo)
                  {
                     $linea = new linea_albaran_cliente();
                     $linea->idalbaran = $albaran->idalbaran;
                     $linea->referencia = $articulo->referencia;
                     $linea->descripcion = $_POST['desc_'.$i];
                     
                     if( !$serie->siniva OR $cliente->regimeniva != 'Exento' )
                     {
                        $linea->codimpuesto = $articulo->codimpuesto;
                        $linea->iva = floatval($_POST['iva_'.$i]);
                        $linea->recargo = floatval($_POST['recargo_'.$i]);
                     }
                     
                     if($linea->iva > 0)
                        $linea->irpf = $albaran->irpf;
                     
                     $linea->pvpunitario = floatval($_POST['pvp_'.$i]);
                     $linea->cantidad = floatval($_POST['cantidad_'.$i]);
                     $linea->dtopor = 0;
                     $linea->pvpsindto = ($linea->pvpunitario * $linea->cantidad);
                     $linea->pvptotal = floatval($_POST['total_'.$i]);
                     
                     if( $linea->save() )
                     {
                        /// descontamos del stock
                        $articulo->sum_stock($albaran->codalmacen, 0 - $linea->cantidad);
                        
                        $albaran->neto += $linea->pvptotal;
                        $albaran->totaliva += ($linea->pvptotal * $linea->iva/100);
                        $albaran->totalirpf += ($linea->pvptotal * $linea->irpf/100);
                        $albaran->totalrecargo += ($linea->pvptotal * $linea->recargo/100);
                     }
                     else
                     {
                        $this->new_error_msg("¡Imposible guardar la linea con referencia: ".$linea->referencia);
                        $continuar = FALSE;
                     }
                  }
                  else
                  {
                     $this->new_error_msg("Artículo no encontrado: ".$_POST['referencia_'.$i]);
                     $continuar = FALSE;
                  }
               }
            }
            
            if($continuar)
            {
               /// redondeamos
               $albaran->neto = round($albaran->neto, FS_NF0);
               $albaran->totaliva = round($albaran->totaliva, FS_NF0);
               $albaran->totalirpf = round($albaran->totalirpf, FS_NF0);
               $albaran->totalrecargo = round($albaran->totalrecargo, FS_NF0);
               $albaran->total = $albaran->neto + $albaran->totaliva - $albaran->totalirpf + $albaran->totalrecargo;
               
               if( $albaran->save() )
               {
                  $this->new_message("<a href='".$albaran->url()."'>".FS_ALBARAN."</a> guardado correctamente.");
                  
                  /// actualizamos la caja
                  $this->caja->dinero_fin += $albaran->total;
                  $this->caja->tickets += 1;
                  if( !$this->caja->save() )
                     $this->new_error_msg("¡Imposible actualizar la caja!");
               }
               else
                  $this->new_error_msg("¡Imposible actualizar el <a href='".$albaran->url()."'>".FS_ALBARAN."</a>!");
            }
            else if( $albaran->delete() )
            {
               $this->new_message(FS_ALBARAN." eliminado correctamente.");
            }
            else
               $this->new_error_msg("¡Imposible eliminar el <a href='".$albaran->url()."'>".FS_ALBARAN."</a>!");
         }
         else
            $this->new_error_msg("¡Imposible guardar el ".FS_ALBARAN."!");
      }
   }
   
      private function nuevo_presupuesto_cliente()
   {
      $continuar = TRUE;
      
      $cliente = $this->cliente->get('000001');
      if( $cliente )
         $this->save_codcliente( $cliente->codcliente );
      else
      {
         $this->new_error_msg('Cliente no encontrado.');
         $continuar = FALSE;
      }
      
      $almacen = $this->almacen->get($_POST['almacen']);
      if( $almacen )
         $this->save_codalmacen( $almacen->codalmacen );
      else
      {
         $this->new_error_msg('Almacén no encontrado.');
         $continuar = FALSE;
      }
      
      $eje0 = new ejercicio();
      $ejercicio = $eje0->get_by_fecha($_POST['fecha']);
      if( $ejercicio )
         $this->save_codejercicio( $ejercicio->codejercicio );
      else
      {
         $this->new_error_msg('Ejercicio no encontrado.');
         $continuar = FALSE;
      }
      
      $serie = $this->serie->get($_POST['serie']);
      if( !$serie )
      {
         $this->new_error_msg('Serie no encontrada.');
         $continuar = FALSE;
      }
      
      $forma_pago = $this->forma_pago->get($_POST['forma_pago']);
      if( $forma_pago )
         $this->save_codpago( $forma_pago->codpago );
      else
      {
         $this->new_error_msg('Forma de pago no encontrada.');
         $continuar = FALSE;
      }
      
      $divisa = $this->divisa->get($_POST['divisa']);
      if( $divisa )
         $this->save_coddivisa( $divisa->coddivisa );
      else
      {
         $this->new_error_msg('Divisa no encontrada.');
         $continuar = FALSE;
      }
      
      $presupuesto = new presupuesto_cliente();
      
      if( $this->duplicated_petition($_POST['petition_id']) )
      {
         $this->new_error_msg('Petición duplicada. Has hecho doble clic sobre el botón guardar
               y se han enviado dos peticiones. Mira en <a href="'.$presupuesto->url().'">Presupuestos</a>
               para ver si el presupuesto se ha guardado correctamente.');
         $continuar = FALSE;
      }
      
      if($continuar)
      {
         $presupuesto->fecha = $_POST['fecha'];
         $presupuesto->finoferta = date("Y-m-d", strtotime($_POST['fecha']." +30 days"));
         $presupuesto->codalmacen = $almacen->codalmacen;
         $presupuesto->codejercicio = $ejercicio->codejercicio;
         $presupuesto->codserie = $serie->codserie;
         $presupuesto->codpago = $forma_pago->codpago;
         $presupuesto->coddivisa = $divisa->coddivisa;
         $presupuesto->tasaconv = $divisa->tasaconv;
         $presupuesto->codagente = $this->agente->codagente;
         $presupuesto->observaciones = $_POST['observaciones'];
         $presupuesto->numero2 = $_POST['numero2'];
         $presupuesto->irpf = $serie->irpf;
         $presupuesto->porcomision = $this->agente->porcomision;
         
         foreach($cliente->get_direcciones() as $d)
         {
            if($d->domfacturacion)
            {
               $presupuesto->codcliente = $cliente->codcliente;
               $presupuesto->cifnif = $cliente->cifnif;
               $presupuesto->nombrecliente = $cliente->nombrecomercial;
               $presupuesto->apartado = $d->apartado;
               $presupuesto->ciudad = $d->ciudad;
               $presupuesto->coddir = $d->id;
               $presupuesto->codpais = $d->codpais;
               $presupuesto->codpostal = $d->codpostal;
               $presupuesto->direccion = $d->direccion;
               $presupuesto->provincia = $d->provincia;
               break;
            }
         }
         
         if( is_null($presupuesto->codcliente) )
         {
            $this->new_error_msg("No hay ninguna dirección asociada al cliente.");
         }
         else if( $presupuesto->save() )
         {
            $art0 = new articulo();
            $n = floatval($_POST['numlineas']);
            for($i = 0; $i <= $n; $i++)
            {
               if( isset($_POST['referencia_'.$i]) )
               {
                  $articulo = $art0->get($_POST['referencia_'.$i]);
                  if($articulo)
                  {
                     $linea = new linea_presupuesto_cliente();
                     $linea->idpresupuesto = $presupuesto->idpresupuesto;
                     $linea->referencia = $articulo->referencia;
                     $linea->descripcion = $_POST['desc_'.$i];
                     
                     if( !$serie->siniva AND $cliente->regimeniva != 'Exento' )
                     {
                        $imp0 = $this->impuesto->get_by_iva($_POST['iva_'.$i]);
                        if($imp0)
                        {
                           $linea->codimpuesto = $imp0->codimpuesto;
                           $linea->iva = floatval($_POST['iva_'.$i]);
                           $linea->recargo = floatval($_POST['recargo_'.$i]);
                        }
                        else
                        {
                           $linea->iva = floatval($_POST['iva_'.$i]);
                           $linea->recargo = floatval($_POST['recargo_'.$i]);
                        }
                     }
                     
                     if($linea->iva > 0)
                        $linea->irpf = $presupuesto->irpf;
                     
                     $linea->pvpunitario = floatval($_POST['pvp_'.$i]);
                     $linea->cantidad = floatval($_POST['cantidad_'.$i]);
                     $linea->dtopor = 0;
                     $linea->pvpsindto = ($linea->pvpunitario * $linea->cantidad);
                     $linea->pvptotal = floatval($_POST['total_'.$i]);
                     
                     if( $linea->save() )
                     {
                        $presupuesto->neto += $linea->pvptotal;
                        $presupuesto->totaliva += ($linea->pvptotal * $linea->iva/100);
                        $presupuesto->totalirpf += ($linea->pvptotal * $linea->irpf/100);
                        $presupuesto->totalrecargo += ($linea->pvptotal * $linea->recargo/100);
                     }
                     else
                     {
                        $this->new_error_msg("¡Imposible guardar la linea con referencia: ".$linea->referencia);
                        $continuar = FALSE;
                     }
                  }
                  else
                  {
                     $this->new_error_msg("Artículo no encontrado: ".$_POST['referencia_'.$i]);
                     $continuar = FALSE;
                  }
               }
            }
            
            if($continuar)
            {
               /// redondeamos
               $presupuesto->neto = round($presupuesto->neto, FS_NF0);
               $presupuesto->totaliva = round($presupuesto->totaliva, FS_NF0);
               $presupuesto->totalirpf = round($presupuesto->totalirpf, FS_NF0);
               $presupuesto->totalrecargo = round($presupuesto->totalrecargo, FS_NF0);
               $presupuesto->total = $presupuesto->neto + $presupuesto->totaliva - $presupuesto->totalirpf + $presupuesto->totalrecargo;
               
					if( $presupuesto->save() )
               {
                  $this->new_message("<a href='".$presupuesto->url()."'>".ucfirst(FS_PRESUPUESTO)."</a> guardado correctamente.");
                  $this->new_change(ucfirst(FS_PRESUPUESTO).' a Cliente '.$presupuesto->codigo, $presupuesto->url(), TRUE);
               }
               else
                  $this->new_error_msg("¡Imposible actualizar el <a href='".$presupuesto->url()."'>".FS_PRESUPUESTO."</a>!");
            }
            else if( $presupuesto->delete() )
            {
               $this->new_message(ucfirst(FS_PRESUPUESTO)." eliminado correctamente.");
            }
            else
               $this->new_error_msg("¡Imposible eliminar el <a href='".$presupuesto->url()."'>".FS_PRESUPUESTO."</a>!");
         }
         else
            $this->new_error_msg("¡Imposible guardar el ".FS_PRESUPUESTO."!");
      }
   }
   
      private function nueva_factura_cliente()
   {
      $continuar = TRUE;
      
      $cliente = $this->cliente->get($_POST['cliente']);
      if( $cliente )
         $this->save_codcliente( $cliente->codcliente );
      else
      {
         $this->new_error_msg('Cliente no encontrado.');
         $continuar = FALSE;
      }
      
      $almacen = $this->almacen->get($_POST['almacen']);
      if( $almacen )
         $this->save_codalmacen( $almacen->codalmacen );
      else
      {
         $this->new_error_msg('Almacén no encontrado.');
         $continuar = FALSE;
      }
      
      $eje0 = new ejercicio();
      $ejercicio = $eje0->get_by_fecha($_POST['fecha']);
      if( $ejercicio )
         $this->save_codejercicio( $ejercicio->codejercicio );
      else
      {
         $this->new_error_msg('Ejercicio no encontrado.');
         $continuar = FALSE;
      }
      
      $serie = $this->serie->get($_POST['serie']);
      if( !$serie )
      {
         $this->new_error_msg('Serie no encontrada.');
         $continuar = FALSE;
      }
      
      $forma_pago = $this->forma_pago->get($_POST['forma_pago']);
      if( $forma_pago )
         $this->save_codpago( $forma_pago->codpago );
      else
      {
         $this->new_error_msg('Forma de pago no encontrada.');
         $continuar = FALSE;
      }
      
      $divisa = $this->divisa->get($_POST['divisa']);
      if( $divisa )
         $this->save_coddivisa( $divisa->coddivisa );
      else
      {
         $this->new_error_msg('Divisa no encontrada.');
         $continuar = FALSE;
      }
      
      $factura = new factura_cliente();
      
      if( $this->duplicated_petition($_POST['petition_id']) )
      {
         $this->new_error_msg('Petición duplicada. Has hecho doble clic sobre el botón guardar
               y se han enviado dos peticiones. Mira en <a href="'.$factura->url().'">Facturas</a>
               para ver si la factura se ha guardado correctamente.');
         $continuar = FALSE;
      }
      
      if($continuar)
      {
         $factura->fecha = $_POST['fecha'];
         $factura->codalmacen = $almacen->codalmacen;
         $factura->codejercicio = $ejercicio->codejercicio;
         $factura->codserie = $serie->codserie;
         $factura->codpago = $forma_pago->codpago;
         $factura->coddivisa = $divisa->coddivisa;
         $factura->tasaconv = $divisa->tasaconv;
         $factura->codagente = $this->agente->codagente;
         $factura->observaciones = $_POST['observaciones'];
         $factura->numero2 = $_POST['numero2'];
         $factura->irpf = $serie->irpf;
         $factura->porcomision = $this->agente->porcomision;
         
         foreach($cliente->get_direcciones() as $d)
         {
            if($d->domfacturacion)
            {
               $factura->codcliente = $cliente->codcliente;
               $factura->cifnif = $cliente->cifnif;
               $factura->nombrecliente = $cliente->nombrecomercial;
               $factura->apartado = $d->apartado;
               $factura->ciudad = $d->ciudad;
               $factura->coddir = $d->id;
               $factura->codpais = $d->codpais;
               $factura->codpostal = $d->codpostal;
               $factura->direccion = $d->direccion;
               $factura->provincia = $d->provincia;
               break;
            }
         }
         
         if( is_null($factura->codcliente) )
         {
            $this->new_error_msg("No hay ninguna dirección asociada al cliente.");
         }
         else if( $factura->save() )
         {
            $art0 = new articulo();
            $n = floatval($_POST['numlineas']);
            for($i = 0; $i <= $n; $i++)
            {
               if( isset($_POST['referencia_'.$i]) )
               {
                  $articulo = $art0->get($_POST['referencia_'.$i]);
                  if($articulo)
                  {
                     $linea = new linea_factura_cliente();
                     $linea->idfactura = $factura->idfactura;
                     $linea->referencia = $articulo->referencia;
                     $linea->descripcion = $_POST['desc_'.$i];
                     
                     if( !$serie->siniva AND $cliente->regimeniva != 'Exento' )
                     {
                        $imp0 = $this->impuesto->get_by_iva($_POST['iva_'.$i]);
                        if($imp0)
                        {
                           $linea->codimpuesto = $imp0->codimpuesto;
                           $linea->iva = floatval($_POST['iva_'.$i]);
                           $linea->recargo = floatval($_POST['recargo_'.$i]);
                        }
                        else
                        {
                           $linea->iva = floatval($_POST['iva_'.$i]);
                           $linea->recargo = floatval($_POST['recargo_'.$i]);
                        }
                     }
                     
                     if($linea->iva > 0)
                        $linea->irpf = $factura->irpf;
                     
                     $linea->pvpunitario = floatval($_POST['pvp_'.$i]);
                     $linea->cantidad = floatval($_POST['cantidad_'.$i]);
                     $linea->dtopor = 0;
                     $linea->pvpsindto = ($linea->pvpunitario * $linea->cantidad);
                     $linea->pvptotal = floatval($_POST['total_'.$i]);
                     
                     if( $linea->save() )
                     {
                        /// descontamos del stock
                        $articulo->sum_stock($factura->codalmacen, 0 - $linea->cantidad);
                        
                        $factura->neto += $linea->pvptotal;
                        $factura->totaliva += ($linea->pvptotal * $linea->iva/100);
                        $factura->totalirpf += ($linea->pvptotal * $linea->irpf/100);
                        $factura->totalrecargo += ($linea->pvptotal * $linea->recargo/100);
                     }
                     else
                     {
                        $this->new_error_msg("¡Imposible guardar la linea con referencia: ".$linea->referencia);
                        $continuar = FALSE;
                     }
                  }
                  else
                  {
                     $this->new_error_msg("Artículo no encontrado: ".$_POST['referencia_'.$i]);
                     $continuar = FALSE;
                  }
               }
            }
            
            if($continuar)
            {
               /// redondeamos
               $factura->neto = round($factura->neto, FS_NF0);
               $factura->totaliva = round($factura->totaliva, FS_NF0);
               $factura->totalirpf = round($factura->totalirpf, FS_NF0);
               $factura->totalrecargo = round($factura->totalrecargo, FS_NF0);
               $factura->total = $factura->neto + $factura->totaliva - $factura->totalirpf + $factura->totalrecargo;
               
					if( $factura->save() )
               {
                  $this->new_message("<a href='".$factura->url()."'>Factura</a> guardada correctamente.");
                  $this->new_change('Factura Cliente '.$factura->codigo, $factura->url(), TRUE);
               }
               else
                  $this->new_error_msg("¡Imposible actualizar la <a href='".$factura->url()."'>Factura</a>!");
            }
            else if( $factura->delete() )
            {
               $this->new_message("Factura eliminada correctamente.");
            }
            else
               $this->new_error_msg("¡Imposible eliminar la <a href='".$factura->url()."'>Factura</a>!");
         }
         else
            $this->new_error_msg("¡Imposible guardar la Factura!");
      }
   }
   
   private function nuevo_pedido_cliente()
   {
      $continuar = TRUE;
      
      $cliente = $this->cliente->get($_POST['cliente']);
      if( $cliente )
         $this->save_codcliente( $cliente->codcliente );
      else
      {
         $this->new_error_msg('Cliente no encontrado.');
         $continuar = FALSE;
      }
      
      $almacen = $this->almacen->get($_POST['almacen']);
      if( $almacen )
         $this->save_codalmacen( $almacen->codalmacen );
      else
      {
         $this->new_error_msg('Almacén no encontrado.');
         $continuar = FALSE;
      }
      
      $eje0 = new ejercicio();
      $ejercicio = $eje0->get_by_fecha($_POST['fecha']);
      if( $ejercicio )
         $this->save_codejercicio( $ejercicio->codejercicio );
      else
      {
         $this->new_error_msg('Ejercicio no encontrado.');
         $continuar = FALSE;
      }
      
      $serie = $this->serie->get($_POST['serie']);
      if( !$serie )
      {
         $this->new_error_msg('Serie no encontrada.');
         $continuar = FALSE;
      }
      
      $forma_pago = $this->forma_pago->get($_POST['forma_pago']);
      if( $forma_pago )
         $this->save_codpago( $forma_pago->codpago );
      else
      {
         $this->new_error_msg('Forma de pago no encontrada.');
         $continuar = FALSE;
      }
      
      $divisa = $this->divisa->get($_POST['divisa']);
      if( $divisa )
         $this->save_coddivisa( $divisa->coddivisa );
      else
      {
         $this->new_error_msg('Divisa no encontrada.');
         $continuar = FALSE;
      }
      
      $pedido = new pedido_cliente();
      
      if( $this->duplicated_petition($_POST['petition_id']) )
      {
         $this->new_error_msg('Petición duplicada. Has hecho doble clic sobre el botón guardar
               y se han enviado dos peticiones. Mira en <a href="'.$pedido->url().'">Pedidos</a>
               para ver si el pedido se ha guardado correctamente.');
         $continuar = FALSE;
      }
      
      if($continuar)
      {
         $pedido->fecha = $_POST['fecha'];
         $pedido->codalmacen = $almacen->codalmacen;
         $pedido->codejercicio = $ejercicio->codejercicio;
         $pedido->codserie = $serie->codserie;
         $pedido->codpago = $forma_pago->codpago;
         $pedido->coddivisa = $divisa->coddivisa;
         $pedido->tasaconv = $divisa->tasaconv;
         $pedido->codagente = $this->agente->codagente;
         $pedido->observaciones = $_POST['observaciones'];
         $pedido->numero2 = $_POST['numero2'];
         $pedido->irpf = $serie->irpf;
         $pedido->porcomision = $this->agente->porcomision;
         
         foreach($cliente->get_direcciones() as $d)
         {
            if($d->domfacturacion)
            {
               $pedido->codcliente = $cliente->codcliente;
               $pedido->cifnif = $cliente->cifnif;
               $pedido->nombrecliente = $cliente->nombrecomercial;
               $pedido->apartado = $d->apartado;
               $pedido->ciudad = $d->ciudad;
               $pedido->coddir = $d->id;
               $pedido->codpais = $d->codpais;
               $pedido->codpostal = $d->codpostal;
               $pedido->direccion = $d->direccion;
               $pedido->provincia = $d->provincia;
               break;
            }
         }
         
         if( is_null($pedido->codcliente) )
         {
            $this->new_error_msg("No hay ninguna dirección asociada al cliente.");
         }
         else if( $pedido->save() )
         {
            $art0 = new articulo();
            $n = floatval($_POST['numlineas']);
            for($i = 0; $i <= $n; $i++)
            {
               if( isset($_POST['referencia_'.$i]) )
               {
                  $articulo = $art0->get($_POST['referencia_'.$i]);
                  if($articulo)
                  {
                     $linea = new linea_pedido_cliente();
                     $linea->idpedido = $pedido->idpedido;
                     $linea->referencia = $articulo->referencia;
                     $linea->descripcion = $_POST['desc_'.$i];
                     
                     if( !$serie->siniva AND $cliente->regimeniva != 'Exento' )
                     {
                        $imp0 = $this->impuesto->get_by_iva($_POST['iva_'.$i]);
                        if($imp0)
                        {
                           $linea->codimpuesto = $imp0->codimpuesto;
                           $linea->iva = floatval($_POST['iva_'.$i]);
                           $linea->recargo = floatval($_POST['recargo_'.$i]);
                        }
                        else
                        {
                           $linea->iva = floatval($_POST['iva_'.$i]);
                           $linea->recargo = floatval($_POST['recargo_'.$i]);
                        }
                     }
                     
                     if($linea->iva > 0)
                        $linea->irpf = $pedido->irpf;
                     
                     $linea->pvpunitario = floatval($_POST['pvp_'.$i]);
                     $linea->cantidad = floatval($_POST['cantidad_'.$i]);
                     $linea->dtopor = 0;
                     $linea->pvpsindto = ($linea->pvpunitario * $linea->cantidad);
                     $linea->pvptotal = floatval($_POST['total_'.$i]);
                     
                     if( $linea->save() )
                     {
                        $pedido->neto += $linea->pvptotal;
                        $pedido->totaliva += ($linea->pvptotal * $linea->iva/100);
                        $pedido->totalirpf += ($linea->pvptotal * $linea->irpf/100);
                        $pedido->totalrecargo += ($linea->pvptotal * $linea->recargo/100);
                     }
                     else
                     {
                        $this->new_error_msg("¡Imposible guardar la linea con referencia: ".$linea->referencia);
                        $continuar = FALSE;
                     }
                  }
                  else
                  {
                     $this->new_error_msg("Artículo no encontrado: ".$_POST['referencia_'.$i]);
                     $continuar = FALSE;
                  }
               }
            }
            
            if($continuar)
            {
               /// redondeamos
               $pedido->neto = round($pedido->neto, FS_NF0);
               $pedido->totaliva = round($pedido->totaliva, FS_NF0);
               $pedido->totalirpf = round($pedido->totalirpf, FS_NF0);
               $pedido->totalrecargo = round($pedido->totalrecargo, FS_NF0);
               $pedido->total = $pedido->neto + $pedido->totaliva - $pedido->totalirpf + $pedido->totalrecargo;
               
					if( $pedido->save() )
               {
                  $this->new_message("<a href='".$pedido->url()."'>".ucfirst(FS_PEDIDO)."</a> guardado correctamente.");
                  $this->new_change(ucfirst(FS_PEDIDO)." a Cliente ".$pedido->codigo, $pedido->url(), TRUE);
               }
               else
                  $this->new_error_msg("¡Imposible actualizar el <a href='".$pedido->url()."'>".FS_PEDIDO."</a>!");
            }
            else if( $pedido->delete() )
            {
               $this->new_message(ucfirst(FS_PEDIDO)." eliminado correctamente.");
            }
            else
               $this->new_error_msg("¡Imposible eliminar el <a href='".$pedido->url()."'>".FS_PEDIDO."</a>!");
         }
         else
            $this->new_error_msg("¡Imposible guardar el ".FS_PEDIDO."!");
      }
   }
   
   private function abrir_caja()
   {
      if( $this->user->admin )
      {
         $fpt = new fs_printer();
         $fpt->abrir_cajon();
      }
      else
         $this->new_error_msg('Sólo un administrador puede abrir la caja.');
   }
   
   private function cerrar_caja()
   {
      $this->caja->fecha_fin = Date('d-m-Y H:i:s');
      if( $this->caja->save() )
      {
         $fpt = new fs_printer();
         $fpt->add_big("\nCIERRE DE CAJA:\n");
         $fpt->add("Empleado: ".$this->user->codagente." ".$this->agente->get_fullname()."\n");
         $fpt->add("Caja: ".$this->caja->fs_id."\n");
         $fpt->add("Fecha inicial: ".$this->caja->fecha_inicial."\n");
         $fpt->add("Dinero inicial: ".$this->show_precio($this->caja->dinero_inicial, FALSE, FALSE)."\n");
         $fpt->add("Fecha fin: ".$this->caja->show_fecha_fin()."\n");
         $fpt->add("Dinero fin: ".$this->show_precio($this->caja->dinero_fin, FALSE, FALSE)."\n");
         $fpt->add("Diferencia: ".$this->show_precio($this->caja->diferencia(), FALSE, FALSE)."\n");
         $fpt->add("Tickets: ".$this->caja->tickets."\n\n");
         $fpt->add("Dinero pesado:\n\n\n");
         $fpt->add("Observaciones:\n\n\n\n");
         $fpt->add("Firma:\n\n\n\n\n\n\n");
         
         /// encabezado común para los tickets
         $fpt->add_big( $fpt->center_text($this->empresa->nombre, 16)."\n");
         $fpt->add( $fpt->center_text($this->empresa->lema) . "\n\n");
         $fpt->add( $fpt->center_text($this->empresa->direccion . " - " . $this->empresa->ciudad) . "\n");
         $fpt->add( $fpt->center_text("CIF: " . $this->empresa->cifnif) . chr(27).chr(105) . "\n\n"); /// corta el papel
         $fpt->add( $fpt->center_text($this->empresa->horario) . "\n");
         
         $fpt->imprimir();
         $fpt->abrir_cajon();
         
         /// recargamos la página
         header('location: '.$this->url());
      }
      else
         $this->new_error_msg("¡Imposible cerrar la caja!");
   }
   
   private function reimprimir_ticket()
   {
      $albaran = new albaran_cliente();
      
      if($_GET['reticket'] == '')
      {
         foreach($albaran->all() as $alb)
         {
            $alb0 = $alb;
            break;
         }
      }
      else
         $alb0 = $albaran->get_by_codigo($_GET['reticket']);
      
      if($alb0)
         $this->imprimir_ticket($alb0, 1, FALSE);
      else
         $this->new_error_msg("Ticket no encontrado.");
   }
   
   public function tipos_a_guardar()
   {
      $tipos = array();
      
      if( $this->user->have_access_to('ventas_presupuesto') )
         $tipos[] = array('tipo' => 'presupuesto', 'nombre' => ucfirst(FS_PRESUPUESTO).' para cliente');
      
      if( $this->user->have_access_to('ventas_pedido') )
         $tipos[] = array('tipo' => 'pedido', 'nombre' => ucfirst(FS_PEDIDO).' de cliente');
      
      if( $this->user->have_access_to('ventas_albaran') )
         $tipos[] = array('tipo' => 'albaran', 'nombre' => ucfirst(FS_ALBARAN).' de cliente');
      
      if( $this->user->have_access_to('ventas_factura') )
         $tipos[] = array('tipo' => 'factura', 'nombre' => 'Factura de cliente');
      
      return $tipos;
   }
   
}
