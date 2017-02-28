<?php

namespace EJLab\Laravel\MultiTenant\Commands\Seeds;

use Illuminate\Database\Console\Seeds\SeederMakeCommand as BaseCommand;
use Illuminate\Support\Composer;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

class SeederMakeCommand extends BaseCommand
{
    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        if ($this->input->getOption('tenant')) return __DIR__.'/stubs/seeder.stub';

        return parent::getStub();
    }

    /**
     * Get the destination class path.
     *
     * @param  string  $name
     * @return string
     */
    protected function getPath($name)
    {
        if ($this->input->getOption('tenant')) return $this->laravel->databasePath().'/seeds/tenant/'.$name.'.php';

        return parent::getPath($name);
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array_merge(parent::getOptions(), [
            ['tenant', 'T', InputOption::VALUE_NONE, "Create a new seeder class for tenant database."]
        ]);
    }
}
