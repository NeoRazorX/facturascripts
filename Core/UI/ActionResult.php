<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\UI;

class ActionResult
{
    /** @var bool */
    public $exit = false;

    /** @var string */
    public $html = '';

    /** @var string */
    public $json = '';

    /** @var bool */
    public $stop = false;

    public function setExit(bool $exit = true): self
    {
        $this->exit = $exit;

        return $this;
    }

    public function setHtml(string $html): self
    {
        $this->html = $html;

        return $this;
    }

    public function setJson(string $json): self
    {
        $this->json = $json;

        return $this;
    }

    public function setStop(bool $stop = true): self
    {
        $this->stop = $stop;

        return $this;
    }
}
