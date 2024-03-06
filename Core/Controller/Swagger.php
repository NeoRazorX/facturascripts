<?php declare(strict_types=1);

namespace FacturaScripts\Core\Controller;

use Exception;
use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Core\Tools;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;

class Swagger extends Controller
{
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'swagger';
        $data['icon'] = 'fa-solid fa-wand-magic-sparkles';
        $data['showonmenu'] = false;
        return $data;
    }

    public function privateCore(&$response, $user, $permissions): void
    {
        parent::privateCore($response, $user, $permissions);

        $action = $this->request->get('action', '');
        $this->execAction($action);
    }

    protected function execAction(string $action): void
    {
        switch ($action) {
            case 'getJson':
                $this->setTemplate(false);
                $data = $this->getJson();
                $this->response->headers->set('Content-Type', 'application/json');
                $this->response->setContent(json_encode($data));
                break;

            default:
                //
                break;
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    protected function getJson(): array
    {
        return [
            "servers" => [
                [
                    "url" => FS_ROUTE . '/',
                    "description" => "FacturaScripts",
                ],
            ],
            "openapi" => "3.1.0",
            "components" => [
                "securitySchemes" =>
                    [
                        "ApiKeyAuth" => [
                            "type" => 'apiKey',
                            "in" => "header",
                            "name" => "TOKEN",
                        ],
                    ],
                "schemas" => $this->getModels(),
            ],
            "security" => [
                [
                    "ApiKeyAuth" => [],
                ],
            ],
            "paths" => $this->getRutas(),
        ];
    }

    /**
     * @return array
     */
    protected function getRutas(): array
    {
        $resources = $this->getResourcesMap();
        $resources = array_filter(
            $resources,
            function ($resource) {
                $fqdn = 'FacturaScripts\\Dinamic\\Model\\' . $resource['Name'];
                $object = new $fqdn();
                return $object instanceof ModelClass;
            }
        );

        $parsedResources = [];

        foreach ($resources as $key => $resource) {
            /** @var ModelClass $fqdn */
            $fqdn = 'FacturaScripts\\Dinamic\\Model\\' . $resource['Name'];

            $parsedResources['/api/3/' . $key] = [
                'get' => [
                    'summary' => 'Devuelve el listado del modelo ' . $resource['Name'],
                    'description' => 'Devuelve el listado del modelo ' . $resource['Name'],
                    'parameters' => [
                        [
                            'in' => 'query',
                            'name' => 'filter[' . $fqdn::primaryColumn() . ']',
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                    ],
                                ],
                            ],
                        ],
                        '400' => [
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'post' => [
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/x-www-form-urlencoded' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/' . $resource['Name'],
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                    ],
                                ],
                            ],
                        ],
                        '400' => [
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];
        }

        return $parsedResources;
    }

    /**
     * @return array
     * @throws Exception
     */
    protected function getModels(): array
    {
        $models = [];

        $modelsFolder = Tools::folder('Dinamic', 'Model');
        foreach (Tools::folderScan($modelsFolder) as $fileName) {
            if ('.php' === substr($fileName, -4)) {
                $modelName = substr($fileName, 0, -4);
                $fqdn = '\\FacturaScripts\\Dinamic\\Model\\' . $modelName;

                try {
                    $rc = new ReflectionClass($fqdn);
                } catch (ReflectionException $e) {
                    throw new Exception($e->getMessage());
                }
                $properties = $this->getProperties($rc->getProperties(ReflectionProperty::IS_PUBLIC));

                $models = array_merge(
                    $models,
                    [
                        $modelName => [
                            'type' => 'object',
                            'properties' => $properties,
                        ],
                    ]
                );
            }
        }

        return $models;
    }

    /**
     * @param ReflectionProperty[] $properties
     * @return array
     */
    protected function getProperties($properties): array
    {
        $parsedProperties = [];

        foreach ($properties as $property) {
            $parsedProperties[$property->getName()] = [
                'type' => $this->getType($property),
            ];
        }

        return $parsedProperties;
    }

    /**
     * Copiado de \FacturaScripts\Core\Controller\ApiRoot::getResourcesMap
     *
     * @return array
     */
    protected function getResourcesMap(): array
    {
        $resources = [[]];
        // Loop all controllers in /Dinamic/Lib/API
        $folder = Tools::folder('Dinamic', 'Lib', 'API');
        foreach (Tools::folderScan($folder, false) as $resource) {
            if (substr($resource, -4) !== '.php') {
                continue;
            }

            // The name of the class will be the same as that of the file without the php extension.
            // Classes will be descendants of Base/APIResourceClass.
            $class = substr('\\FacturaScripts\\Dinamic\\Lib\\API\\' . $resource, 0, -4);
            $APIClass = new $class($this->response, $this->request, []);
            if (isset($APIClass) && method_exists($APIClass, 'getResources')) {
                // getResources obtains an associative array of arrays generated
                // with setResource ('name'). These arrays keep the name of the class
                // and the resource so that they can be invoked later.
                //
                // This allows using different API extensions, and not just the
                // usual Lib/API/APIModel.
                $resources[] = $APIClass->getResources();
            }
        }

        // Returns an ordered array with all available resources.
        $finalResources = array_merge(...$resources);
        ksort($finalResources);
        return $finalResources;
    }

    /**
     * Devuelte el tipo de la propiedad de un modelo
     *
     * @param ReflectionProperty $property
     * @return string
     */
    protected function getType($property): string
    {
        $type = 'string';
        $propertyDocComment = $property->getDocComment();
        if (!$propertyDocComment){
            return $type;
        }

        $propertyDocComment = explode("\n", trim(str_replace(['/', '*'], '', $propertyDocComment)));
        $propertyDocComment = array_filter(
            $propertyDocComment,
            function ($doccomment) {
                return str_contains($doccomment, '@var');
            }
        );
        $propertyDocComment = array_values((array)$propertyDocComment);

        if (isset($propertyDocComment[0])) {
            $type = trim(str_replace('@var', '', $propertyDocComment[0]));
            // pasamos bool a number porque en la validación del modelo espera 1 o 0, no true o false.
            $type = str_replace(['bool', 'integer', 'int'], 'number', $type);
        }

        return (string)$type;
    }
}
