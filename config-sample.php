<?php
/*
 * Copia o renombra este archivo a config.php, y rellena los campos
 * para el correcto funcionamiendo de facturascripts.
 * Si tienes alguna duda consulta -> http://www.kelinux.net/community/facturascripts
 */

/*
 * Configuración de la base de datos.
 * host: la ip del ordenador donde está la base de datos.
 * port: el puerto de la base de datos.
 * name: el nombre de la base de datos.
 * user: el usuario para conectar a la base de datos
 * pass: la contraseña del usuario.
 * history: TRUE si quieres ver todas las consultas que se hacen en cada página.
 */
define('FS_DB_HOST', 'localhost');
define('FS_DB_PORT', '5432');
define('FS_DB_NAME', '');
define('FS_DB_USER', '');
define('FS_DB_PASS', '');
define('FS_DB_HISTORY', FALSE);

/*
 * Configuración de memcache.
 * Host: la ip del servidor donde está memcached.
 * port: el puerto en el que se ejecuta memcached.
 */
define('FS_CACHE_HOST', 'localhost');
define('FS_CACHE_PORT', 11211);

/// caducidad (en segundos) de todas las coockies
define('FS_COOKIES_EXPIRE', 172800);

/// el número de elementos a mostrar en pantalla
define('FS_ITEM_LIMIT', 50);

/*
 * Un número identificador para esta instancia de FacturaScripts.
 * Necesario para identificar cada caja en el TPV_yamyam.
 */
define('FS_ID', 1);

?>
