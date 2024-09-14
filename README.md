# Laravel CRUD Generator

Laravel CRUD Generator is a package that simplifies the process of generating CRUD (Create, Read, Update, Delete) operations for your Laravel applications this will save you a lot of your time so you can concentrate on important things for your development.

## Installation

You can install the package via Composer.

### For Laravel 8.x or later

Run the following command to require the package:

```bash
composer require pacgen/crud-generator:dev-main

For Laravel versions before 8.x, you might need to register the service provider manually. Add the following line to your config/app.php file under the providers array:
Copy code

Pacgen\CrudGenerator\Providers\CrudGeneratorServiceProvider::class,

## To generate CRUD
php artisan crud:generate ModelName

This command will generate:
A model with migration
A controller with all resourceful methods
Views (index, create, edit, show)
Routes for the CRUD operations

Configuration
However, you can customize the generated views and controllers as needed for your application. also you need to manually import the Model class in the controller 

Contributing
If you find this project helpful and want to support its development, you can donate via:
- **[PayPal] email(andrewkhabweri@gmail.com)**: You can donate securely via PayPal.

Your contributions will help in maintaining and improving this project. Thank you for your support!

License
The Laravel CRUD Generator is open-source software licensed under the MIT license.
