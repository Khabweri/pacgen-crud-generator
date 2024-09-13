<?php

namespace Pacgen\CrudGenerator\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class GenerateCrudCommand extends Command
{
    protected $signature = 'crud:generate {name : Name of the model (singular)}';

    protected $description = 'Generate CRUD for a model with views, controller, and routes';

    public function handle()
    {
        $modelName = Str::singular($this->argument('name'));
        $modelNamePlural = Str::plural($modelName); // Initialize $modelNamePlural

        // Generate model and migration
        Artisan::call('make:model', [
            'name' => $modelName,
            '-m' => true,
        ]);

        // Generate controller with resourceful methods
        $this->generateController($modelName, $modelNamePlural); // Pass $modelNamePlural to generateController

        // Generate views directory and basic views
        $viewsPath = resource_path("views/{$modelNamePlural}");
        if (!File::exists($viewsPath)) {
            File::makeDirectory($viewsPath);
        }

        $this->generateView('index', $modelName, $modelNamePlural);
        $this->generateView('create', $modelName, $modelNamePlural);
        $this->generateView('edit', $modelName, $modelNamePlural);
        $this->generateView('show', $modelName, $modelNamePlural);

        // Append route definitions to routes/web.php
        $this->appendRoutes($modelName, $modelNamePlural);

        $this->info("CRUD generated successfully for {$modelName}");
    }

    private function generateController($modelName, $modelNamePlural)
    {
        $controllerName = "{$modelName}Controller";
    
        // Generate the controller using Artisan command
        Artisan::call('make:controller', [
            'name' => $controllerName,
            '--resource' => true,
        ]);
    
        // Path to the generated controller file
        $controllerPath = app_path("Http/Controllers/{$controllerName}.php");
    
        // Check if the controller file exists
        if (File::exists($controllerPath)) {
            // Get the content of the controller file
            $controllerContent = file_get_contents($controllerPath);
    
            // Modify the content to include the necessary methods
            $controllerContentWithMethods = $this->addControllerMethods($controllerContent, $modelName, $modelNamePlural);
    
            // Put the modified content back into the controller file
            file_put_contents($controllerPath, $controllerContentWithMethods);
        }
    }
    
    private function addControllerMethods($controllerContent, $modelName, $modelNamePlural)
    {
        // Define the methods to add
        $methodsToAdd = <<<EOT
    
        public function index()
        {
            \${$modelNamePlural} = ${modelName}::all();
            return view('${modelNamePlural}.index', compact('${modelNamePlural}'));
        }
    
        public function create()
        {
            return view('${modelNamePlural}.create');
        }
    
        public function store(Request \$request)
        {
            \$request->validate([
                'title' => 'required',
                'content' => 'required',
            ]);
    
            ${modelName}::create(\$request->all());
    
            return redirect()->route('${modelNamePlural}.index')
                ->with('success', '${modelName} created successfully.');
        }
    
        public function show(\$id)
        {
            \${$modelName} = ${modelName}::findOrFail(\$id);
            return view('${modelNamePlural}.show', compact('${modelName}'));
        }
    
        public function edit(\$id)
        {
            \${$modelName} = ${modelName}::findOrFail(\$id);
            return view('${modelNamePlural}.edit', compact('${modelName}'));
        }
    
        public function update(Request \$request, \$id)
        {
            \$request->validate([
                'title' => 'required',
                'content' => 'required',
            ]);
    
            \${$modelName} = ${modelName}::findOrFail(\$id);
            \${$modelName}->update(\$request->all());
    
            return redirect()->route('${modelNamePlural}.index')
                ->with('success', '${modelName} updated successfully');
        }
    
        public function destroy(\$id)
        {
            \${$modelName} = ${modelName}::findOrFail(\$id);
            \${$modelName}->delete();
    
            return redirect()->route('${modelNamePlural}.index')
                ->with('success', '${modelName} deleted successfully');
        }
        EOT;
    
        // Extract the existing class content
        if (preg_match('/class \w+Controller extends Controller\s*{/', $controllerContent, $matches)) {
            // Find the position to insert methods, which is after the class definition
            $position = strpos($controllerContent, $matches[0]) + strlen($matches[0]);
            
            // Insert methods after class definition
            $controllerContentWithMethods = substr($controllerContent, 0, $position) . PHP_EOL . $methodsToAdd . PHP_EOL . '}' . PHP_EOL;
            
            return $controllerContentWithMethods;
        }
    
        // Return original content if class definition is not found
        return $controllerContent;
    }
    

