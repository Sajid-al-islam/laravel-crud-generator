<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRUD Generator</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        border: "hsl(214.3 31.8% 91.4%)",
                        input: "hsl(214.3 31.8% 91.4%)",
                        ring: "hsl(221.2 83.2% 53.3%)",
                        background: "hsl(0 0% 100%)",
                        foreground: "hsl(222.2 84% 4.9%)",
                        primary: {
                            DEFAULT: "hsl(221.2 83.2% 53.3%)",
                            foreground: "hsl(210 40% 98%)",
                        },
                        secondary: {
                            DEFAULT: "hsl(210 40% 96.1%)",
                            foreground: "hsl(222.2 47.4% 11.2%)",
                        },
                        muted: {
                            DEFAULT: "hsl(210 40% 96.1%)",
                            foreground: "hsl(215.4 16.3% 46.9%)",
                        },
                    },
                }
            }
        }
    </script>
    <style>
        .field-card { transition: all 0.2s ease-in-out; }
        .field-card:hover { box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1); }
        .stack-card { transition: all 0.2s ease-in-out; }
        .stack-card:hover { transform: translateY(-2px); box-shadow: 0 10px 25px -5px rgb(0 0 0 / 0.1); }
        .stack-card.active { border-color: hsl(221.2 83.2% 53.3%); background: hsl(214.3 31.8% 91.4%); }
        .glow { box-shadow: 0 0 0 1px hsl(221.2 83.2% 53.3%), 0 0 20px rgba(59, 130, 246, 0.3); }
    </style>
