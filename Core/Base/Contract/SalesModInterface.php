<?php
/**
 * Copyright (C) 2021-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
 */

namespace FacturaScripts\Core\Base\Contract;

use FacturaScripts\Core\Base\Translator;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Model\User;

interface SalesModInterface
{
    public function apply(SalesDocument &$model, array $formData, User $user);

    public function applyBefore(SalesDocument &$model, array $formData, User $user);

    public function newFields(): array;

    public function renderField(Translator $i18n, SalesDocument $model, string $field): ?string;
}
