<?php if(!class_exists('raintpl')){exit;}?><!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="es" xml:lang="es" >
<head>
   <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
   <title><?php echo $fsc->page->title;?> &lsaquo; <?php echo $fsc->empresa->nombre;?></title>
   <meta name="description" content="FacturaScripts es un software de facturación y contabilidad para pymes. Es software libre bajo licencia GNU/AGPL." />
   <meta name="viewport" content="width=device-width, initial-scale=1.0" />
   <link rel="shortcut icon" href="view/img/favicon.ico" />
   <link rel="stylesheet" href="view/css/bootstrap-yeti.min.css" />
   <link rel="stylesheet" href="view/css/datepicker.css" />
   <link rel="stylesheet" href="view/css/custom.css" />
   <script type="text/javascript" src="view/js/jquery-2.1.1.min.js"></script>
   <script type="text/javascript" src="view/js/bootstrap.min.js"></script>
   <script type="text/javascript" src="view/js/bootstrap-datepicker.js" charset="UTF-8"></script>
   <script type="text/javascript" src="view/js/jquery.autocomplete.min.js"></script>
   <script type="text/javascript" src="view/js/base.js"></script>
   <script type="text/javascript">
      function show_precio(precio)
      {
         <?php if( FS_POS_DIVISA=='right' ){ ?>

         return number_format(precio, <?php  echo FS_NF0;?>, '<?php  echo FS_NF1;?>', '<?php  echo FS_NF2;?>')+' <?php echo $fsc->simbolo_divisa();?>';
         <?php }else{ ?>

         return '<?php echo $fsc->simbolo_divisa();?>'+number_format(precio, <?php  echo FS_NF0;?>, '<?php  echo FS_NF1;?>', '<?php  echo FS_NF2;?>');
         <?php } ?>

      }
      function show_numero(numero)
      {
         return number_format(numero, <?php  echo FS_NF0;?>, '<?php  echo FS_NF1;?>', '<?php  echo FS_NF2;?>');
      }
   </script>
   <?php $loop_var1=$fsc->head_extensions; $counter1=-1; if($loop_var1) foreach( $loop_var1 as $key1 => $value1 ){ $counter1++; ?><?php echo $value1->text;?><?php } ?>