</head>
<body class="bg-muted/30 min-h-screen" x-data="crudGenerator()">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white shadow-lg">
        <div class="container mx-auto px-4 py-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">
                        <i class="fas fa-magic mr-2"></i>Laravel CRUD Generator
                    </h1>
                    <p class="text-blue-100 mt-1">Generate complete CRUD for any stack — zero boilerplate.</p>
                </div>
                <a href="{{ route('crud-generator.landing') }}" class="text-blue-100 hover:text-white text-sm">
                    <i class="fas fa-arrow-left mr-1"></i>Back to landing
                </a>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8 max-w-6xl">
        <form id="crudForm" @submit.prevent="generateCrud()">
            @csrf

            <!-- Stack selector -->
            <div class="bg-background rounded-lg shadow-sm border border-border mb-6">
                <div class="border-b border-border px-6 py-4">
                    <h2 class="text-xl font-semibold text-foreground flex items-center">
                        <i class="fas fa-layer-group mr-2 text-primary"></i>Choose Stack
                    </h2>
                </div>
                <div class="p-6 grid grid-cols-2 md:grid-cols-4 gap-3">
                    <template x-for="(label, key) in stacks" :key="key">
                        <label class="stack-card border-2 border-border rounded-lg p-4 cursor-pointer"
                               :class="stack === key ? 'active glow' : ''">
                            <input type="radio" name="stack" :value="key" x-model="stack" class="sr-only">
                            <div class="flex items-center gap-3">
                                <span class="text-2xl" x-text="stackIcons[key] || '⚡'"></span>
                                <div>
                                    <div class="font-semibold capitalize" x-text="key"></div>
                                    <div class="text-xs text-muted-foreground" x-text="label"></div>
                                </div>
                            </div>
                        </label>
                    </template>
                </div>
            </div>

            <!-- Basic info + options -->
            <div class="bg-background rounded-lg shadow-sm border border-border mb-6">
                <div class="border-b border-border px-6 py-4">
                    <h2 class="text-xl font-semibold text-foreground flex items-center">
                        <i class="fas fa-cogs mr-2 text-primary"></i>Configuration
                    </h2>
                </div>
                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-foreground">Model Name</label>
                            <input type="text" name="model_name" x-model="modelName" @input="autoGenerateTableName()"
                                placeholder="Post" required
                                class="w-full px-3 py-2 border border-input rounded-md focus:outline-none focus:ring-2 focus:ring-ring">
                            <p class="text-xs text-muted-foreground">Singular, PascalCase</p>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-foreground">Table Name</label>
                            <input type="text" name="table_name" x-model="tableName"
                                placeholder="posts" required
                                class="w-full px-3 py-2 border border-input rounded-md focus:outline-none focus:ring-2 focus:ring-ring">
                            <p class="text-xs text-muted-foreground">Plural, snake_case</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4" x-show="stack === 'blade'">
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-foreground">Layout</label>
                            <input type="text" name="layout" x-model="layout" placeholder="layouts.app"
                                class="w-full px-3 py-2 border border-input rounded-md focus:outline-none focus:ring-2 focus:ring-ring">
                        </div>
                    </div>

                    <!-- Toggles -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex items-center gap-3 p-3 border border-border rounded-md">
                            <input type="checkbox" id="with_migration" x-model="withMigration" class="w-4 h-4">
                            <label for="with_migration" class="text-sm text-foreground cursor-pointer">
                                <i class="fas fa-database mr-1 text-muted-foreground"></i>Generate migration
                            </label>
                        </div>
                        <div class="flex items-center gap-3 p-3 border border-border rounded-md">
                            <input type="checkbox" id="with_service" x-model="withService" class="w-4 h-4">
                            <label for="with_service" class="text-sm text-foreground cursor-pointer">
                                <i class="fas fa-cube mr-1 text-muted-foreground"></i>Generate service class
                            </label>
                        </div>
                        <div class="flex items-center gap-3 p-3 border border-border rounded-md" x-show="withService">
                            <input type="checkbox" id="with_repository" x-model="withRepository" class="w-4 h-4">
                            <label for="with_repository" class="text-sm text-foreground cursor-pointer">
                                <i class="fas fa-archive mr-1 text-muted-foreground"></i>Generate repository (implies service)
                            </label>
                        </div>
                        <div class="flex items-center gap-3 p-3 border border-border rounded-md">
                            <input type="checkbox" id="force" x-model="force" class="w-4 h-4">
                            <label for="force" class="text-sm text-foreground cursor-pointer">
                                <i class="fas fa-bolt mr-1 text-muted-foreground"></i>Overwrite existing files
                            </label>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-foreground">Custom stubs path <span class="text-muted-foreground text-xs">(optional)</span></label>
                        <input type="text" name="custom_stubs" x-model="customStubs"
                            placeholder="./crud-stubs"
                            class="w-full px-3 py-2 border border-input rounded-md focus:outline-none focus:ring-2 focus:ring-ring">
                        <p class="text-xs text-muted-foreground">Absolute or project-relative path. Resolved before package built-in stubs.</p>
                    </div>
                </div>
            </div>

            <!-- Fields -->
            <div class="bg-background rounded-lg shadow-sm border border-border mb-6">
                <div class="border-b border-border px-6 py-4 flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-foreground flex items-center">
                        <i class="fas fa-list mr-2 text-primary"></i>Model Fields
                    </h2>
                    <button type="button" @click="addField()"
                        class="inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors shadow-sm">
                        <i class="fas fa-plus mr-2"></i>Add Field
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    <template x-for="(field, index) in fields" :key="field.id">
                        <div class="field-card bg-muted/50 rounded-lg border border-border p-5">
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                                <div class="space-y-1">
                                    <label class="text-xs text-muted-foreground">Name</label>
                                    <input type="text" class="w-full px-3 py-2 border border-input rounded-md"
                                        x-model="field.name" placeholder="title" required>
                                </div>
                                <div class="space-y-1">
                                    <label class="text-xs text-muted-foreground">Type</label>
                                    <select class="w-full px-3 py-2 border border-input rounded-md" x-model="field.type">
                                        <template x-for="[key, label] in Object.entries(fieldTypes)" :key="key">
                                            <option :value="key" x-text="label"></option>
                                        </template>
                                    </select>
                                </div>
                                <div class="space-y-1">
                                    <label class="text-xs text-muted-foreground">Validation</label>
                                    <input type="text" class="w-full px-3 py-2 border border-input rounded-md"
                                        x-model="field.validation" placeholder="required|max:255">
                                </div>
                                <div class="flex items-end gap-3">
                                    <label class="inline-flex items-center gap-2 text-sm">
                                        <input type="checkbox" x-model="field.nullable" class="w-4 h-4">Nullable
                                    </label>
                                    <button type="button" @click="removeField(field.id)"
                                        class="ml-auto p-2 text-red-500 hover:bg-red-50 rounded-md">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Action buttons -->
            <div class="flex items-center gap-3 mb-6">
                <button type="button" @click="preview()"
                    class="px-4 py-2 border border-border bg-background rounded-md hover:bg-accent">
                    <i class="fas fa-eye mr-1"></i>Preview
                </button>
                <button type="submit" :disabled="loading"
                    class="ml-auto px-6 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 disabled:opacity-50">
                    <i class="fas fa-magic mr-1"></i>
                    <span x-show="!loading">Generate CRUD</span>
                    <span x-show="loading">Generating…</span>
                </button>
            </div>
        </form>

        <!-- Result -->
        <div x-show="result || error" class="bg-background rounded-lg shadow-sm border border-border mb-6" x-cloak>
            <div class="border-b border-border px-6 py-4">
                <h2 class="text-xl font-semibold text-foreground flex items-center" x-show="result">
                    <i class="fas fa-check-circle mr-2 text-green-500"></i>Generated
                </h2>
                <h2 class="text-xl font-semibold text-foreground flex items-center" x-show="error">
                    <i class="fas fa-exclamation-circle mr-2 text-red-500"></i>Error
                </h2>
            </div>
            <div class="p-6">
                <pre class="bg-zinc-900 text-zinc-100 rounded-md p-4 text-sm overflow-x-auto font-mono" x-text="error || formatResult(result)"></pre>
            </div>
        </div>

        <!-- Preview panel -->
        <div x-show="previewData" class="bg-background rounded-lg shadow-sm border border-border mb-6" x-cloak>
            <div class="border-b border-border px-6 py-4">
                <h2 class="text-xl font-semibold text-foreground flex items-center">
                    <i class="fas fa-search mr-2 text-primary"></i>Dry-run preview
                </h2>
            </div>
            <div class="p-6">
                <pre class="bg-zinc-900 text-zinc-100 rounded-md p-4 text-sm overflow-x-auto font-mono" x-text="JSON.stringify(previewData, null, 2)"></pre>
            </div>
        </div>
    </div>

    <script>
        function crudGenerator() {
            return {
                stacks: @json(collect($stacks ?? [])->keys()->mapWithKeys(fn ($key) => [$key => ucfirst($key)])->toArray()),
                stackIcons: { blade: '🧩', api: '🔌', react: '⚛️', vue: '🟢', svelte: '🔥', livewire: '⚡', nova: '🚀', filament: '🧱' },
                stack: '{{ config('crud-generator.default_stack', 'blade') }}',
                modelName: '',
                tableName: '',
                layout: '{{ config('crud-generator.default_layout', 'layouts.app') }}',
                withMigration: true,
                withService: false,
                withRepository: false,
                force: false,
                customStubs: '',
                fieldTypes: @json($fieldTypes ?? []),
                fields: [],
                loading: false,
                result: null,
                error: null,
                previewData: null,

                init() {
                    this.addField('title', 'string', 'required|max:255');
                    this.addField('body', 'text', 'nullable');
                },

                addField(name = '', type = 'string', validation = 'nullable') {
                    this.fields.push({
                        id: Date.now() + Math.random(),
                        name, type, validation,
                        nullable: validation.includes('nullable') || !validation.includes('required'),
                        visibility: { create: true, edit: true, index: true, show: true },
                        table: { searchable: type === 'string', sortable: type !== 'text', formatter: 'text' },
                    });
                },

                removeField(id) {
                    this.fields = this.fields.filter(f => f.id !== id);
                },

                autoGenerateTableName() {
                    if (!this.tableName && this.modelName) {
                        this.tableName = this.modelName.toLowerCase().replace(/([a-z])([A-Z])/g, '$1_$2').toLowerCase() + 's';
                    }
                },

                async generateCrud() {
                    this.loading = true;
                    this.error = null;
                    this.result = null;

                    const payload = {
                        model_name: this.modelName,
                        table_name: this.tableName,
                        stack: this.stack,
                        layout: this.layout,
                        with_migration: this.withMigration,
                        with_service: this.withService,
                        with_repository: this.withRepository,
                        custom_stubs: this.customStubs,
                        force: this.force,
                        fields: this.fields.map(f => ({
                            name: f.name,
                            type: f.type,
                            validation: f.validation,
                            nullable: f.nullable,
                            searchable: f.table?.searchable,
                            sortable: f.table?.sortable,
                        })),
                    };

                    try {
                        const res = await fetch('{{ route('crud-generator.generate') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify(payload),
                        });
                        const data = await res.json();
                        if (data.success) {
                            this.result = data.files;
                        } else {
                            this.error = data.message;
                        }
                    } catch (e) {
                        this.error = e.message;
                    } finally {
                        this.loading = false;
                    }
                },

                async preview() {
                    this.error = null;
                    const payload = {
                        model_name: this.modelName,
                        table_name: this.tableName,
                        stack: this.stack,
                        fields: this.fields,
                    };
                    try {
                        const res = await fetch('{{ route('crud-generator.preview') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify(payload),
                        });
                        const data = await res.json();
                        this.previewData = data.preview || data;
                    } catch (e) {
                        this.error = e.message;
                    }
                },

                formatResult(result) {
                    if (!result) return '';
                    const lines = [];
                    for (const [role, value] of Object.entries(result)) {
                        if (Array.isArray(value)) {
                            value.forEach(v => v.path && lines.push(`✔ ${v.path} (${v.status || 'created'})`));
                        } else if (value && value.path) {
                            lines.push(`~ ${value.path} (${value.status || 'modified'})`);
                        }
                    }
                    return lines.join('\n');
                },
            };
        }
    </script>
</body>
</html>
