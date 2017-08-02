<?php
/**
 * This file is part of facturacion_base
 * Copyright (C) 2015-2017  Carlos Garcia Gomez  neorazorx@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Model;

/**
 * Configuración de un terminal de TPV y de la impresora de tickets,
 * además almacena los tickets a imprimir.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class TerminalCaja
{

    use Base\ModelTrait;

    /**
     * Clave primaria.
     * @var int
     */
    public $id;

    /**
     * Código del almacén a usar en los tickets.
     * @var string
     */
    public $codalmacen;

    /**
     * Código de la serie a utilizar en los tickets.
     * @var string
     */
    public $codserie;

    /**
     * Código del cliente predeterminado para los tickets.
     * @var string
     */
    public $codcliente;

    /**
     * Buffer con los ticket pendientes para imprimir.
     * @var
     */
    public $tickets;

    /**
     * Número de caracteres que caben en una línea del papel del ticket.
     * @var int
     */
    public $anchopapel;

    /**
     * Comando ESC/POS para cortar el papel.
     * @var string
     */
    public $comandocorte;

    /**
     * Comando ESC/POS para abrir el cajón portamonedas conectado a la impresora.
     * @var string
     */
    public $comandoapertura;

    /**
     * Número de impresiones para cada ticket.
     * @var int
     */
    public $num_tickets;

    /**
     * Desactivar los comandos ESC/POS para comprobaciones de la impresora de tickets.
     * @var string
     */
    public $sin_comandos;

    /**
     * TerminalCaja constructor.
     *
     * @param array $data
     */
    public function __construct($data = [])
    {
        $this->init(__CLASS__, 'cajas_terminales', 'id');
        if (is_null($data) || empty($data)) {
            $this->clear();
        } else {
            $this->loadFromData($data);
        }
    }

    /**
     * Resetea los valores de todas las propiedades modelo.
     */
    public function clear()
    {
        $this->id = null;
        $this->codalmacen = null;
        $this->codserie = null;
        $this->codcliente = null;
        $this->tickets = '';
        $this->anchopapel = 40;
        $this->comandocorte = '27.105';
        $this->comandoapertura = '27.112.48';
        $this->num_tickets = 1;
        $this->sin_comandos = false;
    }

    /**
     * TODO
     * @return bool
     */
    public function disponible()
    {
        $sql = 'SELECT * FROM cajas WHERE f_fin IS NULL AND fs_id = ' . $this->var2str($this->id) . ';';
        if ($this->dataBase->select($sql)) {
            return false;
        }
        return true;
    }

    /**
     * TODO
     *
     * @param string $linea
     */
    public function addLinea($linea)
    {
        $this->tickets .= $linea;
    }

    /**
     * TODO
     *
     * @param string $linea
     */
    public function addLineaBig($linea)
    {
        if ($this->sin_comandos) {
            $this->tickets .= $linea;
        } else {
            $this->tickets .= chr(27) . chr(33) . chr(56) . $linea . chr(27) . chr(33) . chr(1);
        }
    }

    /**
     * TODO
     */
    public function abrirCajon()
    {
        if ($this->sin_comandos) {
            /// nada
        } elseif ($this->comandoapertura) {
            $aux = explode('.', $this->comandoapertura);
            if ($aux) {
                foreach ($aux as $a) {
                    $this->tickets .= chr($a);
                }

                $this->tickets .= "\n";
            }
        }
    }

    /**
     * TODO
     */
    public function cortarPapel()
    {
        if ($this->sin_comandos) {
            /// nada
        } elseif ($this->comandocorte) {
            $aux = explode('.', $this->comandocorte);
            if (!empty($aux)) {
                foreach ($aux as $a) {
                    $this->tickets .= chr($a);
                }

                $this->tickets .= "\n";
            }
        }
    }

    /**
     * TODO
     *
     * @param string $word
     * @param int $ancho
     *
     * @return string
     */
    public function centerText($word = '', $ancho = 0)
    {
        if ($ancho !== 0) {
            $ancho = $this->anchopapel;
        }

        if (strlen($word) === $ancho) {
            return $word;
        }
        if (strlen($word) < $ancho) {
            return $this->centerText2($word, $ancho);
        }
        $result = '';
        $nword = '';
        foreach (explode(' ', $word) as $aux) {
            if ($nword === '') {
                $nword = $aux;
            } elseif (strlen($nword) + strlen($aux) + 1 <= $ancho) {
                $nword = $nword . ' ' . $aux;
            } else {
                if ($result !== '') {
                    $result .= "\n";
                }

                $result .= $this->centerText2($nword, $ancho);
                $nword = $aux;
            }
        }
        if ($nword !== '') {
            if ($result !== '') {
                $result .= "\n";
            }

            $result .= $this->centerText2($nword, $ancho);
        }

        return $result;
    }

    /**
     * TODO
     * @return array
     */
    public function disponibles()
    {
        $tlist = [];
        $sql = 'SELECT * FROM cajas_terminales WHERE id NOT IN '
            . '(SELECT fs_id AS id FROM cajas WHERE f_fin IS NULL) '
            . 'ORDER BY id ASC;';

        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $d) {
                $tlist[] = new TerminalCaja($d);
            }
        }

        return $tlist;
    }

    /**
     * A partir de una factura añade un ticket a la cola de impresión de este terminal.
     *
     * @param FacturaCliente $factura
     * @param Empresa $empresa
     * @param bool $imprimirDescripciones
     * @param bool $imprimirObservaciones
     */
    public function imprimirTicket(&$factura, &$empresa, $imprimirDescripciones = true, $imprimirObservaciones = false)
    {
        $medio = $this->anchopapel / 2.5;
        $this->addLineaBig($this->centerText($this->sanitize($empresa->nombre), $medio) . "\n");

        if ($empresa->lema !== '') {
            $this->addLinea($this->centerText($this->sanitize($empresa->lema)) . "\n\n");
        } else {
            $this->addLinea("\n");
        }

        $this->addLinea(
            $this->centerText($this->sanitize($empresa->direccion) . ' - ' . $this->sanitize($empresa->ciudad)) . "\n"
        );
        $this->addLinea($this->centerText(FS_CIFNIF . ': ' . $empresa->cifnif));
        $this->addLinea("\n\n");

        if ($empresa->horario !== '') {
            $this->addLinea($this->centerText($this->sanitize($empresa->horario)) . "\n\n");
        }

        $linea = "\n" . ucfirst(FS_FACTURA_SIMPLIFICADA) . ': ' . $factura->codigo . "\n";
        $linea .= $factura->fecha . ' ' . date('H:i', strtotime($factura->hora)) . "\n";
        $this->addLinea($linea);
        $this->addLinea('Cliente: ' . $this->sanitize($factura->nombrecliente) . "\n");
        $this->addLinea('Empleado: ' . $factura->codagente . "\n\n");

        if ($imprimirObservaciones) {
            $this->addLinea('Observaciones: ' . $this->sanitize($factura->observaciones) . "\n\n");
        }

        $width = $this->anchopapel - 15;
        $this->addLinea(sprintf('%3s', 'Ud.') . ' ' .
            sprintf('%-' . $width . 's', 'Articulo') . ' ' .
            sprintf('%10s', 'TOTAL') . "\n");
        $this->addLinea(
            sprintf('%3s', '---') . ' ' . sprintf(
                '%-' . $width . 's', substr(
                    '--------------------------------------------------------', 0, $width - 1
                )
            ) . ' ' .
            sprintf('%10s', '----------') . "\n"
        );
        foreach ($factura->getLineas() as $col) {
            if ($imprimirDescripciones) {
                $linea = sprintf('%3s', $col->cantidad) . ' ' . sprintf(
                        '%-' . $width . 's', substr($this->sanitize($col->descripcion), 0, $width - 1)
                    ) . ' ' . sprintf('%10s', $this->showNumero($col->totalIva())) . "\n";
            } else {
                $linea = sprintf('%3s', $col->cantidad) . ' ' . sprintf(
                        '%-' . $width . 's', $this->sanitize($col->referencia)
                    ) . ' ' . sprintf('%10s', $this->showNumero($col->totalIva())) . "\n";
            }

            $this->addLinea($linea);
        }

        $lineaiguales = '';
        for ($i = 0; $i < $this->anchopapel; $i++) {
            $lineaiguales .= '=';
        }
        $this->addLinea($lineaiguales . "\n");
        $this->addLinea('TOTAL A PAGAR: ' . sprintf(
                '%' . ($this->anchopapel - 15) . 's', $this->showPrecio($factura->total, $factura->coddivisa)
            ) . "\n");
        $this->addLinea($lineaiguales . "\n");

        /// imprimimos los impuestos desglosados
        $this->addLinea(
            'TIPO   BASE    ' . FS_IVA . '    RE' .
            sprintf('%' . ($this->anchopapel - 24) . 's', 'TOTAL') .
            "\n"
        );
        foreach ($factura->getLineasIva() as $imp) {
            $this->addLinea(
                sprintf('%-6s', $imp->iva . '%') . ' ' .
                sprintf('%-7s', $this->showNumero($imp->neto)) . ' ' .
                sprintf('%-6s', $this->showNumero($imp->totaliva)) . ' ' .
                sprintf('%-6s', $this->showNumero($imp->totalrecargo)) . ' ' .
                sprintf('%' . ($this->anchopapel - 29) . 's', $this->showNumero($imp->totallinea)) .
                '\n'
            );
        }

        $lineaiguales .= "\n\n\n\n\n\n\n\n";
        $this->addLinea($lineaiguales);
        $this->cortarPapel();
    }

    /**
     * A partir de una factura añade un ticket regalo a la cola de impresión de este terminal.
     *
     * @param FacturaCliente $factura
     * @param Empresa $empresa
     * @param bool $imprimirDescripciones
     * @param bool $imprimirObservaciones
     */
    public function imprimirTicketRegalo(&$factura, &$empresa, $imprimirDescripciones = true, $imprimirObservaciones = false)
    {
        $medio = $this->anchopapel / 2.5;
        $this->addLineaBig($this->centerText($this->sanitize($empresa->nombre), $medio) . "\n");

        if ($empresa->lema !== '') {
            $this->addLinea($this->centerText($this->sanitize($empresa->lema)) . "\n\n");
        } else {
            $this->addLinea("\n");
        }

        $this->addLinea($this->centerText($this->sanitize($empresa->direccion) . ' - '
                . $this->sanitize($empresa->ciudad)) . "\n");
        $this->addLinea($this->centerText(FS_CIFNIF . ': ' . $empresa->cifnif));
        $this->addLinea("\n\n");

        if ($empresa->horario !== '') {
            $this->addLinea($this->centerText($this->sanitize($empresa->horario)) . "\n\n");
        }

        $linea = "\n" . ucfirst(FS_FACTURA_SIMPLIFICADA) . ': ' . $factura->codigo . "\n";
        $linea .= $factura->fecha . ' ' . date('H:i', strtotime($factura->hora)) . "\n";
        $this->addLinea($linea);
        $this->addLinea('Cliente: ' . $this->sanitize($factura->nombrecliente) . "\n");
        $this->addLinea('Empleado: ' . $factura->codagente . "\n\n");

        if ($imprimirObservaciones) {
            $this->addLinea('Observaciones: ' . $this->sanitize($factura->observaciones) . "\n\n");
        }

        $width = $this->anchopapel - 15;
        $this->addLinea(sprintf('%3s', 'Ud.') . ' ' .
            sprintf('%-' . $width . 's', 'Articulo') . ' ' .
            sprintf('%10s', 'TOTAL') . "\n");
        $this->addLinea(
            sprintf('%3s', '---') . ' ' . sprintf(
                '%-' . $width . 's', substr('--------------------------------------------------------', 0, $width - 1)
            ) . ' ' .
            sprintf('%10s', '----------') . "\n"
        );
        foreach ($factura->getLineas() as $col) {
            if ($imprimirDescripciones) {
                $linea = sprintf('%3s', $col->cantidad) . ' ' . sprintf(
                        '%-' . $width . 's', substr($this->sanitize($col->descripcion), 0, $width - 1)
                    ) . ' ' . sprintf('%10s', '-') . "\n";
            } else {
                $linea = sprintf('%3s', $col->cantidad) . ' ' . sprintf(
                        '%-' . $width . 's', $this->sanitize($col->referencia)
                    ) . ' ' . sprintf('%10s', '-') . "\n";
            }

            $this->addLinea($linea);
        }


        $lineaiguales = '';
        for ($i = 0; $i < $this->anchopapel; $i++) {
            $lineaiguales .= '=';
        }
        $this->addLinea($lineaiguales);
        $this->addLinea($this->centerText('TICKET REGALO'));
        $lineaiguales .= "\n\n\n\n\n\n\n\n";
        $this->addLinea($lineaiguales);
        $this->cortarPapel();
    }

    /**
     * TODO
     *
     * @param string $txt
     *
     * @return string
     */
    public function sanitize($txt)
    {
        $changes = [
            '/à/' => 'a',
            '/á/' => 'a',
            '/â/' => 'a',
            '/ã/' => 'a',
            '/ä/' => 'a',
            '/å/' => 'a',
            '/æ/' => 'ae',
            '/ç/' => 'c',
            '/è/' => 'e',
            '/é/' => 'e',
            '/ê/' => 'e',
            '/ë/' => 'e',
            '/ì/' => 'i',
            '/í/' => 'i',
            '/î/' => 'i',
            '/ï/' => 'i',
            '/ð/' => 'd',
            '/ñ/' => 'n',
            '/ò/' => 'o',
            '/ó/' => 'o',
            '/ô/' => 'o',
            '/õ/' => 'o',
            '/ö/' => 'o',
            '/ő/' => 'o',
            '/ø/' => 'o',
            '/ù/' => 'u',
            '/ú/' => 'u',
            '/û/' => 'u',
            '/ü/' => 'u',
            '/ű/' => 'u',
            '/ý/' => 'y',
            '/þ/' => 'th',
            '/ÿ/' => 'y',
            '/&quot;/' => '-',
            '/À/' => 'A',
            '/Á/' => 'A',
            '/Â/' => 'A',
            '/Ä/' => 'A',
            '/Ç/' => 'C',
            '/È/' => 'E',
            '/É/' => 'E',
            '/Ê/' => 'E',
            '/Ë/' => 'E',
            '/Ì/' => 'I',
            '/Í/' => 'I',
            '/Î/' => 'I',
            '/Ï/' => 'I',
            '/Ñ/' => 'N',
            '/Ò/' => 'O',
            '/Ó/' => 'O',
            '/Ô/' => 'O',
            '/Ö/' => 'O',
            '/Ù/' => 'U',
            '/Ú/' => 'U',
            '/Û/' => 'U',
            '/Ü/' => 'U',
            '/Ý/' => 'Y',
            '/Ÿ/' => 'Y',
        ];

        return preg_replace(array_keys($changes), $changes, $txt);
    }

    /**
     * TODO
     *
     * @param float $precio
     * @param string $coddivisa
     *
     * @return string
     */
    protected function showPrecio($precio, $coddivisa)
    {
        if (FS_POS_DIVISA === 'right') {
            return number_format($precio, FS_NF0, FS_NF1, FS_NF2) . ' ' . $coddivisa;
        }
        return $coddivisa . ' ' . number_format($precio, FS_NF0, FS_NF1, FS_NF2);
    }

    /**
     * TODO
     *
     * @param int $num
     * @param int $decimales
     *
     * @return string
     */
    protected function showNumero($num = 0, $decimales = FS_NF0)
    {
        return number_format($num, $decimales, FS_NF1, FS_NF2);
    }

    /**
     * TODO
     *
     * @param string $word
     * @param int $ancho
     *
     * @return string
     */
    private function centerText2($word = '', $ancho = 40)
    {
        $symbol = ' ';
        $middle = round($ancho / 2);
        $lengthWord = strlen($word);
        $middleWord = round($lengthWord / 2);
        $lastPosition = $middle + $middleWord;
        $numberOfSpaces = $middle - $middleWord;
        $result = sprintf("%'{$symbol}{$lastPosition}s", $word);
        for ($i = 0; $i < $numberOfSpaces; $i++) {
            $result .= "$symbol";
        }
        return $result;
    }
}
