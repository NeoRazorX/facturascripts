<?php

namespace Lib\ExtendedController;

use FacturaScripts\Core\App\AppController;
use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\AttachedFile;
use FacturaScripts\Core\Model\RoleUser;
use FacturaScripts\Core\Model\Stock;
use FacturaScripts\Core\Model\User;
use FacturaScripts\Core\Model\Variante;
use FacturaScripts\Dinamic\Model\AttachedFileRelation;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class DocFilesTraitTest extends TestCase
{
    use LogErrorsTrait;
    use RandomDataTrait;
    use DefaultSettingsTrait;

    private static string $seed = '';

    protected function setUp(): void
    {
        $db = new DataBase();
        $db->connect();

        new User();
        new RoleUser();
        new Variante();
        new Stock();

        self::setDefaultSettings();
    }

    public function businessDocumentProvider(): array
    {
        return [
            ['AlbaranCliente'],
            ['AlbaranProveedor'],
            ['FacturaCliente'],
            ['FacturaProveedor'],
            ['PedidoCliente'],
            ['PedidoProveedor'],
            ['PresupuestoCliente'],
            ['PresupuestoProveedor'],
        ];
    }

    /**
     * @dataProvider businessDocumentProvider
     * @param string $modelName
     */
    public function testUpdateNumberAttachedDocuments(string $modelName)
    {
        if (str_contains($modelName, 'Cliente')) {
            // creamos un cliente
            $subject = $this->getRandomCustomer();
            static::assertTrue($subject->save());
        } else {
            // creamos un proveedor
            $subject = $this->getRandomSupplier();
            static::assertTrue($subject->save());
        }

        // creamos un documento y le asignamos el subject
        $docClass = '\\FacturaScripts\\Core\\Model\\' . $modelName;
        $doc = new $docClass();

        $doc->setSubject($subject);
        static::assertTrue($doc->save());

        // Comprobamos que se inicia a 0
        self::assertEquals(0, $doc->numdocs);

        // Añadimos el primer adjunto
        $get = ['code' => $doc->primaryColumnValue()];
        $post = ['action' => 'add-file'];
        $files = ['new-file' => $this->createFile('attached_file1.txt')];
        $this->controllerGet('/Edit' . $modelName, $get, $post, $files);

        $doc->loadFromCode($doc->primaryColumnValue());
        self::assertEquals(1, $doc->numdocs);

        // Añadimos el segundo adjunto
        $files = ['new-file' => $this->createFile('attached_file2.txt')];
        $this->controllerGet('/Edit' . $modelName, [], [], $files);

        $doc->loadFromCode($doc->primaryColumnValue());
        self::assertEquals(2, $doc->numdocs);

        // Añadimos el tercer adjunto
        $files = ['new-file' => $this->createFile('attached_file3.txt')];
        $this->controllerGet('/Edit' . $modelName, [], [], $files);

        $doc->loadFromCode($doc->primaryColumnValue());
        self::assertEquals(3, $doc->numdocs);

        // Obtenemos los adjuntos para usar el 'id'
        $attachedFileRelation = new AttachedFileRelation();
        $where = [
            new DataBaseWhere('model', $doc->modelClassName()),
            new DataBaseWhere('modelid', $doc->primaryColumnValue())
        ];
        $adjuntos = $attachedFileRelation->all($where);

        // Borramos el segundo adjunto
        $segundoAdjunto = $adjuntos[1];
        $post = [
            'action' => 'delete-file',
            'id' => $segundoAdjunto->id,
        ];
        $this->controllerGet('/Edit' . $modelName, [], $post, []);

        $doc->loadFromCode($doc->primaryColumnValue());
        self::assertEquals(2, $doc->numdocs);

        // Desvinculamos el tercer adjunto
        $tercerAdjunto = $adjuntos[2];
        $post = [
            'action' => 'unlink-file',
            'id' => $tercerAdjunto->id,
        ];
        $this->controllerGet('/Edit' . $modelName, [], $post, []);

        $doc->loadFromCode($doc->primaryColumnValue());
        self::assertEquals(1, $doc->numdocs);


        // eliminamos
        static::assertTrue($doc->delete());
        static::assertTrue($subject->getDefaultAddress()->delete());
        static::assertTrue($subject->delete());

        // Eliminamos las relaciones de los adjuntos en los documentos
        $attachedFileRelation = new AttachedFileRelation();
        $where = [
            new DataBaseWhere('model', $doc->modelClassName()),
            new DataBaseWhere('modelid', $doc->primaryColumnValue())
        ];
        $adjuntosRelacionados = $attachedFileRelation->all($where);
        foreach ($adjuntosRelacionados as $adjuntoRelacionados)
        {
            $adjuntoRelacionados->delete();
        }

        // Eliminamos los archivos creados
        $attachedFile = new AttachedFile();
        $adjuntos = $attachedFile->all();
        foreach ($adjuntos as $adjunto)
        {
            $adjunto->delete();
        }
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }

    /**
     * @param string $nick
     * @return string
     */
    protected function getTokenTest(string $nick)
    {
        self::$seed .= $nick;

        $pathMultiRequestProtectionFile = FS_FOLDER . DIRECTORY_SEPARATOR . 'Core' . DIRECTORY_SEPARATOR . 'Lib' . DIRECTORY_SEPARATOR . 'MultiRequestProtection.php';
        $frase = PHP_VERSION . $pathMultiRequestProtectionFile . FS_DB_NAME . FS_DB_PASS . self::$seed;

        $num = intval(date('YmdH')) + strlen($frase);
        $value = $frase . $num;

        return sha1($value) . '|test';
    }

    /**
     * @param string $uri
     * @param array $get
     * @param array $post
     * @param array $files
     */
    protected function controllerGet(string $uri, array $get, array $post, array $files): void
    {
        $fsNick = 'admin';
        $fsPassword = 'admin';

        foreach ($get as $key => $value) {
            $_GET[$key] = $value;
        }

        foreach ($post as $key => $value) {
            $_POST[$key] = $value;
        }

        foreach ($files as $key => $value) {
            $_FILES[$key] = $value;
        }

        $_POST["fsNick"] = $fsNick;
        $_POST["fsPassword"] = $fsPassword;
        $_POST["multireqtoken"] = $this->getTokenTest($fsNick);

        $app = new AppController($uri);
        $app->run();
    }

    /**
     * @param string $nombre
     * @return UploadedFile
     */
    protected function createFile(string $nombre)
    {
        $pathAbsoluto = FS_FOLDER . '/Test/__files/' . $nombre;

        file_put_contents($pathAbsoluto, 'test');
        return new UploadedFile(
            FS_FOLDER . '/Test/__files/' . $nombre,
            $nombre,
            'image/jpeg', 0, true);
    }
}
