<?php

namespace Pacgen\CrudGenerator\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class GenerateCrudCommand extends Command
{
   // Define the command name and options
    protected $signature = 'crud:generate {name}';
    protected $description = 'Generate CRUD operations';

    public function __construct()
    {
        parent::__construct();
    }
    public static function generate(string $modelName)
    {
        $modelName = Str::singular($modelName);
        $modelNamePlural = Str::plural($modelName);

        // Generate model and migration
        Artisan::call('make:model', [
            'name' => $modelName,
            '-m' => true,
        ]);

        // Update migration file to include image column
        self::updateMigrationFile($modelName);

        // Generate controller with resourceful methods
        self::generateController($modelName, $modelNamePlural);

        // Generate views directory and basic views
        $viewsPath = resource_path("views/{$modelNamePlural}");
        if (!File::exists($viewsPath)) {
            File::makeDirectory($viewsPath, 0755, true);
        }

        self::generateView('index', $modelName, $modelNamePlural);
        self::generateView('create', $modelName, $modelNamePlural);
        self::generateView('edit', $modelName, $modelNamePlural);
        self::generateView('show', $modelName, $modelNamePlural);

        // Append route definitions to routes/web.php
        self::appendRoutes($modelName, $modelNamePlural);
    }

    private static function updateMigrationFile($modelName)
    {
        $migrationFiles = File::files(database_path('migrations'));

        foreach ($migrationFiles as $file) {
            $filename = $file->getFilename();
            if (Str::contains($filename, 'create_' . Str::plural(Str::snake($modelName)) . '_table')) {
                $migrationPath = $file->getPathname();
                $migrationContent = File::get($migrationPath);
                $updatedMigrationContent = str_replace(
                    'Schema::create',
                    'Schema::create' . PHP_EOL . "            \$table->string('image')->nullable();",
                    $migrationContent
                );
                File::put($migrationPath, $updatedMigrationContent);
                break;
            }
        }
    }

    private static function generateController($modelName, $modelNamePlural)
    {
        $controllerName = "{$modelName}Controller";
        Artisan::call('make:controller', [
            'name' => $controllerName,
            '--resource' => true,
        ]);

        $controllerPath = app_path("Http/Controllers/{$controllerName}.php");
        if (File::exists($controllerPath)) {
            $controllerContent = file_get_contents($controllerPath);
            $controllerContentWithMethods = self::addControllerMethods($controllerContent, $modelName, $modelNamePlural);
            file_put_contents($controllerPath, $controllerContentWithMethods);
        }
    }

    private static function addControllerMethods($controllerContent, $modelName, $modelNamePlural)
    {
        $methodsToAdd = <<<EOT

        use Illuminate\Http\Request;
        use App\Models\\{$modelName};
        use Illuminate\Support\Facades\Storage;

        public function index()
        {
            \${$modelNamePlural} = {$modelName}::all();
            return view('{$modelNamePlural}.index', compact('{$modelNamePlural}'));
        }

        public function create()
        {
            return view('{$modelNamePlural}.create');
        }

        public function store(Request \$request)
        {
            \$request->validate([
                'title' => 'required|string|max:255',
                'content' => 'required|string',
                'image' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
            ]);

            \$imagePath = \$request->file('image') ? \$request->file('image')->store('images') : null;

            {$modelName}::create(array_merge(\$request->all(), ['image' => \$imagePath]));

            session()->flash('status', '{$modelName} created successfully.');

            return redirect()->route('{$modelNamePlural}.index');
        }

        public function show(\$id)
        {
            \${$modelName} = {$modelName}::findOrFail(\$id);
            return view('{$modelNamePlural}.show', compact('{$modelName}'));
        }

        public function edit(\$id)
        {
            \${$modelName} = {$modelName}::findOrFail(\$id);
            return view('{$modelNamePlural}.edit', compact('{$modelName}'));
        }

        public function update(Request \$request, \$id)
        {
            \$request->validate([
                'title' => 'required|string|max:255',
                'content' => 'required|string',
                'image' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
            ]);

            \${$modelName} = {$modelName}::findOrFail(\$id);
            
            \$imagePath = \$request->file('image') ? \$request->file('image')->store('images') : \${$modelName}->image;
            
            \${$modelName}->update(array_merge(\$request->all(), ['image' => \$imagePath]));

            session()->flash('status', '{$modelName} updated successfully.');

            return redirect()->route('{$modelNamePlural}.index');
        }

        public function destroy(\$id)
        {
            \${$modelName} = {$modelName}::findOrFail(\$id);
            
            if (\${$modelName}->image) {
                Storage::delete(\${$modelName}->image);
            }
            
            \${$modelName}->delete();

            session()->flash('status', '{$modelName} deleted successfully.');

            return redirect()->route('{$modelNamePlural}.index');
        }
        EOT;

        if (preg_match('/class \w+Controller extends Controller\s*{/', $controllerContent, $matches)) {
            $position = strpos($controllerContent, $matches[0]) + strlen($matches[0]);
            $controllerContentWithMethods = substr($controllerContent, 0, $position) . PHP_EOL . $methodsToAdd . PHP_EOL . '}' . PHP_EOL;
            return $controllerContentWithMethods;
        }

        return $controllerContent;
    }

    private static function generateView($viewName, $modelName, $modelNamePlural)
    {
        $viewContent = self::getViewContent($viewName, $modelName, $modelNamePlural);
        file_put_contents(resource_path("views/{$modelNamePlural}/{$viewName}.blade.php"), $viewContent);
    }

