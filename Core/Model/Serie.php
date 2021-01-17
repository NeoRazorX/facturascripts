<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Model;

/**
 * A series of invoicing or accounting, to have different numbering
 * in each series.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Serie extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     *
     * @var int
     */
    public $canal;

    /**
     * Primary key. Varchar (4).
     *
     * @var string
     */
    public $codserie;

    /**
     * Description of the billing series.
     *
     * @var string
     */
    public $descripcion;

    /**
     *
     * @var int
     */
    public $iddiario;

    /**
     * If associated invoices are without tax True, else False.
     *
     * @var bool
     */
    public $siniva;

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->siniva = false;
    }

    /**
     * Removed payment method from database.
     * 
     * @return bool
     */
    public function delete()
    {
        if ($this->isDefault()) {
            $this->toolBox()->i18nLog()->warning('cant-delete-default-serie');
            return false;
        }

        return parent::delete();
    }

    /**
     * 
     * @return string
     */
    public function install()
    {
        /// neede dependencies
        new Diario();

        return parent::install();
    }

    /**
     * Returns True if this is the default serie.
     *
     * @return bool
     */
    public function isDefault()
    {
        return $this->codserie === $this->toolBox()->appSettings()->get('default', 'codserie');
    }

    /**
     * Returns the name of the column that is the primary key of the model.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'codserie';
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'series';
    }

    /**
     * Returns True if there is no erros on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $this->codserie = \trim($this->codserie);
        if ($this->codserie && 1 !== \preg_match('/^[A-Z0-9_\+\.\-]{1,4}$/i', $this->codserie)) {
            $this->toolBox()->i18nLog()->error(
                'invalid-alphanumeric-code',
                ['%value%' => $this->codserie, '%column%' => 'codserie', '%min%' => '1', '%max%' => '4']
            );
            return false;
        }

        $this->descripcion = $this->toolBox()->utils()->noHtml($this->descripcion);
        return parent::test();
    }

    /**
     * 
     * @param array $values
     *
     * @return bool
     */
    protected function saveInsert(array $values = [])
    {
        if (empty($this->codserie)) {
            $this->codserie = (string) $this->newCode();
        }

        return parent::saveInsert($values);
    }
}
