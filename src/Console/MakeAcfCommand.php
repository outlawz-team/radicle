<?php

namespace OutlawzTeam\Radicle\Console;

use Roots\Acorn\Console\Commands\GeneratorCommand;

class MakeAcfCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:acf';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new acf class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Acf';

    protected function getStub()
    {
        return $this->resolveStubPath('/stubs/acf.stub');
    }

    /**
     * Resolve the fully-qualified path to the stub.
     *
     * @param  string  $stub
     * @return string
     */
    protected function resolveStubPath($stub)
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.$stub;
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Acf';
    }
}
