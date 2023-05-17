<?php

namespace FacturaScripts\Test\Core\Base\AjaxForms\Mocks;

use FacturaScripts\Core\Base\Contract\SalesModInterface;
use FacturaScripts\Core\Base\Translator;
use FacturaScripts\Core\Model\Base\SalesDocument;

class SalesModMock implements SalesModInterface
{
    public function apply(&$model, $formData, $user)
    {
        //
    }

    public function applyBefore(&$model, $formData, $user)
    {
        //
    }

    public function assets(): void
    {
        //
    }

    public function newFields(): array
    {
        return [];
    }

    public function renderField(Translator $i18n, SalesDocument $model, string $field): ?string
    {
        return null;
    }
}
