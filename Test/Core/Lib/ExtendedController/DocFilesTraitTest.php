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

namespace FacturaScripts\Test\Core\Lib\ExtendedController;

use FacturaScripts\Core\Lib\ExtendedController\DocFilesTrait;
use FacturaScripts\Core\Model\AttachedFile;
use FacturaScripts\Core\Model\AttachedFileRelation;
use FacturaScripts\Core\Request;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;
use stdClass;

class DocFilesTraitHost
{
    use DocFilesTrait;

    public $model;

    public $multiRequestProtection;

    public $ownerAllowed = true;

    public $permissions;

    public $request;

    public $response;

    public $user;

    public function addFile(): bool
    {
        return $this->addFileAction();
    }

    public function deleteFile(): bool
    {
        return $this->deleteFileAction();
    }

    public function getModel()
    {
        return $this->model ?? new stdClass();
    }


    public function getModelClassName(): string
    {
        return 'DocFilesTest';
    }

    public function pipeFalse(string $name, ...$arguments): bool
    {
        return false;
    }

    public function searchLibrary(): bool
    {
        return $this->searchLibraryAction();
    }

    public function setTemplate($template): void
    {
    }

    public function sortFiles(): bool
    {
        return $this->sortFilesAction();
    }

    public function unlinkFile(): bool
    {
        return $this->unlinkFileAction();
    }

    protected function addHtmlView(
        string $viewName,
        string $fileName,
        string $modelName,
        string $viewTitle,
        string $viewIcon = 'fa-brands fa-html5'
    ): void {
    }

    protected function checkOwnerData($model): bool
    {
        return $this->ownerAllowed;
    }
}

class DocFilesResponse
{
    /** @var array */
    public $data = [];

    public function json(array $data): void
    {
        $this->data = $data;
    }
}

class DocFilesUser
{
    /** @var bool */
    public $libraryAccess = true;

    /** @var bool */
    public $libraryDelete = true;

    /** @var string|null */
    public $nick;

    public function can(string $pageName, string $permission = 'access'): bool
    {
        if ($pageName !== 'ListAttachedFile') {
            return true;
        }

        return $permission === 'delete' ? $this->libraryDelete : $this->libraryAccess;
    }
}

class DocFilesTokenProtection
{
    /** @var bool */
    public $valid = true;

    public function tokenExist(string $token): bool
    {
        return false;
    }

    public function validate(string $token): bool
    {
        return $this->valid;
    }
}

final class DocFilesTraitTest extends TestCase
{
    use RandomDataTrait;

    /** @var AttachedFile */
    private $attachedFile;

    /** @var AttachedFileRelation[] */
    private $relations = [];

    protected function setUp(): void
    {
        $fileName = 'docfiles_rel_' . uniqid() . '.txt';
        file_put_contents(FS_FOLDER . '/MyFiles/' . $fileName, 'content');

        $this->attachedFile = new AttachedFile();
        $this->attachedFile->path = $fileName;
        $this->assertTrue($this->attachedFile->save());
    }

    protected function tearDown(): void
    {
        foreach ($this->relations as $relation) {
            if ($relation->exists()) {
                $relation->delete();
            }
        }
        $this->relations = [];

        if ($this->attachedFile && $this->attachedFile->exists()) {
            $this->attachedFile->delete();
        }
    }

    public function testDeleteRejectsRelationOfAnotherRecord(): void
    {
        $code = 'docfiles-' . uniqid();
        $otherRelation = $this->createRelation('DocFilesTest', 'other-' . $code);

        $host = $this->getHost($code, ['id' => $otherRelation->id]);
        $host->permissions->allowDelete = true;
        $this->assertTrue($host->deleteFile());

        $this->assertTrue($otherRelation->exists());
        $this->assertTrue($this->attachedFile->exists());
    }

