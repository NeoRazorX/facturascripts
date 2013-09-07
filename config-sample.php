<?php
/*
 * Copia o renombra este archivo a config.php, y rellena los campos
 * para el correcto funcionamiendo de facturascripts.
 * Si tienes alguna duda consulta -> http://code.google.com/p/facturascripts/issues/list
 */

/*
 * Configuración de la base de datos.
 * type: postgresql o mysql (mysql está en fase experimental).
 * host: la ip del ordenador donde está la base de datos.
 * port: el puerto de la base de datos.
 * name: el nombre de la base de datos.
 * user: el usuario para conectar a la base de datos
 * pass: la contraseña del usuario.
 * history: TRUE si quieres ver todas las consultas que se hacen en cada página.
 */
define('FS_DB_TYPE', 'POSTGRESQL'); /// si usas mysql, pon mysql !!!
define('FS_DB_HOST', 'localhost');
define('FS_DB_PORT', '5432'); /// si usar mysql, pon 3306 !!!
define('FS_DB_NAME', '');
define('FS_DB_USER', ''); /// si usas mysql pon root !!!
define('FS_DB_PASS', '');

/*
 * En cada ejecución muestra todas las sentencias SQL utilizadas.
 */
define('FS_DB_HISTORY', FALSE);

/*
 * Habilita el modo demo, para pruebas.
 * Este modo permite hacer login con cualquier usuario y la contraseña demo,
 * además deshabilita el límite de una conexión por usuario.
 */
define('FS_DEMO', FALSE);

/*
 * Configuración de memcache.
 * Host: la ip del servidor donde está memcached.
 * port: el puerto en el que se ejecuta memcached.
 * prefix: prefijo para las claves, por si tienes varias instancias de
 * FacturaScripts conectadas al mismo servidor memcache.
 */
define('FS_CACHE_HOST', 'localhost');
define('FS_CACHE_PORT', 11211);
define('FS_CACHE_PREFIX', '');

/// caducidad (en segundos) de todas las coockies
define('FS_COOKIES_EXPIRE', 315360000);

/// el número de elementos a mostrar en pantalla
define('FS_ITEM_LIMIT', 50);

/*
 * Un número identificador para esta instancia de FacturaScripts.
 * Necesario para identificar cada caja en el TPV.
 */
define('FS_ID', 1);

/*
 * Nombre o dirección de la impresora de tickets.
 * Sólo disponible en Linux.
 * 
 * Ejemplo: 'Generic-text-only' ó '/dev/usb/lp0'
 * 
 * Nota: si escribes directamente sobre el puerto de la impresora,
 * asegurate de añadir apache al grupo de impresoras:
 * $ sudo usermod -a -G lp www-data
 */
define('FS_PRINTER', '');

/*
 * Nombre o dirección de la impresora que representa el dispositivo
 * LCD del terminal POS.
 * Sólo disponible en Linux. Además el LCD tiene que ser
 * de dos líneas de 20 caracteres cada una (GLANCETRON 8035).
 * Si tienes alguna duda, escríbela
 * aquí -> http://code.google.com/p/facturascripts/issues/list
 * 
 * Ejemplo: 'Generic-text-only' ó '/dev/ttyUSB0'
 */
define('FS_LCD', '');

?>