</head>
<body>
   <nav class="navbar navbar-default" role="navigation" style="margin: 0px 0px 5px 0px;">
      <div class="container-fluid">
         <div class="navbar-header">
            <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
               <span class="sr-only">Toggle navigation</span>
               <span class="icon-bar"></span>
               <span class="icon-bar"></span>
               <span class="icon-bar"></span>
            </button>
            <?php if( FS_DEMO ){ ?>

            <a class="navbar-brand" href="index.php">DEMO</a>
            <?php }else{ ?>

            <a class="navbar-brand" href="index.php"><?php if( $fsc->empresa->nombrecorto ){ ?><?php echo $fsc->empresa->nombrecorto;?><?php }else{ ?><?php echo $fsc->empresa->nombre;?><?php } ?></a>
            <?php } ?>

         </div>
         
         <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
            <ul class="nav navbar-nav">
               <?php $loop_var1=$fsc->folders(); $counter1=-1; if($loop_var1) foreach( $loop_var1 as $key1 => $value1 ){ $counter1++; ?>

               <li class="dropdown<?php if( $value1==$fsc->page->folder ){ ?> active<?php } ?>">
                  <a href="#" class="dropdown-toggle" data-toggle="dropdown" style="text-transform: capitalize;"><?php echo $value1;?></a>
                  <ul class="dropdown-menu">
                     <?php $loop_var2=$fsc->pages($value1); $counter2=-1; if($loop_var2) foreach( $loop_var2 as $key2 => $value2 ){ $counter2++; ?>

                     <li<?php if( $value2->showing() ){ ?> class="active"<?php } ?>><a href="<?php echo $value2->url();?>"><?php echo $value2->title;?></a></li>
                     <?php } ?>

                  </ul>
               </li>
               <?php } ?>

            </ul>
            
            <ul class="nav navbar-nav navbar-right">
               <?php if( $fsc->get_last_changes() ){ ?>

               <li class="dropdown">
                  <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                     <span class="glyphicon glyphicon-time hidden-xs"></span>
                     <span class="visible-xs">Historial</span>
                  </a>
                  <ul class="dropdown-menu">
                     <?php $loop_var1=$fsc->get_last_changes(); $counter1=-1; if($loop_var1) foreach( $loop_var1 as $key1 => $value1 ){ $counter1++; ?>

                        <li title="<?php if( $value1['nuevo'] ){ ?>creado<?php }else{ ?>modificado<?php } ?> el <?php echo $value1['cambio'];?>">
                           <a href="<?php echo $value1['url'];?>">
                              <?php if( $value1['nuevo'] ){ ?>

                              <span class="glyphicon glyphicon-file"></span> &nbsp;
                              <?php }else{ ?>

                              <span class="glyphicon glyphicon-edit"></span> &nbsp;
                              <?php } ?>

                              <?php echo $value1['texto'];?>

                           </a>
                        </li>
                     <?php } ?>

                  </ul>
               </li>
               <?php } ?>

               
               <li>
                  <a href="#" class="dropdown-toggle" data-toggle="dropdown" title="Ayuda">
                     <span class="glyphicon glyphicon-question-sign hidden-xs"></span>
                     <span class="visible-xs">Ayuda</span>
                  </a>
                  <ul class="dropdown-menu">
                     <li><a href="<?php  echo FS_COMMUNITY_URL;?>/questions.php" target="_blank">Preguntas</a></li>
                     <li><a href="<?php  echo FS_COMMUNITY_URL;?>/errors.php" target="_blank">Errores</a></li>
                     <li><a href="<?php  echo FS_COMMUNITY_URL;?>/ideas.php" target="_blank">Sugerencias</a></li>
                     <li><a href="<?php  echo FS_COMMUNITY_URL;?>/all.php" target="_blank">Todo</a></li>
                     <li class="divider"></li>
                     <li><a href="#" id="b_feedback">Informar...</a></li>
                  </ul>
               </li>
               
               <li class="dropdown">
                  <a href="#" class="dropdown-toggle" data-toggle="dropdown" title="<?php echo $fsc->user->nick;?>">
                     <span class="glyphicon glyphicon-user hidden-xs"></span>
                     <span class="visible-xs">Usuario</span>
                  </a>
                  <ul class="dropdown-menu">
                     <li><a href="<?php echo $fsc->user->url();?>">Usuario: <?php echo $fsc->user->nick;?></a></li>
                     <?php if( $fsc->user->codagente ){ ?>

                     <li><a href="<?php echo $fsc->user->get_agente_url();?>">Agente: <?php echo $fsc->user->codagente;?></a></li>
                     <?php } ?>

                     <?php if( count($fsc->user->all())>1 AND !FS_DEMO ){ ?>

                        <li class="divider"></li>
                        <?php $loop_var1=$fsc->user->all(); $counter1=-1; if($loop_var1) foreach( $loop_var1 as $key1 => $value1 ){ $counter1++; ?>

                           <?php if( $value1->nick != $fsc->user->nick ){ ?>

                           <li><a href="index.php?logout=TRUE&amp;nlogin=<?php echo $value1->nick;?>"><?php echo $value1->nick;?></a></li>
                           <?php } ?>

                        <?php } ?>

                     <?php } ?>

                     <li class="divider"></li>
                     <li>
                        <a href="<?php echo $fsc->url();?>&logout=TRUE">
                           <span class="glyphicon glyphicon-log-out"></span> &nbsp;
                           Cerrar sesión
                        </a>
                     </li>
                  </ul>
               </li>
            </ul>
         </div>
      </div>
   </nav>
   
   <?php if( $fsc->get_errors() ){ ?>

   <div class="alert alert-danger hidden-print">
      <ul><?php $loop_var1=$fsc->get_errors(); $counter1=-1; if($loop_var1) foreach( $loop_var1 as $key1 => $value1 ){ $counter1++; ?><li><?php echo $value1;?></li><?php } ?></ul>
   </div>
   <?php } ?>

   <?php if( $fsc->get_messages() ){ ?>

   <div class="alert alert-success hidden-print">
      <ul><?php $loop_var1=$fsc->get_messages(); $counter1=-1; if($loop_var1) foreach( $loop_var1 as $key1 => $value1 ){ $counter1++; ?><li><?php echo $value1;?></li><?php } ?></ul>
   </div>
   <?php } ?>

   <?php if( $fsc->get_advices() ){ ?>

   <div class="alert alert-info hidden-print">
      <ul><?php $loop_var1=$fsc->get_advices(); $counter1=-1; if($loop_var1) foreach( $loop_var1 as $key1 => $value1 ){ $counter1++; ?><li><?php echo $value1;?></li><?php } ?></ul>
   </div>
   <?php } ?>

   
   <?php if( $fsc->show_fs_toolbar ){ ?>

   <div class="container-fluid" style="margin: 10px 0px 10px 0px;">
      <div class="row">
         <?php if( $fsc->custom_search ){ ?>

         <div class="col-lg-10 col-md-10 col-sm-10 col-xs-10">
         <?php }else{ ?>

         <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
         <?php } ?>

            <div class="btn-toolbar" role="toolbar">
               <div class="btn-group hidden-xs">
                  <a class="btn btn-sm btn-default" href="<?php echo $fsc->url();?>" title="recargar la página">
                     <span class="glyphicon glyphicon-refresh"></span>
                  </a>
                  <?php if( $fsc->page->show_on_menu ){ ?>

                     <?php if( $fsc->page->is_default() ){ ?>

                     <a class="btn btn-sm btn-default active" href="<?php echo $fsc->url();?>&amp;default_page=FALSE" title="desmarcar como página de inicio">
                        <span class="glyphicon glyphicon-home"></span>
                     </a>
                     <?php }else{ ?>

                     <a class="btn btn-sm btn-default" href="<?php echo $fsc->url();?>&amp;default_page=TRUE" title="marcar como página de inicio">
                        <span class="glyphicon glyphicon-home"></span>
                     </a>
                     <?php } ?>

                  <?php } ?>

               </div>
               
               <div class="btn-group hidden-xs">
                  <?php if( $fsc->ppage ){ ?>

                  <a class="btn btn-sm btn-default" href="<?php echo $fsc->ppage->url();?>">
                     <span class="glyphicon glyphicon-arrow-left"></span>
                     <?php echo $fsc->ppage->title;?>

                  </a>
                  <?php } ?>

                  <a class="btn btn-sm btn-default active" href="<?php echo $fsc->url();?>" title="recargar la página">
                     <?php echo $fsc->page->title;?>

                  </a>
               </div>
               
               <div class="btn-group">
                  <?php $loop_var1=$fsc->buttons; $counter1=-1; if($loop_var1) foreach( $loop_var1 as $key1 => $value1 ){ $counter1++; ?>

                     <?php if( stripos($value1->value, 'nuev')!==FALSE ){ ?>

                     <a id="<?php echo $value1->id;?>" class="btn btn-sm btn-success" href="<?php echo $value1->href;?>">
                        <?php echo $value1->value;?>

                     </a>
                     <?php }elseif( get_class($value1)=='fs_button_img' ){ ?>

                     <a id="<?php echo $value1->id;?>" class="btn btn-sm<?php if( $value1->remove ){ ?> btn-danger<?php }else{ ?> btn-default<?php } ?>" href="<?php echo $value1->href;?>">
                        <?php echo $value1->value;?>

                     </a>
                     <?php }else{ ?>

                     <a id="<?php echo $value1->id;?>" class="btn btn-sm btn-default" href="<?php echo $value1->href;?>">
                        <?php echo $value1->value;?>

                     </a>
                     <?php } ?>

                  <?php } ?>

               </div>
            </div>
         </div>
         <?php if( $fsc->custom_search ){ ?>

         <div class="col-lg-2 col-md-2 col-sm-2">
            <form name="f_custom_search" action="<?php echo $fsc->url();?>" method="post" class="form">
               <div class="input-group">
                  <input class="form-control" type="text" name="query" value="<?php echo $fsc->query;?>" autocomplete="off" placeholder="Buscar">
                  <span class="input-group-btn hidden-xs hidden-sm">
                     <button class="btn btn-primary" type="submit">
                        <span class="glyphicon glyphicon-search"></span>
                     </button>
                  </span>
               </div>
            </form>
         </div>
         <?php } ?>

      </div>
   </div>
   <?php } ?>

   
   <?php $tpl = new RainTPL;$tpl_dir_temp = self::$tpl_dir;$tpl->assign( $this->var );$tpl->draw( dirname("feedback") . ( substr("feedback",-1,1) != "/" ? "/" : "" ) . basename("feedback") );?>

