<?php
/**
 * This file is part of facturacion_base
 * Copyright (C) 2015-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class TerminalCaja
{

    use Base\ModelTrait;

    /**
     * Clave primaria.
     *
     * @var int
     */
    public $id;

    /**
     * Código del almacén a usar en los tickets.
     *
     * @var string
     */
    public $codalmacen;

    /**
     * Código de la serie a utilizar en los tickets.
     *
     * @var string
     */
    public $codserie;

    /**
     * Código del cliente predeterminado para los tickets.
     *
     * @var string
     */
    public $codcliente;

    /**
     * Buffer con los ticket pendientes para imprimir.
     *
     * @var string
     */
    public $tickets;

    /**
     * Número de caracteres que caben en una línea del papel del ticket.
     *
     * @var int
     */
    public $anchopapel;

    /**
     * Comando ESC/POS para cortar el papel.
     *
     * @var string
     */
    public $comandocorte;

    /**
     * Comando ESC/POS para abrir el cajón portamonedas conectado a la impresora.
     *
     * @var string
     */
    public $comandoapertura;

    /**
     * Número de impresiones para cada ticket.
     *
     * @var int
     */
    public $num_tickets;

    /**
     * Desactivar los comandos ESC/POS para comprobaciones de la impresora de tickets.
     *
     * @var string
     */
    public $sin_comandos;

    /**
     * Devuelve el nombre de la tabla que usa este modelo.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'cajas_terminales';
    }

    /**
     * Devuelve el nombre de la columna que es clave primaria del modelo.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'id';
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
     * Devuelve si el terminal está o no disponible
     *
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
     * Añade una línea al tiquet
     *
     * @param string $linea
     */
    public function addLinea($linea)
    {
        $this->tickets .= $this->sanitize($linea);
    }

    /**
     * Añade una línea grande al tiquet
     *
     * @param string $linea
     */
    public function addLineaBig($linea)
    {
        if ($this->sin_comandos) {
            $this->tickets .= $this->sanitize($linea);
        } else {
            $this->tickets .= chr(27) . chr(33) . chr(56) . $this->sanitize($linea) . chr(27) . chr(33) . chr(1);
        }
    }

    /**
     * Abre el cajón del terminal
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

                $this->tickets .= PHP_EOL;
            }
        }
    }

    /**
     * Corta el papel del ticket
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

                $this->tickets .= PHP_EOL;
            }
        }
    }

    /**
     * Centra el texto del ticket
     *
     * @param string $word
     * @param int    $ancho
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
                    $result .= PHP_EOL;
                }

                $result .= $this->centerText2($nword, $ancho);
                $nword = $aux;
            }
        }
        if ($nword !== '') {
            if ($result !== '') {
                $result .= PHP_EOL;
            }

            $result .= $this->centerText2($nword, $ancho);
        }

        return $result;
    }

    /**
     * Devuelve un listao de los terminales disponibles
     *
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
                $tlist[] = new self($d);
            }
        }

        return $tlist;
    }

    /**
     * A partir de una factura añade un ticket a la cola de impresión de este terminal.
     *
     * @param FacturaCliente $factura
     * @param Empresa        $empresa
     * @param bool           $imprimirDescripciones
     * @param bool           $imprimirObservaciones
     */
    public function imprimirTicket(&$factura, &$empresa, $imprimirDescripciones = true, $imprimirObservaciones = false)
    {
        $medio = $this->anchopapel / 2.5;
        $this->addLineaBig($this->centerText($empresa->nombre, $medio) . PHP_EOL);

        if ($empresa->lema !== '') {
            $this->addLinea($this->centerText($empresa->lema) . PHP_EOL . PHP_EOL);
        } else {
            $this->addLinea(PHP_EOL);
        }

        $this->addLinea(
            $this->centerText($empresa->direccion . ' - ' . $empresa->ciudad) . PHP_EOL
        );
        $this->addLinea($this->centerText($this->i18n->trans('cifnif') . ': ' . $empresa->cifnif));
        $this->addLinea(PHP_EOL . PHP_EOL);

        if ($empresa->horario !== '') {
            $this->addLinea($this->centerText($empresa->horario) . PHP_EOL . PHP_EOL);
        }

        $linea = PHP_EOL . ucfirst($this->i18n->trans('simplified-invoice')) . ': ' . $factura->codigo . PHP_EOL;
        $linea .= $factura->fecha . ' ' . date('H:i', strtotime($factura->hora)) . PHP_EOL;
        $this->addLinea($linea);
        $this->addLinea($this->i18n->trans('customer') . ': ' . $factura->nombrecliente . PHP_EOL);
        $this->addLinea($this->i18n->trans('employee') . ': ' . $factura->codagente . PHP_EOL . PHP_EOL);

        if ($imprimirObservaciones) {
            $this->addLinea($this->i18n->trans('observations') . ': ' . $factura->observaciones . PHP_EOL . PHP_EOL);
        }

        $width = $this->anchopapel - 15;
        $this->addLinea(sprintf('%3s', $this->i18n->trans('units_short')) . ' ' .
            sprintf('%-' . $width . 's', $this->i18n->trans('product')) . ' ' .
            sprintf('%10s', $this->i18n->trans('total-caps')) . PHP_EOL);
        $this->addLinea(
            sprintf('%3s', '---') . ' ' . sprintf(
                '%-' . $width . 's', substr(
                    '--------------------------------------------------------', 0, $width - 1
                )
            ) . ' ' .
            sprintf('%10s', '----------') . PHP_EOL
        );
        foreach ($factura->getLineas() as $col) {
            if ($imprimirDescripciones) {
                $linea = sprintf('%3s', $col->cantidad) . ' ' . sprintf(
                        '%-' . $width . 's', substr($col->descripcion, 0, $width - 1)
                    ) . ' ' . sprintf('%10s', $this->showNumero($col->totalIva())) . PHP_EOL;
            } else {
                $linea = sprintf('%3s', $col->cantidad) . ' ' . sprintf(
                        '%-' . $width . 's', $col->referencia
                    ) . ' ' . sprintf('%10s', $this->showNumero($col->totalIva())) . PHP_EOL;
            }

            $this->addLinea($linea);
        }

        $lineaiguales = '';
        for ($i = 0; $i < $this->anchopapel; ++$i) {
            $lineaiguales .= '=';
        }
        $this->addLinea($lineaiguales . PHP_EOL);
        $this->addLinea($this->i18n->trans('total-pay-caps') . ': ' . sprintf(
                '%' . ($this->anchopapel - 15) . 's', $this->showPrecio($factura->total, $factura->coddivisa)
            ) . PHP_EOL);
        $this->addLinea($lineaiguales . PHP_EOL);

        /// imprimimos los impuestos desglosados
        $this->addLinea(
            $this->i18n->trans('ticket-footer', [FS_IVA]) .
            sprintf('%' . ($this->anchopapel - 24) . 's', $this->i18n->trans('total-caps')) .
            PHP_EOL
        );
        foreach ($factura->getLineasIva() as $imp) {
            $this->addLinea(
                sprintf('%-6s', $imp->iva . '%') . ' ' .
                sprintf('%-7s', $this->showNumero($imp->neto)) . ' ' .
                sprintf('%-6s', $this->showNumero($imp->totaliva)) . ' ' .
                sprintf('%-6s', $this->showNumero($imp->totalrecargo)) . ' ' .
                sprintf('%' . ($this->anchopapel - 29) . 's', $this->showNumero($imp->totallinea)) .
                PHP_EOL
            );
        }

        $lineaiguales .= PHP_EOL . PHP_EOL . PHP_EOL . PHP_EOL . PHP_EOL . PHP_EOL . PHP_EOL . PHP_EOL;
        $this->addLinea($lineaiguales);
        $this->cortarPapel();
    }

    /**
     * A partir de una factura añade un ticket regalo a la cola de impresión de este terminal.
     *
     * @param FacturaCliente $factura
     * @param Empresa        $empresa
     * @param bool           $imprimirDescripciones
     * @param bool           $imprimirObservaciones
     */
    public function imprimirTicketRegalo(&$factura, &$empresa, $imprimirDescripciones = true, $imprimirObservaciones = false)
    {
        $medio = $this->anchopapel / 2.5;
        $this->addLineaBig($this->centerText($empresa->nombre, $medio) . PHP_EOL);

        if ($empresa->lema !== '') {
            $this->addLinea($this->centerText($empresa->lema) . PHP_EOL . PHP_EOL);
        } else {
            $this->addLinea(PHP_EOL);
        }

        $this->addLinea($this->centerText($empresa->direccion . ' - '
                . $empresa->ciudad) . PHP_EOL);
        $this->addLinea($this->centerText($this->i18n->trans('cifnif') . ': ' . $empresa->cifnif));
        $this->addLinea(PHP_EOL . PHP_EOL);

        if ($empresa->horario !== '') {
            $this->addLinea($this->centerText($empresa->horario) . PHP_EOL . PHP_EOL);
        }

        $linea = PHP_EOL . ucfirst($this->i18n->trans('simplified-invoice')) . ': ' . $factura->codigo . PHP_EOL;
        $linea .= $factura->fecha . ' ' . date('H:i', strtotime($factura->hora)) . PHP_EOL;
        $this->addLinea($linea);
        $this->addLinea($this->i18n->trans('customer') . ': ' . $factura->nombrecliente . PHP_EOL);
        $this->addLinea($this->i18n->trans('employee') . ': ' . $factura->codagente . PHP_EOL . PHP_EOL);

        if ($imprimirObservaciones) {
            $this->addLinea($this->i18n->trans('observations') . ': ' . $factura->observaciones . PHP_EOL . PHP_EOL);
        }

        $width = $this->anchopapel - 15;
        $this->addLinea(sprintf('%3s', $this->i18n->trans('units_short')) . ' ' .
            sprintf('%-' . $width . 's', $this->i18n->trans('product')) . ' ' .
            sprintf('%10s', $this->i18n->trans('total-caps')) . PHP_EOL);
        $this->addLinea(
            sprintf('%3s', '---') . ' ' . sprintf(
                '%-' . $width . 's', substr('--------------------------------------------------------', 0, $width - 1)
            ) . ' ' .
            sprintf('%10s', '----------') . PHP_EOL
        );
        foreach ($factura->getLineas() as $col) {
            if ($imprimirDescripciones) {
                $linea = sprintf('%3s', $col->cantidad) . ' ' . sprintf(
                        '%-' . $width . 's', substr($col->descripcion, 0, $width - 1)
                    ) . ' ' . sprintf('%10s', '-') . PHP_EOL;
            } else {
                $linea = sprintf('%3s', $col->cantidad) . ' ' . sprintf(
                        '%-' . $width . 's', $col->referencia
                    ) . ' ' . sprintf('%10s', '-') . PHP_EOL;
            }

            $this->addLinea($linea);
        }

        $lineaiguales = '';
        for ($i = 0; $i < $this->anchopapel; ++$i) {
            $lineaiguales .= '=';
        }
        $this->addLinea($lineaiguales);
        $this->addLinea($this->centerText($this->i18n->trans('gift-ticket')));
        $lineaiguales .= PHP_EOL . PHP_EOL . PHP_EOL . PHP_EOL . PHP_EOL . PHP_EOL . PHP_EOL . PHP_EOL;
        $this->addLinea($lineaiguales);
        $this->cortarPapel();
    }

    /**
     * Sanea el texto a imprimir
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
     * Muestra el precio formateado
     *
     * @param float  $precio
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
     * Muestra el número formateado
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
     * Centra el texto
     *
     * @param string $word
     * @param int    $ancho
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
        for ($i = 0; $i < $numberOfSpaces; ++$i) {
            $result .= "$symbol";
        }

        return $result;
    }
}
