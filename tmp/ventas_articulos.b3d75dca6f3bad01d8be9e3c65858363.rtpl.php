<?php if(!class_exists('raintpl')){exit;}?><?php $tpl = new RainTPL;$tpl_dir_temp = self::$tpl_dir;$tpl->assign( $this->var );$tpl->draw( dirname("header") . ( substr("header",-1,1) != "/" ? "/" : "" ) . basename("header") );?>


<script type="text/javascript">
   function show_nuevo_articulo()
   {
      $("#modal_nuevo_articulo").modal('show');
      document.f_nuevo_articulo.referencia.focus();
   }
   function show_tarifas()
   {
      $("#ul_tabs li").each(function() {
         $(this).removeClass("active");
      });
      $("#div_articulos").hide();
      $("#b_tarifas").addClass('active');
      $("#div_tarifas").show();
      document.f_nueva_tarifa.nombre.focus();
   }
   function show_mod_iva()
   {
      $("#modal_modificar_iva").modal('show');
   }
   $(document).ready(function() {
      document.f_custom_search.query.focus();
      
      if(window.location.hash.substring(1) == 'nuevo')
      {
         show_nuevo_articulo();
      }
      else if(window.location.hash.substring(1) == 'tarifas')
      {
         show_tarifas();
      }
      else if(window.location.hash.substring(1) == 'mod-iva')
      {
         show_mod_iva();
      }
      
      $("#b_nuevo_articulo").click(function(event) {
         event.preventDefault();
         show_nuevo_articulo();
      });
      $("#b_tarifas").click(function(event) {
         event.preventDefault();
         show_tarifas();
      });
      $("#b_modificar_iva").click(function(event) {
         event.preventDefault();
         show_mod_iva();
      });
   });
</script>

<form class="form-horizontal" role="form" name="f_nuevo_articulo" action="<?php echo $fsc->url();?>" method="post">
   <div class="modal" id="modal_nuevo_articulo">
      <div class="modal-dialog">
         <div class="modal-content">
            <div class="modal-header">
               <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
               <h4 class="modal-title">Nuevo artículo</h4>
            </div>
            <?php if( $fsc->familia->all() ){ ?>

            <div class="modal-body">
               <div class="form-group">
                  <label for="referencia" class="col-lg-2 col-md-2 col-sm-2 control-label">Referencia</label>
                  <div class="col-lg-10 col-md-10 col-sm-10">
                     <input class="form-control" type="text" name="referencia" maxlength="18" autocomplete="off"/>
                  </div>
               </div>
               <div class="form-group">
                  <label for="codfamilia" class="col-lg-2 col-md-2 col-sm-2 control-label"><a href="<?php echo $fsc->familia->url();?>">Familia</a></label>
                  <div class="col-lg-10 col-md-10 col-sm-10">
                     <select class="form-control" name="codfamilia">
                        <?php $loop_var1=$fsc->familia->all(); $counter1=-1; if($loop_var1) foreach( $loop_var1 as $key1 => $value1 ){ $counter1++; ?>

                        <option value="<?php echo $value1->codfamilia;?>"<?php if( $value1->is_default() ){ ?> selected="selected"<?php } ?>><?php echo $value1->descripcion;?></option>
                        <?php } ?>

                     </select>
                  </div>
               </div>
               <div class="form-group">
                  <label for="codimpuesto" class="col-lg-2 col-md-2 col-sm-2 control-label"><a href="<?php echo $fsc->impuesto->url();?>">IVA</a></label>
                  <div class="col-lg-10 col-md-10 col-sm-10">
                     <select class="form-control" name="codimpuesto">
                        <?php $loop_var1=$fsc->impuesto->all(); $counter1=-1; if($loop_var1) foreach( $loop_var1 as $key1 => $value1 ){ $counter1++; ?>

                        <option value="<?php echo $value1->codimpuesto;?>"<?php if( $value1->is_default() ){ ?> selected="selected"<?php } ?>><?php echo $value1->descripcion;?></option>
                        <?php } ?>

                     </select>
                  </div>
               </div>
            </div>
            <div class="modal-footer">
               <button class="btn btn-sm btn-primary" type="submit" onclick="this.disabled=true;this.form.submit();">
                  <span class="glyphicon glyphicon-floppy-disk"></span>
                  &nbsp; Guardar
               </button>
            </div>
            <?php }else{ ?>

            <div class="error">
               No hay <a target="_blank" href="<?php echo $fsc->familia->url();?>">familias</a> creadas. Debes crear al menos una.
            </div>
            <?php } ?>

         </div>
      </div>
   </div>
</form>

