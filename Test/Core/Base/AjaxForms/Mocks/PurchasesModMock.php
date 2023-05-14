<?php

namespace FacturaScripts\Test\Core\Base\AjaxForms\Mocks;

use FacturaScripts\Core\Base\Contract\PurchasesModInterface;
use FacturaScripts\Core\Base\Translator;
use FacturaScripts\Core\Model\Base\PurchaseDocument;

class PurchasesModMock implements PurchasesModInterface
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

    public function renderField(Translator $i18n, PurchaseDocument $model, string $field): ?string
    {
        return null;
    }
}