    public function testFileActionsRespectOwnerData(): void
    {
        $code = 'docfiles-' . uniqid();
        $relation = $this->createRelation('DocFilesTest', $code);

        $host = $this->getHost($code, ['id' => $relation->id]);
        $host->permissions->onlyOwnerData = true;
        $host->model = $this->attachedFile;
        $host->ownerAllowed = false;
        $this->assertTrue($host->unlinkFile());
        $this->assertTrue($relation->exists());

        $host->ownerAllowed = true;
        $this->assertTrue($host->unlinkFile());
        $this->assertFalse($relation->exists());
    }

    public function testSortOnlyReordersRelationsOfCurrentRecord(): void
    {
        $code = 'docfiles-' . uniqid();
        $relation = $this->createRelation('DocFilesTest', $code);
        $otherRecord = $this->createRelation('DocFilesTest', 'other-' . $code);
        $otherModel = $this->createRelation('OtherDocFilesTest', $code);

        $host = $this->getHost($code, ['orden' => [$otherRecord->id, $otherModel->id, $relation->id]]);
        $this->assertFalse($host->sortFiles());

        $this->assertTrue($relation->reload());
        $this->assertEquals(1, $relation->orden);
        $this->assertTrue($otherRecord->reload());
        $this->assertNull($otherRecord->orden);
        $this->assertTrue($otherModel->reload());
        $this->assertNull($otherModel->orden);
    }

    public function testUnlinkOnlyRemovesRelationsOfCurrentRecord(): void
    {
        $code = 'docfiles-' . uniqid();
        $relation = $this->createRelation('DocFilesTest', $code);
        $otherRecord = $this->createRelation('DocFilesTest', 'other-' . $code);
        $otherModel = $this->createRelation('OtherDocFilesTest', $code);

        // relación de otro registro del mismo modelo
        $host = $this->getHost($code, ['id' => $otherRecord->id]);
        $this->assertTrue($host->unlinkFile());
        $this->assertTrue($otherRecord->exists());

        // relación de otro modelo con el mismo código
        $host = $this->getHost($code, ['id' => $otherModel->id]);
        $this->assertTrue($host->unlinkFile());
        $this->assertTrue($otherModel->exists());

        // relación del registro actual
        $host = $this->getHost($code, ['id' => $relation->id]);
        $this->assertTrue($host->unlinkFile());
        $this->assertFalse($relation->exists());
        $this->assertTrue($this->attachedFile->exists());
    }

    public function testUploadWithExistingFileName(): void
    {
        $fileName = 'docfiles_collision_' . uniqid() . '.txt';
        $originalPath = FS_FOLDER . '/MyFiles/' . $fileName;
        $tempPath = tempnam(sys_get_temp_dir(), 'docfiles_');
        $modelCode = 'docfiles-' . uniqid();
        $relation = null;
        $attachedFile = null;

        file_put_contents($originalPath, 'existing content');
        file_put_contents($tempPath, 'new content');

        try {
            $host = new DocFilesTraitHost();
            $host->multiRequestProtection = new DocFilesTokenProtection();
            $host->permissions = new stdClass();
            $host->permissions->allowUpdate = true;
            $host->user = new stdClass();
            $host->user->nick = null;
            $host->request = new Request([
                'files' => [
                    'new-files' => [
                        'name' => [$fileName],
                        'type' => ['text/plain'],
                        'tmp_name' => [$tempPath],
                        'error' => [UPLOAD_ERR_OK],
                        'size' => [filesize($tempPath)],
                        'test' => [true],
                    ],
                ],
                'query' => ['code' => $modelCode],
                'request' => ['multireqtoken' => 'valid-token'],
            ]);

            $this->assertTrue($host->addFile());

            $relations = AttachedFileRelation::allWhereEq('modelcode', $modelCode);
            $this->assertCount(1, $relations);
            $relation = $relations[0];
            $attachedFile = $relation->getFile();

            $this->assertInstanceOf(AttachedFile::class, $attachedFile);
            $this->assertSame('new content', file_get_contents($attachedFile->getFullPath()));
            $this->assertSame('existing content', file_get_contents($originalPath));
        } finally {
            if ($relation && $relation->exists()) {
                $relation->delete();
            }
            if ($attachedFile && $attachedFile->exists()) {
                $attachedFile->delete();
            }
            if (file_exists($originalPath)) {
                unlink($originalPath);
            }
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
            foreach (glob(FS_FOLDER . '/MyFiles/*_' . $fileName) ?: [] as $orphan) {
                unlink($orphan);
            }
        }
    }

