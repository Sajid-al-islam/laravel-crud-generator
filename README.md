# Laravel CRUD Generator

A Laravel package that lets you scaffold complete CRUD (Create, Read, Update, Delete) functionality—with models, migrations, controllers, requests, views, and routes—through a simple web-based UI or programmatically.

## Features

- **Model Generation**  
  Creates an Eloquent model class with fillable properties.

- **Optional Migration Creation**  
  Generates a timestamped migration file with columns based on your field definitions (string, text, integer, boolean, date, datetime, etc.), including `nullable` support.

- **Controller Generation**  
  Builds a resource controller with index, create, store, edit, update, show, and destroy methods.

- **Form Request Generation**  
  Generates a dedicated FormRequest class for server-side validation of your model's fields.

- **Blade View Scaffolding**  
  Produces a complete set of Blade templates (`index`, `create`, `edit`, `show`) under `resources/views/{resource}`—including dynamic form inputs, tables, and detail pages.

- **Automatic Route Registration**  
  Appends a `Route::resource(...)` declaration to `routes/web.php`, enabling immediate access to your new CRUD endpoints.

- **Web UI for Configuration**  
  A Bootstrap- and Font Awesome-powered interface where you can:
  - Enter Model Name (PascalCase) and Table Name (snake_case, plural).
  - Choose whether to generate a migration/seeder.
  - Define fields (name, type, validation rules, nullable).
  - Dynamically add/remove fields.
  - Load quick field templates (Blog Post, Product, User).

- **Programmatic (CLI) Usage**  
  Inject `CrudGeneratorService` into your own Artisan commands to generate CRUD definitions from an array of parameters.  


## Requirements

- PHP 8.0+  
- Laravel 9.x+  
- Composer  
- Writable directories:  
  - `app/Models/` (for models)  
  - `database/migrations/` (for migrations)  
  - `app/Http/Controllers/` (for controllers)  
  - `app/Http/Requests/` (for form requests)  
  - `resources/views/` (for views)  

---

## Installation

1. **Require the Package**  
   ```bash
   composer require sajidul-islam/laravel-crud-generator
   ```

2. **Publish Stubs (Optional)**  
   If you want to customize default stubs, run:

   ```bash
   php artisan vendor:publish --provider="SajidUlIslam\CrudGenerator\CrudGeneratorServiceProvider" --tag=stubs
   ```

   This copies the `stubs/` directory into `resources/stubs/vendor/crud-generator/`.

3. **Publish Views (UI) (Optional)**  
   To override the built-in web UI, publish the view files:

   ```bash
   php artisan vendor:publish --provider="SajidUlIslam\CrudGenerator\CrudGeneratorServiceProvider" --tag=views
   ```

   You'll find the published Blade file(s) under `resources/views/vendor/crud-generator/`.

4. **(Optional) Migrate Your Database**

   ```bash
   php artisan migrate
   ```

   Ensure your existing migrations are up-to-date before generating new tables.

---

## Configuration

If a configuration file is provided, you can publish it with:

```bash
php artisan vendor:publish --provider="SajidUlIslam\CrudGenerator\CrudGeneratorServiceProvider"
```

Typical settings include:

* Custom namespaces for generated classes.
* Default view paths.
* List of available field types in the UI dropdown.

If no `config/crud-generator.php` appears after installation, the package works out-of-the-box with sensible defaults.

---

## Usage

### Web UI

