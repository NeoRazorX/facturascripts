<?php if(!class_exists('raintpl')){exit;}?>   <hr style="margin-top: 50px;"/>   
   
   <div class="container-fluid hidden-print" style="margin-bottom: 10px;">
      <?php if( FS_DB_HISTORY ){ ?>

      <div class="row">
         <div class="col-lg-12 col-md-12 col-sm-12">
            <div class="panel panel-default hidden-print">
               <div class="panel-heading">
                  <h3 class="panel-title">Consultas SQL:</h3>
               </div>
               <div class="panel-body">
                  <ol style="font-size: 11px; margin: 0px; padding: 0px 0px 0px 20px;">
                     <?php $loop_var1=$fsc->get_db_history(); $counter1=-1; if($loop_var1) foreach( $loop_var1 as $key1 => $value1 ){ $counter1++; ?><li><?php echo $value1;?></li><?php } ?>

                  </ol>
               </div>
            </div>
         </div>
      </div>
      <?php } ?>

      
      <div class="row">
         <div class="col-lg-4 col-md-4 col-sm-4 col-xs-4">
            <small>
               Creado con <a target="_blank" href="http://www.facturascripts.com">FacturaScripts</a>.
            </small>
         </div>
         <div class="col-lg-4 col-md-4 col-sm-4 col-xs-4 text-center">
            <span class="label label-default">Consultas: <?php echo $fsc->selects();?></span>
            <span class="label label-default">Transacciones: <?php echo $fsc->transactions();?></span>
         </div>
         <div class="col-lg-4 col-md-4 col-sm-4 col-xs-4 text-right">
            <span class="label label-default">Procesado en: <?php echo $fsc->duration();?></span>
         </div>
      </div>
   </div>
</body>
</html>