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

require_model('asiento.php');
require_model('balance.php');
require_model('cliente.php');
require_model('cuenta.php');
require_model('ejercicio.php');
require_model('epigrafe.php');
require_model('partida.php');
require_model('proveedor.php');
require_model('secuencia.php');
require_model('subcuenta.php');

class contabilidad_ejercicio extends fs_controller
{
   public $asiento_apertura_url;
   public $asiento_cierre_url;
   public $asiento_pyg_url;
   public $ejercicio;
   public $importar_url;
   public $listado;
   public $listar;
   public $offset;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Ejercicio', 'contabilidad', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->show_fs_toolbar = FALSE;
      
      
      /// cargamos las putas secuencias para que se actualicen.
      /// Abanq/Eneboo, yo te maldigooooo!!!!!!!!!!!!!!!!!!!!!!
      $sec0 = new secuencia_ejercicio();
      $sec1 = new secuencia_contabilidad();
      $sec2 = new secuencia();
      
      
      $this->ejercicio = FALSE;
      if( isset($_POST['codejercicio']) )
      {
         $this->ejercicio = new ejercicio();
         $this->ejercicio = $this->ejercicio->get($_POST['codejercicio']);
         if($this->ejercicio)
         {
            $this->ejercicio->nombre = $_POST['nombre'];
            $this->ejercicio->fechainicio = $_POST['fechainicio'];
            $this->ejercicio->fechafin = $_POST['fechafin'];
            $this->ejercicio->longsubcuenta = intval($_POST['longsubcuenta']);
            $this->ejercicio->estado = $_POST['estado'];
            if( $this->ejercicio->save() )
               $this->new_message('Datos guardados correctamente.');
            else
               $this->new_error_msg('Imposible guardar los datos.');
         }
      }
      else if( isset($_GET['cod']) )
      {
         $this->ejercicio = new ejercicio();
         $this->ejercicio = $this->ejercicio->get($_GET['cod']);
      }
      