    private static function getViewContent($viewName, $modelName, $modelNamePlural)
    {
        switch ($viewName) {
            case 'index':
                return self::getIndexViewContent($modelName, $modelNamePlural);
            case 'create':
                return self::getCreateViewContent($modelName, $modelNamePlural);
            case 'edit':
                return self::getEditViewContent($modelName, $modelNamePlural);
            case 'show':
                return self::getShowViewContent($modelName, $modelNamePlural);
            default:
                return '';
        }
    }

    private static function getIndexViewContent($modelName, $modelNamePlural)
    {
        return <<<EOT
@extends('layouts.app')

@section('content')
    <div class='container my-4'>
        <h1 class='mb-4 text-center'>{$modelNamePlural} List</h1>

        <div class='row'>
            <div class='col-md-8'>
                <div class='card'>
                    <div class='card-header'>
                        <h4>{$modelNamePlural}</h4>
                    </div>
                    <div class='card-body'>
                        <a href='{{ route('{$modelNamePlural}.create') }}' class='btn btn-success'>Create New {$modelName}</a>
                        <table class='table table-striped'>
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Content</th>
                                    <th>Image</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(\${$modelNamePlural} as \${$modelName})
                                <tr>
                                    <td>{{ \${$modelName}->title }}</td>
                                    <td>{{ \${$modelName}->content }}</td>
                                    <td>
                                        @if(\${$modelName}->image)
                                            <img src='{{ Storage::url(\${$modelName}->image) }}' alt='{{ \${$modelName}->title }}' width='100'>
                                        @endif
                                    </td>
                                    <td>
                                        <a href='{{ route('{$modelNamePlural}.show', \${$modelName}->id) }}' class='btn btn-info btn-sm'>View</a>
                                        <a href='{{ route('{$modelNamePlural}.edit', \${$modelName}->id) }}' class='btn btn-primary btn-sm'>Edit</a>
                                        <form action='{{ route('{$modelNamePlural}.destroy', \${$modelName}->id) }}' method='POST' style='display: inline;'>
                                            @csrf
                                            @method('DELETE')
                                            <button type='submit' class='btn btn-danger btn-sm'>Delete</button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
EOT;
    }

    private static function getCreateViewContent($modelName, $modelNamePlural)
    {
        return <<<EOT
@extends('layouts.app')

@section('content')
    <div class='container my-4'>
        <h1 class='mb-4 text-center'>Create {$modelName}</h1>

        <div class='row'>
            <div class='col-md-8'>
                <div class='card'>
                    <div class='card-header'>
                        <h4>Create {$modelName}</h4>
                    </div>
                    <div class='card-body'>
                        @if(\$errors->any())
                            <div class='alert alert-danger'>
                                <ul>
                                    @foreach(\$errors->all() as \$error)
                                        <li>{{ \$error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form action='{{ route('{$modelNamePlural}.store') }}' method='POST' enctype='multipart/form-data'>
                            @csrf
                            <div class='form-group'>
                                <label for='title'>Title</label>
                                <input type='text' id='title' name='title' class='form-control' value='{{ old('title') }}'>
                            </div>
                            <div class='form-group'>
                                <label for='content'>Content</label>
                                <textarea id='content' name='content' class='form-control'>{{ old('content') }}</textarea>
                            </div>
                            <div class='form-group'>
                                <label for='image'>Image</label>
                                <input type='file' id='image' name='image' class='form-control-file'>
                            </div>
                            <button type='submit' class='btn btn-primary'>Create {$modelName}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
EOT;
    }

    private static function getEditViewContent($modelName, $modelNamePlural)
    {
        return <<<EOT
@extends('layouts.app')

@section('content')
    <div class='container my-4'>
        <h1 class='mb-4 text-center'>Edit {$modelName}</h1>

        <div class='row'>
            <div class='col-md-8'>
                <div class='card'>
                    <div class='card-header'>
                        <h4>Edit {$modelName}</h4>
                    </div>
                    <div class='card-body'>
                        @if(\$errors->any())
                            <div class='alert alert-danger'>
                                <ul>
                                    @foreach(\$errors->all() as \$error)
                                        <li>{{ \$error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form action='{{ route('{$modelNamePlural}.update', \${$modelName}->id) }}' method='POST' enctype='multipart/form-data'>
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
                            <div class='form-group'>
                                <label for='image'>Image</label>
                                <input type='file' id='image' name='image' class='form-control-file'>
                            </div>
                            <button type='submit' class='btn btn-primary'>Update {$modelName}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
EOT;
    }

    private static function getShowViewContent($modelName, $modelNamePlural)
    {
        return <<<EOT
@extends('layouts.app')

@section('content')
    <div class='container my-4'>
        <h1 class='mb-4 text-center'>Show {$modelName}</h1>

        <div class='row'>
            <div class='col-md-8'>
                <div class='card'>
                    <div class='card-header'>
                        <h4>Show {$modelName}</h4>
                    </div>
                    <div class='card-body'>
                        <table class='table'>
                            <tbody>
                                <tr>
                                    <th>Title</th>
                                    <td>{{ \${$modelName}->title }}</td>
                                </tr>
                                <tr>
                                    <th>Content</th>
                                    <td>{{ \${$modelName}->content }}</td>
                                </tr>
                                <tr>
                                    <th>Image</th>
                                    <td>
                                        @if(\${$modelName}->image)
                                            <img src='{{ Storage::url(\${$modelName}->image) }}' alt='{{ \${$modelName}->title }}' width='300'>
                                        @else
                                            No image uploaded.
                                        @endif
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <a href='{{ route('{$modelNamePlural}.index') }}' class='btn btn-secondary'>Back to List</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
EOT;
    }

    private static function appendRoutes($modelName, $modelNamePlural)
    {
        $routesContent = <<<EOT
Route::resource('{$modelNamePlural}', '{$modelName}Controller');
EOT;

        File::append(base_path('routes/web.php'), $routesContent . PHP_EOL);
    }
}
