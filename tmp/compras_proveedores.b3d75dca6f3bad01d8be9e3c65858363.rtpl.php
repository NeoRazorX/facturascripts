<?php if(!class_exists('raintpl')){exit;}?><?php $tpl = new RainTPL;$tpl_dir_temp = self::$tpl_dir;$tpl->assign( $this->var );$tpl->draw( dirname("header") . ( substr("header",-1,1) != "/" ? "/" : "" ) . basename("header") );?>


<script type="text/javascript">
   $(document).ready(function() {
      document.f_custom_search.query.focus();
      if(window.location.hash.substring(1) == 'nuevo')
      {
         $("#modal_nuevo_proveedor").modal('show');
         document.f_nuevo_proveedor.nombre.focus();
      }
      $("#b_nuevo_proveedor").click(function(event) {
         event.preventDefault();
         $("#modal_nuevo_proveedor").modal('show');
         document.f_nuevo_proveedor.nombre.focus();
      });
   });
</script>

<div class="modal" id="modal_nuevo_proveedor">
   <div class="modal-dialog">
      <div class="modal-content">
		 <form name="f_nuevo_proveedor" action="<?php echo $fsc->url();?>" method="post">
            <div class="modal-header">
               <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
               <h4 class="modal-title">Nuevo proveedor</h4>
            </div>
            <div class="modal-body">
               <div class="form-group">
                  Código:
                  <input class="form-control" type="text" name="codproveedor" value="<?php echo $fsc->proveedor->get_new_codigo();?>" maxlegth="6" autocomplete="off"/>
               </div>
               <div class="form-group">
                  Nombre:
                  <input class="form-control" type="text" name="nombre" autocomplete="off"/>
               </div>
               <div class="form-group">
                  <?php  echo FS_CIFNIF;?>:
                  <input class="form-control" type="text" name="cifnif" autocomplete="off"/>
               </div>
               <div class="form-group">
                  <a href="<?php echo $fsc->pais->url();?>">País</a>:
                  <select class="form-control" name="pais" class="form-control">
                     <?php $loop_var1=$fsc->pais->all(); $counter1=-1; if($loop_var1) foreach( $loop_var1 as $key1 => $value1 ){ $counter1++; ?>

                     <option value="<?php echo $value1->codpais;?>"<?php if( $value1->is_default() ){ ?> selected="selected"<?php } ?>><?php echo $value1->nombre;?></option>
                     <?php } ?>

                  </select>
               </div>
               <div class="form-group">
                  Provincia:
                  <input class="form-control" type="text" name="provincia" autocomplete="off" value="<?php echo $fsc->empresa->provincia;?>"/>
               </div>
               <div class="form-group">
                  Ciudad:
                  <input class="form-control" type="text" name="ciudad" autocomplete="off" value="<?php echo $fsc->empresa->ciudad;?>"/>
               </div>
               <div class="form-group">
                  Código Postal:
                  <input class="form-control" type="text" name="codpostal" autocomplete="off" value="<?php echo $fsc->empresa->codpostal;?>"/>
               </div>
               <div class="form-group">
                  Dirección:
                  <input class="form-control" type="text" name="direccion" value="C/ " autocomplete="off"/>
               </div>
            </div>
            <div class="modal-footer">
               <button class="btn btn-sm btn-primary" type="submit" onclick="this.disabled=true;this.form.submit();" title="Guardar">
                   <span class="glyphicon glyphicon-floppy-disk"></span>
                   &nbsp; Guardar
                </button>
            </div>
         </form>
      </div>
   </div>
</div>

<?php if( $fsc->query!='' ){ ?>

<h3 class="text-center">Resultados de la búsqueda "<?php echo $fsc->query;?>":</h3>
<?php } ?>


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
         <td><a href="<?php echo $value1->url();?>"><?php echo $value1->codproveedor;?></a> <?php echo $value1->nombre;?></td>
         <td><?php echo $value1->cifnif;?></td>
         <td><?php echo $value1->observaciones_resume();?></td>
      </tr>
      <?php }else{ ?>

      <tr class="bg-warning">
         <td colspan="3">Ningún proveedor encontrado. Pulsa el botón <b>Nuevo</b> para crear uno.</td>
      </tr>
      <?php } ?>

   </table>
</div>

<ul class="pager">
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

<?php $tpl = new RainTPL;$tpl_dir_temp = self::$tpl_dir;$tpl->assign( $this->var );$tpl->draw( dirname("footer") . ( substr("footer",-1,1) != "/" ? "/" : "" ) . basename("footer") );?>