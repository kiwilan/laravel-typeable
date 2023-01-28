<?php

namespace Kiwilan\Typeable\Services;

use Illuminate\Support\Facades\File;
use Kiwilan\Typeable\Services\TypeableService\TypeableClass;
use Kiwilan\Typeable\Services\TypeableService\Utils\TypeableTeam;

/**
 * @property string $path
 * @property TypeableClass[] $typeables
 */
class TypeableService
{
    protected function __construct(
        public string $path,
        /** @var TypeableClass[] */
        public array $typeables = [],
    ) {
    }

    public static function make(): self
    {
        $path = app_path('Models');

        $service = new TypeableService($path);
        $service->typeables = $service->setTypeables();
        $service->typeables['Team'] = TypeableClass::fake('Team', TypeableTeam::setFakeTeam());

        $service->setTsModelTypes();
        // $service->setPhpModelTypes();

        return $service;
    }

    protected function setPhpModelTypes()
    {
        foreach ($this->typeables as $name => $typeable) {
            unset($typeable->reflector);
            $path = app_path('Types');

            if (! File::exists($path)) {
                File::makeDirectory($path);
            }
            $filename = "{$name}.php";
            $path = "{$path}/{$filename}";
            File::put($path, $typeable->typeableModel->phpString);
        }
    }

    protected function setTsModelTypes()
    {
        $content = [];

        $content[] = '// This file is auto generated by GenerateTypeCommand.';
        $content[] = 'declare namespace App.Models {';

        foreach ($this->typeables as $typeable) {
            $content[] = $typeable->typeableModel?->tsString;
        }
        $content[] = '}';

        $content = implode(PHP_EOL, $content);

        $path = config('typeable.models.path') ?? resource_path('js');
        $filename = config('typeable.models.file.models') ?? 'types-models.d.ts';

        $path = "{$path}/{$filename}";
        File::put($path, $content);
    }

    /**
     * @return TypeableClass[]
     */
    protected function setTypeables(): array
    {
        $classes = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->path, \FilesystemIterator::SKIP_DOTS)
        );

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if (! $file->isDir()) {
                $model = TypeableClass::make(
                    path: $file->getPathname(),
                    file: $file,
                );
                $classes[$model->name] = $model;
            }
        }

        return $classes;
    }
}
