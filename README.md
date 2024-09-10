# Laravel CRUD Generator

Laravel CRUD Generator is a package that simplifies the process of generating CRUD (Create, Read, Update, Delete) operations for your Laravel applications.

## Installation

You can install the package via Composer. Inside your Laravel project directory, run the following command:

```bash
composer require laragen/crud-generator

Registering the Command
If you intend to use this class as a command in your Laravel application, you would register it in your app/Console/Kernel.php file:

php
Copy code
protected $commands = [
    \Laragen\CrudGenerator\CrudGeneratorCommand::class,
];


To generate a basic README.md file for your Laravel package, you should include essential information that helps users understand what your package does, how to install and use it, and any other relevant details. Here’s a template you can use as a starting point:

markdown
Copy code
# Laravel CRUD Generator

Laravel CRUD Generator is a package that simplifies the process of generating CRUD (Create, Read, Update, Delete) operations for your Laravel applications.

## Installation

You can install the package via Composer. Inside your Laravel project directory, run the following command:

```bash
composer require laragen/crud-generator
Usage
To generate CRUD for a model, run the following artisan command, replacing ModelName with the name of your model:

bash

php artisan crud:generate ModelName
This command will generate:

A model with migration
A controller with resourceful methods
Views (index, create, edit, show)
Routes for the CRUD operations
Configuration
No additional configuration is required out of the box. However, you can customize the generated views and controllers as needed for your application. however you need to manually import the classes in the controller with resourceful methods 

Contributing
Contributions are welcome! Please fork the repository and submit a pull request with your changes.

License
The Laravel CRUD Generator is open-source software licensed under the MIT license.