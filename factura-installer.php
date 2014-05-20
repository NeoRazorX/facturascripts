<?php
	function execute_config()
	{
		
		/// Filtrar data
		
		$database = $_POST['database'];
		$type = $_POST['type'];
		$port = $_POST['port'];
		$user = $_POST['user'];
		$password = $_POST['password'];

		echo "Proceso de instalacion de FacturaScript.<br>";
		echo "Configurando base de datos.<br>";

		if($type == "MYSQL")
		{
			$link = mysqli_connect('localhost', $user, $password) or die("Imposible conectarse con el servidor<br>");
			$query = "CREATE DATABASE IF NOT EXISTS $database";
			
			if (mysqli_query($query)) 
			{
				echo "Base de datos <b>$database</b> creada correctamente<br>";
			} 
			else 
			{
				echo "Error al crear la base de datos: <br /><br />". mysqli_error();
				exit;
			}
		}
		if($type == "POSTGRESQL")
		{
			$link = pg_connect("host=localhost port=$port user=$user password=$password");
			$query = "CREATE DATABASE $database";
			
			/// Verificar si la base de datos existe y ejecutar la consulta
		}
		
		echo "Inicializando proceso de descarga de FacturaScript.<br>";

		/// Descarga del archivo del servidor y descompresion

		$fileUrl = "https://github.com/NeoRazorX/facturascripts/archive/master.zip";
		$compressed = "facturascript.zip";
		 	
	 	echo "Descargando FacturaScript...<br>";

		$fileContent = file_get_contents($fileUrl);
		$result = file_put_contents($compressed, $fileContent);
		if($result === FALSE)
		{
			echo "Error al guardar descarga. Comprueba que el directorio raiz tenga permisos de escritura.";
			exit;
		}
		
		echo "Descarga finalizada.<br>";
		echo "Descompresion en curso...<br>";

		/// Descomprimir en una carpeta con el siguiente formato: nombre-base-datos/new
		
		$zip = new ZipArchive;
		if ($zip->open($compressed) === TRUE) {
		   $zip->extractTo("$database");
		   $zip->close();
		   
		   /// Al descomprimir se genera una carpeta facturascripts-maste. La renombramos a new
		   
		   rename("$database/facturascripts-master", "$database/new");
		   echo "El archivo se ha descomprimido correctamente.<br>";
		}
		else {
		   echo "Se ha producido un error al descomprimir.<br>";
		}
		
		/// Eliminar archivo descomprimido
		
		unlink($compressed);

		echo "Procesando config.php.<br>";

		/// Copia del archivo config-sample.php a config configurado con los parametros de configuracion recogidas en el formulario. 
		
		/// No se elimina el config-sample
		
		$path_to_file = "$database/new/config-sample.php";
		$path_to_copy = "$database/new/config.php";
		$file_contents = file_get_contents($path_to_file);
		$file_contents = str_replace("define('FS_DB_TYPE', 'MYSQL')","define('FS_DB_TYPE', '$type');",$file_contents);
		$file_contents = str_replace("define('FS_DB_PORT', '3306')","define('FS_DB_PORT', '$port');",$file_contents);
		$file_contents = str_replace("define('FS_DB_NAME', '')","define('FS_DB_NAME', '$database');",$file_contents);
		$file_contents = str_replace("define('FS_DB_USER', 'root')","define('FS_DB_USER', '$user');",$file_contents);
		$file_contents = str_replace("define('FS_DB_PASS', '')","define('FS_DB_PASS', '$password');",$file_contents);
		file_put_contents($path_to_copy, $file_contents);

		echo "Instalacion completada. Pulse <a href='factura-installer2.php'>aqui</a> para volver al inicio.";
	}
	function get_instances()
	{
		$folder = opendir(".");
		$links = array();
		while ($file = readdir($folder))
		{
		    if (is_dir($file) && $file != "." && $file != "..")
		    {
		        $links[] = $file;
		    } 
		}
		return $links;
	}
	function validate_version($dir)
	{
		/// Version actual del proyecto
		
		$fileURL ="https://raw.githubusercontent.com/NeoRazorX/facturascripts/master/VERSION";
	    $remote = file_get_contents($fileURL);
	    $latestVersion = explode(".", $remote);

	    /// Version actual de la instancia instalada
	    
	    $local = file_get_contents("$dir/new/VERSION");
	    $currentVersion = explode(".", $local);



	    if((int)$latestVersion[0] > (int)$currentVersion[0])
	    {
	    	/// Existe una actualizacion
	    	return TRUE;
	    }
	    else
	    {
	    	if((int)$latestVersion[1] > (int)$currentVersion[1])
	    	{
	    		/// Existe una actualizacion
	    		return TRUE;
	    	}
	    }
	    return FALSE;
	}
?>
<html>
	<head>
		<title>Instalacion FacturaScript</title>
	</head>
	<body>
		<div>
			<?php
				if(isset($_POST['action']) && $_POST['action'] == "install")
				{

					execute_config();
					
				}
				else
				{
					/// Listar los directorios de las empresas generadas
					
					$companies = get_instances();
					$max = count($companies);
					for($x = 0; $x < $max; $x ++)
					{

					    echo "<a href='$companies[$x]/new'>$companies[$x]</a>";
					    if(validate_version($companies[$x])) 
					    {
					    	echo " - Hay una actualizacion de FacturaScript disponible!";
					    }
					    echo "<br />";
					}
			?>
					<br>
					<form action="factura-installer.php" method="POST">
						<input type="text" name="database" placeholder="Introduce el nombre de la base de datos" />
						<select name="type">
							<option value="MYSQL">MySQL</option>
							<option value="POSTGRESQL">Postgresql</option>
						</select>
						<input type="text" name="port" placeholder="Puerto" />
						<input type="text" name="user" placeholder="Usuario de la  BDD" />
						<input type="text" name="password" placeholder="Password" />
						<input type="hidden" name="action" value="install" />
						<input type="submit" value="Crear" />
					</form>
				<?php } ?>
		</div>
	</body>
</html>