    public function testAddFileLinksLibraryFilesWithoutDuplicates(): void
    {
        $code = 'docfiles-' . uniqid();

        $host = $this->getHost($code, [
            'idfiles' => [$this->attachedFile->idfile],
            'observations' => 'desde la biblioteca',
        ]);
        $this->assertTrue($host->addFile());

        $relations = AttachedFileRelation::allWhereEq('modelcode', $code);
        $this->assertCount(1, $relations);
        $this->relations[] = $relations[0];
        $this->assertSame($this->attachedFile->idfile, $relations[0]->idfile);
        $this->assertSame('desde la biblioteca', $relations[0]->observations);

        // al repetir la acción no se duplica la relación
        $this->assertTrue($host->addFile());
        $this->assertCount(1, AttachedFileRelation::allWhereEq('modelcode', $code));
    }

    public function testAddFileFromLibraryRespectsOwnerData(): void
    {
        $code = 'docfiles-' . uniqid();

        $host = $this->getHost($code, ['idfiles' => [$this->attachedFile->idfile]]);
        $host->permissions->onlyOwnerData = true;
        $host->model = $this->attachedFile;
        $host->ownerAllowed = false;
        $this->assertTrue($host->addFile());
        $this->assertCount(0, AttachedFileRelation::allWhereEq('modelcode', $code));

        $host->ownerAllowed = true;
        $this->assertTrue($host->addFile());

        $relations = AttachedFileRelation::allWhereEq('modelcode', $code);
        $this->assertCount(1, $relations);
        $this->relations[] = $relations[0];
    }

    public function testSearchLibraryLimitsResults(): void
    {
        $prefix = 'docfileslimit' . uniqid();
        $files = [];

        try {
            // creamos más archivos de los que puede devolver la búsqueda
            for ($num = 0; $num < 10; $num++) {
                $fileName = $prefix . '_' . $num . '.txt';
                file_put_contents(FS_FOLDER . '/MyFiles/' . $fileName, 'content');

                $file = new AttachedFile();
                $file->path = $fileName;
                $this->assertTrue($file->save());
                $files[] = $file;
            }

            $host = $this->getHost('docfiles-' . uniqid(), ['query' => $prefix]);
            $this->assertFalse($host->searchLibrary());

            $this->assertCount(8, $host->response->data['files']);

            // hay más de los que caben, pero no decimos cuántos
            $this->assertTrue($host->response->data['more']);
            $this->assertArrayNotHasKey('total', $host->response->data);

            $firstPage = array_column($host->response->data['files'], 'idfile');

            // la segunda página trae el resto, sin repetir ninguno de la primera
            $second = $this->getHost('docfiles-' . uniqid(), [
                'query' => $prefix,
                'offset' => $host->libraryPageSize(),
            ]);
            $this->assertFalse($second->searchLibrary());

            $secondPage = array_column($second->response->data['files'], 'idfile');
            $this->assertCount(2, $secondPage);
            $this->assertFalse($second->response->data['more']);
            $this->assertEmpty(array_intersect($firstPage, $secondPage));
        } finally {
            foreach ($files as $file) {
                if ($file->exists()) {
                    $file->delete();
                }
            }
        }
    }

