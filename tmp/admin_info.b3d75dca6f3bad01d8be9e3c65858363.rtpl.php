<?php if(!class_exists('raintpl')){exit;}?><?php $tpl = new RainTPL;$tpl_dir_temp = self::$tpl_dir;$tpl->assign( $this->var );$tpl->draw( dirname("header") . ( substr("header",-1,1) != "/" ? "/" : "" ) . basename("header") );?>


<div class="table-responsive">
   <table class="table table-hover">
      <thead>
         <tr>
            <th>FacturaScripts</th>
            <th>PHP</th>
            <th>Base de datos</th>
            <th>Motor de base de datos</th>
            <th>Memcache</th>
         </tr>
      </thead>
      <tr>
         <td class="text-center"><?php echo $fsc->version();?></td>
         <td class="text-center"><?php echo $fsc->php_version();?></td>
         <td class="text-center"><?php echo $fsc->fs_db_name();?></td>
         <td class="text-center"><?php echo $fsc->fs_db_version();?></td>
         <td class="text-center"><?php echo $fsc->cache_version();?></td>
      </tr>
   </table>
</div>

<div class="panel-group" id="accordion" style="margin: 10px;">
   <div class="panel panel-default">
      <div class="panel-heading">
         <h3 class="panel-title">
            <a data-toggle="collapse" data-parent="#accordion" href="#collapse_so">Sistema operativo</a>
         </h3>
      </div>
      <div id="collapse_so" class="panel-collapse collapse in">
         <div class="panel-body"><?php echo $fsc->uname();?></div>
         <?php if( $fsc->linux() ){ ?>

         <div class="panel-footer">
            <b>Uptime:</b> <?php echo $fsc->sys_uptime();?>

         </div>
         <?php } ?>

      </div>
   </div>
   
   <?php if( $fsc->linux() ){ ?>

   <div class="panel panel-default">
      <div class="panel-heading">
         <h3 class="panel-title">
            <a data-toggle="collapse" data-parent="#accordion" href="#collapse_mem">Memoria</a>
         </h3>
      </div>
      <div id="collapse_mem" class="panel-collapse collapse">
         <div class="panel-body"><pre><?php echo $fsc->sys_free();?></pre></div>
      </div>
   </div>
   
   <div class="panel panel-default">
      <div class="panel-heading">
         <h3 class="panel-title">
            <a data-toggle="collapse" data-parent="#accordion" href="#collapse_dd">Disco duro</a>
         </h3>
      </div>
      <div id="collapse_dd" class="panel-collapse collapse">
         <div class="panel-body"><pre><?php echo $fsc->sys_df();?></pre></div>
      </div>
   </div>
   <?php } ?>

   
   <div class="panel panel-default">
      <div class="panel-heading">
         <h3 class="panel-title">
            <a data-toggle="collapse" data-parent="#accordion" href="#collapse_bloq">Bloqueos en la base de datos</a>
         </h3>
      </div>
      <div id="collapse_bloq" class="panel-collapse collapse">
         <div class="table-responsive">
            <table class="table table-hover">
               <thead>
                  <tr>
                     <th class="text-left">Base de datos</th>
                     <th class="text-left">relname</th>
                     <th class="text-left">relation</th>
                     <th class="text-left">transaction ID</th>
                     <th class="text-left">PID</th>
                     <th class="text-left">modo</th>
                     <th class="text-left">granted</th>
                  </tr>
               </thead>
               <?php $loop_var1=$fsc->get_locks(); $counter1=-1; if($loop_var1) foreach( $loop_var1 as $key1 => $value1 ){ $counter1++; ?>

               <tr>
                  <td><?php echo $value1["database"];?></td>
                  <td><?php echo $value1["relname"];?></td>
                  <td><?php echo $value1["relation"];?></td>
                  <td><?php echo $value1["transactionid"];?></td>
                  <td><?php echo $value1["pid"];?></td>
                  <td><?php echo $value1["mode"];?></td>
                  <td><?php echo $value1["granted"];?></td>
               </tr>
               <?php }else{ ?>

               <tr class="bg-warning"><td colspan="7" class="text-center">Ningún bloqueo detectado.</td></tr>
               <?php } ?>

            </table>
         </div>
      </div>
   </div>
</div>

<?php $tpl = new RainTPL;$tpl_dir_temp = self::$tpl_dir;$tpl->assign( $this->var );$tpl->draw( dirname("footer") . ( substr("footer",-1,1) != "/" ? "/" : "" ) . basename("footer") );?>