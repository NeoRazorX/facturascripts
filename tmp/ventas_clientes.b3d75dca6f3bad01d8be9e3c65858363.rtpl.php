<?php if(!class_exists('raintpl')){exit;}?><?php $tpl = new RainTPL;$tpl_dir_temp = self::$tpl_dir;$tpl->assign( $this->var );$tpl->draw( dirname("header") . ( substr("header",-1,1) != "/" ? "/" : "" ) . basename("header") );?>


<script type="text/javascript">
   function show_nuevo_cliente()
   {
      $("#modal_nuevo_cliente").modal('show');
      document.f_nuevo_cliente.nombre.focus();
   }
   function show_grupos()
   {
      $("#ul_tabs li").each(function() {
         $( this ).removeClass("active");
      });
      $("#div_clientes").hide();
      $("#b_grupos_clientes").addClass('active');
      $("#div_grupos").show();
      document.f_new_grupo.nombre.focus();
   }
   $(document).ready(function() {
      document.f_custom_search.query.focus();
      
      if(window.location.hash.substring(1) == 'nuevo')
      {
         show_nuevo_cliente();
      }
      else if(window.location.hash.substring(1) == 'grupos')
      {
         show_grupos();
      }
      
      $("#b_grupos_clientes").click(function(event) {
         event.preventDefault();
         show_grupos();
      });
      $("#b_nuevo_cliente").click(function(event) {
         event.preventDefault();
         show_nuevo_cliente();
      });
   });
</script>

<form class="form-horizontal" role="form" name="f_nuevo_cliente" action="<?php echo $fsc->url();?>" method="post">
   <div class="modal" id="modal_nuevo_cliente">
      <div class="modal-dialog">
         <div class="modal-content">
            <div class="modal-header">
               <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
               <h4 class="modal-title">Nuevo cliente</h4>
            </div>
            <div class="modal-body">
               <div class="form-group">
                  <label for="codcliente" class="col-lg-2 col-md-2 col-sm-2 control-label">Código</label>
                  <div class="col-lg-10 col-md-10 col-sm-10">
                     <input class="form-control" type="text" name="codcliente" value="<?php echo $fsc->cliente->get_new_codigo();?>" maxlegth="6" autocomplete="off"/>
                  </div>
               </div>
               <div class="form-group">
                  <label for="codserie" class="col-lg-2 col-md-2 col-sm-2 control-label"><a href="<?php echo $fsc->serie->url();?>">Serie</a></label>
                  <div class="col-lg-10 col-md-10 col-sm-10">
                     <select class="form-control" name="codserie">
                     <?php $loop_var1=$fsc->serie->all(); $counter1=-1; if($loop_var1) foreach( $loop_var1 as $key1 => $value1 ){ $counter1++; ?>

                        <option value="<?php echo $value1->codserie;?>"<?php if( $value1->is_default() ){ ?> selected="selected"<?php } ?>><?php echo $value1->descripcion;?></option>
                     <?php } ?>

                     </select>
                  </div>
               </div>
               <div class="form-group">
                  <label for="nombre" class="col-lg-2 col-md-2 col-sm-2 control-label">Nombre</label>
                  <div class="col-lg-10 col-md-10 col-sm-10">
                     <input class="form-control" type="text" name="nombre" autocomplete="off"/>
                  </div>
               </div>
               <div class="form-group">
                  <label for="cifnif" class="col-lg-2 col-md-2 col-sm-2 control-label"><?php  echo FS_CIFNIF;?></label>
                  <div class="col-lg-10 col-md-10 col-sm-10">
                     <input class="form-control" type="text" name="cifnif" autocomplete="off"/>
                  </div>
               </div>
               <div class="form-group">
                  <label for="pais" class="col-lg-2 col-md-2 col-sm-2 control-label"><a href="<?php echo $fsc->pais->url();?>">País</a></label>
                  <div class="col-lg-10 col-md-10 col-sm-10">
                     <select class="form-control" name="pais">
                     <?php $loop_var1=$fsc->pais->all(); $counter1=-1; if($loop_var1) foreach( $loop_var1 as $key1 => $value1 ){ $counter1++; ?>

                        <option value="<?php echo $value1->codpais;?>"<?php if( $value1->is_default() ){ ?> selected="selected"<?php } ?>><?php echo $value1->nombre;?></option>
                     <?php } ?>

                     </select>
                  </div>
               </div>
               <div class="form-group">
                  <label for="provincia" class="col-lg-2 col-md-2 col-sm-2 control-label">Provincia</label>
                  <div class="col-lg-10 col-md-10 col-sm-10">
                     <input class="form-control" type="text" name="provincia" autocomplete="off" value="<?php echo $fsc->empresa->provincia;?>"/>
                  </div>
               </div>
               <div class="form-group">
                  <label for="ciudad" class="col-lg-2 col-md-2 col-sm-2 control-label">Ciudad</label>
                  <div class="col-lg-10 col-md-10 col-sm-10">
                     <input class="form-control" type="text" name="ciudad" autocomplete="off" value="<?php echo $fsc->empresa->ciudad;?>"/>
                  </div>
               </div>
               <div class="form-group">
                  <label for="codpostal" class="col-lg-2 col-md-2 col-sm-2 control-label">Código Postal</label>
                  <div class="col-lg-10 col-md-10 col-sm-10">
                     <input class="form-control" type="text" name="codpostal" autocomplete="off" value="<?php echo $fsc->empresa->codpostal;?>"/>
                  </div>
               </div>
               <div class="form-group">
                  <label for="direccion" class="col-lg-2 col-md-2 col-sm-2 control-label">Dirección</label>
                  <div class="col-lg-10 col-md-10 col-sm-10">
                     <input class="form-control" type="text" name="direccion" value="C/ " autocomplete="off"/>
                  </div>
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

