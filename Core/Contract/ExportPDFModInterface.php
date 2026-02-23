<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Contract;

use FacturaScripts\Core\Contract\ExportPDFInterface as ExportPDF;

interface ExportPDFModInterface
{
    /**
     * Returns true when the mod should run for the provided context.
     *
     * @param ExportPDF $export
     * @param mixed $model
     * @param array $context
     */
    public function appliesTo(ExportPDF $export, $model, array $context = []): bool;

    /**
     * Allows the mod to tweak or replace the document format that will be used.
     * Implementations can adjust the format entry in the context or return false to stop propagation.
     *
     * @param ExportPDF $export
     * @param array $context
     */
    public function customizeFormat(ExportPDF $export, array &$context): bool;

    /**
     * Runs before the model rendering starts. Return false to stop the default rendering.
     *
     * @param ExportPDF $export
     * @param array $context
     */
    public function beforeAddModel(ExportPDF $export, array &$context): bool;

    /**
     * Runs after the default model rendering finishes. Return false to prevent other mods from running.
     *
     * @param ExportPDF $export
     * @param array $context
     */
    public function afterAddModel(ExportPDF $export, array &$context): bool;

    /**
     * Gives the mod the opportunity to inject custom content in a specific section.
     * Return false to stop the propagation for that section.
     *
     * @param ExportPDF $export
     * @param string $section
     * @param array $context
     */
    public function renderSection(ExportPDF $export, string $section, array &$context): bool;

    /**
     * Allows the mod to append watermarks or similar decorations.
     *
     * @param ExportPDF $export
     * @param array $context
     */
    public function applyWatermark(ExportPDF $export, array &$context): void;

    /**
     * Gives the mod the chance to append additional pages after the main rendering block.
     *
     * @param ExportPDF $export
     * @param array $context
     */
    public function appendExtraPages(ExportPDF $export, array &$context): void;
}