![crud-generator](https://github.com/user-attachments/assets/bd781b3c-db78-43e2-936a-d16809607ba0)

1. **Access the Generator**  
   Open your browser and navigate to `/crud-generator`. If your application uses a custom prefix (e.g., `admin`), adjust the URL accordingly.

2. **Fill Out the Form**

   * **Model Name (PascalCase)** (e.g., `Post`)
   * **Table Name (snake_case, plural)** (e.g., `posts`—auto-filled from Model Name)
   * **Generate Migration** (checkbox)
   * **Generate Seeder** (checkbox)
   * **Fields**

     * **Field Name** (snake_case, no spaces)
     * **Type** (string, text, integer, boolean, date, datetime, email, password)
     * **Validation Rules** (Laravel validation syntax, e.g., `required|string|max:255`)
     * **Nullable** (checkbox)
   * **Quick Templates**

     * Click a template button (e.g., "Blog Post", "Product", "User") to load predefined fields.

3. **Generate CRUD**  
   Click the "Generate CRUD" button. The package will scaffold files and append routes. A "Results" panel will list every created/modified file and next steps (e.g., run `php artisan migrate`).

### Programmatic (CLI/Artisan)

You can call the `CrudGeneratorService` from within an Artisan command or a controller:

```php
use SajidUlIslam\CrudGenerator\Services\CrudGeneratorService;

$data = [
    'model_name'     => 'Book',
    'table_name'     => 'books',
    'with_migration' => true,
    'with_seeder'    => false,
    'fields' => [
        [
            'name'       => 'title',
            'type'       => 'string',
            'validation' => 'required|string|max:255',
            'nullable'   => false,
        ],
        [
            'name'       => 'author',
            'type'       => 'string',
            'validation' => 'required|string|max:255',
            'nullable'   => false,
        ],
        [
            'name'       => 'published_date',
            'type'       => 'date',
            'validation' => 'nullable|date',
            'nullable'   => true,
        ],
        [
            'name'       => 'summary',
            'type'       => 'text',
            'validation' => 'nullable|string',
            'nullable'   => true,
        ],
        [
            'name'       => 'is_best_seller',
            'type'       => 'boolean',
            'validation' => 'boolean',
            'nullable'   => false,
        ],
    ],
];

$service = app(CrudGeneratorService::class);
$generated = $service->generateCrud($data);

// $generated is an array of created file paths and the appended route
```

---

## Troubleshooting

* **Directory Permissions**  
  Ensure `app/Models/`, `database/migrations/`, `app/Http/Controllers/`, `app/Http/Requests/`, and `resources/views/` are writable (`chmod -R 755 <directory>`).

* **Route Duplication**  
  If you regenerate CRUD for the same resource, you may see duplicate `Route::resource(...)` entries. Manually remove the extra line in `routes/web.php`.

* **UI FieldTypes Not Loading**  
  If the UI's `<select>` elements show empty, verify that your view is receiving a `$fieldTypes` array. In a controller method serving the UI, you should pass something like:

  ```php
  $fieldTypes = [
      'string'   => 'String',
      'text'     => 'Text',
      'integer'  => 'Integer',
      'boolean'  => 'Boolean',
      'date'     => 'Date',
      'datetime' => 'DateTime',
      'email'    => 'Email',
      'password' => 'Password',
  ];

  return view('vendor.crud-generator.index', compact('fieldTypes'));
  ```

---

## Contributing

1. **Fork the Repo**  
   Clone your fork:

   ```bash
   git clone https://github.com/your-username/laravel-crud-generator.git
   cd laravel-crud-generator
   ```

2. **Create a New Branch**

   ```bash
   git checkout -b feature/my-feature
   ```

3. **Make Changes & Commit**  
   Use clear, concise commit messages.

4. **Push & Open a PR**

   ```bash
   git push origin feature/my-feature
   ```

   Open a pull request against the `main` branch. Include usage examples or tests if you introduce new functionality.

---

## License

This package is released under the [MIT License](LICENSE). Use, modify, and distribute freely.

---

## Version

1.0.0

## Acknowledgments

* Inspired by Laravel's scaffolders and community-driven CRUD generators.
* UI built with [Bootstrap 5](https://getbootstrap.com/) and [Font Awesome](https://fontawesome.com/).
* Thanks to the Laravel community for ongoing inspiration and best practices.

Happy coding! 🚀
