# Laravel CRUD Generator v3

Generate complete CRUD operations for **any Laravel stack** — Blade, API, React, Vue, Svelte, Livewire, Nova, or Filament — from a single artisan command or a slick web UI.

```
$ php artisan make:crud posts --stack=react --service

✔ app/Models/Post.php
✔ app/Http/Controllers/PostController.php
✔ app/Http/Requests/StorePostRequest.php
✔ app/Http/Requests/UpdatePostRequest.php
✔ app/Services/PostService.php
✔ resources/js/pages/Posts/Index.tsx
✔ resources/js/pages/Posts/Create.tsx
✔ resources/js/pages/Posts/Edit.tsx
✔ resources/js/pages/Posts/Show.tsx
✔ resources/js/types/post.ts
~ routes/web.php  (appended)

Generated 10 files in 0.3s
```

## Features

- **8 first-party stacks**: Blade, API, React (Inertia + shadcn/ui), Vue (Inertia + shadcn-vue), Svelte (Inertia + shadcn-svelte), Livewire (Flux UI), Nova, Filament v3
- **Pluggable driver architecture** — every stack is its own class implementing `GeneratorDriver`
- **Service layer** — generate `PostService` to extract business logic from controllers
- **Repository layer** — generate `PostRepository` + `PostRepositoryInterface` (requires service)
- **Custom stubs** — bring your own `.stub` files, drop a `crud-generator.json` config
- **Interactive CLI** — `php artisan make:crud` launches a `laravel/prompts` wizard
- **Modern landing page** — beautiful dark-theme marketing page at `/`
- **Syntactically valid output** — every generated file parses
- **Fail-gracefully** — detects missing dependencies (Nova, Filament) with clear errors

## Installation

```bash
composer require sajidul-islam/laravel-crud-generator --dev
php artisan vendor:publish --tag=crud-generator
```

## Usage

### Interactive wizard

```bash
php artisan make:crud
```

You'll be prompted for the table name, stack, service layer, and fields.

### Non-interactive

```bash
php artisan make:crud posts --stack=react --service --force
```

### Available options

| Option | Description |
| --- | --- |
| `--stack=` | `blade`, `api`, `react`, `vue`, `svelte`, `livewire`, `nova`, `filament` |
| `--service` | Generate a `PostService` class |
| `--repository` | Generate repository (implies `--service`) |
| `--no-migration` | Skip migration generation |
| `--no-model` | Skip model generation |
| `--no-requests` | Skip form request generation |
| `--no-routes` | Skip route registration |
| `--custom-stubs=` | Path to a custom stubs directory |
| `--force` | Overwrite existing files |
| `--dry-run` | Show what would be generated without writing files |
| `--fields=` | JSON-encoded field definitions |

### Other commands

```bash
# Publish stubs to your project for customization
php artisan crud:publish-stubs --stack=react

# Import a crud-generator.json config
php artisan crud:import-config ./my-config.json

# Validate that stubs contain all required variables
php artisan crud:validate-stubs --stack=react
```

### Web UI

Visit `/crud-generator` in your browser. (Local environment only by default.)

Visit `/` for the landing page.

## Customization

### `crud-generator.json`

Drop a `crud-generator.json` in your project root to control imports, component library, etc:

```json
{
    "stack": "react",
    "customStubsPath": "./crud-stubs",
    "componentLibrary": "shadcn/ui",
    "imports": {
        "Button":   "@/components/ui/button",
        "Input":    "@/components/ui/input",
        "Table":    "@/components/ui/table",
        "Select":   "@/components/ui/select",
        "Textarea": "@/components/ui/textarea"
    },
    "wrappers": {
        "form": "AppLayout",
        "table": "AppLayout"
    },
    "pagePrefix": "Admin/"
}
```

### Custom stubs

```bash
php artisan crud:publish-stubs --stack=react
```

This copies the React stubs to `crud-stubs/react/` in your project root. Edit them and the generator will use your version.

### Configuration

`config/crud-generator.php` exposes:

- `default_stack` — fall-back stack when none is specified
- `allowed_environments` — environments where the generator is available
- `service_layer.pattern` — `suggested` | `required` | `off`
- `repository.enabled` — global default
- `stacks.<name>` — custom driver class overrides

## Driver Architecture

Every stack is a class implementing `SajidUlIslam\CrudGenerator\Contracts\GeneratorDriver`. Build your own:

```php
use SajidUlIslam\CrudGenerator\Contracts\GeneratorDriver;
use SajidUlIslam\CrudGenerator\CrudDefinition;
use SajidUlIslam\CrudGenerator\Drivers\AbstractDriver;

class CustomDriver extends AbstractDriver
{
    public function getStackName(): string { return 'custom'; }
    protected function resolveStackKey(): Stack { return Stack::Custom; }

    public function generate(CrudDefinition $definition): array
    {
        // ...
    }
}
```

Register it in `config/crud-generator.php`:

```php
'stacks' => [
    'custom' => \App\Generators\CustomDriver::class,
],
```

## Testing

```bash
composer test
```

## License

MIT © Muhammad Sajidul Islam
