<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Plugins;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Template\ApiController;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Lib\Accounting\AccountingPlanImport;
use FacturaScripts\Dinamic\Model\Almacen;
use FacturaScripts\Dinamic\Model\ApiAccess;
use FacturaScripts\Dinamic\Model\ApiKey;
use FacturaScripts\Dinamic\Model\AttachedFile;
use FacturaScripts\Dinamic\Model\Cuenta;
use FacturaScripts\Dinamic\Model\CuentaBanco;
use FacturaScripts\Dinamic\Model\Diario;
use FacturaScripts\Dinamic\Model\Ejercicio;
use FacturaScripts\Dinamic\Model\Empresa;
use FacturaScripts\Dinamic\Model\FormaPago;
use FacturaScripts\Dinamic\Model\Page;
use FacturaScripts\Dinamic\Model\Pais;
use FacturaScripts\Dinamic\Model\Role;
use FacturaScripts\Dinamic\Model\SecuenciaDocumento;
use FacturaScripts\Dinamic\Model\Serie;
use FacturaScripts\Dinamic\Model\User;

/**
 * Endpoint REST para completar la instalación de FacturaScripts sin interfaz web.
 *
 * Permite automatizar el proceso de post-instalación (configurar empresa, parámetros
 * fiscales y despliegue final de modelos/plugins) enviando datos vía JSON en lugar de
 * usar el asistente web (Wizard). El estado se persiste en MyFiles/wizard_progress.json
 * para tolerancia a fallos y coordinación con el Wizard web.
 * 
 * El config.php debe contener al menos:
 *
 *     define('FS_DB_TYPE',      'mysql');          // motor: mysql | postgresql
 *     define('FS_DB_HOST',      'localhost');       // host de la base de datos
 *     define('FS_DB_PORT',      3306);              // puerto de la base de datos
 *     define('FS_DB_NAME',      'facturascripts');  // nombre de la base de datos
 *     define('FS_DB_USER',      'root');            // usuario de la base de datos
 *     define('FS_DB_PASS',      '');                // contraseña de la base de datos
 *     define('FS_INITIAL_USER', 'admin');           // nick del administrador
 *     define('FS_INITIAL_PASS', 'admin');           // contraseña del administrador
 *     define('FS_API_KEY',      'clave-secreta');   // habilita la API y sirve como token
 *
 * El flujo es el siguiente:
 * 
 *  1) POST: un json con los datos
 *   Campos obligatorios:
 *     empresa       string    Nombre completo de la empresa.
 *     email         string    Email de contacto.
 *
 *   Campos opcionales:
 *     password          string    Nueva contraseña para el usuario administrador.
 *     repassword        string    Confirmación de contraseña.
 *     codpais           string    Código ISO 3166-1 alpha-3. Ej: "ESP", "MEX", "ARG".
 *     cifnif            string    CIF, NIF o RFC de la empresa.
 *     tipoidfiscal      string    Tipo de identificador fiscal según el país.
 *     personafisica     bool      true si la empresa es una persona física.
 *     direccion         string    Dirección fiscal.
 *     codpostal         string    Código postal.
 *     ciudad            string    Ciudad.
 *     provincia         string    Provincia o estado.
 *     apartado          string    Apartado de correos.
 *     telefono1         string    Teléfono principal.
 *     telefono2         string    Teléfono secundario.
 *     regimeniva        string    Régimen de IVA. Ej: "General", "Exento", "Recargo".
 *     codimpuesto       string    Código del impuesto por defecto. Ej: "IVA21", "IVA10".
 *     codpago           string    Código de la forma de pago por defecto. Ej: "CONT", "TRANS".
 *     ventasinstock     bool      Permitir ventas sin stock. Default: false.
 *     defaultplan       bool      Importar el plan contable del país configurado. Default: false.
 *     invoice_start_number  int   Número de inicio para las facturas. Default: 1.
 *     iban              string    IBAN de la cuenta bancaria principal.
 *     bank_name         string    Nombre descriptivo del banco. 
 * 
 *  2) GET sondeando hasta se reciba {"status":"installed"}.
 *
 *   Configura empresa (paso 1) y parámetros fiscales (paso 2).
 *
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
class ApiSetupWizard extends ApiController
{
    const NEW_DEFAULT_PAGE = 'Dashboard';

    /** @var Empresa */
    private $empresa;

    /** @var User|false */
    private $user = false;

    /**
     * Instancia las dependencias que ApiAccess necesita antes de llamar al constructor del padre.
     */
    public function __construct(string $className, string $url = '')
    {
        // Dependencias de la ApiAccess (necesario para que este funcione)
        new AttachedFile();
        new Diario();
        new Page();
        new Empresa();
        new Serie();
        new User();
        new ApiKey();
        new ApiAccess();

        parent::__construct($className, $url);
    }

    /**
     * Punto de entrada del endpoint. Verifica que no haya otra instalación en curso
     * y despacha a handleGet() o handlePost() según el método HTTP.
     */
    protected function runResource(): void
    {
        $this->empresa = $this->loadDefaultEmpresa();

        if ($this->checkIsInstalled()) {
            $this->response
                ->setHttpCode(Response::HTTP_FORBIDDEN)
                ->json(['status' => 'installed', 'message' => Tools::trans('wizard-already-completed')]);
            return;
        }

        if (($this->readWizardProgress()['installAgent'] ?? '') === 'wizard') {
            $this->response
                ->setHttpCode(Response::HTTP_CONFLICT)
                ->json([
                    'status'     => 'error',
                    'message'    => 'installation-managed-by-wizard',
                    'wizard_url' => Tools::siteUrl() . '/Wizard',
                ]);
            return;
        }

        $method = $this->request->method();

        if ($method === 'GET') {
            $this->handleGet();
        } elseif ($method === 'POST') {
            $this->handlePost();
        } else {
            $this->response
                ->setHttpCode(Response::HTTP_METHOD_NOT_ALLOWED)
                ->json(['status' => 'error', 'message' => 'method-not-allowed']);
        }
    }

    /**
     * Devuelve el estado actual. Si current_step === 'step3', ejecuta el despliegue final
     * y responde con {status:"installed"} o HTTP 500 según el resultado.
     */
    private function handleGet(): void
    {
        $progress = $this->readWizardProgress();
        $currentStep = $progress['current_step'] ?? '';

        if ($currentStep !== 'step3') {
            $this->response->json([
                'status'       => 'installing',
                'current_step' => empty($currentStep) ? 'step1' : $currentStep,
            ]);
            return;
        }

        $users = (new User())->all([Where::eq('admin', true)], [], 0, 1);
        if (empty($users)) {
            $this->response
                ->setHttpCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                ->json(['status' => 'error', 'step' => 'step3', 'message' => 'admin-user-not-found']);
            return;
        }
        $this->user = $users[0];

        $error = $this->saveStep3();
        if (!empty($error)) {
            $this->response
                ->setHttpCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                ->json(['status' => 'error', 'step' => 'step3', 'message' => $error]);
            return;
        }

        $this->completeWizardStep('step3');
        $this->response->json(['status' => 'installed', 'message' => Tools::trans('record-updated-correctly')]);
    }

    /**
     * Recibe el formulario completo y ejecuta step1 y step2 en orden.
     * Los pasos ya completados se omiten para permitir reintentos.
     */
    private function handlePost(): void
    {
        $body = $this->request->json() ?? [];

        // registrar este endpoint como agente responsable de la instalación
        $progress = $this->readWizardProgress();
        if (empty($progress['installAgent'])) {
            $progress['installAgent'] = 'apiWizard';
            file_put_contents(self::wizardStateFile(), json_encode($progress, JSON_PRETTY_PRINT));
        }

        if (!$this->isStepCompleted('step1')) {
            $users = (new User())->all([Where::eq('admin', true)], [], 0, 1);
            if (empty($users)) {
                $this->response
                    ->setHttpCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                    ->json(['status' => 'error', 'step' => 'step1', 'message' => 'admin-user-not-found']);
                return;
            }
            $this->user = $users[0];

            $errors = $this->validateStep1($body);
            if (!empty($errors)) {
                $this->response
                    ->setHttpCode(Response::HTTP_BAD_REQUEST)
                    ->json(['status' => 'error', 'step' => 'step1', 'errors' => $errors]);
                return;
            }

            $error = $this->saveStep1($body);
            if (!empty($error)) {
                $this->response
                    ->setHttpCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                    ->json(['status' => 'error', 'step' => 'step1', 'message' => $error]);
                return;
            }
            $this->completeWizardStep('step1');
        }

        if (!$this->isStepCompleted('step2')) {
            $error = $this->saveStep2($body);
            if (!empty($error)) {
                $this->response
                    ->setHttpCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                    ->json(['status' => 'error', 'step' => 'step2', 'message' => $error]);
                return;
            }
            $this->completeWizardStep('step2');
        }

        $this->response->json([
            'status'       => 'installing',
            'current_step' => 'step3',
            'message'      => Tools::trans('record-updated-correctly'),
        ]);
    }

    /**
     * Carga la empresa predeterminada desde la configuración o la primera disponible en BD.
     *
     * @return Empresa
     */
    private function loadDefaultEmpresa(): Empresa
    {
        $empresa = new Empresa();

        $idempresa = Tools::settings('default', 'idempresa');
        if (!empty($idempresa) && $empresa->load($idempresa)) {
            return $empresa;
        }

        $list = $empresa->all([], [], 0, 1);
        return empty($list) ? new Empresa() : $list[0];
    }

    /**
     * Paso 1: configura el país, inicializa modelos auxiliares, guarda la dirección
     * fiscal y, si se proporcionaron, la contraseña y el email del admin.
     *
     * @param array $data Cuerpo JSON del POST.
     * @return string Vacío si todo fue bien, key de traducción si hubo error.
     */
    private function saveStep1(array $data): string
    {
        $codpais = $data['codpais'] ?? $this->empresa->codpais;
        $this->preSetAppSettings($codpais);

        $this->initModels(['AttachedFile', 'Diario', 'EstadoDocumento', 'FormaPago',
            'Impuesto', 'Retencion', 'Serie', 'Provincia']);

        $error = $this->saveAddress($codpais, $data);
        if (!empty($error)) {
            return $error;
        }

        $pass = $data['password'] ?? '';
        if ('' !== $pass) {
            $error = $this->saveNewPassword($pass, $data['repassword'] ?? '');
            if (!empty($error)) {
                return $error;
            }
        }

        $email = $data['email'] ?? '';
        if ('' !== $email) {
            $error = $this->saveEmail($email);
            if (!empty($error)) {
                return $error;
            }
        }

        return '';
    }

    /**
     * Paso 2: guarda régimen de IVA, impuesto y forma de pago por defecto,
     * ventas sin stock, plan contable, número de inicio de facturas y cuenta bancaria.
     *
     * @param array $data Cuerpo JSON del POST.
     * @return string Vacío si todo fue bien, key de traducción si hubo error.
     */
    private function saveStep2(array $data): string
    {
        $this->empresa->regimeniva = $data['regimeniva'] ?? null;
        if (!$this->empresa->save()) {
            return Tools::trans('api-wizard-step2-company-save-error');
        }

        $codimpuesto = $data['codimpuesto'] ?? null;
        Tools::settingsSet('default', 'codimpuesto', empty($codimpuesto) ? null : $codimpuesto);

        $codpago = $data['codpago'] ?? null;
        Tools::settingsSet('default', 'codpago', empty($codpago) ? null : $codpago);

        Tools::settingsSet('default', 'ventasinstock', (bool)($data['ventasinstock'] ?? '0'));
        Tools::settingsSet('default', 'site_url', Tools::siteUrl());
        Tools::settingsSave();

        if ($data['defaultplan'] ?? '0') {
            $this->loadDefaultAccountingPlan($this->empresa->codpais);
        }

        $error = $this->saveInvoiceStartNumber((int)($data['invoice_start_number'] ?? '1'));
        if (!empty($error)) {
            return $error;
        }

        $error = $this->saveBankAccount($data['iban'] ?? '', $data['bank_name'] ?? '');
        if (!empty($error)) {
            return $error;
        }

        return '';
    }

    /**
     * Paso 3: inicializa todos los modelos (crea tablas), despliega plugins,
     * asigna el rol de empleado como predeterminado y actualiza la homepage del admin.
     * Se invoca automáticamente desde el GET cuando current_step === 'step3'.
     *
     * @return string Vacío si todo fue bien, key de traducción si hubo error.
     */
    private function saveStep3(): string
    {
        $modelNames = [];
        $modelsFolder = Tools::folder('Dinamic', 'Model');
        foreach (Tools::folderScan($modelsFolder) as $fileName) {
            if ('.php' === substr($fileName, -4)) {
                $modelNames[] = substr($fileName, 0, -4);
            }
        }
        if (false === $this->db()->tableExists('fs_users')) {
            $this->initModels($modelNames);
        }

        Plugins::deploy(true, true);

        $role = new Role();
        if ($role->load('employee')) {
            Tools::settingsSet('default', 'codrole', $role->codrole);
            Tools::settingsSave();
        }

        $this->user->homepage = $this->db()->tableExists('fs_users') ? 'AdminPlugins' : self::NEW_DEFAULT_PAGE;
        if (!$this->user->save()) {
            return Tools::trans('api-wizard-admin-user-save-error');
        }

        return '';
    }

    /**
     * Carga los valores predeterminados del país desde Dinamic/Data/Codpais/<codpais>/default.json.
     *
     * @param string $codpais Código ISO 3166-1 alpha-3.
     */
    private function preSetAppSettings(string $codpais): void
    {
        $filePath = FS_FOLDER . '/Dinamic/Data/Codpais/' . $codpais . '/default.json';
        if (file_exists($filePath)) {
            $defaultValues = json_decode(file_get_contents($filePath), true) ?? [];
            foreach ($defaultValues as $group => $values) {
                foreach ($values as $key => $value) {
                    Tools::settingsSet($group, $key, $value);
                }
            }
        }

        Tools::settingsSet('default', 'codpais', $codpais);
        Tools::settingsSet('default', 'homepage', 'Root');
        Tools::settingsSave();
    }

    /**
     * Instancia los modelos indicados para que el ORM cree sus tablas si no existen.
     *
     * @param array $names Nombres de modelo sin namespace, ej.: ['Impuesto', 'Serie'].
     */
    private function initModels(array $names): void
    {
        foreach ($names as $name) {
            $className = '\\FacturaScripts\\Dinamic\\Model\\' . $name;
            new $className();
        }
    }

    /**
     * Guarda la dirección fiscal de la empresa y sincroniza el almacén asociado
     * (o crea uno nuevo si no existe ninguno).
     *
     * @param string $codpais Código ISO 3166-1 alpha-3.
     * @param array  $data    Campos de dirección del POST.
     * @return string Vacío si todo fue bien, key de traducción si hubo error.
     */
    private function saveAddress(string $codpais, array $data): string
    {
        $this->empresa->apartado = $data['apartado'] ?? '';
        $this->empresa->cifnif = $data['cifnif'] ?? '';
        $this->empresa->ciudad = $data['ciudad'] ?? '';
        $this->empresa->codpais = $codpais;
        $this->empresa->codpostal = $data['codpostal'] ?? '';
        $this->empresa->direccion = $data['direccion'] ?? '';
        $this->empresa->nombre = $data['empresa'] ?? '';
        $this->empresa->nombrecorto = Tools::textBreak($this->empresa->nombre, 32);
        $this->empresa->personafisica = (bool)($data['personafisica'] ?? '0');
        $this->empresa->provincia = $data['provincia'] ?? '';
        $this->empresa->telefono1 = $data['telefono1'] ?? '';
        $this->empresa->telefono2 = $data['telefono2'] ?? '';
        $this->empresa->tipoidfiscal = $data['tipoidfiscal'] ?? '';
        if (empty($this->empresa->tipoidfiscal)) {
            $this->empresa->tipoidfiscal = Tools::settings('default', 'tipoidfiscal');
        }
        if (!$this->empresa->save()) {
            return Tools::trans('api-wizard-step1-company-save-error');
        }

        $where = [
            Where::eq('idempresa', $this->empresa->idempresa),
            Where::orIsNull('idempresa'),
        ];
        foreach (Almacen::all($where) as $almacen) {
            $this->setWarehouse($almacen, $codpais);
            return '';
        }

        $almacen = new Almacen();
        $this->setWarehouse($almacen, $codpais);
        return '';
    }

    /**
     * @param string $email
     * @return string Vacío si todo fue bien, key de traducción si hubo error.
     */
    private function saveEmail(string $email): string
    {
        if (empty($this->empresa->email)) {
            $this->empresa->email = $email;
        }
        if (empty($this->user->email)) {
            $this->user->email = $email;
        }
        if (!$this->empresa->save() || !$this->user->save()) {
            return Tools::trans('api-wizard-email-save-error');
        }

        return '';
    }

    /**
     * @param string $pass
     * @param string $repass
     * @return string Vacío si todo fue bien, key de traducción si hubo error.
     */
    private function saveNewPassword(string $pass, string $repass): string
    {
        if ($pass !== $repass) {
            return Tools::trans('api-wizard-passwords-must-match');
        }

        $this->user->newPassword  = $pass;
        $this->user->newPassword2 = $repass;
        if (!$this->user->save()) {
            return Tools::trans('api-wizard-password-save-error');
        }

        return '';
    }

    /**
     * Importa el plan contable del país desde Dinamic/Data/Codpais/<codpais>/defaultPlan.csv.
     * No hace nada si el fichero no existe, la BD es de 2017 o ya hay cuentas.
     *
     * @param string $codpais Código ISO 3166-1 alpha-3.
     */
    private function loadDefaultAccountingPlan(string $codpais): void
    {
        $filePath = FS_FOLDER . '/Dinamic/Data/Codpais/' . $codpais . '/defaultPlan.csv';
        if (!file_exists($filePath)) {
            return;
        }

        if ($this->db()->tableExists('co_cuentas')) {
            return;
        }

        $cuenta = new Cuenta();
        if ($cuenta->count() > 0) {
            return;
        }

        foreach (Ejercicio::all() as $exercise) {
            $planImport = new AccountingPlanImport();
            $planImport->importCSV($filePath, $exercise->codejercicio);
            return;
        }
    }

    /**
     * Configura el número de inicio de la secuencia de FacturaCliente (serie A).
     * Crea la secuencia si no existe. Ignorado si $startNumber < 2.
     *
     * @param int $startNumber
     * @return string Vacío si todo fue bien, key de traducción si hubo error.
     */
    private function saveInvoiceStartNumber(int $startNumber): string
    {
        if ($startNumber < 2) {
            return '';
        }

        $exerciseCode = $this->getCompanyExerciseCode();
        if (empty($exerciseCode)) {
            return '';
        }

        $secuencia = new SecuenciaDocumento();
        $where = [
            Where::eq('codejercicio', $exerciseCode),
            Where::eq('codserie', 'A'),
            Where::eq('tipodoc', 'FacturaCliente'),
            Where::eq('idempresa', $this->empresa->idempresa),
        ];
        $found = false;
        foreach ($secuencia->all($where) as $sec) {
            $found = true;
            $sec->inicio  = $startNumber;
            $sec->numero  = $startNumber;
            $sec->patron  = 'F{EJE}{SERIE}{NUM}';
            if (!$sec->save()) {
                return Tools::trans('api-wizard-sequence-save-error');
            }
        }
        if ($found) {
            return '';
        }

        $secuencia->codejercicio = $exerciseCode;
        $secuencia->codserie     = 'A';
        $secuencia->idempresa    = $this->empresa->idempresa;
        $secuencia->inicio       = $startNumber;
        $secuencia->numero       = $startNumber;
        $secuencia->patron       = 'F{EJE}{SERIE}{NUM}';
        $secuencia->tipodoc      = 'FacturaCliente';
        $secuencia->usarhuecos   = true;
        if (!$secuencia->save()) {
            return Tools::trans('api-wizard-sequence-save-error');
        }

        return '';
    }

    /**
     * Crea o actualiza la cuenta bancaria principal y la asocia a la forma de pago TRANS.
     * Genera la subcuenta contable si el ejercicio está disponible.
     * No hace nada si $iban y $bankName están ambos vacíos.
     *
     * @param string $iban
     * @param string $bankName Nombre descriptivo; si está vacío se usa el nombre corto de la empresa.
     * @return string Vacío si todo fue bien, key de traducción si hubo error.
     */
    private function saveBankAccount(string $iban, string $bankName): string
    {
        if (empty($iban) && empty($bankName)) {
            return '';
        }

        $paymentMethod = $this->getTransferPaymentMethod();
        if (!$paymentMethod->exists()) {
            return '';
        }

        $account = new CuentaBanco();
        if (!empty($paymentMethod->codcuentabanco)) {
            $account->load($paymentMethod->codcuentabanco);
        }

        $account->descripcion = empty($bankName) ? $this->empresa->nombrecorto : $bankName;
        $account->iban        = $iban;
        $account->idempresa   = $this->empresa->idempresa;
        if (!$account->save()) {
            return Tools::trans('api-wizard-bank-account-save-error');
        }

        $paymentMethod->codcuentabanco = $account->codcuenta;
        $paymentMethod->idempresa      = $this->empresa->idempresa;
        if (!$paymentMethod->save()) {
            return Tools::trans('api-wizard-payment-method-save-error');
        }

        if (empty($account->codsubcuenta)) {
            $exerciseCode = $this->getCompanyExerciseCode();
            if (!empty($exerciseCode)) {
                $account->createSubcuenta($exerciseCode);
            }
        }

        return '';
    }

    /**
     * Sincroniza el almacén con la dirección de la empresa y lo registra como predeterminado.
     *
     * @param Almacen $almacen
     * @param string  $codpais Código ISO 3166-1 alpha-3.
     */
    private function setWarehouse(Almacen $almacen, string $codpais): void
    {
        $almacen->ciudad    = $this->empresa->ciudad;
        $almacen->codpais   = $codpais;
        $almacen->codpostal = $this->empresa->codpostal;
        $almacen->direccion = $this->empresa->direccion;
        $almacen->idempresa = $this->empresa->idempresa;
        $almacen->nombre    = $this->empresa->nombrecorto;
        $almacen->provincia = $this->empresa->provincia;
        $almacen->save();

        Tools::settingsSet('default', 'codalmacen', $almacen->codalmacen);
        Tools::settingsSet('default', 'idempresa', $this->empresa->idempresa);
        Tools::settingsSave();
    }

    /**
     * Devuelve la forma de pago TRANS (transferencia bancaria).
     * La crea con valores predeterminados si no existe.
     *
     * @return FormaPago
     */
    private function getTransferPaymentMethod(): FormaPago
    {
        $paymentMethod = new FormaPago();
        if ($paymentMethod->load('TRANS')) {
            return $paymentMethod;
        }

        $paymentMethod->codpago          = 'TRANS';
        $paymentMethod->descripcion      = 'Transferencia bancaria';
        $paymentMethod->idempresa        = $this->empresa->idempresa;
        $paymentMethod->plazovencimiento = 1;
        $paymentMethod->tipovencimiento  = 'months';
        $paymentMethod->save();
        return $paymentMethod;
    }

    /**
     * Devuelve el código del primer ejercicio fiscal activo de la empresa,
     * o cadena vacía si no hay ninguno.
     *
     * @return string
     */
    private function getCompanyExerciseCode(): string
    {
        foreach ($this->empresa->getExercises() as $exercise) {
            return (string)$exercise->codejercicio;
        }

        return '';
    }

    /**
     * Ruta al fichero de estado compartido con el Wizard web.
     *
     * @return string
     */
    private static function wizardStateFile(): string
    {
        return Tools::folder('MyFiles') . DIRECTORY_SEPARATOR . 'wizard_progress.json';
    }

    /**
     * Lee wizard_progress.json y devuelve su contenido, o [] si no existe.
     *
     * @return array
     */
    private function readWizardProgress(): array
    {
        $file = self::wizardStateFile();
        return file_exists($file) ? (json_decode(file_get_contents($file), true) ?? []) : [];
    }

    /**
     * Indica si el paso dado está marcado como completado en el fichero de progreso.
     *
     * @param string $step 'step1', 'step2' o 'step3'.
     * @return bool
     */
    private function isStepCompleted(string $step): bool
    {
        return ($this->readWizardProgress()[$step]['completed'] ?? false) === true;
    }

    /**
     * Marca el paso como completado y avanza current_step al siguiente (null tras step3).
     *
     * @param string $step 'step1', 'step2' o 'step3'.
     */
    private function completeWizardStep(string $step): void
    {
        $next = ['step1' => 'step2', 'step2' => 'step3'];
        $data = $this->readWizardProgress();
        $data[$step] = [
            'completed'    => true,
            'completed_at' => date('c'),
            'version'      => \FacturaScripts\Core\Kernel::version(),
        ];
        $data['current_step'] = $next[$step] ?? null;
        file_put_contents(self::wizardStateFile(), json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * Comprueba si la instalación ya fue completada.
     * Retrocompatibilidad: instalaciones previas al fichero de progreso se detectan
     * comprobando que la homepage del admin ya no sea 'Wizard' (igual que en Wizard.php).
     *
     * @return bool
     */
    private function checkIsInstalled(): bool
    {
        // Retrocompatibilidad: instalaciones antiguas sin fichero de progreso.
        // El wizard cambia la homepage al completar el step3; si ya no es 'Wizard',
        // la instalación está hecha aunque no exista wizard_progress.json.
        $users = (new User())->all([Where::eq('admin', true)], [], 0, 1);
        if (!empty($users) && $users[0]->homepage !== 'Wizard') {
            return true;
        }

        return $this->isStepCompleted('step3');
    }


    /**
     * Valida los campos obligatorios del paso 1.
     * 'empresa' siempre es requerido; 'email' solo si ni empresa ni usuario tienen uno guardado.
     *
     * @param array $body Datos del POST.
     * @return array Mensajes de error traducidos; vacío si la validación pasa.
     */
    private function validateStep1(array $body): array
    {
        $errors = [];

        if (empty($body['empresa'])) {
            $errors[] = Tools::trans('field-can-not-be-null', ['%fieldName%' => 'empresa']);
        }

        if (empty($this->empresa->email) && empty($this->user->email) && empty($body['email'])) {
            $errors[] = Tools::trans('field-can-not-be-null', ['%fieldName%' => 'email']);
        }

        if (!empty($body['codpais'])) {
            $pais = new Pais();
            if (!$pais->load($body['codpais'])) {
                $errors[] = Tools::trans('field-can-not-be-null', ['%fieldName%' => 'codpais']);
            }
        }

        return $errors;
    }
}
