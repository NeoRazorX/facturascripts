<?php if(!class_exists('raintpl')){exit;}?><?php $tpl = new RainTPL;$tpl_dir_temp = self::$tpl_dir;$tpl->assign( $this->var );$tpl->draw( dirname("header") . ( substr("header",-1,1) != "/" ? "/" : "" ) . basename("header") );?>


<?php if( $fsc->proveedor ){ ?>

<script type="text/javascript">
   function comprobar_url()
   {
      $("#panel_generales").hide();
      $("#panel_facturacion").hide();
      $("#panel_cuentas").hide();
      $("#panel_direcciones").hide();
      $("#div_subcuentas").hide();
      $("#chart_albaranes_month").hide();
      $("#b_generales").removeClass('active');
      $("#b_facturacion").removeClass('active');
      $("#b_direcciones").removeClass('active');
      $("#b_subcuentas").removeClass('active');
      $("#b_stats").removeClass('active');
      
      if(window.location.hash.substring(1) == 'facturacion')
      {
         $("#panel_facturacion").show();
         $("#panel_cuentas").show();
         $("#b_facturacion").addClass('active');
         document.f_proveedor.codserie.focus();
      }
      else if(window.location.hash.substring(1) == 'direcciones')
      {
         $("#panel_direcciones").show();
         $("#b_direcciones").addClass('active');
      }
      else if(window.location.hash.substring(1) == 'subcuentas')
      {
         $("#div_subcuentas").show();
         $("#b_subcuentas").addClass('active');
      }
      else if(window.location.hash.substring(1) == 'stats')
      {
         $("#chart_albaranes_month").show();
         $("#b_stats").addClass('active');
      }
      else
      {
         $("#panel_generales").show();
         $("#b_generales").addClass('active');
         document.f_proveedor.nombre.focus();
      }
   }
   $(document).ready(function() {
      comprobar_url();
      window.onpopstate = function(){
         comprobar_url();
      }
      $("#b_eliminar").click(function(event) {
         event.preventDefault();
         if( confirm("¿Realmente desea eliminar este proveedor?") )
            window.location.href = '<?php echo $fsc->ppage->url();?>&delete=<?php echo $fsc->proveedor->codproveedor;?>';
      });
      $("#b_nueva_cuenta").click(function(event) {
         event.preventDefault();
         $("#modal_nueva_cuenta").modal('show');
         document.f_nueva_cuenta.descripcion.focus();
      });
      $("#b_nueva_direccion").click(function(event) {
         event.preventDefault();
         $("#modal_nueva_direccion").modal('show');
         document.f_nueva_direccion.provincia.focus();
      });
   });
</script>

<?php if( isset($_GET['stats']) ){ ?>

<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script type="text/javascript">
   // Load the Visualization API and the piechart package.
   google.load('visualization', '1.0', {'packages':['corechart']});
   
   // Set a callback to run when the Google Visualization API is loaded.
   google.setOnLoadCallback(drawChart);
   
   // Callback that creates and populates a data table,
   // instantiates the pie chart, passes in the data and
   // draws it.
   function drawChart()
   {
      // Create the data table.
      var data = new google.visualization.DataTable();
      data.addColumn('string', 'mes');
      data.addColumn('number', 'compras');
      data.addRows([
      <?php $loop_var1=$fsc->stats_from_prov(); $counter1=-1; if($loop_var1) foreach( $loop_var1 as $key1 => $value1 ){ $counter1++; ?>

         ['<?php echo $value1['mes'];?>', <?php echo $value1['compras'];?>],
      <?php } ?>

      ]);
      
      // Instantiate and draw our chart, passing in some options.
      var chart = new google.visualization.AreaChart(document.getElementById('chart_albaranes_month'));
      chart.draw(data);
   }
</script>
<?php } ?>


<div class="container-fluid">
   <div class="row">
      <div class="col-lg-2 col-md-2 col-sm-2">
         <div class="list-group">
            <a id="b_generales" href="#" class="list-group-item active">
               <span class="glyphicon glyphicon-dashboard"></span>
               &nbsp; Datos generales
            </a>
            <a id="b_facturacion" href="#facturacion" class="list-group-item">
               <span class="glyphicon glyphicon-euro"></span>
               &nbsp; Facturación
            </a>
            <a id="b_direcciones" href="#direcciones" class="list-group-item">
               <span class="glyphicon glyphicon-road"></span>
               &nbsp; Direcciones
            </a>
            <a id="b_subcuentas" href="#subcuentas" class="list-group-item">
               <span class="glyphicon glyphicon-credit-card"></span>
               &nbsp; Subcuentas
            </a>
            <?php $loop_var1=$fsc->extensiones; $counter1=-1; if($loop_var1) foreach( $loop_var1 as $key1 => $value1 ){ $counter1++; ?>

               <?php if( $value1->type=='button' ){ ?>

               <a id="b_<?php echo $value1->from;?>" href="index.php?page=<?php echo $value1->from;?>&codproveedor=<?php echo $fsc->proveedor->codproveedor;?>" class="list-group-item">
                  <span class="glyphicon glyphicon-list"></span>
                  &nbsp; <?php echo $value1->text;?>

               </a>
               <?php } ?>

            <?php } ?>

            <a id="b_stats" href="<?php echo $fsc->url();?>&stats=TRUE#stats" class="list-group-item">
               <span class="glyphicon glyphicon-stats"></span>
               &nbsp; Estadísticas
            </a>
         </div>
      </div>
      
      <div class="col-lg-10 col-md-10 col-sm-10">
         <form name="f_proveedor" action="<?php echo $fsc->url();?>" method="post" class="form">
            <input type="hidden" name="codproveedor" value="<?php echo $fsc->proveedor->codproveedor;?>"/>
            <div class="panel panel-primary" id="panel_generales">
               <div class="panel-heading">
                  <h3 class="panel-title">Datos generales</h3>
               </div>
               <div class="panel-body">
                  <div class="form-group col-lg-6 col-md-6 col-sm-6">
                     Nombre:
                     <input class="form-control" type="text" name="nombre" value="<?php echo $fsc->proveedor->nombre;?>" autocomplete="off"/>
                  </div>
                  <div class="form-group col-lg-6 col-md-6 col-sm-6">
                     Nombre comercial:
                     <input class="form-control" type="text" name="nombrecomercial" value="<?php echo $fsc->proveedor->nombrecomercial;?>" autocomplete="off"/>
                  </div>
                  <div class="form-group col-lg-4 col-md-4 col-sm-4">
                     <?php  echo FS_CIFNIF;?>:
                     <input class="form-control" type="text" name="cifnif" value="<?php echo $fsc->proveedor->cifnif;?>" autocomplete="off"/>
                  </div>
                  <div class="form-group col-lg-4 col-md-4 col-sm-4">
                     Teléfono 1:
                     <input class="form-control" type="text" name="telefono1" value="<?php echo $fsc->proveedor->telefono1;?>" autocomplete="off"/>
                  </div>
                  <div class="form-group col-lg-4 col-md-4 col-sm-4">
                     Teléfono 2:
                     <input class="form-control" type="text" name="telefono2" value="<?php echo $fsc->proveedor->telefono2;?>" autocomplete="off"/>
                  </div>
                  <div class="form-group col-lg-4 col-md-4 col-sm-4">
                     Fax:
                     <input class="form-control" type="text" name="fax" value="<?php echo $fsc->proveedor->fax;?>" autocomplete="off"/>
                  </div>
                  <div class="form-group col-lg-4 col-md-4 col-sm-4">
                     Email:
                     <input class="form-control" type="text" name="email" value="<?php echo $fsc->proveedor->email;?>" maxlength="50" autocomplete="off"/>
                  </div>
                  <div class="form-group col-lg-4 col-md-4 col-sm-4">
                     Web:
                     <input class="form-control" type="text" name="web" value="<?php echo $fsc->proveedor->web;?>" autocomplete="off"/>
                  </div>
                  <div>
                     Observaciones:
                     <textarea class="form-control" name="observaciones" rows="3"><?php echo $fsc->proveedor->observaciones;?></textarea>
                  </div>
               </div>
               <div class="panel-footer text-right">
                  <button class="btn btn-sm btn-primary" type="submit" onclick="this.disabled=true;this.form.submit();">
                     <span class="glyphicon glyphicon-floppy-disk"></span>
                     &nbsp; Guardar
                  </button>
               </div>
            </div>
            
            <div class="panel panel-primary" id="panel_facturacion">
               <div class="panel-heading">
                  <h3 class="panel-title">Facturación</h3>
               </div>
               <div class="panel-body">
                  <div class="form-group col-lg-6 col-md-6 col-sm-6">
                     <a href="<?php echo $fsc->serie->url();?>">Serie</a>:
                     <select class="form-control" name="codserie">
                     <?php $loop_var1=$fsc->serie->all(); $counter1=-1; if($loop_var1) foreach( $loop_var1 as $key1 => $value1 ){ $counter1++; ?>

                        <?php if( $value1->codserie==$fsc->proveedor->codserie ){ ?>

                        <option value="<?php echo $value1->codserie;?>" selected="selected"><?php echo $value1->descripcion;?></option>
                        <?php }else{ ?>

                        <option value="<?php echo $value1->codserie;?>"><?php echo $value1->descripcion;?></option>
                        <?php } ?>

                     <?php } ?>

                     </select>
                  </div>
                  <div class="form-group col-lg-6 col-md-6 col-sm-6">
                     <a href="<?php echo $fsc->forma_pago->url();?>">Forma de pago</a>:
                     <select class="form-control" name="codpago">
                     <?php $loop_var1=$fsc->forma_pago->all(); $counter1=-1; if($loop_var1) foreach( $loop_var1 as $key1 => $value1 ){ $counter1++; ?>

                        <?php if( $value1->codpago==$fsc->proveedor->codpago ){ ?>

                        <option value="<?php echo $value1->codpago;?>" selected="selected"><?php echo $value1->descripcion;?></option>
                        <?php }else{ ?>

                        <option value="<?php echo $value1->codpago;?>"><?php echo $value1->descripcion;?></option>
                        <?php } ?>

                     <?php } ?>

                     </select>
                  </div>
                  <div class="form-group col-lg-6 col-md-6 col-sm-6">
                     <a href="<?php echo $fsc->divisa->url();?>">Divisa</a>:
                     <select class="form-control" name="coddivisa">
                     <?php $loop_var1=$fsc->divisa->all(); $counter1=-1; if($loop_var1) foreach( $loop_var1 as $key1 => $value1 ){ $counter1++; ?>

                        <?php if( $value1->coddivisa==$fsc->proveedor->coddivisa ){ ?>

                        <option value="<?php echo $value1->coddivisa;?>" selected="selected"><?php echo $value1->descripcion;?></option>
                        <?php }else{ ?>

                        <option value="<?php echo $value1->coddivisa;?>"><?php echo $value1->descripcion;?></option>
                        <?php } ?>

                     <?php } ?>

                     </select>
                  </div>
                  <div class="form-group col-lg-6 col-md-6 col-sm-6">
                     <a href="http://www.facturascripts.com/community/item/regimen-de-iva-desde-la-version-2014-4b-se-puede-seleccionar-el-regimen-de-784.html" target="_blank">Régimen IVA</a>:
                     <select class="form-control" name="regimeniva">
                     <?php $loop_var1=$fsc->proveedor->regimenes_iva(); $counter1=-1; if($loop_var1) foreach( $loop_var1 as $key1 => $value1 ){ $counter1++; ?>

                        <?php if( $value1==$fsc->proveedor->regimeniva ){ ?>

                        <option value="<?php echo $value1;?>" selected="selected"><?php echo $value1;?></option>
                        <?php }else{ ?>

                        <option value="<?php echo $value1;?>"><?php echo $value1;?></option>
                        <?php } ?>

                     <?php } ?>

                     </select>
                  </div>
               </div>
               <div class="panel-footer text-right">
                  <button class="btn btn-sm btn-primary" type="submit" onclick="this.disabled=true;this.form.submit();">
                     <span class="glyphicon glyphicon-floppy-disk"></span>
                     &nbsp; Guardar
                  </button>
               </div>
            </div>
         </form>
         
         <div class="panel-group" id="panel_cuentas">
            <?php $loop_var1=$fsc->cuenta_banco->all_from_proveedor($fsc->proveedor->codproveedor); $counter1=-1; if($loop_var1) foreach( $loop_var1 as $key1 => $value1 ){ $counter1++; ?>

            <form action="<?php echo $fsc->url();?>#facturacion" method="post" class="form">
               <input type="hidden" name="codcuenta" value="<?php echo $value1->codcuenta;?>"/>
               <input type="hidden" name="codproveedor" value="<?php echo $value1->codproveedor;?>"/>
               <div class="panel panel-info">
                  <div class="panel-heading">
                     <h3 class="panel-title">Cuenta bancaria #<?php echo $value1->codcuenta;?></h3>
                  </div>
                  <div class="panel-body">
                     <div class="form-group">
                        Descripción:
                        <input class="form-control" type="text" name="descripcion" value="<?php echo $value1->descripcion;?>" placeholder="Cuenta principal" autocomplete="off"/>
                     </div>
                     <div class="form-group col-lg-6">
                        <a target="_blank" href="http://es.wikipedia.org/wiki/International_Bank_Account_Number">IBAN</a>:
                        <input class="form-control" type="text" name="iban" value="<?php echo $value1->iban;?>" maxlength="28" placeholder="ES12345678901234567890123456" autocomplete="off"/>
                     </div>
                     <div class="form-group col-lg-6">
                        Calcular IBAN:
                        <input class="form-control" type="text" name="ciban" maxlength="20" placeholder="ENTIDAD SUCURSAL DC CUENTA" autocomplete="off"/>
                     </div>
                  </div>
                  <div class="panel-footer text-right">
                     <a class="btn btn-sm btn-danger pull-left" type="button" href="<?php echo $fsc->url();?>&delete_cuenta=<?php echo $value1->codcuenta;?>#facturacion">
                         <span class="glyphicon glyphicon-trash"></span>
                         &nbsp; Eliminar
                     </a>
                     <button class="btn btn-sm btn-primary" type="submit" onclick="this.disabled=true;this.form.submit();">
                         <span class="glyphicon glyphicon-floppy-disk"></span>
                         &nbsp; Guardar
                     </button>
                  </div>
               </div>
            </form>
            <?php } ?>

            <div class="panel panel-success">
               <div class="panel-heading">
                  <h3 class="panel-title">
                     <a id="b_nueva_cuenta" href="#">Nueva cuenta bancaria...</a>
                  </h3>
               </div>
            </div>
         </div>
         
         <div class="panel-group" id="panel_direcciones">
            <?php $loop_var1=$fsc->proveedor->get_direcciones(); $counter1=-1; if($loop_var1) foreach( $loop_var1 as $key1 => $value1 ){ $counter1++; ?>

            <form action="<?php echo $fsc->url();?>#direcciones" method="post" class="form">
               <input type="hidden" name="codproveedor" value="<?php echo $fsc->proveedor->codproveedor;?>"/>
               <input type="hidden" name="coddir" value="<?php echo $value1->id;?>"/>
               <div class="panel panel-info">
                  <div class="panel-heading">
                     <h3 class="panel-title"><?php echo $value1->descripcion;?></h3>
                  </div>
                  <div class="panel-body">
                     <div class="form-group col-lg-4 col-md-4 col-sm-4">
                        <a href="<?php echo $fsc->pais->url();?>">País</a>
                        <select class="form-control" name="pais">
                        <?php $loop_var2=$fsc->pais->all(); $counter2=-1; if($loop_var2) foreach( $loop_var2 as $key2 => $value2 ){ $counter2++; ?>

                        <option value="<?php echo $value2->codpais;?>"<?php if( $value1->codpais==$value2->codpais ){ ?> selected="selected"<?php } ?>><?php echo $value2->nombre;?></option>
                        <?php } ?>

                        </select>
                     </div>
                     <div class="form-group col-lg-4 col-md-4 col-sm-4">
                        Provincia:
                        <input class="form-control" type="text" name="provincia" value="<?php echo $value1->provincia;?>"/>
                     </div>
                     <div class="form-group col-lg-4 col-md-4 col-sm-4">
                        Ciudad:
                        <input class="form-control" type="text" name="ciudad" value="<?php echo $value1->ciudad;?>"/>
                     </div>
                     <div class="form-group col-lg-3 col-md-3 col-sm-3">
                        Código Postal:
                        <input class="form-control" type="text" name="codpostal" value="<?php echo $value1->codpostal;?>" autocomplete="off"/>
                     </div>
                     <div class="form-group col-lg-9 col-md-9 col-sm-9">
                        Dirección:
                        <input class="form-control" type="text" name="direccion" value="<?php echo $value1->direccion;?>" autocomplete="off"/>
                     </div>
                     <div class="form-group col-lg-4 col-md-4 col-sm-4">
                        Apartado:
                        <input class="form-control" type="text" name="apartado" value="<?php echo $value1->apartado;?>" autocomplete="off"/>
                     </div>
                     <div class="radio col-lg-3 col-md-3 col-sm-3">
                        <label>
                           <?php if( $value1->direccionppal ){ ?>

                           <input type="checkbox" name="direccionppal" id="direccionppal_<?php echo $value1->id;?>" value="TRUE" checked="checked"/>
                           <?php }else{ ?>

                           <input type="checkbox" name="direccionppal" id="direccionppal_<?php echo $value1->id;?>" value="TRUE"/>
                           <?php } ?>

                           Dirección principal
                        </label>
                     </div>
                     <div class="form-group col-lg-5 col-md-5 col-sm-5">
                        Descripción:
                        <input class="form-control" type="text" name="descripcion" value="<?php echo $value1->descripcion;?>" autocomplete="off"/>
                     </div>
                  </div>
                  <div class="panel-footer text-right">
                     <a class="btn btn-sm btn-danger pull-left" type="button" href="<?php echo $fsc->url();?>&delete_dir=<?php echo $value1->id;?>#direcciones">
                         <span class="glyphicon glyphicon-trash"></span>
                         &nbsp; Eliminar
                     </a>
                     <button class="btn btn-sm btn-primary" type="submit" onclick="this.disabled=true;this.form.submit();">
                         <span class="glyphicon glyphicon-floppy-disk"></span>
                         &nbsp; Guardar
                     </button>
                  </div>
               </div>
            </form>
            <?php } ?>

            <div class="panel panel-success">
               <div class="panel-heading">
                  <h3 class="panel-title">
                     <a id="b_nueva_direccion" href="#">Nueva dirección...</a>
                  </h3>
               </div>
            </div>
         </div>
         
         <div class="table-responsive" id="div_subcuentas">
            <div class="table-responsive">
               <table class="table table-hover">
                  <thead>
                     <tr>
                        <th class="text-left">Ejercicio</th>
                        <th></th>
                        <th class="text-left">Subcuenta + Descripción</th>
                        <th class="text-right">Debe</th>
                        <th class="text-right">Haber</th>
                        <th class="text-right">Saldo</th>
                     </tr>
                  </thead>
                  <?php $loop_var1=$fsc->proveedor->get_subcuentas(); $counter1=-1; if($loop_var1) foreach( $loop_var1 as $key1 => $value1 ){ $counter1++; ?>

                  <tr>
                     <td><div class="form-control"><?php echo $value1->codejercicio;?></div></td>
                     <td class="text-right">
                        <a class="btn btn-sm btn-default" href="index.php?page=subcuenta_asociada&pro=<?php echo $fsc->proveedor->codproveedor;?>&idsc=<?php echo $value1->idsubcuenta;?>">
                           <span class="glyphicon glyphicon-wrench"></span>
                        </a>
                     </td>
                     <td>
                        <div class="form-control">
                           <a href="<?php echo $value1->url();?>"><?php echo $value1->codsubcuenta;?></a> <?php echo $value1->descripcion;?>

                        </div>
                     </td>
                     <td>
                        <div class="form-control text-right"><?php echo $fsc->show_precio($value1->debe, $value1->coddivisa);?></div>
                     </td>
                     <td>
                        <div class="form-control text-right"><?php echo $fsc->show_precio($value1->haber, $value1->coddivisa);?></div>
                     </td>
                     <td>
                        <div class="form-control text-right"><?php echo $fsc->show_precio($value1->saldo, $value1->coddivisa);?></div>
                     </td>
                  </tr>
                  <?php } ?>

                  <tr>
                     <td colspan="6" class="text-center">
                        <a class="btn btn-sm btn-block btn-success" href="index.php?page=subcuenta_asociada&pro=<?php echo $fsc->proveedor->codproveedor;?>">Asignar una nueva subcuenta...</a>
                     </td>
                  </tr>
               </table>
            </div>
         </div>
         
         <div id="chart_albaranes_month" style="height: 400px;"></div>
      </div>
   </div>
</div>

<form name="f_nueva_cuenta" action="<?php echo $fsc->url();?>#facturacion" method="post" class="form">
   <input type="hidden" name="codproveedor" value="<?php echo $fsc->proveedor->codproveedor;?>"/>
   <div class="modal" id="modal_nueva_cuenta">
      <div class="modal-dialog">
         <div class="modal-content">
            <div class="modal-header">
               <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
               <h4 class="modal-title">Nueva cuenta bancaria</h4>
            </div>
            <div class="modal-body">
               <div class="form-group">
                  Descripción:
                  <input class="form-control" type="text" name="descripcion" placeholder="Cuenta principal" autocomplete="off"/>
               </div>
               <div class="form-group">
                  <a target="_blank" href="http://es.wikipedia.org/wiki/International_Bank_Account_Number">IBAN</a>:
                  <input class="form-control" type="text" name="iban" maxlength="28" placeholder="ES12345678901234567890123456" autocomplete="off"/>
               </div>
               <div class="form-group">
                  Calcular IBAN:
                  <input class="form-control" type="text" name="ciban" maxlength="20" placeholder="ENTIDAD SUCURSAL DC CUENTA" autocomplete="off"/>
               </div>
            </div>
            <div class="modal-footer">
               <button class="btn btn-sm btn-primary" type="submit" onclick="this.disabled=true;this.form.submit();" title="Guardar">
                  <span class="glyphicon glyphicon-floppy-disk"></span>
                  &nbsp; Guardar
                </button>
            </div>
         </div>
      </div>
   </div>
</form>

<form name="f_nueva_direccion" action="<?php echo $fsc->url();?>#direcciones" method="post">
   <input type="hidden" name="codproveedor" value="<?php echo $fsc->proveedor->codproveedor;?>"/>
   <input type="hidden" name="coddir" value=""/>
   <div class="modal" id="modal_nueva_direccion">
      <div class="modal-dialog">
         <div class="modal-content">
            <div class="modal-header">
               <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
               <h4 class="modal-title">Nueva dirección</h4>
            </div>
            <div class="modal-body">
               <div class="form-group">
                  <a href="<?php echo $fsc->pais->url();?>">País</a>:
                  <select class="form-control" name="pais">
                     <?php $loop_var1=$fsc->pais->all(); $counter1=-1; if($loop_var1) foreach( $loop_var1 as $key1 => $value1 ){ $counter1++; ?>

                     <option value="<?php echo $value1->codpais;?>"<?php if( $value1->is_default() ){ ?> selected="selected"<?php } ?>><?php echo $value1->nombre;?></option>
                     <?php } ?>

                  </select>
               </div>
               <div class="form-group">
                  Provincia:
                  <input class="form-control" type="text" name="provincia" value="<?php echo $fsc->empresa->provincia;?>"/>
               </div>
               <div class="form-group">
                  Ciudad:
                  <input class="form-control" type="text" name="ciudad" value="<?php echo $fsc->empresa->ciudad;?>"/>
               </div>
               <div class="form-group">
                  Código Postal:
                  <input class="form-control" type="text" name="codpostal" autocomplete="off"/>
               </div>
               <div class="form-group">
                  Dirección:
                  <input class="form-control" type="text" name="direccion" autocomplete="off"/>
               </div>
               <div class="form-group">
                  Apartado:
                  <input class="form-control" type="text" name="apartado" autocomplete="off"/>
               </div>
               <div class="checkbox">
                  <label>
                     <input type="checkbox" name="direccionppal" value="TRUE" checked="checked"/>
                     Dirección principal
                  </label>
               </div>
               <div class="form-group">
                  Descripción:
                  <input class="form-control" type="text" name="descripcion" value="Nueva dirección"/>
               </div>
            </div>
            <div class="modal-footer">
               <button class="btn btn-sm btn-primary" type="submit" onclick="this.disabled=true;this.form.submit();">
                  <span class="glyphicon glyphicon-floppy-disk"></span>
                  &nbsp; Guardar
               </button>
            </div>
         </div>
      </div>
   </div>
</form>
<?php }else{ ?>

<div class="text-center">
   <img src="view/img/fuuu_face.png" alt="fuuuuu"/>
</div>
<?php } ?>


<?php $tpl = new RainTPL;$tpl_dir_temp = self::$tpl_dir;$tpl->assign( $this->var );$tpl->draw( dirname("footer") . ( substr("footer",-1,1) != "/" ? "/" : "" ) . basename("footer") );?>