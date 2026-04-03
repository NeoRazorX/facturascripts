<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Model\Base;

use FacturaScripts\Core\Template\ModelClass as NewModelClass;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\DocTransformation;
use FacturaScripts\Dinamic\Model\Impuesto;
use FacturaScripts\Dinamic\Model\Producto;
use FacturaScripts\Dinamic\Model\Stock;
use FacturaScripts\Dinamic\Model\Variante;

/**
 * Linea de documento de negocio.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
abstract class BusinessDocumentLine extends NewModelClass
{
    use TaxRelationTrait;

    /**
     * Estado de actualización de stock.
     *
     * @var int
     */
    public $actualizastock;

    /**
     * Cantidad.
     *
     * @var float|int
     */
    public $cantidad;

    /**
     * Descripción de la línea.
     *
     * @var string
     */
    public $descripcion;

    /** @var bool */
    private $disable_update_stock = false;

    /** @var array */
    protected static $dont_copy_fields = ['idlinea', 'orden', 'servido'];

    /**
     * Porcentaje de descuento.
     *
     * @var float|int
     */
    public $dtopor;

    /**
     * Porcentaje de segundo descuento.
     *
     * @var float|int
     */
    public $dtopor2;

    /** @var string */
    public $excepcioniva;

    /**
     * Clave primaria.
     *
     * @var int
     */
    public $idlinea;

    /** @var int */
    public $idproducto;

    /**
     * % de IRPF de la línea.
     *
     * @var float|int
     */
    public $irpf;

    /**
     * % del impuesto relacionado.
     *
     * @var float|int
     */
    public $iva;

    /**
     * Posición de la línea en el documento. Cuanto mayor, más abajo.
     *
     * @var int
     */
    public $orden;

    /**
     * Importe neto sin descuentos.
     *
     * @var float|int
     */
    public $pvpsindto;

    /**
     * Importe neto de la línea, sin impuestos.
     *
     * @var float|int
     */
    public $pvptotal;

    /**
     * Precio del artículo, una unidad.
     *
     * @var float|int
     */
    public $pvpunitario;

    /**
     * % de recargo de equivalencia de la línea.
     *
     * @var float|int
     */
    public $recargo;

    /**
     * Referencia del artículo.
     *
     * @var string
     */
    public $referencia;

    /**
     * Servido.
     *
     * @var float|int
     */
    public $servido;

    /** @var bool */
    public $suplido;

    /**
     * Devuelve el documento padre de esta línea.
     */
    abstract public function getDocument();

    /**
     * Devuelve el nombre de la columna que almacena el identificador del documento.
     */
    abstract public function documentColumn();

    public function clear(): void
    {
        parent::clear();

        $this->actualizastock = 0;
        $this->cantidad = 1.0;
        $this->descripcion = '';
        $this->dtopor = 0.0;
        $this->dtopor2 = 0.0;
        $this->irpf = 0.0;
        $this->orden = 0;
        $this->pvpsindto = 0.0;
        $this->pvptotal = 0.0;
        $this->pvpunitario = 0.0;
        $this->servido = 0.0;
        $this->suplido = false;

        // impuesto por defecto
        $this->codimpuesto = Tools::settings('default', 'codimpuesto');
        $this->iva = $this->getTax()->iva;
        $this->recargo = $this->getTax()->recargo;
    }

    public function disableUpdateStock(bool $value): void
    {
        $this->disable_update_stock = $value;
    }

    /**
     * Devuelve el identificador del documento.
     *
     * @return int
     */
    public function documentColumnValue()
    {
        return $this->{$this->documentColumn()};
    }

    /**
     * Devuelve las líneas hijas generadas desde esta línea.
     *
     * @return BusinessDocumentLine[]
     */
    public function childrenLines(): array
    {
        if (empty($this->documentColumnValue()) || empty($this->id())) {
            return [];
        }

        $children = [];
        $keys = [];
        $where = [
            Where::eq('model1', $this->getDocument()->modelClassName()),
            Where::eq('iddoc1', $this->documentColumnValue()),
            Where::eq('idlinea1', $this->id())
        ];
        foreach (DocTransformation::all($where, ['id' => 'ASC'], 0, 0) as $docTrans) {
            $childLine = $docTrans->getChildLine();
            if (false === $childLine->exists()) {
                continue;
            }

            $key = $docTrans->model2 . '|' . $docTrans->iddoc2 . '|' . $docTrans->idlinea2;
            if (in_array($key, $keys, true)) {
                continue;
            }

            $children[] = $childLine;
            $keys[] = $key;
        }

        return $children;
    }

    public static function dontCopyField(string $field): void
    {
        static::$dont_copy_fields[] = $field;
    }

    public static function dontCopyFields(): array
    {
        $more = [static::primaryColumn()];
        return array_merge(static::$dont_copy_fields, $more);
    }

    public function getDisableUpdateStock(): bool
    {
        return $this->disable_update_stock;
    }

    /**
     * Devuelve la línea padre desde la que se generó esta línea.
     */
    public function getParentLine(): ?BusinessDocumentLine
    {
        if (empty($this->documentColumnValue()) || empty($this->id())) {
            return null;
        }

        $where = [
            Where::eq('model2', $this->getDocument()->modelClassName()),
            Where::eq('iddoc2', $this->documentColumnValue()),
            Where::eq('idlinea2', $this->id())
        ];
        foreach (DocTransformation::all($where) as $docTrans) {
            $parentLine = $docTrans->getParentLine();
            if ($parentLine->exists()) {
                return $parentLine;
            }
        }

        return null;
    }

    /**
     * Devuelve el Descuento Unificado Equivalente.
     *
     * @return float
     */
    public function getEUDiscount(): float
    {
        $eud = 1.0;
        foreach ([$this->dtopor, $this->dtopor2] as $dto) {
            $eud *= 1 - $dto / 100;
        }

        return $eud;
    }

    public function getOriginal(?string $key = null)
    {
        $original = parent::getOriginal($key);
        if (is_null($original)) {
            switch ($key) {
                case 'actualizastock':
                    $original = 0;
                    break;

                case 'servido':
                case 'cantidad':
                    $original = 0.0;
                    break;
            }
        }

        return $original;
    }

    public function getProducto(): Producto
    {
        $producto = new Producto();

        // por compatibilidad buscamos por referencia
        if (empty($this->idproducto) && !empty($this->referencia)) {
            $this->idproducto = $this->getVariante()->idproducto;
        }

        $producto->load($this->idproducto);
        return $producto;
    }

    public function getVariante(): Variante
    {
        $variante = new Variante();
        $variante->loadWhereEq('referencia', $this->referencia);
        return $variante;
    }

    public function install(): string
    {
        // dependencias necesarias
        new Impuesto();
        new Producto();

        return parent::install();
    }

    public static function primaryColumn(): string
    {
        return 'idlinea';
    }

    public function save(): bool
    {
        $done = parent::save();

        $this->disableUpdateStock(false);

        return $done;
    }

    public function setPriceWithTax(float $price): void
    {
        $newPrice = (100 * $price) / (100 + $this->getTax()->iva);
        $this->pvpunitario = round($newPrice, Producto::ROUND_DECIMALS);
    }

    public function setTax(?string $codimpuesto): void
    {
        if (empty($codimpuesto)) {
            $this->codimpuesto = null;
            $this->iva = 0.0;
            $this->recargo = 0.0;
            return;
        }

        $this->codimpuesto = $codimpuesto;
        $this->iva = $this->getTax()->iva;
        $this->recargo = $this->getTax()->recargo;
    }

    /**
     * Transfiere el stock de la línea de un almacén a otro.
     *
     * @param string $fromCodalmacen
     * @param string $toCodalmacen
     *
     * @return bool
     */
    public function transfer($fromCodalmacen, $toCodalmacen): bool
    {
        // buscamos el stock
        $fromStock = new Stock();
        $where = [
            Where::eq('codalmacen', $fromCodalmacen),
            Where::eq('referencia', $this->referencia)
        ];
        if (empty($this->referencia) || false === $fromStock->loadWhere($where)) {
            // no es necesario transferir
            return true;
        }
        $this->applyStockChanges(
            $fromStock,
            $this->getOriginal('actualizastock'),
            $this->getOriginal('cantidad') * -1,
            $this->getOriginal('servido') * -1
        );
        $fromStock->save();

        // buscamos el nuevo stock
        $toStock = new Stock();
        $where2 = [
            Where::eq('codalmacen', $toCodalmacen),
            Where::eq('referencia', $this->referencia)
        ];
        if (false === $toStock->loadWhere($where2)) {
            // stock no encontrado, creamos uno
            $toStock->codalmacen = $toCodalmacen;
            $toStock->idproducto = $this->idproducto ?? $this->getProducto()->idproducto;
            $toStock->referencia = $this->referencia;
        }

        $this->applyStockChanges($toStock, $this->actualizastock, $this->cantidad, $this->servido);
        if (false === $toStock->save()) {
            return false;
        }

        $this->pipe('transfer', $fromCodalmacen, $toCodalmacen, $this->getDocument());

        return true;
    }

    public function test(): bool
    {
        if (empty($this->codimpuesto)) {
            $this->codimpuesto = null;
            $this->iva = 0.0;
            $this->recargo = 0.0;
        }

        if ($this->servido < 0 && $this->cantidad >= 0) {
            $this->servido = 0.0;
        }

        $this->descripcion = Tools::noHtml($this->descripcion);
        $this->referencia = Tools::noHtml($this->referencia);

        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'List'): string
    {
        $name = str_replace('Linea', '', $this->modelClassName());
        return $type === 'new' ?
            'Edit' . $name :
            parent::url($type, 'List' . $name . '?activetab=List');
    }

    /**
     * Aplica las modificaciones de stock según el $mode.
     *
     * @param Stock $stock
     * @param int $mode
     * @param float $quantity
     * @param float $served
     */
    private function applyStockChanges(&$stock, int $mode, float $quantity, float $served): void
    {
        if ($quantity < 0 && $served < $quantity) {
            $served = $quantity;
        }

        switch ($mode) {
            case 1:
            case -1:
                $stock->cantidad += $mode * ($quantity - $served);
                break;

            case 2:
                $stock->pterecibir += $quantity - $served;
                break;

            case -2:
                $stock->reservada += $quantity - $served;
                break;
        }
    }

    /**
     * Este método se ejecuta antes de guardar (update) cuando algún campo ha cambiado.
     *
     * @param string $field
     *
     * @return bool
     */
    protected function onChange(string $field): bool
    {
        switch ($field) {
            case 'actualizastock':
            case 'cantidad':
            case 'servido':
                return $this->updateStock() && parent::onChange($field);
        }

        return parent::onChange($field);
    }

    /**
     * Este método se ejecuta después de eliminar este registro de la base de datos.
     */
    protected function onDelete(): void
    {
        $this->cantidad = 0.0;
        $this->updateStock();

        parent::onDelete();
    }

    protected function saveInsert(): bool
    {
        return $this->updateStock() && parent::saveInsert();
    }

    /**
     * Actualiza el stock según los datos de la línea y el almacén.
     *
     * @return bool
     */
    protected function updateStock(): bool
    {
        if ($this->disable_update_stock) {
            return true;
        } elseif (empty($this->actualizastock) && empty($this->getOriginal('actualizastock'))) {
            return true;
        }

        // buscamos la variante
        $variante = new Variante();
        $where = [Where::eq('referencia', $this->referencia)];
        if (empty($this->referencia) || false === $variante->loadWhere($where)) {
            return true;
        }

        // buscamos el producto
        $producto = $variante->getProducto();
        if ($producto->nostock) {
            return true;
        }

        // buscamos el stock
        $stock = new Stock();
        $doc = $this->getDocument();
        $where2 = [
            Where::eq('codalmacen', $doc->codalmacen),
            Where::eq('referencia', $this->referencia)
        ];
        if (false === $stock->loadWhere($where2)) {
            // stock no encontrado, creamos uno
            $stock->codalmacen = $doc->codalmacen;
            $stock->idproducto = $this->idproducto ?? $this->getProducto()->idproducto;
            $stock->referencia = $this->referencia;
        }

        $this->applyStockChanges(
            $stock,
            $this->getOriginal('actualizastock'),
            $this->getOriginal('cantidad') * -1,
            $this->getOriginal('servido') * -1
        );

        $this->applyStockChanges($stock, $this->actualizastock, $this->cantidad, $this->servido);

        // ¿hay suficiente stock?
        if (false === $producto->ventasinstock && $this->actualizastock === -1 && $stock->cantidad < 0) {
            Tools::log()->warning('not-enough-stock', ['%reference%' => $this->referencia]);
            return false;
        }

        if (false === $stock->save()) {
            Tools::log()->warning('cant-update-stock', [
                '%reference%' => $this->referencia,
                '%codalmacen%' => $doc->codalmacen,
            ]);
            return false;
        }

        $this->pipe('updateStock', $doc);

        $this->disable_update_stock = true;

        return true;
    }
}
