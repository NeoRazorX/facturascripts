<?php

namespace FacturaScripts\Core\Template;

use Exception;
use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\UI\Section;

class UIController extends Controller
{
    /** @var Section[] */
    private $sections = [];

    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);

        $this->setTemplate('Master/UIController');
    }

    public function section(string $name): Section
    {
        foreach ($this->sections as $section) {
            if ($section->name === $name) {
                return $section;
            }
        }

        throw new Exception("Section $name not found");
    }

    public function sections(): array
    {
        $this->sortSections();

        return $this->sections;
    }

    protected function addSection(string $name, ?Section $section = null, ?int $position = null): Section
    {
        // comprobamos que no exista ya una sección con ese nombre
        foreach ($this->sections as $sec) {
            if ($sec->name === $name) {
                throw new Exception("Section $name already exists");
            }
        }

        // si section es null, lo creamos
        if (null === $section) {
            $section = new Section();
        }

        $section->name = $name;
        $section->position = $position ?? count($this->sections) * 10;

        $this->sections[] = $section;
        $this->sortSections();

        return $section;
    }

    private function sortSections(): void
    {
        usort($this->sections, function (Section $a, Section $b) {
            return $a->position <=> $b->position;
        });
    }
}