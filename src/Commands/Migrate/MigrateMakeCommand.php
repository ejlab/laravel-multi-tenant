<?php

namespace EJLab\Laravel\MultiTenant\Commands\Migrate;

class MigrateMakeCommand extends \Illuminate\Database\Console\Migrations\MigrateMakeCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:migration {name : The name of the migration}
        {--create= : The table to be created}
        {--table= : The table to migrate}
        {--path= : The location where the migration file should be created}
        {--realpath : Indicate any provided migration file paths are pre-resolved absolute paths}
        {--fullpath : Output the full path of the migration}
        {--S|system : Create migration for system database. }
        {--T|tenant : Create migration for tenant databases. }';

    protected function getMigrationPath()
    {
        $path = parent::getMigrationPath();
        if ($this->input->getOption('system')) {
            $path .= DIRECTORY_SEPARATOR.'system';
        } elseif ($this->input->getOption('tenant')) {
            $path .= DIRECTORY_SEPARATOR.'tenant';
        }
        if (!file_exists($path) || !is_dir($path)) mkdir($path);

        return $path;
    }
}
