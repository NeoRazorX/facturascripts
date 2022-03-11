<?php
/**
 * Copyright (C) 2021-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
 */

namespace FacturaScripts\Core\Base\Contract;

use FacturaScripts\Core\Base\Translator;
use FacturaScripts\Core\Model\Base\PurchaseDocument;
use FacturaScripts\Core\Model\User;

interface PurchasesModInterface
{
    public function apply(PurchaseDocument &$model, array $formData, User $user);

    public function applyBefore(PurchaseDocument &$model, array $formData, User $user);

    public function newFields(): array;

    public function renderField(Translator $i18n, PurchaseDocument $model, string $field): ?string;
}