    public function testSearchLibraryFindsWordsInAnyOrder(): void
    {
        $prefix = 'docfileswords' . uniqid();
        $fileName = $prefix . ' informe enero.txt';
        file_put_contents(FS_FOLDER . '/MyFiles/' . $fileName, 'content');

        $file = new AttachedFile();
        $file->path = $fileName;
        $this->assertTrue($file->save());

        try {
            // las palabras pueden ir en otro orden del que tiene el nombre
            $host = $this->getHost('docfiles-' . uniqid(), ['query' => 'enero ' . $prefix]);
            $this->assertFalse($host->searchLibrary());

            $this->assertSame(
                [$file->idfile],
                array_column($host->response->data['files'], 'idfile')
            );
        } finally {
            if ($file->exists()) {
                $file->delete();
            }
        }
    }

    public function testSearchLibraryMarksLinkedFiles(): void
    {
        $code = 'docfiles-' . uniqid();
        $this->createRelation('DocFilesTest', $code);

        $host = $this->getHost($code, ['query' => $this->attachedFile->filename]);
        $this->assertFalse($host->searchLibrary());

        // el archivo ya vinculado sigue apareciendo, pero marcado
        $found = false;
        foreach ($host->response->data['files'] as $file) {
            if ($file['idfile'] === $this->attachedFile->idfile) {
                $found = true;
                $this->assertTrue($file['linked']);
            }
        }
        $this->assertTrue($found);

        // desde otro registro no está vinculado
        $other = $this->getHost('docfiles-' . uniqid(), ['query' => $this->attachedFile->filename]);
        $this->assertFalse($other->searchLibrary());
        foreach ($other->response->data['files'] as $file) {
            if ($file['idfile'] === $this->attachedFile->idfile) {
                $this->assertFalse($file['linked']);
            }
        }
    }

    public function testSearchLibraryNeedsToken(): void
    {
        $host = $this->getHost('docfiles-' . uniqid(), ['query' => 'docfiles']);
        $host->multiRequestProtection->valid = false;
        $this->assertFalse($host->searchLibrary());

        // no devolvemos una lista vacía, sino el motivo y la petición de recargar
        $this->assertArrayNotHasKey('files', $host->response->data);
        $this->assertNotEmpty($host->response->data['error']);
        $this->assertTrue($host->response->data['reload']);

        // con un token válido sí responde
        $host->multiRequestProtection->valid = true;
        $this->assertFalse($host->searchLibrary());
        $this->assertIsArray($host->response->data['files']);
    }

    public function testLibraryNeedsAttachedFilesPermission(): void
    {
        $code = 'docfiles-' . uniqid();

        $host = $this->getHost($code, ['idfiles' => [$this->attachedFile->idfile]]);
        $host->user->libraryAccess = false;

        // sin permiso sobre la biblioteca no se vincula nada
        $this->assertTrue($host->addFile());
        $this->assertCount(0, AttachedFileRelation::allWhereEq('modelcode', $code));

        // ni se devuelve la lista de archivos
        $this->assertFalse($host->searchLibrary());
        $this->assertArrayNotHasKey('files', $host->response->data);

        // con permiso sí
        $host->user->libraryAccess = true;
        $this->assertTrue($host->addFile());

        $relations = AttachedFileRelation::allWhereEq('modelcode', $code);
        $this->assertCount(1, $relations);
        $this->relations[] = $relations[0];

        $this->assertFalse($host->searchLibrary());
        $this->assertArrayHasKey('files', $host->response->data);
    }

    public function testDeleteRejectedWhenFileSharedWithOtherRecords(): void
    {
        $code = 'docfiles-' . uniqid();
        $relation = $this->createRelation('DocFilesTest', $code);

        // el mismo archivo adjunto en otro registro
        $otherRelation = $this->createRelation('DocFilesTest', 'other-' . $code);

        $host = $this->getHost($code, ['id' => $relation->id]);
        $host->permissions->allowDelete = true;
        $this->assertTrue($host->deleteFile());

        // no se elimina nada: el archivo está en uso, hay que desvincularlo
        $this->assertTrue($relation->exists());
        $this->assertTrue($otherRelation->exists());
        $this->assertTrue($this->attachedFile->exists());
        $this->assertFileExists($this->attachedFile->getFullPath());

        // al desvincular sí se quita la relación, y el archivo se conserva
        $this->assertTrue($host->unlinkFile());
        $this->assertFalse($relation->exists());
        $this->assertTrue($this->attachedFile->exists());

        // con una sola relación, eliminar ya borra el archivo
        $last = $this->getHost('other-' . $code, ['id' => $otherRelation->id]);
        $last->permissions->allowDelete = true;
        $this->assertTrue($last->deleteFile());

        $this->assertFalse($otherRelation->exists());
        $this->assertFalse($this->attachedFile->exists());
    }

