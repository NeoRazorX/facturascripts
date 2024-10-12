<?php declare(strict_types=1);

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Kernel;
use FacturaScripts\Core\Plugins;
use mysqli;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class About extends Controller
{
    /** @var array */
    public $data = [];

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'about';
        $data['icon'] = 'fa-solid fa-circle-info';
        return $data;
    }

    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);

        $this->data = $this->getData();
    }

    private function getData()
    {
        // Obtener la versión de FacturaScripts
        $facturascripts_version = Kernel::version();

        // Obtener la versión de PHP
        $php_version = phpversion();

        // Obtener las extensiones de PHP instaladas
        $extensions = get_loaded_extensions();

        // Obtener el tamaño maximo de subida de archivo
        $max_filesize = UploadedFile::getMaxFilesize();

        // Información del servidor web
        $server_software = $_SERVER['SERVER_SOFTWARE'];

        // Información del sistema operativo
        $os_info = php_uname();

        // Obtener la versión de la Base de Datos
        switch (strtolower(FS_DB_TYPE)) {
            case 'postgresql':
                $string = 'host=' . \FS_DB_HOST . ' dbname=' . \FS_DB_NAME . ' port=' . \FS_DB_PORT
                    . ' user=' . \FS_DB_USER . ' password=' . \FS_DB_PASS;
                $pg_conn = pg_connect($string);
                $database_version = pg_version($pg_conn)['client'] . '-' . 'PostgreSQL';
                break;

            default:
                $mysqli = new mysqli(FS_DB_HOST, FS_DB_USER, FS_DB_PASS, FS_DB_NAME);
                $database_version = $mysqli->server_info;
                break;
        }

        $plugins = Plugins::list();

        return compact(
            'facturascripts_version',
            'php_version',
            'extensions',
            'server_software',
            'os_info',
            'database_version',
            'max_filesize',
            'plugins'
        );
    }
}
