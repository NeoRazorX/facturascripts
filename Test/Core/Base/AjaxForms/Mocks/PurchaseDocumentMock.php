<?php

namespace FacturaScripts\Test\Core\Base\AjaxForms\Mocks;

use FacturaScripts\Core\Model\Base\PurchaseDocument;

class PurchaseDocumentMock extends PurchaseDocument
{
    protected $test;

    public function getLines(): array
    {
        return [];
    }

    public function getNewLine($data = [], $exclude = [])
    {
    }

    public static function addExtension($extension)
    {
    }

    public function getModelFields(): array
    {
        return [];
    }

    protected function loadModelFields(&$dataBase, $tableName)
    {
    }

    public function modelClassName(): string
    {
        return '';
    }

    protected function modelName(): string
    {
        return '';
    }

    public function pipe($name, ...$arguments)
    {
    }

    public function pipeFalse($name, ...$arguments): bool
    {
        return false;
    }

    public static function primaryColumn(): string
    {
        return 'test';
    }

    public static function tableName(): string
    {
        return '';
    }
}
