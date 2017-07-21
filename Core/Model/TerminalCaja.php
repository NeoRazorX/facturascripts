<?php

/*
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

use FacturaScripts\Core\Base\Model;

/**
 * Configuración de un terminal de TPV y de la impresora de tickets,
 * además almacena los tickets a imprimir.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class TerminalCaja
{
    use Model;

    /**
     *
     * @var type Clave primiaria.
     */
    public $id;

    /**
     * Códifo del almacén a usar en los tickets.
     * @var type 
     */
    public $codalmacen;

    /**
     * Código de la serie a utilizar en los tickets.
     * @var type 
     */
    public $codserie;

    /**
     * Código del cliente predeterminado para los tickets.
     * @var type 
     */
    public $codcliente;

    /**
     * Buffer con los ticket pendientes para imprimir.
     * @var type
     */
    public $tickets;

    /**
     * Número de caracteres que caben en una línea del papel del ticket.
     * @var type 
     */
    public $anchopapel;

    /**
     * Comando ESC/POS para cortar el papel.
     * @var type 
     */
    public $comandocorte;

    /**
     * Comando ESC/POS para abrir el cajón portamonedas conectado a la impresora.
     * @var type 
     */
    public $comandoapertura;

    /**
     * Número de impresiones para cada ticket.
     * @var integer 
     */
    public $num_tickets;

    /**
     * Desactivar los comandos ESC/POS para comprobaciones de la impresora de tickets.
     * @var type 
     */
    public $sin_comandos;

    public function __construct(array $data = []) 
	{
        $this->init(__CLASS__, 'cajas_terminales', 'id');
        if (!empty($data)) {
            $this->loadFromData($data);
        } else {
            $this->clear();
        }
    }
	
    public function clear()
    {
        $this->id = NULL;
        $this->codalmacen = NULL;
        $this->codserie = NULL;
        $this->codcliente = NULL;
        $this->tickets = '';
        $this->anchopapel = 40;
        $this->comandocorte = '27.105';
        $this->comandoapertura = '27.112.48';
        $this->num_tickets = 1;
        $this->sin_comandos = FALSE;
    }

    protected function install() {
        return '';
    }

    public function disponible() {
        if (self::$dataBase->select("SELECT * FROM cajas WHERE f_fin IS NULL AND fs_id = " . $this->var2str($this->id) . ";")) {
            return FALSE;
        } else
            return TRUE;
    }

    public function add_linea($linea) {
        $this->tickets .= $linea;
    }

    public function add_linea_big($linea) {
        if ($this->sin_comandos) {
            $this->tickets .= $linea;
        } else {
            $this->tickets .= chr(27) . chr(33) . chr(56) . $linea . chr(27) . chr(33) . chr(1);
        }
    }

    public function abrir_cajon() {
        if ($this->sin_comandos) {
            /// nada
        } else if ($this->comandoapertura) {
            $aux = explode('.', $this->comandoapertura);
            if ($aux) {
                foreach ($aux as $a) {
                    $this->tickets .= chr($a);
                }

                $this->tickets .= "\n";
            }
        }
    }

    public function cortar_papel() {
        if ($this->sin_comandos) {
            /// nada
        } else if ($this->comandocorte) {
            $aux = explode('.', $this->comandocorte);
            if ($aux) {
                foreach ($aux as $a) {
                    $this->tickets .= chr($a);
                }

                $this->tickets .= "\n";
            }
        }
    }

    public function center_text($word = '', $ancho = FALSE) {
        if (!$ancho) {
            $ancho = $this->anchopapel;
        }

        if (strlen($word) == $ancho) {
            return $word;
        } else if (strlen($word) < $ancho) {
            return $this->center_text2($word, $ancho);
        } else {
            $result = '';
            $nword = '';
            foreach (explode(' ', $word) as $aux) {
                if ($nword == '') {
                    $nword = $aux;
                } else if (strlen($nword) + strlen($aux) + 1 <= $ancho) {
                    $nword = $nword . ' ' . $aux;
                } else {
                    if ($result != '') {
                        $result .= "\n";
                    }

                    $result .= $this->center_text2($nword, $ancho);
                    $nword = $aux;
                }
            }
            if ($nword != '') {
                if ($result != '') {
                    $result .= "\n";
                }

                $result .= $this->center_text2($nword, $ancho);
            }

            return $result;
        }
    }

    private function center_text2($word = '', $ancho = 40) {
        $symbol = " ";
        $middle = round($ancho / 2);
        $length_word = strlen($word);
        $middle_word = round($length_word / 2);
        $last_position = $middle + $middle_word;
        $number_of_spaces = $middle - $middle_word;
        $result = sprintf("%'{$symbol}{$last_position}s", $word);
        for ($i = 0; $i < $number_of_spaces; $i++) {
            $result .= "$symbol";
        }
        return $result;
    }

    public function get($id) {
        $data = self::$dataBase->select("SELECT * FROM cajas_terminales WHERE id = " . $this->var2str($id) . ";");
        if ($data) {
            return new \terminal_caja($data[0]);
        } else
            return FALSE;
    }

    public function exists() {
        if (is_null($this->id)) {
            return FALSE;
        } else
            return self::$dataBase->select("SELECT * FROM cajas_terminales WHERE id = " . $this->var2str($this->id) . ";");
    }

    public function save() {
        if ($this->exists()) {
            $sql = "UPDATE cajas_terminales SET codalmacen = " . $this->var2str($this->codalmacen) .
                    ", codserie = " . $this->var2str($this->codserie) .
                    ", codcliente = " . $this->var2str($this->codcliente) .
                    ", tickets = " . $this->var2str($this->tickets) .
                    ", anchopapel = " . $this->var2str($this->anchopapel) .
                    ", comandocorte = " . $this->var2str($this->comandocorte) .
                    ", comandoapertura = " . $this->var2str($this->comandoapertura) .
                    ", num_tickets = " . $this->var2str($this->num_tickets) .
                    ", sin_comandos = " . $this->var2str($this->sin_comandos) .
                    "  WHERE id = " . $this->var2str($this->id) . ";";

            return self::$dataBase->exec($sql);
        } else {
            $sql = "INSERT INTO cajas_terminales (codalmacen,codserie,codcliente,tickets,anchopapel,"
                    . "comandocorte,comandoapertura,num_tickets,sin_comandos) VALUES (" .
                    $this->var2str($this->codalmacen) . "," .
                    $this->var2str($this->codserie) . "," .
                    $this->var2str($this->codcliente) . "," .
                    $this->var2str($this->tickets) . "," .
                    $this->var2str($this->anchopapel) . "," .
                    $this->var2str($this->comandocorte) . "," .
                    $this->var2str($this->comandoapertura) . "," .
                    $this->var2str($this->num_tickets) . "," .
                    $this->var2str($this->sin_comandos) . ");";

            if (self::$dataBase->exec($sql)) {
                $this->id = self::$dataBase->lastval();
                return TRUE;
            } else
                return FALSE;
        }
    }

    public function delete() {
        return self::$dataBase->exec("DELETE FROM cajas_terminales WHERE id = " . $this->var2str($this->id) . ";");
    }

    public function all() {
        $tlist = array();

        $data = self::$dataBase->select("SELECT * FROM cajas_terminales ORDER BY id ASC;");
        if ($data) {
            foreach ($data as $d) {
                $tlist[] = new \terminal_caja($d);
            }
        }

        return $tlist;
    }

    public function disponibles() {
        $tlist = array();
        $sql = "SELECT * FROM cajas_terminales WHERE id NOT IN "
                . "(SELECT fs_id as id FROM cajas WHERE f_fin IS NULL) "
                . "ORDER BY id ASC;";

        $data = self::$dataBase->select($sql);
        if ($data) {
            foreach ($data as $d) {
                $tlist[] = new \terminal_caja($d);
            }
        }

        return $tlist;
    }

    /**
     * A partir de una factura añade un ticket a la cola de impresión de este terminal.
     * @param \factura_cliente $factura
     * @param \empresa $empresa
     * @param type $imprimir_descripciones
     * @param type $imprimir_observaciones
     */
    public function imprimir_ticket(&$factura, &$empresa, $imprimir_descripciones = TRUE, $imprimir_observaciones = FALSE) {
        $medio = $this->anchopapel / 2.5;
        $this->add_linea_big($this->center_text($this->sanitize($empresa->nombre), $medio) . "\n");

        if ($empresa->lema != '') {
            $this->add_linea($this->center_text($this->sanitize($empresa->lema)) . "\n\n");
        } else
            $this->add_linea("\n");

        $this->add_linea(
                $this->center_text($this->sanitize($empresa->direccion) . " - " . $this->sanitize($empresa->ciudad)) . "\n"
        );
        $this->add_linea($this->center_text(FS_CIFNIF . ": " . $empresa->cifnif));
        $this->add_linea("\n\n");

        if ($empresa->horario != '') {
            $this->add_linea($this->center_text($this->sanitize($empresa->horario)) . "\n\n");
        }

        $linea = "\n" . ucfirst(FS_FACTURA_SIMPLIFICADA) . ": " . $factura->codigo . "\n";
        $linea .= $factura->fecha . " " . Date('H:i', strtotime($factura->hora)) . "\n";
        $this->add_linea($linea);
        $this->add_linea("Cliente: " . $this->sanitize($factura->nombrecliente) . "\n");
        $this->add_linea("Empleado: " . $factura->codagente . "\n\n");

        if ($imprimir_observaciones) {
            $this->add_linea('Observaciones: ' . $this->sanitize($factura->observaciones) . "\n\n");
        }

        $width = $this->anchopapel - 15;
        $this->add_linea(
                sprintf("%3s", "Ud.") . " " .
                sprintf("%-" . $width . "s", "Articulo") . " " .
                sprintf("%10s", "TOTAL") . "\n"
        );
        $this->add_linea(
                sprintf("%3s", "---") . " " .
                sprintf("%-" . $width . "s", substr("--------------------------------------------------------", 0, $width - 1)) . " " .
                sprintf("%10s", "----------") . "\n"
        );
        foreach ($factura->get_lineas() as $col) {
            if ($imprimir_descripciones) {
                $linea = sprintf("%3s", $col->cantidad) . " " . sprintf("%-" . $width . "s", substr($this->sanitize($col->descripcion), 0, $width - 1)) . " " .
                        sprintf("%10s", $this->show_numero($col->total_iva())) . "\n";
            } else {
                $linea = sprintf("%3s", $col->cantidad) . " " . sprintf("%-" . $width . "s", $this->sanitize($col->referencia))
                        . " " . sprintf("%10s", $this->show_numero($col->total_iva())) . "\n";
            }

            $this->add_linea($linea);
        }

        $lineaiguales = '';
        for ($i = 0; $i < $this->anchopapel; $i++) {
            $lineaiguales .= '=';
        }
        $this->add_linea($lineaiguales . "\n");
        $this->add_linea(
                'TOTAL A PAGAR: ' . sprintf("%" . ($this->anchopapel - 15) . "s", $this->show_precio($factura->total, $factura->coddivisa)) . "\n"
        );
        $this->add_linea($lineaiguales . "\n");

        /// imprimimos los impuestos desglosados
        $this->add_linea(
                'TIPO   BASE    ' . FS_IVA . '    RE' .
                sprintf('%' . ($this->anchopapel - 24) . 's', 'TOTAL') .
                "\n"
        );
        foreach ($factura->get_lineas_iva() as $imp) {
            $this->add_linea(
                    sprintf("%-6s", $imp->iva . '%') . ' ' .
                    sprintf("%-7s", $this->show_numero($imp->neto)) . ' ' .
                    sprintf("%-6s", $this->show_numero($imp->totaliva)) . ' ' .
                    sprintf("%-6s", $this->show_numero($imp->totalrecargo)) . ' ' .
                    sprintf('%' . ($this->anchopapel - 29) . 's', $this->show_numero($imp->totallinea)) .
                    "\n"
            );
        }

        $lineaiguales .= "\n\n\n\n\n\n\n\n";
        $this->add_linea($lineaiguales);
        $this->cortar_papel();
    }

    /**
     * A partir de una factura añade un ticket regalo a la cola de impresión de este terminal.
     * @param \factura_cliente $factura
     * @param \empresa $empresa
     */
    public function imprimir_ticket_regalo(&$factura, &$empresa, $imprimir_descripciones = TRUE, $imprimir_observaciones = FALSE) {
        $medio = $this->anchopapel / 2.5;
        $this->add_linea_big($this->center_text($this->sanitize($empresa->nombre), $medio) . "\n");

        if ($empresa->lema != '') {
            $this->add_linea($this->center_text($this->sanitize($empresa->lema)) . "\n\n");
        } else
            $this->add_linea("\n");

        $this->add_linea(
                $this->center_text($this->sanitize($empresa->direccion) . " - " . $this->sanitize($empresa->ciudad)) . "\n"
        );
        $this->add_linea($this->center_text(FS_CIFNIF . ": " . $empresa->cifnif));
        $this->add_linea("\n\n");

        if ($empresa->horario != '') {
            $this->add_linea($this->center_text($this->sanitize($empresa->horario)) . "\n\n");
        }

        $linea = "\n" . ucfirst(FS_FACTURA_SIMPLIFICADA) . ": " . $factura->codigo . "\n";
        $linea .= $factura->fecha . " " . Date('H:i', strtotime($factura->hora)) . "\n";
        $this->add_linea($linea);
        $this->add_linea("Cliente: " . $this->sanitize($factura->nombrecliente) . "\n");
        $this->add_linea("Empleado: " . $factura->codagente . "\n\n");

        if ($imprimir_observaciones) {
            $this->add_linea('Observaciones: ' . $this->sanitize($factura->observaciones) . "\n\n");
        }

        $width = $this->anchopapel - 15;
        $this->add_linea(
                sprintf("%3s", "Ud.") . " " .
                sprintf("%-" . $width . "s", "Articulo") . " " .
                sprintf("%10s", "TOTAL") . "\n"
        );
        $this->add_linea(
                sprintf("%3s", "---") . " " .
                sprintf("%-" . $width . "s", substr("--------------------------------------------------------", 0, $width - 1)) . " " .
                sprintf("%10s", "----------") . "\n"
        );
        foreach ($factura->get_lineas() as $col) {
            if ($imprimir_descripciones) {
                $linea = sprintf("%3s", $col->cantidad) . " " . sprintf("%-" . $width . "s", substr($this->sanitize($col->descripcion), 0, $width - 1)) . " " .
                        sprintf("%10s", '-') . "\n";
            } else {
                $linea = sprintf("%3s", $col->cantidad) . " " . sprintf("%-" . $width . "s", $this->sanitize($col->referencia))
                        . " " . sprintf("%10s", '-') . "\n";
            }

            $this->add_linea($linea);
        }


        $lineaiguales = '';
        for ($i = 0; $i < $this->anchopapel; $i++) {
            $lineaiguales .= '=';
        }
        $this->add_linea($lineaiguales);
        $this->add_linea($this->center_text('TICKET REGALO'));
        $lineaiguales .= "\n\n\n\n\n\n\n\n";
        $this->add_linea($lineaiguales);
        $this->cortar_papel();
    }

    public function sanitize($txt) {
        $changes = array('/à/' => 'a', '/á/' => 'a', '/â/' => 'a', '/ã/' => 'a', '/ä/' => 'a',
            '/å/' => 'a', '/æ/' => 'ae', '/ç/' => 'c', '/è/' => 'e', '/é/' => 'e', '/ê/' => 'e',
            '/ë/' => 'e', '/ì/' => 'i', '/í/' => 'i', '/î/' => 'i', '/ï/' => 'i', '/ð/' => 'd',
            '/ñ/' => 'n', '/ò/' => 'o', '/ó/' => 'o', '/ô/' => 'o', '/õ/' => 'o', '/ö/' => 'o',
            '/ő/' => 'o', '/ø/' => 'o', '/ù/' => 'u', '/ú/' => 'u', '/û/' => 'u', '/ü/' => 'u',
            '/ű/' => 'u', '/ý/' => 'y', '/þ/' => 'th', '/ÿ/' => 'y',
            '/&quot;/' => '-',
            '/À/' => 'A', '/Á/' => 'A', '/Â/' => 'A', '/Ä/' => 'A',
            '/Ç/' => 'C', '/È/' => 'E', '/É/' => 'E', '/Ê/' => 'E',
            '/Ë/' => 'E', '/Ì/' => 'I', '/Í/' => 'I', '/Î/' => 'I', '/Ï/' => 'I',
            '/Ñ/' => 'N', '/Ò/' => 'O', '/Ó/' => 'O', '/Ô/' => 'O', '/Ö/' => 'O',
            '/Ù/' => 'U', '/Ú/' => 'U', '/Û/' => 'U', '/Ü/' => 'U',
            '/Ý/' => 'Y', '/Ÿ/' => 'Y',
        );

        return preg_replace(array_keys($changes), $changes, $txt);
    }

    protected function show_precio($precio, $coddivisa) {
        if (FS_POS_DIVISA == 'right') {
            return number_format($precio, FS_NF0, FS_NF1, FS_NF2) . ' ' . $coddivisa;
        } else {
            return $coddivisa . ' ' . number_format($precio, FS_NF0, FS_NF1, FS_NF2);
        }
    }

    protected function show_numero($num = 0, $decimales = FS_NF0) {
        return number_format($num, $decimales, FS_NF1, FS_NF2);
    }

}