<?php if( $fsc->query!='' ){ ?>

<h3 class="text-center">Resultados de la búsqueda "<?php echo $fsc->query;?>":</h3>
<?php } ?>


<ul class="nav nav-tabs" id="ul_tabs">
   <li class="active"><a href="<?php echo $fsc->url();?>">Todos</a></li>
   <li id="b_grupos_clientes"><a href="#grupos">Grupos</a></li>
</ul>

<div id="div_clientes">
   <div class="table-responsive">
      <table class="table table-hover">
         <thead>
            <tr>
               <th class="text-left">Código + Nombre</th>
               <th class="text-left"><?php  echo FS_CIFNIF;?></th>
               <th class="text-left">Observaciones</th>
            </tr>
         </thead>
         <?php $loop_var1=$fsc->resultados; $counter1=-1; if($loop_var1) foreach( $loop_var1 as $key1 => $value1 ){ $counter1++; ?>

         <tr>
            <td><a href="<?php echo $value1->url();?>"><?php echo $value1->codcliente;?></a> <?php echo $value1->nombre;?></td>
            <td><?php echo $value1->cifnif;?></td>
            <td><?php echo $value1->observaciones_resume();?></td>
         </tr>
         <?php }else{ ?>

         <tr class="bg-warning">
            <td colspan="3">Ningún cliente encontrado. Pulse el botón <b>Nuevo</b> para crear uno.</td>
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

<div id="div_grupos" class="table-responsive" style="display: none;">
   <table class="table table-hover">
      <thead>
         <tr>
            <th align='left' width="100">Código</th>
            <th align='left'>Nombre</th>
            <th align='left'>Tarifa</th>
            <th align='right'>Acción</th>
         </tr>
      </thead>
      <?php $loop_var1=$fsc->grupo->all(); $counter1=-1; if($loop_var1) foreach( $loop_var1 as $key1 => $value1 ){ $counter1++; ?>

      <form action="<?php echo $fsc->url();?>" method="post" class="form">
         <tr>
            <td>
               <input class="form-control" type="text" name="codgrupo" value="<?php echo $value1->codgrupo;?>" readonly="true"/>
            </td>
            <td>
               <input class="form-control" type="text" name="nombre" value="<?php echo $value1->nombre;?>" maxlength="50" autocomplete="off"/>
            </td>
            <td>
               <select name="codtarifa" class="form-control">
                  <option value="---">Ninguna</option>
                  <option value="---">---</option>
                  <?php $loop_var2=$fsc->tarifa->all(); $counter2=-1; if($loop_var2) foreach( $loop_var2 as $key2 => $value2 ){ $counter2++; ?>

                     <?php if( $value1->codtarifa==$value2->codtarifa ){ ?>

                     <option value="<?php echo $value2->codtarifa;?>" selected="selected"><?php echo $value2->nombre;?></option>
                     <?php }else{ ?>

                     <option value="<?php echo $value2->codtarifa;?>"><?php echo $value2->nombre;?></option>
                     <?php } ?>

                  <?php } ?>

               </select>
            </td>
            <td align='right'>
               <div class="btn-group">
                  <a class="btn btn-sm btn-danger" title="Eliminar" href="<?php echo $fsc->url();?>&delete_grupo=<?php echo $value1->codgrupo;?>#grupos">
                     <span class="glyphicon glyphicon-trash"></span>
                  </a>
                  <button class="btn btn-sm btn-primary" type="submit" title="Guardar" onclick="this.disabled=true;this.form.submit();">
                     <span class="glyphicon glyphicon-floppy-disk"></span>
                  </button>
               </div>
            </td>
         </tr>
      </form>
      <?php } ?>

      <form name="f_new_grupo" action="<?php echo $fsc->url();?>" method="post" class="form">
         <tr class="bg-info">
            <td>
               <input class="form-control" type="text" name="codgrupo" value="<?php echo $fsc->grupo->get_new_codigo();?>" maxlength="6" autocomplete="off"/>
            </td>
            <td>
               <input class="form-control" type="text" name="nombre" maxlength="50" placeholder="Nuevo grupo" autocomplete="off"/>
            </td>
            <td>
               <select name="codtarifa" class="form-control">
                  <option value="---">Ninguna</option>
                  <option value="---">---</option>
                  <?php $loop_var1=$fsc->tarifa->all(); $counter1=-1; if($loop_var1) foreach( $loop_var1 as $key1 => $value1 ){ $counter1++; ?>

                  <option value="<?php echo $value1->codtarifa;?>"><?php echo $value1->nombre;?></option>
                  <?php } ?>

               </select>
            </td>
            <td align='right'>
               <button class="btn btn-sm btn-primary" type="submit" onclick="this.disabled=true;this.form.submit();" title="Guardar">
                  <span class="glyphicon glyphicon-floppy-disk"></span>
               </button>
            </td>
         </tr>
      </form>
   </table>
</div>

<?php $tpl = new RainTPL;$tpl_dir_temp = self::$tpl_dir;$tpl->assign( $this->var );$tpl->draw( dirname("footer") . ( substr("footer",-1,1) != "/" ? "/" : "" ) . basename("footer") );?>