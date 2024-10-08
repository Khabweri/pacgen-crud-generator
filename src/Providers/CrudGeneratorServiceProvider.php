<?php

namespace Pacgen\CrudGenerator\Providers;

use Illuminate\Support\ServiceProvider;
use Pacgen\CrudGenerator\Console\GenerateCrudCommand;

class CrudGeneratorServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->commands([
            GenerateCrudCommand::class,
        ]);
    }

    public function register()
    {
        //
    }
}
