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
namespace FacturaScripts\Core\Lib;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

/**
 * Imagen PNG de un código QR, con la librería que ya trae el core (chillerlan/php-qrcode; el PNG necesita GD,
 * que el core exige). Así los plugins no dependen directamente de la API de la librería, que cambia entre
 * versiones mayores, y pueden elegir el nivel de corrección de errores y el margen que pida cada uso (por
 * ejemplo, el QR de las facturas de VERI*FACTU y TicketBAI exige el nivel M).
 *
 * @author Daniel Fernández Giménez <contacto@danielfg.es>
 */
class QrImage
{
    /** Corrección de errores alta: recupera el 30 % del código. */
    const ECC_H = 'H';

    /** Corrección de errores baja: recupera el 7 % del código. */
    const ECC_L = 'L';

    /** Corrección de errores media: recupera el 15 % del código. */
    const ECC_M = 'M';

    /** Corrección de errores cuartil: recupera el 25 % del código. */
    const ECC_Q = 'Q';

    /**
     * Imagen PNG del código en data URI (data:image/png;base64,...), lista para un <img>, el PDF del core o
     * PlantillasPDF. La versión del código se elige sola según la longitud de los datos.
     *
     * @param string $data      texto o URL que se codifica
     * @param string $eccLevel  nivel de corrección de errores (constantes ECC_*); otro valor se toma como M
     * @param int    $scale     píxeles por módulo
     * @param int    $quietZone margen en blanco alrededor del código, en módulos (0 = sin margen)
     *
     * @return string
     */
    public static function png(string $data, string $eccLevel = self::ECC_M, int $scale = 5, int $quietZone = 4): string
    {
        $options = new QROptions([
            'version' => QRCode::VERSION_AUTO,
            'eccLevel' => self::eccLevel($eccLevel),
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'imageBase64' => true,
            'scale' => max(1, $scale),
            'addQuietzone' => $quietZone > 0,
            'quietzoneSize' => max(0, $quietZone),
        ]);

        return (new QRCode($options))->render($data);
    }

    /**
     * Módulos por lado del código, sin el margen. Sirve para calcular su medida real (por ejemplo, en
     * milímetros en un PDF o en una impresora de tickets).
     *
     * @param string $data     texto o URL que se codifica
     * @param string $eccLevel nivel de corrección de errores (constantes ECC_*); otro valor se toma como M
     *
     * @return int
     */
    public static function size(string $data, string $eccLevel = self::ECC_M): int
    {
        $options = new QROptions([
            'version' => QRCode::VERSION_AUTO,
            'eccLevel' => self::eccLevel($eccLevel),
            'addQuietzone' => false,
        ]);

        return (new QRCode($options))->getMatrix($data)->size();
    }

    /**
     * Nivel de corrección de errores de la librería.
     */
    private static function eccLevel(string $eccLevel): int
    {
        return match ($eccLevel) {
            self::ECC_L => QRCode::ECC_L,
            self::ECC_Q => QRCode::ECC_Q,
            self::ECC_H => QRCode::ECC_H,
            default => QRCode::ECC_M,
        };
    }
}
