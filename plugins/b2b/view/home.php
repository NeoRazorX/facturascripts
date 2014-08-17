<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="es" xml:lang="es" >
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>FacturaScripts B2B</title>
	<meta name="description" content="FacturaScripts es un software de facturación y contabilidad para pymes. Es software libre bajo licencia GNU/AGPL." />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<link rel="shortcut icon" href="http://localhost/shawe/facturascripts/view/img/favicon.ico" />
	<link rel="stylesheet" href="http://localhost/shawe/facturascripts/view/css/bootstrap-yeti.min.css" />
	<link rel="stylesheet" href="css/dashboard.css" />
	<script type="text/javascript" src="http://localhost/shawe/facturascripts/view/js/jquery-2.1.1.min.js"></script>
	<script type="text/javascript" src="http://localhost/shawe/facturascripts/view/js/bootstrap.min.js"></script>
</head>
<body style="background-color: #E9EAED;">
	<nav class="navbar navbar-inverse navbar-fixed-top" role="navigation" style="margin: 0px;">
		<div class="container-fluid">
			<div class="navbar-header">
				<button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
					<span class="sr-only">Toggle navigation</span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				</button>
				<a class="navbar-brand" href="index.php">FacturaScripts B2B</a>
			</div>
			
			<div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
				<ul class="nav navbar-nav">
					<li class="dropdown">
						<a href="#" class="dropdown-toggle" data-toggle="dropdown" style="text-transform: capitalize;">Mis documentos</a>
						<ul class="dropdown-menu">
							<li><a href="http://localhost/shawe/facturascripts/index.php?page=ventas_presupuestos&codcliente=000001">Presupuestos</a></li>
							<li><a href="http://localhost/shawe/facturascripts/index.php?page=ventas_pedidos&codcliente=000001">Pedidos</a></li>
							<li><a href="http://localhost/shawe/facturascripts/index.php?page=ventas_albaranes&codcliente=000001">Albaranes</a></li>
							<li><a href="http://localhost/shawe/facturascripts/index.php?page=ventas_facturas&codcliente=000001">Facturas</a></li>
						</ul>
					</li>
				</ul>
				
				<ul class="nav navbar-nav navbar-right">
					<li>
						<a href="#Ayuda" title="Ayuda" data-toggle="modal" data-target="#modal_help">
							<span class="glyphicon glyphicon-question-sign"></span>
						</a>
					</li>
					<li class="dropdown">
						<a href="#" class="dropdown-toggle" data-toggle="dropdown" title="admin">
							<span class="glyphicon glyphicon-user hidden-xs"></span>
							<span class="visible-xs">Usuario</span>
						</a>
						<ul class="dropdown-menu">
							<li><a href="http://localhost/shawe/facturascripts/index.php?page=ventas_cliente&cod=000001">Usuario: admin</a></li>
							<li><a href="http://localhost/shawe/facturascripts/index.php?page=ventas_cliente&cod=000001">Agente: 1</a></li>
							<li class="divider"></li>
							<li><a href="http://localhost/shawe/facturascripts/index.php?logout=TRUE&amp;nlogin=Pepe">Pepe</a></li>
							<li class="divider"></li>
							<li>
								<a href="http://localhost/shawe/facturascripts/index.php?logout=TRUE&amp;nlogin=Pepe">
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
	
	<div class="container-fluid" style="margin: 10px 0px 10px 0px;">
		<div class="row">
			<div class="col-lg-10 col-sm-9">
				<div class="btn-toolbar" role="toolbar">
					<div class="btn-group hidden-xs">
						<a class="btn btn-sm btn-default active" href="index.php?page=venta_online" title="Ir a inicio">
							<span class="glyphicon glyphicon-home"></span>
						</a>
						<a class="btn btn-sm btn-default active" href="index.php?page=ventas_online" title="Recargar la página">
							Inicio
						</a>
					</div>
					<div class="btn-group">
						<a id="b_nuevo_presupuesto" class="btn btn-sm btn-success" href="index.php?page=ventas_online&tipo=presupuesto">
							Nuevo presupuesto
						</a>
						<a id="b_nuevo_pedido" class="btn btn-sm btn-default" href="index.php?page=ventas_online&tipo=pedido">
							Nuevo pedido
						</a>
					</div>
				</div>
			</div>
			<div class="col-lg-2 col-sm-3">
				<form name="f_custom_search" action="index.php?page=ventas_albaranes" method="post" class="form">
					<div class="input-group">
						<input class="form-control" type="text" name="query" value="" autocomplete="off" placeholder="Buscar">
						<span class="input-group-btn">
							<button class="btn btn-primary" type="submit">
								<span class="glyphicon glyphicon-search"></span>
							</button>
						</span>
					</div>
				</form>
			</div>
		</div>
	</div>
	
	<div class="container">
		Banners de ofertas por aquí
	</div>
	
	<div class="container">
		Algún gráfico de estadísticas por allá
	</div>
	
	<div class="container" align="center">
		<div class="footer">
			<p>&copy; 2014 Company, Inc. &middot; <a href="#">Privacy</a> &middot; <a href="#">Terms</a></p>
		</footer>
    </div>
	
</body>
</html>
