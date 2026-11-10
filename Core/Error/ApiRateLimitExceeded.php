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

namespace FacturaScripts\Core\Error;

use FacturaScripts\Core\Template\ErrorController;

class ApiRateLimitExceeded extends ErrorController
{
    public function run(): void
    {
        // el código de la excepción indica los segundos que faltan para volver a intentarlo
        $retryAfter = max(1, (int)$this->exception->getCode());

        $this->response()
            ->setHttpCode(429)
            ->header('Retry-After', (string)$retryAfter)
            ->json([
                'status' => 'error',
                'message' => $this->exception->getMessage(),
            ]);
    }
}
