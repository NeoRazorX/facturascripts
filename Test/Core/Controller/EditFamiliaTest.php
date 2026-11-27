<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2026 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Test\Core\Controller;

use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Controller\EditFamilia;
use FacturaScripts\Core\Request;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\AttachedFile;
use FacturaScripts\Dinamic\Model\Familia;
use FacturaScripts\Dinamic\Model\Producto;
use FacturaScripts\Dinamic\Model\User;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class EditFamiliaTest extends TestCase
{
    use LogErrorsTrait;
    use RandomDataTrait;

    public function testActionsWithoutSelectionWarn(): void
    {
        $family = new Familia();
        $family->codfamilia = 'TEF' . mt_rand(100, 999);
        $family->descripcion = 'Test EditFamilia';
        $this->assertTrue($family->save());
        $code = $family->codfamilia;

        try {
            foreach (['addProductAction' => 'items-added-correctly', 'removeProductAction' => 'items-removed-correctly'] as $method => $notice) {
                MiniLog::clear();
                $this->runAction($method, $code, [], true, true);

                // sin elementos seleccionados se avisa, en lugar de informar de 0 elementos
                $messages = array_column(MiniLog::read(), 'original');
                $this->assertContains('no-selected-item', $messages, $method);
                $this->assertNotContains($notice, $messages, $method);
            }
        } finally {
            $family->delete();
        }
    }

    public function testProductActionsRequireUpdatePermissionAndToken(): void
    {
        $family = new Familia();
        $family->codfamilia = 'TEF' . mt_rand(100, 999);
        $family->descripcion = 'Test EditFamilia';
        $this->assertTrue($family->save());

        $product = $this->getRandomProduct();
        $this->assertTrue($product->save());

        try {
            // sin permiso de actualización no se asigna el producto a la familia
            $this->runAction('addProductAction', $family->codfamilia, [$product->idproducto], false, true);
            $this->assertNull($this->reload($product)->codfamilia);

            // con permiso, pero sin token válido, tampoco
            $this->runAction('addProductAction', $family->codfamilia, [$product->idproducto], true, false);
            $this->assertNull($this->reload($product)->codfamilia);

            // con permiso y token se asigna
            $this->runAction('addProductAction', $family->codfamilia, [$product->idproducto], true, true);
            $this->assertSame($family->codfamilia, $this->reload($product)->codfamilia);

            // sin permiso de actualización no se desasigna
            $this->runAction('removeProductAction', $family->codfamilia, [$product->idproducto], false, true);
            $this->assertSame($family->codfamilia, $this->reload($product)->codfamilia);

            // con permiso y token se desasigna
            $this->runAction('removeProductAction', $family->codfamilia, [$product->idproducto], true, true);
            $this->assertNull($this->reload($product)->codfamilia);
        } finally {
            $product->delete();
            $family->delete();
        }
    }

    public function testLibraryWidgetRequiresUpdatePermission(): void
    {
        // adjunto que ya existe en la biblioteca
        $name = 'edit_familia_' . Tools::randomString(6) . '.jpg';
        $this->assertTrue(copy(FS_FOLDER . '/Test/__files/product_image.jpg', FS_FOLDER . '/MyFiles/' . $name));
        $file = new AttachedFile();
        $file->path = $name;
        $this->assertTrue($file->save());

        $family = new Familia();
        $family->codfamilia = 'TEF' . mt_rand(100, 999);
        $family->descripcion = 'Test EditFamilia';
        $this->assertTrue($family->save());

        $request = ['active_tab' => 'EditFamilia', 'col_name' => 'idattachedfile', 'widget_id' => 'w1'];
        $total = AttachedFile::count();

        try {
            // sin permiso de actualización no se listan los archivos de la biblioteca
            $controller = $this->libraryController($family->codfamilia, $request, false, true);
            $result = $controller->runLibrary('widgetLibrarySearchAction');
            $this->assertSame(0, $result['records']);
            $this->assertSame('', $result['html']);

            // ni la página incluye el listado: el selector queda en solo lectura
            $widget = $controller->tab('EditFamilia')->columnForField('idattachedfile')->widget;
            $this->assertSame('true', $widget->readonly);
            $html = $widget->edit($controller->tab('EditFamilia')->model);
            $this->assertStringNotContainsString('myft=', $html);
            $this->assertStringNotContainsString($file->filename, $html);

            // ni se pueden subir archivos
            $upload = $this->uploadField('edit-familia-' . uniqid() . '.txt');
            $controller = $this->libraryController($family->codfamilia, $request, false, true, $upload);
            $this->assertSame([], $controller->runLibrary('widgetLibraryUploadAction'));
            $this->assertSame($total, AttachedFile::count());

            // con permiso, pero sin token válido, tampoco
            $controller = $this->libraryController($family->codfamilia, $request, true, false, $upload);
            $this->assertSame([], $controller->runLibrary('widgetLibraryUploadAction'));
            $this->assertSame($total, AttachedFile::count());

            // con permiso se listan los archivos
            $controller = $this->libraryController($family->codfamilia, $request, true, true);
            $result = $controller->runLibrary('widgetLibrarySearchAction');
            $this->assertGreaterThan(0, $result['records']);
            $this->assertStringContainsString($file->filename, $result['html']);
            $this->assertSame('false', $controller->tab('EditFamilia')->columnForField('idattachedfile')->widget->readonly);

            // y con permiso y token se sube el archivo, sin consumir el token del formulario
            $controller = $this->libraryController($family->codfamilia, $request, true, true, $upload);
            $result = $controller->runLibrary('widgetLibraryUploadAction');
            $this->assertNotEmpty($result['new_file'] ?? null);
            $this->assertFalse($controller->multiRequestProtection->tokenExist($controller->request->input('multireqtoken')));

            $uploaded = new AttachedFile();
            $this->assertTrue($uploaded->load($result['new_file']));
            $this->assertTrue($uploaded->delete());
        } finally {
            MiniLog::clear();
            $file->delete();
            $family->delete();
        }
    }

    protected function setUp(): void
    {
        MiniLog::clear();
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }

    private function libraryController(string $code, array $data, bool $allowUpdate, bool $validToken, array $files = []): TestableEditFamilia
    {
        $controller = new TestableEditFamilia('EditFamilia', '/EditFamilia');

        $user = new User();
        $user->nick = 'test-edit-familia';
        $controller->user = $user;

        $controller->permissions = new ControllerPermissions();
        $controller->permissions->set(true, 1, false, $allowUpdate);

        $controller->multiRequestProtection->clearSeed();
        $controller->multiRequestProtection->addSeed($user->nick);
        $data['multireqtoken'] = $validToken ? $controller->multiRequestProtection->newToken() : 'invalid-token';

        $controller->request = new Request([
            'query' => ['code' => $code],
            'request' => $data,
            'files' => $files,
        ]);
        foreach ($controller->request->files->all() as $file) {
            $file->test = true;
        }

        return $controller;
    }

    private function reload(Producto $product): Producto
    {
        $reloaded = new Producto();
        $this->assertTrue($reloaded->load($product->idproducto));
        return $reloaded;
    }

    private function uploadField(string $name): array
    {
        $tmpName = tempnam(sys_get_temp_dir(), 'tef');
        file_put_contents($tmpName, 'test');

        return ['file' => [
            'name' => $name,
            'type' => 'text/plain',
            'tmp_name' => $tmpName,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($tmpName),
        ]];
    }

    private function runAction(string $method, string $code, array $codes, bool $allowUpdate, bool $validToken): void
    {
        $controller = new TestableEditFamilia('EditFamilia', '/EditFamilia');

        $user = new User();
        $user->nick = 'test-edit-familia';
        $controller->user = $user;

        $controller->permissions = new ControllerPermissions();
        $controller->permissions->set(true, 1, false, $allowUpdate);

        $controller->multiRequestProtection->clearSeed();
        $controller->multiRequestProtection->addSeed($user->nick);
        $token = $validToken ? $controller->multiRequestProtection->newToken() : 'invalid-token';

        $controller->request = new Request([
            'query' => ['code' => $code],
            'request' => ['codes' => $codes, 'multireqtoken' => $token],
        ]);
        $controller->runAction($method);
    }
}

final class TestableEditFamilia extends EditFamilia
{
    public function runAction(string $method): void
    {
        $this->{$method}();
    }

    public function runLibrary(string $method): array
    {
        // cargamos las pestañas y sus columnas, como hace privateCore() antes de las acciones
        if (empty($this->views)) {
            $this->createViews();
            foreach ($this->views as $viewName => $view) {
                $this->loadData($viewName, $view);
            }
        }
        $this->lockLibraryWidgets();

        return $this->{$method}();
    }
}
