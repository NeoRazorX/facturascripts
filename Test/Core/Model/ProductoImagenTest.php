<?php

namespace Model;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Base\MyFilesToken;
use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Core\Model\AttachedFile;
use FacturaScripts\Core\Model\ProductoImagen;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ProductoImagenTest extends TestCase
{
    use RandomDataTrait;

    private $file_name;
    private $attached_file;
    private $wrong_attached_file;
    private $producto;
    private $destPath;

    protected function setUp(): void
    {
        parent::setUp();

        $db = new DataBase();
        if (false === $db->connected()) {
            $db->connect();
        }

        $this->attached_file = $this->getFakeAttachedFile('test.jpeg');

        $this->producto = $this->getRandomProduct();
        $this->producto->save();
    }

    public function testGetThumbnail()
    {
        $productoImagen = new ProductoImagen();

        // Como la imagen no existe, devuelve en string vacío
        $result = $productoImagen->getThumbnail();
        static::assertEquals('', $result);

        // Relacionamos un archivo y un producto
        $productoImagen->idfile = $this->attached_file->idfile;
        $productoImagen->idproducto = $this->producto->idproducto;
        $productoImagen->referencia = $this->producto->referencia;

        // Si no existe el directorio THUMBNAIL_PATH = '/MyFiles/Tmp/Thumbnails/', lo crea
        if (is_dir(FS_FOLDER . $productoImagen::THUMBNAIL_PATH)) {
            ToolBox::files()::delTree(FS_FOLDER . $productoImagen::THUMBNAIL_PATH);
        }

        static::assertDirectoryNotExists(FS_FOLDER . $productoImagen::THUMBNAIL_PATH);
        $productoImagen->getThumbnail();
        static::assertDirectoryExists(FS_FOLDER . $productoImagen::THUMBNAIL_PATH);

        // Devuelve la ruta del archivo JPEG. Creamos una thumbnail JPEG sin parametros
        $result = $productoImagen->getThumbnail();

        $expected_path = '/MyFiles/Tmp/Thumbnails/' . pathinfo($this->attached_file->filename, PATHINFO_FILENAME) . '_100x100.jpeg';
        static::assertEquals($expected_path, $result);
        static::assertFileExists(FS_FOLDER . $expected_path);

        unlink(FS_FOLDER . $expected_path);

        // Comprobamos las rutas con tokens
        $thumbnails_path = $expected_path;

        $result = $productoImagen->getThumbnail(100, 100, true, false);
        $expected_path = '/MyFiles/Tmp/Thumbnails/test_100x100.jpeg?myft=' . MyFilesToken::get($thumbnails_path, false);
        static::assertEquals($expected_path, $result);

        $result = $productoImagen->getThumbnail(100, 100, true, true);
        $expected_path = '/MyFiles/Tmp/Thumbnails/test_100x100.jpeg?myft=' . MyFilesToken::get($thumbnails_path, true);
        static::assertEquals($expected_path, $result);

        // Devuelve la ruta del archivo PNG
        $png_file = $this->getFakeAttachedFile('test.png');
        $productoImagen->idfile = $png_file->idfile;

        $result = $productoImagen->getThumbnail();

        $expected_path = '/MyFiles/Tmp/Thumbnails/' . pathinfo($this->attached_file->filename, PATHINFO_FILENAME) . '_100x100.png';

        static::assertEquals($expected_path, $result);

        $png_file->delete();

        // Devuelve la ruta del archivo GIF
        $png_file = $this->getFakeAttachedFile('test.gif');
        $productoImagen->idfile = $png_file->idfile;

        $result = $productoImagen->getThumbnail();

        $expected_path = '/MyFiles/Tmp/Thumbnails/' . pathinfo($this->attached_file->filename, PATHINFO_FILENAME) . '_100x100.gif';
        static::assertEquals($expected_path, $result);

        $png_file->delete();

        // Devuelve string vacío al pasarle un archivo con extensión no permitida
        $wrong_file = $this->getFakeAttachedFile('test.wrong_extension');
        $productoImagen->idfile = $wrong_file->idfile;

        $result = $productoImagen->getThumbnail();

        static::assertEquals('', $result);

        $wrong_file->delete();

        // Creamos una thumbnail pasando dimensiones
        $png_file = $this->getFakeAttachedFile('test.jpeg');
        $productoImagen->idfile = $png_file->idfile;

        $result = $productoImagen->getThumbnail(100, 50);

        $expected_path = '/MyFiles/Tmp/Thumbnails/' . pathinfo($this->attached_file->filename, PATHINFO_FILENAME) . '_100x50.jpeg';
        static::assertEquals($expected_path, $result);
        static::assertFileExists(FS_FOLDER . $expected_path);
        static::assertEquals(75, getimagesize(FS_FOLDER . $expected_path)[0]);
        static::assertEquals(50, getimagesize(FS_FOLDER . $expected_path)[1]);

        unlink(FS_FOLDER . $expected_path);

        // Devuelve string vacío y genera log al pasarle un archivo erroneo
        file_put_contents(FS_FOLDER . '/MyFiles/wrong_file.jpeg', 'wrong_content');

        $attached_file = new AttachedFile();
        $attached_file->path = 'wrong_file.jpeg';
        $attached_file->save();

        $productoImagen->idfile = $attached_file->idfile;

        $result = $productoImagen->getThumbnail();

        static::assertEquals('', $result);

        $logs = MiniLog::read();
        static::assertEquals('imagecreatefromstring(): Data is not in a recognized format', end($logs)['message']);
    }

    public function testDelete()
    {
        $attached_file = $this->getFakeAttachedFile('test.jpeg');

        $productoImagen = new ProductoImagen();
        $productoImagen->idfile = $attached_file->idfile;
        $productoImagen->idproducto = $this->producto->idproducto;
        $productoImagen->referencia = $this->producto->referencia;
        $productoImagen->save();

        $productoImagen->getThumbnail();
        $productoImagen->getThumbnail(200, 200);
        $productoImagen->getThumbnail(300, 500);

        // Comprobamos antes de borrarlo que existen los archivos y entradas en la BBDD
        $expected_path = FS_FOLDER . '/MyFiles/Tmp/Thumbnails/' . pathinfo($this->attached_file->filename, PATHINFO_FILENAME);
        static::assertFileExists($expected_path . '_100x100.jpeg');
        static::assertFileExists($expected_path . '_200x200.jpeg');
        static::assertFileExists($expected_path . '_300x500.jpeg');
        static::assertTrue((new ProductoImagen())->loadFromCode($productoImagen->id));

        // BORRAMOS
        $productoImagen->delete();

        // Comprobamos que, una vez borrado, no existen los archivos ni las entradas en la BBDD
        $expected_path = FS_FOLDER . '/MyFiles/Tmp/Thumbnails/' . pathinfo($this->attached_file->filename, PATHINFO_FILENAME);
        static::assertFileNotExists($expected_path . '_100x100.jpeg');
        static::assertFileNotExists($expected_path . '_200x200.jpeg');
        static::assertFileNotExists($expected_path . '_300x500.jpeg');
        static::assertFalse((new ProductoImagen())->loadFromCode($productoImagen->id));
    }

    public function testInstall()
    {
        $productoImagen = new ProductoImagen();

        $result = $productoImagen->install();

        static::assertEquals('', $result);
    }

    public function testGetProducto()
    {
        $productoImagen = new ProductoImagen();

        // Relacionamos un archivo y un producto
        $productoImagen->idfile = $this->attached_file->idfile;
        $productoImagen->idproducto = $this->producto->idproducto;
        $productoImagen->referencia = $this->producto->referencia;

        $result = $productoImagen->getProducto();

        static::assertEquals($this->producto->idproducto, $result->idproducto);
        static::assertEquals($this->producto->descripcion, $result->descripcion);
    }

    public function testGetFile()
    {
        $productoImagen = new ProductoImagen();
        $productoImagen->idfile = $this->attached_file->idfile;

        $result = $productoImagen->getFile();

        static::assertEquals($this->attached_file->idfile, $result->idfile);
        static::assertEquals($this->attached_file->path, $result->path);
    }

    public function testPrimaryColumn()
    {
        $productoImagen = new ProductoImagen();

        $result = $productoImagen::primaryColumn();

        static::assertEquals('id', $result);
    }

    public function testGetMaxFileUpload()
    {
        $productoImagen = new ProductoImagen();

        $result = $productoImagen->getMaxFileUpload();

        static::assertEquals((UploadedFile::getMaxFilesize() / 1024 / 1024), $result);
    }

    public function testTableName()
    {
        $productoImagen = new ProductoImagen();

        $result = $productoImagen::tableName();

        static::assertEquals('productos_imagenes', $result);
    }

    public function testUrl()
    {
        $productoImagen = new ProductoImagen();

        $result = $productoImagen->url();
        static::assertEquals('ListProductoImagen', $result);

        $result = $productoImagen->url('download');
        static::assertEquals('?myft=' . MyFilesToken::get('', false), $result);

        $result = $productoImagen->url('download-permanent');
        static::assertEquals('?myft=' . MyFilesToken::get('', true), $result);
    }

    /**
     * @param $file_name
     * @return AttachedFile
     */
    private function getFakeAttachedFile($file_name)
    {
        $tests_file_name = 'xss_img_src_onerror_alert(123).jpeg';
        $source_path = FS_FOLDER . '/Test/__files/' . $tests_file_name;
        $dest_path = FS_FOLDER . '/MyFiles/' . $file_name;
        copy($source_path, $dest_path);

        $attached_file = new AttachedFile();
        $attached_file->path = $file_name;
        $attached_file->save();

        return $attached_file;
    }
}
