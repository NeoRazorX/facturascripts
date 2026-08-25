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

    public $multiRequestProtection;

    public $permissions;

    public $request;

    public $user;

    public function addFile(): bool
    {
        return $this->addFileAction();
    }

    public function getModel(): stdClass
    {
        return new stdClass();
    }

    public function getModelClassName(): string
    {
        return 'DocFilesTest';
    }

    public function pipeFalse(string $name, ...$arguments): bool
    {
        return false;
    }

    protected function addHtmlView(
        string $viewName,
        string $fileName,
        string $modelName,
        string $viewTitle,
        string $viewIcon = 'fa-brands fa-html5'
    ): void {
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
}