      if($this->ejercicio)
      {
         if( isset($_GET['export']) )
         {
            $this->exportar_xml();
         }
         else
         {
            $this->ppage = $this->page->get('contabilidad_ejercicios');
            $this->page->title = $this->ejercicio->codejercicio.' ('.$this->ejercicio->nombre.')';
            
            if( isset($_GET['cerrar']) AND isset($_GET['petid']) )
            {
               if( $this->duplicated_petition($_GET['petid']) )
               {
                  $this->new_error_msg('Petición duplicada. Evita hacer doble clic sobre los botones.');
               }
               else
                  $this->cerrar_ejercicio();
            }
            else
               $this->ejercicio->full_test();
            
            $asiento = new asiento();
            $this->asiento_apertura_url = FALSE;
            if( $this->ejercicio->idasientoapertura )
            {
               $asiento_a = $asiento->get( $this->ejercicio->idasientoapertura );
               if($asiento_a)
                  $this->asiento_apertura_url = $asiento_a->url();
            }
            $this->asiento_cierre_url = FALSE;
            if( $this->ejercicio->idasientocierre )
            {
               $asiento_c = $asiento->get( $this->ejercicio->idasientocierre );
               if($asiento_c)
                  $this->asiento_cierre_url = $asiento_c->url();
            }
            $this->asiento_pyg_url = FALSE;
            if( $this->ejercicio->idasientopyg )
            {
               $asiento_pyg = $asiento->get( $this->ejercicio->idasientopyg );
               if($asiento_pyg)
                  $this->asiento_pyg_url = $asiento_pyg->url();
            }
            
            /// comprobamos el proceso de importación
            $this->importar_xml();
            
            $this->offset = 0;
            if( isset($_GET['offset']) )
               $this->offset = intval($_GET['offset']);
            
            if( !isset($_GET['listar']) )
               $this->listar = 'cuentas';
            else if($_GET['listar'] == 'grupos')
               $this->listar = 'grupos';
            else if($_GET['listar'] == 'epigrafes')
               $this->listar = 'epigrafes';
            else if($_GET['listar'] == 'subcuentas')
               $this->listar = 'subcuentas';
            else
               $this->listar = 'cuentas';
            
            switch($this->listar)
            {
               default:
                  $cuenta = new cuenta();
                  $this->listado = $cuenta->full_from_ejercicio( $this->ejercicio->codejercicio );
                  break;
               
               case 'grupos';
                  $ge = new grupo_epigrafes();
                  $this->listado = $ge->all_from_ejercicio( $this->ejercicio->codejercicio );
                  break;
               
               case 'epigrafes':
                  $epigrafe = new epigrafe();
                  $this->listado = $epigrafe->all_from_ejercicio( $this->ejercicio->codejercicio );
                  break;
               
               case 'subcuentas':
                  $subcuenta = new subcuenta();
                  $this->listado = $subcuenta->all_from_ejercicio( $this->ejercicio->codejercicio );
                  break;
            }
         }
      }
      else
         $this->new_error_msg('Ejercicio no encontrado.');
   }
   
   public function url()
   {
      if( !isset($this->ejercicio) )
         return parent::url();
      else if($this->ejercicio)
         return $this->ejercicio->url();
      else
         return parent::url();
   }
   
   private function exportar_xml()
   {
      /// desactivamos el motor de plantillas
      $this->template = FALSE;
      
      /// creamos el xml
      $cadena_xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<!--
    Document   : ejercicio_".$this->ejercicio->codejercicio.".xml
    Description:
        Estructura de grupos de epígrafes, epígrafes, cuentas y subcuentas del ejercicio ".
      $this->ejercicio->codejercicio.".
-->

<ejercicio>
</ejercicio>\n";
      $archivo_xml = simplexml_load_string($cadena_xml);
      
      /// añadimos los balances
      $balance = new balance();
      foreach($balance->all() as $ba)
      {
         $aux = $archivo_xml->addChild("balance");
         $aux->addChild("codbalance", $ba->codbalance);
         $aux->addChild("naturaleza", $ba->naturaleza);
         $aux->addChild("nivel1", $ba->nivel1);
         $aux->addChild("descripcion1", base64_encode($ba->descripcion1) );
         $aux->addChild("nivel2", $ba->nivel2);
         $aux->addChild("descripcion2", base64_encode($ba->descripcion2) );
         $aux->addChild("nivel3", $ba->nivel3);
         $aux->addChild("descripcion3", base64_encode($ba->descripcion3) );
         $aux->addChild("orden3", $ba->orden3);
         $aux->addChild("nivel4", $ba->nivel4);
         $aux->addChild("descripcion4", base64_encode($ba->descripcion4) );
         $aux->addChild("descripcion4ba", base64_encode($ba->descripcion4ba) );
      }
      
      /// añadimos las cuentas de balances
      $balance_cuenta = new balance_cuenta();
      foreach($balance_cuenta->all() as $ba)
      {
         $aux = $archivo_xml->addChild("balance_cuenta");
         $aux->addChild("codbalance", $ba->codbalance);
         $aux->addChild("codcuenta", $ba->codcuenta);
         $aux->addChild("descripcion", base64_encode($ba->desccuenta) );
      }
      
      /// añadimos las cuentas de balance abreviadas
      $balance_cuenta_a = new balance_cuenta_a();
      foreach($balance_cuenta_a->all() as $ba)
      {
         $aux = $archivo_xml->addChild("balance_cuenta_a");
         $aux->addChild("codbalance", $ba->codbalance);
         $aux->addChild("codcuenta", $ba->codcuenta);
         $aux->addChild("descripcion", base64_encode($ba->desccuenta) );
      }
      
      /// añadimos las cuentas especiales
      $cuenta_esp = new cuenta_especial();
      foreach($cuenta_esp->all() as $ce)
      {
         $aux = $archivo_xml->addChild("cuenta_especial");
         $aux->addChild("idcuentaesp", $ce->idcuentaesp);
         $aux->addChild("descripcion", base64_encode($ce->descripcion) );
      }
      
      /// añadimos los grupos de epigrafes
      $grupo_epigrafes = new grupo_epigrafes();
      $grupos_ep = $grupo_epigrafes->all_from_ejercicio($this->ejercicio->codejercicio);
      foreach($grupos_ep as $ge)
      {
         $aux = $archivo_xml->addChild("grupo_epigrafes");
         $aux->addChild("codgrupo", $ge->codgrupo);
         $aux->addChild("descripcion", base64_encode($ge->descripcion) );
      }
      
      /// añadimos los epigrafes
      $epigrafe = new epigrafe();
      foreach($epigrafe->all_from_ejercicio($this->ejercicio->codejercicio) as $ep)
      {
         $aux = $archivo_xml->addChild("epigrafe");
         $aux->addChild("codgrupo", $ep->codgrupo);
         $aux->addChild("codepigrafe", $ep->codepigrafe);
         $aux->addChild("descripcion", base64_encode($ep->descripcion) );
      }
      
      /// añadimos las cuentas
      $cuenta = new cuenta();
      $num = 0;
      $cuentas = $cuenta->full_from_ejercicio($this->ejercicio->codejercicio);
      foreach($cuentas as $c)
      {
         $aux = $archivo_xml->addChild("cuenta");
         $aux->addChild("codepigrafe", $c->codepigrafe);
         $aux->addChild("codcuenta", $c->codcuenta);
         $aux->addChild("descripcion", base64_encode($c->descripcion) );
         $aux->addChild("idcuentaesp", $c->idcuentaesp);
      }
      
      /// añadimos las subcuentas
      $subcuenta = new subcuenta();
      foreach($subcuenta->all_from_ejercicio($this->ejercicio->codejercicio) as $sc)
      {
         $aux = $archivo_xml->addChild("subcuenta");
         $aux->addChild("codcuenta", $sc->codcuenta);
         $aux->addChild("codsubcuenta", $sc->codsubcuenta);
         $aux->addChild("descripcion", base64_encode($sc->descripcion) );
         $aux->addChild("coddivisa", $sc->coddivisa);
      }
      
      /// volcamos el XML
      header("content-type: application/xml; charset=UTF-8");
      header('Content-Disposition: attachment; filename="ejercicio_'.$this->ejercicio->codejercicio.'.xml"');
      echo $archivo_xml->asXML();
   }
   
   private function importar_xml()
   {
      $import_step = 0;
      $this->importar_url = FALSE;
      
      if( isset($_POST['fuente']) )
      {
         if( file_exists('tmp/'.FS_TMP_NAME.'ejercicio.xml') )
            unlink('tmp/'.FS_TMP_NAME.'ejercicio.xml');
         
         if( in_array($_POST['fuente'], array('espanya', 'colombia', 'panama', 'peru', 'venezuela') ) )
         {
            copy('extras/'.$_POST['fuente'].'.xml', 'tmp/'.FS_TMP_NAME.'ejercicio.xml');
         }
         else if( $_POST['fuente'] == 'archivo' AND isset($_POST['archivo']) )
         {
            copy($_FILES['farchivo']['tmp_name'], 'tmp/'.FS_TMP_NAME.'ejercicio.xml');
         }
         else
         {
            $this->new_error_msg('Has seleccionado importar desde un archivo externo,
               pero no has seleccionado ningún archivo.');
         }
         
         $import_step = 1;
         $this->importar_url = $this->url().'&importar='.(1 + $import_step);
      }
      else if( isset($_GET['importar']) )
      {
         $import_step = intval($_GET['importar']);
         if( $import_step < 7 )
         {
            $this->importar_url = $this->url().'&importar='.(1 + $import_step);
         }
         else
         {
            $this->new_message('Datos importados correctamente.');
            $this->new_message("Ahora es el momento de <a href='index.php?page=ventas_clientes#nuevo'>"
                    . "añadir algún cliente</a>, si todavía no lo has hecho.");
            $import_step = 0;
         }
      }
      
      if( file_exists('tmp/'.FS_TMP_NAME.'ejercicio.xml') AND $import_step > 0 )
      {
         $this->new_message('Importando ejercicio: paso '.$import_step.' de 6 ...');
         
         $xml = simplexml_load_file('tmp/'.FS_TMP_NAME.'ejercicio.xml');
         if( $xml )
         {
            if( $xml->balance AND $import_step == 1 )
            {
               foreach($xml->balance as $b)
               {
                  $balance = new balance();
                  if( !$balance->get($b->codbalance) )
                  {
                     $balance->codbalance = $b->codbalance;
                     $balance->naturaleza = $b->naturaleza;
                     $balance->nivel1 = $b->nivel1;
                     $balance->descripcion1 = base64_decode($b->descripcion1);
                     $balance->nivel2 = $balance->intval($b->nivel2);
                     $balance->descripcion2 = base64_decode($b->descripcion2);
                     $balance->nivel3 = $b->nivel3;
                     $balance->descripcion3 = base64_decode($b->descripcion3);
                     $balance->orden3 = $b->orden3;
                     $balance->nivel4 = $b->nivel4;
                     $balance->descripcion4 = base64_decode($b->descripcion4);
                     $balance->descripcion4ba = base64_decode($b->descripcion4ba);
                     
                     if( !$balance->save() )
                        $this->importar_url = FALSE;
                  }
               }
               
               if( $xml->balance_cuenta )
               {
                  $balance_cuenta = new balance_cuenta();
                  $all_bcs = $balance_cuenta->all();
                  foreach($xml->balance_cuenta as $bc)
                  {
                     $encontrado = FALSE;
                     foreach($all_bcs as $bc2)
                     {
                        if($bc2->codbalance == $bc->codbalance AND $bc2->codcuenta == $bc->codcuenta)
                        {
                           $encontrado = TRUE;
                           break;
                        }
                     }
                     if( !$encontrado )
                     {
                        $new_bc = new balance_cuenta();
                        $new_bc->codbalance = $bc->codbalance;
                        $new_bc->codcuenta = $bc->codcuenta;
                        $new_bc->desccuenta = base64_decode($bc->descripcion);
                        
                        if( !$new_bc->save() )
                           $this->importar_url = FALSE;
                     }
                  }
               }
               
               if( $xml->balance_cuenta_a )
               {
                  $balance_cuenta_a = new balance_cuenta_a();
                  $all_bcas = $balance_cuenta_a->all();
                  foreach($xml->balance_cuenta_a as $bc)
                  {
                     $encontrado = FALSE;
                     foreach($all_bcas as $bc2)
                     {
                        if($bc2->codbalance == $bc->codbalance AND $bc2->codcuenta == $bc->codcuenta)
                        {
                           $encontrado = TRUE;
                           break;
                        }
                     }
                     if( !$encontrado )
                     {
                        $new_bc = new balance_cuenta_a();
                        $new_bc->codbalance = $bc->codbalance;
                        $new_bc->codcuenta = $bc->codcuenta;
                        $new_bc->desccuenta = base64_decode($bc->descripcion);
                        
                        if( !$new_bc->save() )
                           $this->importar_url = FALSE;
                     }
                  }
               }
            }
            
            if( $import_step == 2 )
            {
               if( $xml->cuenta_especial )
               {
                  foreach($xml->cuenta_especial as $ce)
                  {
                     $cuenta_especial = new cuenta_especial();
                     if( !$cuenta_especial->get( $ce->idcuentaesp ) )
                     {
                        $cuenta_especial->idcuentaesp = $ce->idcuentaesp;
                        $cuenta_especial->descripcion = base64_decode($ce->descripcion);
                        
                        if( !$cuenta_especial->save() )
                           $this->importar_url = FALSE;
                     }
                  }
               }
               
               if( $xml->grupo_epigrafes )
               {
                  foreach($xml->grupo_epigrafes as $ge)
                  {
                     $grupo_epigrafes = new grupo_epigrafes();
                     if( !$grupo_epigrafes->get_by_codigo($ge->codgrupo, $this->ejercicio->codejercicio) )
                     {
                        $grupo_epigrafes->codejercicio = $this->ejercicio->codejercicio;
                        $grupo_epigrafes->codgrupo = $ge->codgrupo;
                        $grupo_epigrafes->descripcion = base64_decode($ge->descripcion);
                        
                        if( !$grupo_epigrafes->save() )
                           $this->importar_url = FALSE;
                     }
                  }
               }
               
               if( $xml->epigrafe )
               {
                  $grupo_epigrafes = new grupo_epigrafes();
                  foreach($xml->epigrafe as $ep)
                  {
                     $epigrafe = new epigrafe();
                     if( !$epigrafe->get_by_codigo($ep->codepigrafe, $this->ejercicio->codejercicio) )
                     {
                        $ge = $grupo_epigrafes->get_by_codigo($ep->codgrupo, $this->ejercicio->codejercicio);
                        if($ge)
                        {
                           $epigrafe->idgrupo = $ge->idgrupo;
                           $epigrafe->codgrupo = $ge->codgrupo;
                           $epigrafe->codejercicio = $this->ejercicio->codejercicio;
                           $epigrafe->codepigrafe = $ep->codepigrafe;
                           $epigrafe->descripcion = base64_decode($ep->descripcion);
                           
                           if( !$epigrafe->save() )
                              $this->importar_url = FALSE;
                        }
                     }
                  }
               }
            }
            
            if( $xml->cuenta AND $import_step == 3 )
            {
               $epigrafe = new epigrafe();
               foreach($xml->cuenta as $c)
               {
                  $cuenta = new cuenta();
                  if( !$cuenta->get_by_codigo($c->codcuenta, $this->ejercicio->codejercicio) )
                  {
                     $ep = $epigrafe->get_by_codigo($c->codepigrafe, $this->ejercicio->codejercicio);
                     if($ep)
                     {
                        $cuenta->idepigrafe = $ep->idepigrafe;
                        $cuenta->codepigrafe = $ep->codepigrafe;
                        $cuenta->codcuenta = $c->codcuenta;
                        $cuenta->codejercicio = $this->ejercicio->codejercicio;
                        $cuenta->descripcion = base64_decode($c->descripcion);
                        $cuenta->idcuentaesp = $c->idcuentaesp;
                        
                        if( !$cuenta->save() )
                           $this->importar_url = FALSE;
                     }
                  }
               }
            }
            
            if( $xml->subcuenta AND $import_step == 4 )
            {
               $cuenta = new cuenta();
               foreach($xml->subcuenta as $sc)
               {
                  $subcuenta = new subcuenta();
                  if( !$subcuenta->get_by_codigo($sc->codsubcuenta, $this->ejercicio->codejercicio) )
                  {
                     $cu = $cuenta->get_by_codigo($sc->codcuenta, $this->ejercicio->codejercicio);
                     if($cu)
                     {
                        $subcuenta->idcuenta = $cu->idcuenta;
                        $subcuenta->codcuenta = $cu->codcuenta;
                        $subcuenta->coddivisa = $sc->coddivisa;
                        $subcuenta->codejercicio = $this->ejercicio->codejercicio;
                        $subcuenta->codsubcuenta = $sc->codsubcuenta;
                        $subcuenta->descripcion = base64_decode($sc->descripcion);
                        
                        if( !$subcuenta->save() )
                           $this->importar_url = FALSE;
                     }
                  }
               }
            }
            
            if( $import_step == 5 )
            {
               $cliente = new cliente();
               foreach($cliente->all_full() as $cli)
               {
                  /// forzamos la generación y asociación de una subcuenta para el cliente
                  $cli->get_subcuenta( $this->ejercicio->codejercicio );
               }
            }
            
            if( $import_step == 6 )
            {
               $proveedor = new proveedor();
               foreach($proveedor->all_full() as $pro)
               {
                  /// forzamos la generación y asociación de una subcuenta para cada proveedor
                  $pro->get_subcuenta( $this->ejercicio->codejercicio );
               }
            }
         }
         else
            $this->new_error("Imposible leer el archivo.");
      }
   }
   
   private function cerrar_ejercicio()
   {
      $this->new_message('Cerrando ejercicio...');
      $asiento = new asiento();
      
      $continuar = TRUE;
      
      if( isset($this->ejercicio->idasientopyg) )
      {
         $aspyg = $asiento->get( $this->ejercicio->idasientopyg );
         if( $aspyg )
         {
            if( !$aspyg->delete() )
            {
               $this->new_error_msg('Imposible eliminar el asiento de pérdidas y ganancias.');
               $continuar = FALSE;
            }
         }
         else
            $this->ejercicio->save(); /// al guardar ya comprueba los asientos especiales
      }
      
      if( isset($this->ejercicio->idasientocierre) )
      {
         $asc = $asiento->get( $this->ejercicio->idasientocierre );
         if( $asc )
         {
            if( !$asc->delete() )
            {
               $this->new_error_msg('Imposible eliminar el asiento de cierre.');
               $continuar = FALSE;
            }
         }
         else
            $this->ejercicio->save(); /// al guardar ya comprueba los asientos especiales
      }
      
      $siguiente_ejercicio = $this->ejercicio->get_by_fecha( Date('d-m-Y', strtotime($this->ejercicio->fechafin)+24*3600) );
      
      if( isset($siguiente_ejercicio->idasientoapertura) )
      {
         $asap = $asiento->get( $siguiente_ejercicio->idasientoapertura );
         if( $asap )
         {
            if( !$asap->delete() )
            {
               $this->new_error_msg('Imposible eliminar el asiento de apertura.');
               $continuar = FALSE;
            }
         }
         else
            $this->ejercicio->save(); /// al guardar ya comprueba los asientos especiales
      }
      
      if( $continuar )
      {
         $asiento_pyg = new asiento();
         $asiento_pyg->codejercicio = $this->ejercicio->codejercicio;
         $asiento_pyg->concepto = 'Regularización ejercicio '.$this->ejercicio->nombre;
         $asiento_pyg->editable = FALSE;
         $asiento_pyg->fecha = $this->ejercicio->fechafin;
         if( !$asiento_pyg->save() )
            $continuar = FALSE;
      }
      
      if( $continuar )
      {
         $asiento_cierre = new asiento();
         $asiento_cierre->codejercicio = $this->ejercicio->codejercicio;
         $asiento_cierre->concepto = 'Asiento de cierre del ejercicio '.$this->ejercicio->nombre;
         $asiento_cierre->editable = FALSE;
         $asiento_cierre->fecha = $this->ejercicio->fechafin;
         if( !$asiento_cierre->save() )
            $continuar = FALSE;
      }
      
      if( $continuar )
      {
         
         $asiento_apertura = new asiento();
         $asiento_apertura->codejercicio = $siguiente_ejercicio->codejercicio;
         $asiento_apertura->concepto = 'Asiento de apertura del ejercicio '.$siguiente_ejercicio->nombre;
         $asiento_apertura->editable = FALSE;
         $asiento_apertura->fecha = $siguiente_ejercicio->fechainicio;
         if( !$asiento_apertura->save() )
            $continuar = FALSE;
      }
      
      if( $continuar )
      {
         /// actualizamos los saldos de las subcuentas:
         $subcuenta = new subcuenta();
         foreach($subcuenta->all_from_ejercicio($this->ejercicio->codejercicio) as $sc)
         {
            if( $sc->is_outdated() )
               $sc->save();
         }
         
         
         /*
          * Abonamos y cargamos los saldos de las cuentas de los grupos 6 y 7,
          * la diferencia la enviamos a la cuenta 129.
          */
         $diferencia = 0;
         foreach($subcuenta->all_from_ejercicio($this->ejercicio->codejercicio) as $sc)
         {
            if( in_array(substr($sc->codcuenta, 0, 1), array('6', '7')) AND $sc->tiene_saldo() )
            {
               $ppyg = new partida();
               $ppyg->idasiento = $asiento_pyg->idasiento;
               $ppyg->concepto = $asiento_pyg->concepto;
               $ppyg->idsubcuenta = $sc->idsubcuenta;
               $ppyg->codsubcuenta = $sc->codsubcuenta;
               
               if($sc->saldo < 0)
                  $ppyg->debe = abs($sc->saldo);
               else
                  $ppyg->haber = $sc->saldo;
               
               $diferencia += $ppyg->debe - $ppyg->haber;
               
               $ppyg->coddivisa = $sc->coddivisa;
               if( !$ppyg->save() )
                  $continuar = FALSE;
            }
         }
         
         $cuenta = new cuenta();
         $cuenta_pyg = $cuenta->get_by_codigo('129', $this->ejercicio->codejercicio);
         if($cuenta_pyg)
         {
            $subcuenta_pyg = FALSE;
            foreach($cuenta_pyg->get_subcuentas() as $sc)
            {
               $subcuenta_pyg = $sc;
               break;
            }
            
            if($subcuenta_pyg)
            {
               $ppyg = new partida();
               $ppyg->idasiento = $asiento_pyg->idasiento;
               $ppyg->concepto = $asiento_pyg->concepto;
               $ppyg->idsubcuenta = $subcuenta_pyg->idsubcuenta;
               $ppyg->codsubcuenta = $subcuenta_pyg->codsubcuenta;
               $ppyg->haber = $diferencia;
               $ppyg->coddivisa = $sc->coddivisa;
               if( !$ppyg->save() )
                  $continuar = FALSE;
            }
            else
            {
               $this->new_error_msg('No se encuentra una subcuenta para la cuenta 129.');
               $continuar = FALSE;
            }
         }
         else
         {
            $this->new_error_msg('No se encuentra la cuenta 129.');
            $continuar = FALSE;
         }
         
         
         /*
          * Generamos los asientos de cierre y apertura
          */
         foreach($subcuenta->all_from_ejercicio($this->ejercicio->codejercicio) as $sc)
         {
            if( $sc->tiene_saldo() )
            {
               $pac = new partida();
               $pac->idasiento = $asiento_cierre->idasiento;
               $pac->concepto = $asiento_cierre->concepto;
               $pac->idsubcuenta = $sc->idsubcuenta;
               $pac->codsubcuenta = $sc->codsubcuenta;
               
               if($sc->saldo < 0)
                  $pac->debe = abs($sc->saldo);
               else
                  $pac->haber = $sc->saldo;
               
               $pac->coddivisa = $sc->coddivisa;
               if( !$pac->save() )
                  $continuar = FALSE;
               
               if($sc->codcuenta == '129')
                  $nsc = $subcuenta->get_by_codigo('1200000000', $siguiente_ejercicio->codejercicio, TRUE);
               else
                  $nsc = $subcuenta->get_by_codigo($sc->codsubcuenta, $siguiente_ejercicio->codejercicio, TRUE);
               
               if( $nsc )
               {
                  $paa = new partida();
                  $paa->idasiento = $asiento_apertura->idasiento;
                  $paa->concepto = $asiento_apertura->concepto;
                  $paa->idsubcuenta = $nsc->idsubcuenta;
                  $paa->codsubcuenta = $nsc->codsubcuenta;
                  
                  if($sc->saldo > 0)
                     $paa->debe = $sc->saldo;
                  else
                     $paa->haber = abs($sc->saldo);
                  
                  $paa->coddivisa = $nsc->coddivisa;
                  if( !$paa->save() )
                     $continuar = FALSE;
               }
               else
                  $continuar = FALSE;
            }
         }
         
         /// cerramos el ejercicio
         if( $continuar )
         {
            $this->ejercicio->estado = 'CERRADO';
            $this->ejercicio->idasientopyg = $asiento_pyg->idasiento;
            $this->ejercicio->idasientocierre = $asiento_cierre->idasiento;
            if( $this->ejercicio->save() )
               $this->new_message('Ejercicio cerrado correctamente.');
            else
               $this->new_error_msg('Error al cerrar el ejercicio.');
            
            $siguiente_ejercicio->idasientoapertura = $asiento_apertura->idasiento;
            if( !$siguiente_ejercicio->save() )
               $this->new_error_msg('Error al modificar el siguiente ejercicio.');
         }
         else
         {
            $this->new_error_msg('Error al generar los asientos.');
            
            if( $asiento_pyg->delete() )
               $this->new_message('Asiento de pérdidas y ganancias eliminado.');
            else
               $this->new_error_msg('Imposible eliminar el asiento de pérdidas y ganancias.');
            
            if( $asiento_cierre->delete() )
               $this->new_message('Asiento de cierre eliminado.');
            else
               $this->new_error_msg('Imposible eliminar el asiento de cierre.');
            
            if( $asiento_apertura->delete() )
               $this->new_message('Asiento de apertura eliminado.');
            else
               $this->new_error_msg('Imposible eliminar el asiento de apertura.');
         }
      }
   }
}
