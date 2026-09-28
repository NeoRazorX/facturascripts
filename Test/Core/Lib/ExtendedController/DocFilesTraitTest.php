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
    public function json(array $data): void
    {
    }
}

class DocFilesTokenProtection
{
    public function tokenExist(string $token): bool
    {
        return false;
    }

    public function validate(string $token): bool
    {
        return true;
    }
}

final class DocFilesTraitTest extends TestCase
{
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
        $host->user = new stdClass();
        $host->user->nick = null;
        $host->request = new Request([
            'query' => ['code' => $code],
            'request' => array_merge(['multireqtoken' => 'valid-token'], $input),
        ]);

        return $host;
    }
}