    public function testAddFileRejectsTooManyLibraryFiles(): void
    {
        $code = 'docfiles-' . uniqid();

        // repetimos el mismo id hasta pasarnos del tope
        $host = $this->getHost($code, ['idfiles' => []]);
        $idFiles = array_fill(0, $host->maxLibraryFilesPerRequest() + 1, $this->attachedFile->idfile);

        $host = $this->getHost($code, ['idfiles' => $idFiles]);
        $this->assertTrue($host->addFile());

        // no se guarda ninguna, ni siquiera las que caben
        $this->assertCount(0, AttachedFileRelation::allWhereEq('modelcode', $code));
    }

    public function testSharedFileIds(): void
    {
        $code = 'docfiles-' . uniqid();
        $this->createRelation('DocFilesTest', $code);

        // adjunto en un solo registro: no está compartido
        $host = $this->getHost($code, []);
        $this->assertSame([], $host->getSharedFileIds());

        // el mismo archivo en otro registro: ahora sí
        $this->createRelation('DocFilesTest', 'docfiles-' . uniqid());
        $other = $this->getHost($code, []);
        $this->assertSame([$this->attachedFile->idfile], $other->getSharedFileIds());
    }

    public function testDeleteFileNeedsLibraryDeletePermission(): void
    {
        // la relación guarda el nick de quien subió el archivo, y hay clave ajena a users
        $owner = $this->getRandomUser();
        $this->assertTrue($owner->save());

        $other = $this->getRandomUser();
        $other->nick .= 'b';
        $other->email = $other->nick . '@facturascripts.com';
        $this->assertTrue($other->save());

        try {
            $code = 'docfiles-' . uniqid();
            $relation = $this->createRelation('DocFilesTest', $code);
            $relation->nick = $owner->nick;
            $this->assertTrue($relation->save());

            $host = $this->getHost($code, ['id' => $relation->id]);
            $host->permissions->allowDelete = true;
            $host->user->libraryDelete = false;
            $host->user->nick = $other->nick;

            // sin permiso sobre la biblioteca no se elimina lo que subió otro
            $this->assertTrue($host->deleteFile());
            $this->assertTrue($relation->exists());
            $this->assertTrue($this->attachedFile->exists());

            // pero sí puede eliminar lo que ha subido él mismo
            $relation->nick = $other->nick;
            $this->assertTrue($relation->save());
            $this->assertTrue($host->deleteFile());
            $this->assertFalse($relation->exists());
        } finally {
            $owner->delete();
            $other->delete();
        }
    }

    private function createRelation(string $model, string $code): AttachedFileRelation
    {
        $relation = new AttachedFileRelation();
        $relation->idfile = $this->attachedFile->idfile;
        $relation->model = $model;
        $relation->modelcode = $code;
        $relation->modelid = 0;
        $this->assertTrue($relation->save());

        $this->relations[] = $relation;
        return $relation;
    }

    private function getHost(string $code, array $input): DocFilesTraitHost
    {
        $host = new DocFilesTraitHost();
        $host->multiRequestProtection = new DocFilesTokenProtection();
        $host->permissions = new stdClass();
        $host->permissions->allowUpdate = true;
        $host->permissions->onlyOwnerData = false;
        $host->response = new DocFilesResponse();
        $host->user = new DocFilesUser();
        $host->request = new Request([
            'query' => ['code' => $code],
            'request' => array_merge(['multireqtoken' => 'valid-token'], $input),
        ]);

        return $host;
    }
}
