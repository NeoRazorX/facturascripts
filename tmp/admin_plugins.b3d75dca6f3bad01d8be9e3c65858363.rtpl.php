<?php if(!class_exists('raintpl')){exit;}?><?php $tpl = new RainTPL;$tpl_dir_temp = self::$tpl_dir;$tpl->assign( $this->var );$tpl->draw( dirname("header") . ( substr("header",-1,1) != "/" ? "/" : "" ) . basename("header") );?>


<?php if( FS_DEMO ){ ?>

<div class="text-center">
   <img src="view/img/fuuu_face.png" alt="fuuuuu"/>
</div>
<?php }else{ ?>

<div class="container-fluid">
   <div class="row">
      <div class="col-lg-2 col-md-2 col-sm-2">
         <div class="list-group">
            <a href="<?php echo $fsc->url();?>" class="list-group-item<?php if( !$fsc->unstables ){ ?> active<?php } ?>">Estables</a>
            <a href="<?php echo $fsc->url();?>&unstable=TRUE" class="list-group-item<?php if( $fsc->unstables ){ ?> active<?php } ?>">Inestables</a>
         </div>
      </div>
      <div class="col-lg-10 col-md-10 col-sm-10">
         <div class="panel-group" id="accordion">
            <?php $loop_var1=$fsc->plugins(); $counter1=-1; if($loop_var1) foreach( $loop_var1 as $key1 => $value1 ){ $counter1++; ?>

            <div class="panel<?php if( $value1['enabled'] ){ ?> panel-success<?php }else{ ?> panel-default<?php } ?>">
               <div class="panel-heading">
                  <h3 class="panel-title">
                     <a data-toggle="collapse" data-parent="#accordion" href="#collapse_<?php echo $counter1;?>"><?php echo $value1['name'];?></a>
                  </h3>
               </div>
               <div id="collapse_<?php echo $counter1;?>" class="panel-collapse collapse<?php if( $counter1==0 ){ ?> in<?php } ?>">
                  <div class="panel-body">
                     <?php echo $value1['description'];?>

                  </div>
                  <div class="panel-footer">
                     <?php if( $fsc->unstables ){ ?>

                        <?php if( $value1['enabled'] ){ ?>

                        <a class="btn btn-sm btn-danger" type="button" value="Desactivar" title="Desactivar" href="<?php echo $fsc->url();?>&unstable=TRUE&disable=<?php echo $value1['name'];?>">
                            <span class="glyphicon glyphicon-remove"></span>
                            &nbsp; Desactivar
                        </a>
                        <?php }else{ ?>

                        <a class="btn btn-sm btn-success" type="button" value="Activar" title="Activar" href="<?php echo $fsc->url();?>&unstable=TRUE&enable=<?php echo $value1['name'];?>">
                            <span class="glyphicon glyphicon-ok"></span>
                            &nbsp; Activar
                        </a>
                        <?php } ?>

                     <?php }else{ ?>

                        <?php if( $value1['enabled'] ){ ?>

                        <a class="btn btn-sm btn-danger" type="button" value="Desactivar" title="Desactivar" href="<?php echo $fsc->url();?>&disable=<?php echo $value1['name'];?>">
                            <span class="glyphicon glyphicon-remove"></span>
                            &nbsp; Desactivar
                        </a>
                        <?php }else{ ?>

                        <a class="btn btn-sm btn-success" type="button" value="Activar" title="Activar" href="<?php echo $fsc->url();?>&enable=<?php echo $value1['name'];?>">
                            <span class="glyphicon glyphicon-ok"></span>
                            &nbsp; Activar
                        </a>
                        <?php } ?>

                     <?php } ?>

                  </div>
               </div>
            </div>
            <?php } ?>

         </div>
      </div>
   </div>
</div>
<?php } ?>


<?php $tpl = new RainTPL;$tpl_dir_temp = self::$tpl_dir;$tpl->assign( $this->var );$tpl->draw( dirname("footer") . ( substr("footer",-1,1) != "/" ? "/" : "" ) . basename("footer") );?>

