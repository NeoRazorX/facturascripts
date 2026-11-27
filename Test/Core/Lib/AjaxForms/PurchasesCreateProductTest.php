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

namespace FacturaScripts\Test\Core\Lib\AjaxForms;

use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Controller\EditPresupuestoProveedor;
use FacturaScripts\Core\Lib\AjaxForms\PurchasesModalHTML;
use FacturaScripts\Core\Request;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Session;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\PresupuestoProveedor;
use FacturaScripts\Dinamic\Model\Producto;
use FacturaScripts\Dinamic\Model\ProductoImagen;
use FacturaScripts\Dinamic\Model\ProductoProveedor;
use FacturaScripts\Dinamic\Model\Proveedor;
use FacturaScripts\Dinamic\Model\Stock;
use FacturaScripts\Dinamic\Model\User;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

/**
 * Alta de productos desde el modal de productos de los documentos de compra.
 */
final class PurchasesCreateProductTest extends TestCase
{
    use DefaultSettingsTrait;
    use LogErrorsTrait;
    use RandomDataTrait;

    /** @var PresupuestoProveedor */
    private $doc;

    /** @var string[] */
    private $references = [];

    /** @var Proveedor */
    private $supplier;

    /** @var User[] */
    private $users = [];

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();

        // el modal consulta los stocks y al borrar el producto se consultan sus imágenes;
        // estas tablas no se pueden crear dentro de una transacción
        new Stock();
        new ProductoImagen();
    }

    public function testCreateProductAddsLineAndSupplierPrice(): void
    {
        $reference = $this->newReference();
        $supplierRef = 'PRV-' . mt_rand(1, 99999);
        $response = $this->runCreateProduct($this->createUser(true), true, [
            'newproduct_coste' => '12.5',
            'newproduct_descripcion' => 'Producto desde compras',
            'newproduct_precio' => '20',
            'newproduct_referencia' => $reference,
            'newproduct_refproveedor' => $supplierRef,
        ]);

        $this->assertTrue($response['productCreated'], 'product-not-created');

        $product = new Producto();
        $this->assertTrue($product->loadWhereEq('referencia', $reference), 'product-not-found');
        $this->assertSame('Producto desde compras', $product->descripcion);
        $this->assertSame(Tools::settings('default', 'codimpuesto'), $product->codimpuesto);
        $this->assertEquals(20, $product->getVariants()[0]->precio);

        // el precio y la referencia del proveedor del documento
        $supplierProduct = new ProductoProveedor();
        $where = [
            Where::eq('codproveedor', $this->supplier->codproveedor),
            Where::eq('referencia', $reference),
        ];
        $this->assertTrue($supplierProduct->loadWhere($where), 'supplier-product-not-found');
        $this->assertSame($supplierRef, $supplierProduct->refproveedor);
        $this->assertSame($this->doc->coddivisa, $supplierProduct->coddivisa);
        $this->assertEquals(12.5, $supplierProduct->precio);

        // el producto se añade como línea del documento, con el precio del proveedor
        $this->assertStringContainsString('value="' . $reference . '"', $response['lines']);
        $this->assertStringContainsString('value="12.5"', $response['lines']);
    }

    public function testCreateProductRequiresDescription(): void
    {
        $reference = $this->newReference();
        $response = $this->runCreateProduct($this->createUser(true), true, [
            'newproduct_descripcion' => '   ',
            'newproduct_referencia' => $reference,
        ]);

        $this->assertFalse($response['productCreated']);
        $this->assertFalse((new Producto())->loadWhereEq('referencia', $reference), 'product-created-without-description');
    }

    public function testCreateProductRequiresPermission(): void
    {
        $reference = $this->newReference();
        $response = $this->runCreateProduct($this->createUser(false), true, [
            'newproduct_descripcion' => 'Sin permiso',
            'newproduct_referencia' => $reference,
        ]);

        $this->assertFalse($response['productCreated']);
        $this->assertFalse((new Producto())->loadWhereEq('referencia', $reference), 'product-created-without-permission');
    }

    public function testCreateProductRequiresToken(): void
    {
        $reference = $this->newReference();
        $response = $this->runCreateProduct($this->createUser(true), false, [
            'newproduct_descripcion' => 'Token incorrecto',
            'newproduct_referencia' => $reference,
        ]);

        $this->assertFalse($response['productCreated']);
        $this->assertFalse((new Producto())->loadWhereEq('referencia', $reference), 'product-created-with-invalid-token');
    }

    public function testCreateProductWithoutSupplierSetsCost(): void
    {
        $reference = $this->newReference();
        $response = $this->runCreateProduct($this->createUser(true), true, [
            'newproduct_coste' => '7',
            'newproduct_descripcion' => 'Producto sin proveedor',
            'newproduct_referencia' => $reference,
        ], false);

        $this->assertTrue($response['productCreated'], 'product-not-created');

        $product = new Producto();
        $this->assertTrue($product->loadWhereEq('referencia', $reference), 'product-not-found');
        $this->assertEquals(7, $product->getVariants()[0]->coste);
        $this->assertEmpty(ProductoProveedor::all([Where::eq('referencia', $reference)]));
    }

    public function testModalShowsNewProductOnlyWithPermission(): void
    {
        Session::set('user', $this->createUser(true));
        $html = PurchasesModalHTML::render($this->doc);
        $this->assertStringContainsString('id="newProductModal"', $html);
        $this->assertStringContainsString('showNewProductModal()', $html);

        Session::set('user', $this->createUser(false));
        $html = PurchasesModalHTML::render($this->doc);
        $this->assertStringContainsString('id="findProductModal"', $html);
        $this->assertStringNotContainsString('id="newProductModal"', $html);
        $this->assertStringNotContainsString('showNewProductModal()', $html);
    }

    protected function setUp(): void
    {
        MiniLog::clear();

        $this->supplier = $this->getRandomSupplier();
        $this->assertTrue($this->supplier->save(), 'supplier-cant-save');

        $this->doc = new PresupuestoProveedor();
        $this->assertTrue($this->doc->setSubject($this->supplier), 'can-not-set-subject');
        $this->assertTrue($this->doc->save(), 'doc-cant-save');
    }

    protected function tearDown(): void
    {
        Session::set('user', new User());

        foreach ($this->references as $reference) {
            $product = new Producto();
            if ($product->loadWhereEq('referencia', $reference)) {
                $product->delete();
            }
        }

        $this->doc->delete();
        $this->supplier->getDefaultAddress()->delete();
        $this->supplier->delete();

        foreach ($this->users as $user) {
            $user->delete();
        }

        $this->logErrors();
    }

    private function createUser(bool $admin): User
    {
        $user = $this->getRandomUser();
        $user->admin = $admin;
        $this->assertTrue($user->save(), 'user-cant-save');
        $this->users[] = $user;
        return $user;
    }

    private function newReference(): string
    {
        $reference = 'test-np-' . mt_rand(1, 999999);
        $this->references[] = $reference;
        return $reference;
    }

    /**
     * Ejecuta la acción create-product del controlador y devuelve la respuesta json.
     *
     * @param array $fields Campos del modal de nuevo producto.
     * @param bool $withDoc Si es false, se usa un documento nuevo, sin proveedor.
     */
    private function runCreateProduct(User $user, bool $validToken, array $fields, bool $withDoc = true): array
    {
        $controller = new EditPresupuestoProveedor('EditPresupuestoProveedor', '/EditPresupuestoProveedor');
        $controller->multiRequestProtection->clearSeed();
        $controller->multiRequestProtection->addSeed($user->nick);
        $token = $controller->multiRequestProtection->newToken();
        $controller->multiRequestProtection->clearSeed();

        $formData = array_merge([
            'action' => 'create-product',
            'codproveedor' => $withDoc ? $this->supplier->codproveedor : '',
            'newproduct_codfabricante' => '',
            'newproduct_codfamilia' => '',
            'newproduct_codimpuesto' => Tools::settings('default', 'codimpuesto'),
            'newproduct_coste' => '',
            'newproduct_descripcion' => '',
            'newproduct_precio' => '',
            'newproduct_referencia' => '',
            'newproduct_refproveedor' => '',
            'selectedLine' => '0',
        ], $fields);

        $controller->request = new Request(['request' => [
            'action' => 'create-product',
            'code' => $withDoc ? $this->doc->id() : '',
            'data' => json_encode($formData),
            'multireqtoken' => $validToken ? $token : 'invalid-token',
            'selectedLine' => '0',
        ]]);

        $permissions = new ControllerPermissions();
        $permissions->set(true, 99, true, true);

        $response = new Response();
        $response->disableSend(true);
        $controller->privateCore($response, $user, $permissions);

        $json = json_decode($response->getContent(), true);
        $this->assertIsArray($json, 'invalid-json-response');
        return $json;
    }
}