<form action="<?php echo $fsc->url();?>" method="post" class="form">
   <input type="hidden" name="mod_iva" value="TRUE"/>
   <div class="modal fade" id="modal_modificar_iva">
      <div class="modal-dialog">
         <div class="modal-content">
            <div class="modal-header">
               <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
               <h4 class="modal-title">Modificar IVA</h4>
            </div>
            <div class="modal-body">
               <div class="form-group">
                  Mover los artículos con
                  <select class="form-control" name="codimpuesto">
                     <?php $loop_var1=$fsc->impuesto->all(); $counter1=-1; if($loop_var1) foreach( $loop_var1 as $key1 => $value1 ){ $counter1++; ?>

                     <option value="<?php echo $value1->codimpuesto;?>"><?php echo $value1->descripcion;?></option>
                     <?php } ?>

                  </select>
               </div>
               <div class="form-group">
                  al impuesto
                  <select class="form-control" name="codimpuesto2">
                     <?php $loop_var1=$fsc->impuesto->all(); $counter1=-1; if($loop_var1) foreach( $loop_var1 as $key1 => $value1 ){ $counter1++; ?>

                     <option value="<?php echo $value1->codimpuesto;?>"><?php echo $value1->descripcion;?></option>
                     <?php } ?>

                  </select>
               </div>
               <div class="checkbox">
                  <label>
                     <input type="checkbox" name="mantener" value="TRUE"/>
                     Mantener precios (baja el PVP para mentener el mismo PVP+IVA)
                  </label>
               </div>
            </div>
            <div class="modal-footer">
               <button class="btn btn-sm btn-primary" type="submit" onclick="this.disabled=true;this.form.submit();">
                  <span class="glyphicon glyphicon-floppy-disk"></span>
                  &nbsp; Mover
               </button>
            </div>
         </div>
      </div>
   </div>
</form>

<?php if( $fsc->query!='' ){ ?>

<form name="custom_search2" action="<?php echo $fsc->url();?>" method="post" class="form">
   <input type="hidden" name="query" value="<?php echo $fsc->query;?>"/>
   <div class="container-fluid">
      <div class="row">
         <div class="col-lg-7 col-md-7 col-sm-7">
            <h3 style="margin-top: 0px;">Resultados de la búsqueda "<?php echo $fsc->query;?>".</h3>
         </div>
         <div class="col-lg-2 col-md-2 col-sm-2">
            <div class="checkbox">
               <label>
                  <input type="checkbox" name="con_stock"<?php if( $fsc->con_stock ){ ?> checked="checked"<?php } ?> value="TRUE" onchange="document.custom_search2.submit()"/>
                  Sólo con stock
               </label>
            </div>
         </div>
         <div class="form-group col-lg-3 col-md-3 col-sm-3">
            <select class="form-control" name="codfamilia" onchange="document.custom_search2.submit()">
               <option value="">Todas las familias</option>
               <option value="">-----</option>
               <?php $loop_var1=$fsc->familia->all(); $counter1=-1; if($loop_var1) foreach( $loop_var1 as $key1 => $value1 ){ $counter1++; ?>

                  <?php if( $value1->codfamilia==$fsc->codfamilia ){ ?>

                  <option value="<?php echo $value1->codfamilia;?>" selected="selected"><?php echo $value1->descripcion;?></option>
                  <?php }else{ ?>

                  <option value="<?php echo $value1->codfamilia;?>"><?php echo $value1->descripcion;?></option>
                  <?php } ?>

               <?php } ?>

            </select>
         </div>
      </div>
   </div>
</form>
<?php } ?>


<ul class="nav nav-tabs" id="ul_tabs">
   <li<?php if( !isset($_GET['public']) ){ ?> class="active"<?php } ?>><a href="<?php echo $fsc->url();?>">Todos</a></li>
   <li<?php if( isset($_GET['public']) ){ ?> class="active"<?php } ?>><a href="<?php echo $fsc->url();?>&public=TRUE">Públicos</a></li>
   <li id="b_tarifas"><a href="#tarifas">Tarifas</a></li>
</ul>

