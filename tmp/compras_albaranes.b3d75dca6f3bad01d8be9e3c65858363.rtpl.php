<?php if(!class_exists('raintpl')){exit;}?><?php $tpl = new RainTPL;$tpl_dir_temp = self::$tpl_dir;$tpl->assign( $this->var );$tpl->draw( dirname("header") . ( substr("header",-1,1) != "/" ? "/" : "" ) . basename("header") );?>


<script type="text/javascript">
   function buscar_lineas()
   {
      if(document.f_buscar_lineas.buscar_lineas.value == '')
      {
         $("#search_results").html('');
      }
      else
      {
         $.ajax({
            type: 'POST',
            url: '<?php echo $fsc->url();?>',
            dataType: 'html',
            data: $("form[name=f_buscar_lineas]").serialize(),
            success: function(datos) {
               var re = /<!--(.*?)-->/g;
               var m = re.exec( datos );
               if( m[1] == document.f_buscar_lineas.buscar_lineas.value )
               {
                  $("#search_results").html(datos);
               }
            }
         });
      }
   }
   $(document).ready(function() {
      document.f_custom_search.query.focus();
      $("#b_buscar_lineas").click(function(event) {
         event.preventDefault();
         $("#modal_buscar_lineas").modal('show');
         document.f_buscar_lineas.buscar_lineas.focus();
      });
      $("#f_buscar_lineas").keyup(function() {
         buscar_lineas();
      });
      $("#f_buscar_lineas").submit(function(event) {
         event.preventDefault();
         buscar_lineas();
      });
   });
</script>

<form class="form" role="form" id="f_buscar_lineas" name="f_buscar_lineas" action="<?php echo $fsc->url();?>" method="post">
   <div class="modal" id="modal_buscar_lineas">
      <div class="modal-dialog" style="width: 99%; max-width: 950px;">
         <div class="modal-content">
            <div class="modal-header">
               <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
               <h4 class="modal-title">Buscar en las líneas</h4>
            </div>
            <div class="modal-body">
               <div class="input-group">
                  <input class="form-control" type="text" name="buscar_lineas" placeholder="Referencia" autocomplete="off"/>
                  <span class="input-group-btn">
                     <button class="btn btn-primary" type="submit">
                        <span class="glyphicon glyphicon-search"></span>
                     </button>
                  </span>
               </div>
            </div>
            <div id="search_results" class="table-responsive"></div>
         </div>
      </div>
   </div>
</form>

<?php if( $fsc->query!='' ){ ?>

<h3 class="text-center">Resultados de "<?php echo $fsc->query;?>":</h3>
<?php } ?>


<ul class="nav nav-tabs" role="tablist">
   <li<?php if( !isset($_GET['ptefactura']) ){ ?> class="active"<?php } ?>><a href="<?php echo $fsc->url();?>">Todos</a></li>
   <li<?php if( isset($_GET['ptefactura']) ){ ?> class="active"<?php } ?>><a href="<?php echo $fsc->url();?>&ptefactura=TRUE">Pendientes</a></li>
</ul>

<div class="table-responsive">
   <table class="table table-hover">
      <thead>
         <tr>
            <th></th>
            <th class="text-left">Código + Núm. Proveedor</th>
            <th class="text-left">Proveedor</th>
            <th class="text-left">Observaciones</th>
            <th class="text-right">Total</th>
            <th class="text-right">Fecha</th>
         </tr>
      </thead>
      <?php $loop_var1=$fsc->resultados; $counter1=-1; if($loop_var1) foreach( $loop_var1 as $key1 => $value1 ){ $counter1++; ?>

      <tr<?php if( $value1->total<=0 ){ ?> class="bg-warning"<?php } ?>>
         <td class="text-center">
            <?php if( !$value1->ptefactura ){ ?>

            <span class="glyphicon glyphicon-paperclip" title="facturado"></span>
            <?php } ?>

         </td>
         <td><a href="<?php echo $value1->url();?>"><?php echo $value1->codigo;?></a> <?php echo $value1->numproveedor;?></td>
         <td><?php echo $value1->nombre;?></td>
         <td><?php echo $value1->observaciones_resume();?></td>
         <td class="text-right"><?php echo $fsc->show_precio($value1->total, $value1->coddivisa);?></td>
         <td class="text-right"><span title="<?php echo $value1->hora;?>"><?php echo $value1->fecha;?></span></td>
      </tr>
      <?php }else{ ?>

      <tr class="bg-warning">
         <td></td>
         <td colspan="5">Ningún <?php  echo FS_ALBARAN;?> encontrado. Pulsa el botón <b>Nuevo</b> para crear uno.</td>
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