    private function generateView($viewName, $modelName, $modelNamePlural)
    {
        $viewContent = $this->getViewContent($viewName, $modelName, $modelNamePlural);

        file_put_contents(resource_path("views/{$modelNamePlural}/{$viewName}.blade.php"), $viewContent);
    }

    private function getViewContent($viewName, $modelName, $modelNamePlural)
    {
        switch ($viewName) {
            case 'index':
                return $this->getIndexViewContent($modelName, $modelNamePlural);
            case 'create':
                return $this->getCreateViewContent($modelName, $modelNamePlural);
            case 'edit':
                return $this->getEditViewContent($modelName, $modelNamePlural);
            case 'show':
                return $this->getShowViewContent($modelName, $modelNamePlural);
            default:
                return '';
        }
    }

    private function getIndexViewContent($modelName, $modelNamePlural)
    {
        return "@extends('layouts.app')

        @section('content')
            <h1>{$modelNamePlural} List</h1>

            <table class='table'>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(\${$modelNamePlural} as \${$modelName})
                    <tr>
                        <td>{{ \${$modelName}->title }}</td>
                        <td>
                            <a href='{{ route('{$modelNamePlural}.show', \${$modelName}->id) }}' class='btn btn-info'>View</a>
                            <a href='{{ route('{$modelNamePlural}.edit', \${$modelName}->id) }}' class='btn btn-primary'>Edit</a>
                            <form action='{{ route('{$modelNamePlural}.destroy', \${$modelName}->id) }}' method='POST' style='display: inline;'>
                                @csrf
                                @method('DELETE')
                                <button type='submit' class='btn btn-danger'>Delete</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <a href='{{ route('{$modelNamePlural}.create') }}' class='btn btn-success'>Create New {$modelName}</a>
        @endsection";
    }

    private function getCreateViewContent($modelName, $modelNamePlural)
    {
        return "@extends('layouts.app')

        @section('content')
            <h1>Create New {$modelName}</h1>

            <form method='POST' action='{{ route('{$modelNamePlural}.store') }}'>
                @csrf

                <div class='form-group'>
                    <label for='title'>Title</label>
                    <input type='text' id='title' name='title' class='form-control'>
                </div>

                <div class='form-group'>
                    <label for='content'>Content</label>
                    <textarea id='content' name='content' class='form-control'></textarea>
                </div>

                <button type='submit' class='btn btn-primary'>Create {$modelName}</button>
            </form>
        @endsection";
    }

    private function getEditViewContent($modelName, $modelNamePlural)
    {
        return "@extends('layouts.app')

        @section('content')
            <h1>Edit {$modelName}</h1>

            <form method='POST' action='{{ route('{$modelNamePlural}.update', \${$modelName}->id) }}'>
                @csrf
                @method('PUT')

                <div class='form-group'>
                    <label for='title'>Title</label>
                    <input type='text' id='title' name='title' class='form-control' value='{{ \${$modelName}->title }}'>
                </div>

                <div class='form-group'>
                    <label for='content'>Content</label>
                    <textarea id='content' name='content' class='form-control'>{{ \${$modelName}->content }}</textarea>
                </div>

                <button type='submit' class='btn btn-primary'>Update {$modelName}</button>
            </form>
        @endsection";
    }

    private function getShowViewContent($modelName, $modelNamePlural)
    {
        return "@extends('layouts.app')

        @section('content')
            <h1>{{ \${$modelName}->title }}</h1>

            <p>{{ \${$modelName}->content }}</p>

            <a href='{{ route('{$modelNamePlural}.edit', \${$modelName}->id) }}' class='btn btn-primary'>Edit</a>
            <form action='{{ route('{$modelNamePlural}.destroy', \${$modelName}->id) }}' method='POST' style='display: inline;'>
                @csrf
                @method('DELETE')
                <button type='submit' class='btn btn-danger'>Delete</button>
            </form>
        @endsection";
    }

    private function appendRoutes($modelName, $modelNamePlural)
    {
        $controllerName = "{$modelName}Controller";
    
        $controllerNamespace = 'App\\Http\\Controllers\\';
        $controllerFullName = $controllerNamespace . $controllerName;
    
        $useStatement = "use {$controllerFullName};";
    
        $routeContent = <<<EOT
    
    {$useStatement}
    
    Route::resource('{$modelNamePlural}', {$controllerName}::class);
    EOT;
    
        File::append(base_path('routes/web.php'), $routeContent);
    }
    
}