<div id="div_articulos">
   <div class="table-responsive">
      <table class="table table-hover">
         <thead>
            <tr>
               <th class="text-left">Familia</th>
               <th class="text-left">Referencia + Descripción</th>
               <th class="text-right">PVP</th>
               <th class="text-right">PVP+IVA</th>
               <th class="text-right">Stock</th>
            </tr>
         </thead>
         <?php $loop_var1=$fsc->resultados; $counter1=-1; if($loop_var1) foreach( $loop_var1 as $key1 => $value1 ){ $counter1++; ?>

         <tr<?php if( $value1->bloqueado ){ ?> class="bg-danger"<?php }elseif( $value1->stockfis<$value1->stockmin ){ ?> class="bg-warning"<?php } ?>>
            <td><?php echo $value1->codfamilia;?></td>
            <?php if( $value1->bloqueado ){ ?>

               <td class="bg-danger"><a href="<?php echo $value1->url();?>"><?php echo $value1->referencia;?></a> <?php echo $value1->descripcion;?></td>
            <?php }else{ ?>

               <td><a href="<?php echo $value1->url();?>"><?php echo $value1->referencia;?></a> <?php echo $value1->descripcion;?></td>
            <?php } ?>

            <td class="text-right"><span title="actualizado el <?php echo $value1->factualizado;?>"><?php echo $fsc->show_precio($value1->pvp);?></span></td>
            <td class="text-right"><span title="actualizado el <?php echo $value1->factualizado;?>"><?php echo $fsc->show_precio($value1->pvp_iva());?></span></td>
            <td class="text-right"><?php echo $value1->stockfis;?></td>
         </tr>
         <?php }else{ ?>

         <tr class="bg-warning">
            <td colspan="5">Ningun artículo encontrado. Pulsa el botón <b>Nuevo</b> para crear uno.</td>
         </tr>
         <?php } ?>

      </table>
   </div>
   
   <ul class="pager" id="ul_paginador">
      <?php if( $fsc->anterior_url()!='' ){ ?>

      <li class="previous">
         <a href="<?php echo $fsc->anterior_url();?>">
            <span class="glyphicon glyphicon-chevron-left"></span>
            &nbsp; Anteriores
         </a>
      </li>
      <?php } ?>

      
      <?php if( $fsc->siguiente_url()!='' ){ ?>

      <li class="next">
         <a href="<?php echo $fsc->siguiente_url();?>">
            Siguientes &nbsp;
            <span class="glyphicon glyphicon-chevron-right"></span>
         </a>
      </li>
      <?php } ?>

   </ul>
</div>

<div id="div_tarifas" class="table-responsive" style="display: none;">
   <table class="table table-hover">
      <thead>
         <tr>
            <th class="text-left">Código</th>
            <th class="text-left">Nombre</th>
            <th class="text-right">% dto.</th>
            <th class="text-right">Acción</th>
         </tr>
      </thead>
      <?php $loop_var1=$fsc->tarifa->all(); $counter1=-1; if($loop_var1) foreach( $loop_var1 as $key1 => $value1 ){ $counter1++; ?>

      <form action="<?php echo $fsc->url();?>#tarifas" method="post" class="form">
         <input type="hidden" name="codtarifa" value="<?php echo $value1->codtarifa;?>"/>
         <tr>
            <td>
               <input class="form-control" type="text" name="codtarifa" value="<?php echo $value1->codtarifa;?>" maxlength="6" autocomplete="off"/>
            </td>
            <td>
               <input class="form-control" type="text" name="nombre" value="<?php echo $value1->nombre;?>" maxlength="50" autocomplete="off"/>
            </td>
            <td>
               <input class="form-control text-right" type="number" step="any" name="dtopor" value="<?php echo $value1->dtopor();?>" autocomplete="off"/>
            </td>
            <td class="text-right">
               <div class="btn-group">
                  <a class="btn btn-sm btn-danger" type="button" title="Eliminar" href="<?php echo $fsc->url();?>&delete_tarifa=<?php echo $value1->codtarifa;?>#tarifas">
                     <span class="glyphicon glyphicon-trash"></span>
                  </a>
                  <button class="btn btn-sm btn-primary" type="submit" onclick="this.disabled=true;this.form.submit();" title="Guardar">
                     <span class="glyphicon glyphicon-floppy-disk"></span>
                  </button>
               </div>
            </td>
         </tr>
      </form>
      <?php } ?>

      <form name="f_nueva_tarifa" action="<?php echo $fsc->url();?>#tarifas" method="post" class="form">
         <tr class="bg-info">
            <td>
               <input class="form-control" type="text" name="codtarifa" value="<?php echo $fsc->tarifa->get_new_codigo();?>" maxlength="6" autocomplete="off"/>
            </td>
            <td>
               <input class="form-control" type="text" name="nombre" maxlength="50" placeholder="Nueva Tarifa" autocomplete="off"/>
            </td>
            <td>
               <input class="form-control text-right" type="number" step="any" name="dtopor" value="0" autocomplete="off"/>
            </td>
            <td class="text-right">
               <button class="btn btn-sm btn-primary" type="submit" onclick="this.disabled=true;this.form.submit();" title="Guardar">
                  <span class="glyphicon glyphicon-floppy-disk"></span>
               </button>
            </td>
         </tr>
      </form>
   </table>
</div>

<?php $tpl = new RainTPL;$tpl_dir_temp = self::$tpl_dir;$tpl->assign( $this->var );$tpl->draw( dirname("footer") . ( substr("footer",-1,1) != "/" ? "/" : "" ) . basename("footer") );?>