# Laravel CRUD Generator

Laravel CRUD Generator is a package that simplifies the process of generating CRUD (Create, Read, Update, Delete) operations for your Laravel applications.

## Installation

You can install the package via Composer.

### For Laravel 8.x or later

Run the following command to require the package:

```bash
composer require pacgen/crud-generator

Service Provider (if not auto-discovered)
For Laravel versions before 8.x, you might need to register the service provider manually. Add the following line to your config/app.php file under the providers array:

php
Copy code
Pacgen\CrudGenerator\Providers\CrudGeneratorServiceProvider::class,

# Laravel CRUD Generator

Laravel CRUD Generator is a package that simplifies the process of generating CRUD (Create, Read, Update, Delete) operations for your Laravel applications.

## to generate CRUD
php artisan crud:generate ModelName
This command will generate:

A model with migration
A controller with resourceful methods
Views (index, create, edit, show)
Routes for the CRUD operations
Configuration
No additional configuration is required out of the box. However, you can customize the generated views and controllers as needed for your application. also you need to manually import the classes in the controller with resourceful methods 

Contributing
Contributions are welcome! Please fork the repository and submit a pull request with your changes.

License
The Laravel CRUD Generator is open-source software licensed under the MIT license.
