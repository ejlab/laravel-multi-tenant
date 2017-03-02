<?php

namespace EJLab\Laravel\MultiTenant\Commands;

use Config;
use Illuminate\Support\Str;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Foundation\Console\ModelMakeCommand as BaseCommand;
use Symfony\Component\Console\Input\InputOption;

class ModelMakeCommand extends BaseCommand
{
    /**
     * Create a migration file for the model.
     *
     * @return void
     */
    protected function createMigration()
    {
        $table = Str::plural(Str::snake(class_basename($this->argument('name'))));

        $args = [
            'name' => "create_{$table}_table",
            '--create' => $table,
        ];

        if ($this->input->getOption('tenant')) $args['--tenant'] = TRUE;

        $this->call('make:migration', $args);
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        if ($this->input->getOption('tenant')) return __DIR__.'/stubs/model.stub';
        else return parent::getStub();
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array_merge(parent::getOptions(), [
            ['tenant', 'T', InputOption::VALUE_NONE, "Create a new Eloquent model class for tenant database."],
        ]);
    }

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {
        if (!$this->input->getOption('tenant')) return parent::buildClass($name);

        $stub = $this->files->get($this->getStub());
        return $this->replaceConnection($stub, Config::get('elmt.tenant-connection', 'tenant2'))->replaceNamespace($stub, $name)->replaceClass($stub, $name);
    }

    protected function replaceConnection(&$stub, $connection)
    {
        $stub = str_replace('DummyConnectionName', $connection, $stub);

        return $this;
    }
}
