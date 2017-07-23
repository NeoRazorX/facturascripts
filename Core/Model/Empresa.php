<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  carlos@facturascripts.com
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
use FacturaScripts\Core\Base\Utils;

/**
 * Esta clase almacena los principales datos de la empresa.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Empresa
{

    use Model;
    use Utils;

    /**
     * Clave primaria. Integer.
     * @var int
     */
    public $id;

    /**
     * TODO
     * @var string
     */
    public $xid;

    /**
     * Todavía sin uso.
     * @var bool
     */
    public $stockpedidos;

    /**
     * TRUE -> activa la contabilidad integrada. Se genera el asiento correspondiente
     * cada vez que se crea/modifica una factura.
     * @var bool
     */
    public $contintegrada;

    /**
     * TRUE -> activa el uso de recargo de equivalencia en los albaranes y facturas de compra.
     * @var bool
     */
    public $recequivalencia;

    /**
     * Código de la serie por defecto.
     * @var string
     */
    public $codserie;

    /**
     * Código del almacén predeterminado.
     * @var string
     */
    public $codalmacen;

    /**
     * Código de la forma de pago predeterminada.
     * @var string
     */
    public $codpago;

    /**
     * Código de la divisa predeterminada.
     * @var string
     */
    public $coddivisa;

    /**
     * Código del ejercicio predeterminado.
     * @var string
     */
    public $codejercicio;

    /**
     * URL de la web de la empresa.
     * @var string
     */
    public $web;

    /**
     * Dirección de email de la empresa.
     * @var string
     */
    public $email;

    /**
     * Número de fax de la empresa.
     * @var string
     */
    public $fax;

    /**
     * Número de teléfono de la empresa.
     * @var string
     */
    public $telefono;

    /**
     * Código del país predeterminado.
     * @var string
     */
    public $codpais;

    /**
     * Apartado de correos de la empresa.
     * @var string
     */
    public $apartado;

    /**
     * Provincia de la empresa.
     * @var string
     */
    public $provincia;

    /**
     * Ciudad de la empresa.
     * @var string
     */
    public $ciudad;

    /**
     * Código postal de la empresa.
     * @var string
     */
    public $codpostal;

    /**
     * Dirección de la empresa.
     * @var string
     */
    public $direccion;

    /**
     * Nombre del administrador de la empresa.
     * @var string
     */
    public $administrador;

    /**
     * Actualmente sin uso.
     * @var string
     */
    public $codedi;

    /**
     * Código de identificación fiscal dela empresa.
     * @var string
     */
    public $cifnif;

    /**
     * Nombre de la empresa.
     * @var string
     */
    public $nombre;

    /**
     * Nombre corto de la empresa, para mostrar en el menú
     * @var string Nombre a mostrar en el menú de facturaScripts.
     */
    public $nombrecorto;

    /**
     * Lema de la empresa
     * @var string
     */
    public $lema;

    /**
     * Horario de apertura
     * @var string
     */
    public $horario;

    /**
     * Texto al pié de las facturas de venta.
     * @var string
     */
    public $pie_factura;

    /**
     * Fecha de inicio de la actividad.
     * @var string
     */
    public $inicio_actividad;

    /**
     * Régimen de IVA de la empresa.
     * @var string
     */
    public $regimeniva;

    /**
     * Configuración de email de la empresa.
     * @var array de string
     * ] */
    public $email_config;

    /**
     * Empresa constructor.
     *
     * @param array $data
     */
    public function __construct($data = [])
    {
        $this->init(__CLASS__, 'empresa', 'id');
        $this->clear();
        if (is_array($data) && !empty($data)) {
            $this->loadFromData($data);

            /// cargamos las opciones de email por defecto
            $this->email_config = [
                'mail_password' => '',
                'mail_bcc' => '',
                'mail_firma' => "\n---\nEnviado con FacturaScripts",
                'mail_mailer' => 'smtp',
                'mail_host' => 'smtp.gmail.com',
                'mail_port' => '465',
                'mail_enc' => 'ssl',
                'mail_user' => '',
                'mail_low_security' => false,
            ];

            if ($this->xid === null) {
                $this->xid = static::randomString(30);
                $this->save();
            }
        }
    }

    /**
     * Devuelve la empresa predeterminada (la primera, por ahora).
     * @return Empresa|false
     */
    public function getDefault()
    {
        $emp = $this->all();
        if (!empty($emp)) {
            return $emp[0];
        }

        return false;
    }

    /**
     * Devuelve la url donde ver/modificar los datos
     * @return string
     */
    public function url()
    {
        return 'index.php?page=AdminEmpresa';
    }

    /**
     * Comprueba los datos de la empresa, devuelve TRUE si es correcto
     * @return bool
     */
    public function test()
    {
        $status = false;

        $this->nombre = static::noHtml($this->nombre);
        $this->nombrecorto = static::noHtml($this->nombrecorto);
        $this->administrador = static::noHtml($this->administrador);
        $this->apartado = static::noHtml($this->apartado);
        $this->cifnif = static::noHtml($this->cifnif);
        $this->ciudad = static::noHtml($this->ciudad);
        $this->codpostal = static::noHtml($this->codpostal);
        $this->direccion = static::noHtml($this->direccion);
        $this->email = static::noHtml($this->email);
        $this->fax = static::noHtml($this->fax);
        $this->horario = static::noHtml($this->horario);
        $this->lema = static::noHtml($this->lema);
        $this->pie_factura = static::noHtml($this->pie_factura);
        $this->provincia = static::noHtml($this->provincia);
        $this->telefono = static::noHtml($this->telefono);
        $this->web = static::noHtml($this->web);

        if (!(strlen($this->nombre) > 1) && !(strlen($this->nombre) < 100)) {
            $this->miniLog->alert($this->i18n->trans('company-name-invalid'));
        } elseif (strlen($this->nombre) < strlen($this->nombrecorto)) {
            $this->miniLog->alert($this->i18n->trans('company-short-name-smaller-name'));
        } else {
            $status = true;
        }

        return $status;
    }

    /**
     * Crea la consulta necesaria para dotar de datos a la empresa en la base de datos.
     * @return string
     */
    private function install()
    {
        $num = mt_rand(1, 9999);
        return 'INSERT INTO ' . $this->tableName() . ' (stockpedidos,contintegrada,recequivalencia,codserie,'
            . 'codalmacen,codpago,coddivisa,codejercicio,web,email,fax,telefono,codpais,apartado,provincia,'
            . 'ciudad,codpostal,direccion,administrador,codedi,cifnif,nombre,nombrecorto,lema,horario)'
            . "VALUES (NULL,FALSE,NULL,'A','ALG','CONT','EUR','0001','https://www.facturascripts.com',"
            . "NULL,NULL,NULL,'ESP',NULL,NULL,NULL,NULL,'C/ Falsa, 123','',NULL,'00000014Z',"
            . "'Empresa " . $num . " S.L.','E-" . $num . "','','');";
    }
}
