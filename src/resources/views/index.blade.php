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
                        accent: {
                            DEFAULT: "hsl(210 40% 96.1%)",
                            foreground: "hsl(222.2 47.4% 11.2%)",
                        },
                    },
                    borderRadius: {
                        lg: "0.5rem",
                        md: "calc(0.5rem - 2px)",
                        sm: "calc(0.5rem - 4px)",
                    },
                }
            }
        }
    </script>
    <style>
        .field-card {
            transition: all 0.2s ease-in-out;
        }
        .field-card:hover {
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        }
    </style>
</head>
<body class="bg-muted/30 min-h-screen" x-data="crudGenerator()">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white shadow-lg">
        <div class="container mx-auto px-4 py-8">
            <div class="text-center">
                <h1 class="text-4xl font-bold mb-2">
                    <i class="fas fa-magic mr-3"></i>Laravel CRUD Generator
                </h1>
                <p class="text-blue-100 text-lg">Build powerful CRUDs with advanced features</p>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8 max-w-6xl">
        <!-- Main Configuration Card -->
        <div class="bg-background rounded-lg shadow-sm border border-border mb-6">
            <div class="border-b border-border px-6 py-4">
                <h2 class="text-2xl font-semibold text-foreground flex items-center">
                    <i class="fas fa-cogs mr-2 text-primary"></i>
                    CRUD Configuration
                </h2>
            </div>
            
            <div class="p-6">
                <form id="crudForm" @submit.prevent="generateCrud()">
                    @csrf
                    
                    <!-- Basic Information -->
                    <div class="mb-6">
                        <h3 class="text-sm font-medium text-muted-foreground mb-3 uppercase tracking-wide">Basic Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label for="model_name" class="text-sm font-medium text-foreground">Model Name</label>
                                <input type="text" 
                                    id="model_name" 
                                    name="model_name" 
                                    placeholder="e.g., Post" 
                                    required
                                    x-model="modelName"
                                    @input="autoGenerateTableName()"
                                    class="w-full px-3 py-2 border border-input rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent">
                                <p class="text-xs text-muted-foreground">Singular, PascalCase</p>
                            </div>
                            <div class="space-y-2">
                                <label for="table_name" class="text-sm font-medium text-foreground">Table Name</label>
                                <input type="text" 
                                    id="table_name" 
                                    name="table_name" 
                                    placeholder="e.g., posts" 
                                    required
                                    x-model="tableName"
                                    class="w-full px-3 py-2 border border-input rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent">
                                <p class="text-xs text-muted-foreground">Plural, snake_case</p>
                            </div>
                        </div>
                    </div>

                    <!-- Generation Options -->
                    <div class="mb-6">
                        <h3 class="text-sm font-medium text-muted-foreground mb-3 uppercase tracking-wide">Generation Options</h3>
                        
                        <!-- API Mode Toggle -->
                        <div class="mb-4 p-4 bg-gradient-to-r from-indigo-50 to-blue-50 border border-indigo-200 rounded-lg">
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" id="api_mode" name="api_mode" 
                                    x-model="apiMode"
                                    class="w-5 h-5 text-indigo-600 border-indigo-300 rounded focus:ring-2 focus:ring-indigo-500">
                                <span class="ml-3 text-sm font-semibold text-indigo-900">
                                    <i class="fas fa-plug mr-1"></i>API Only Mode
                                </span>
                                <span class="ml-2 text-xs text-indigo-600">
                                    Generates API controller, resources &amp; api routes
                                </span>
                            </label>
                        </div>

                        <div id="layout-options" class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4" x-show="!apiMode" x-transition>
                            <div class="space-y-2">
                                <label for="layout" class="text-sm font-medium text-foreground">Layout</label>
                                <input type="text" 
                                    id="layout" 
                                    name="layout" 
                                    placeholder="layouts.app" 
                                    value="layouts.app"
                                    x-model="layout"
                                    class="w-full px-3 py-2 border border-input rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent">
                                <p class="text-xs text-muted-foreground">Dot notation (e.g. layouts.admin). Auto-created if missing.</p>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-4">
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" id="with_migration" name="with_migration" checked 
                                    x-model="withMigration"
                                    class="w-4 h-4 text-primary border-input rounded focus:ring-2 focus:ring-ring">
                                <span class="ml-2 text-sm text-foreground">
                                    <i class="fas fa-database mr-1 text-muted-foreground"></i>Generate Migration
                                </span>
                            </label>
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" id="with_seeder" name="with_seeder" 
                                    x-model="withSeeder"
                                    class="w-4 h-4 text-primary border-input rounded focus:ring-2 focus:ring-ring">
                                <span class="ml-2 text-sm text-foreground">
                                    <i class="fas fa-seedling mr-1 text-muted-foreground"></i>Generate Seeder
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="border-t border-border my-6"></div>

                    <!-- Fields Section -->
                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-medium text-muted-foreground uppercase tracking-wide">
                                <i class="fas fa-list mr-2"></i>Model Fields
                            </h3>
                            <button type="button" 
                                @click="addField()"
                                class="inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors shadow-sm">
                                <i class="fas fa-plus mr-2"></i>Add Field
                            </button>
                        </div>
                        
                        <div id="fields-container" class="space-y-4">
                            <template x-for="(field, index) in fields" :key="field.id">
                                <div class="field-card bg-muted/50 rounded-lg border border-border p-5 hover:border-primary/50 transition-all"
                                     :id="'field-' + field.id"
                                     x-show="true">
                                    <div class="flex items-center justify-between mb-4 cursor-pointer" @click="toggleField(field.id)">
                                        <div class="flex items-center space-x-3">
                                            <span class="field-name-display text-lg font-semibold text-foreground" x-text="field.name || 'New Field'"></span>
                                            <span class="field-type-display px-2 py-1 bg-secondary text-secondary-foreground text-xs font-medium rounded-md" x-text="getFieldTypeLabel(field.type)"></span>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <i class="fas fa-chevron-down text-muted-foreground transition-transform" :class="field.collapsed ? 'rotate-180' : ''"></i>
                                            <button type="button" @click.stop="removeField(field.id)" 
                                                class="p-2 text-red-500 hover:bg-red-50 rounded-md transition-colors">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="space-y-4" x-show="!field.collapsed" x-transition>
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <div class="space-y-2">
                                                <label class="text-sm font-medium text-foreground">Field Name</label>
                                                <input type="text" 
                                                    class="field-name-input w-full px-3 py-2 border border-input rounded-md focus:outline-none focus:ring-2 focus:ring-ring" 
                                                    x-model="field.name" 
                                                    @change="updateFieldHeader(index)"
                                                    placeholder="field_name" required>
                                            </div>
                                            <div class="space-y-2">
                                                <label class="text-sm font-medium text-foreground">Type</label>
                                                <select class="field-type-input w-full px-3 py-2 border border-input rounded-md focus:outline-none focus:ring-2 focus:ring-ring" 
                                                    x-model="field.type" 
                                                    @change="updateFieldHeader(index)"
                                                    required>
                                                    <template x-for="[key, label] in Object.entries(fieldTypes)" :key="key">
                                                        <option :value="key" x-text="label"></option>
                                                    </template>
                                                </select>
                                            </div>
                                            <div class="space-y-2">
                                                <label class="text-sm font-medium text-foreground">Validation Rules</label>
                                                <input type="text" 
                                                    class="w-full px-3 py-2 border border-input rounded-md focus:outline-none focus:ring-2 focus:ring-ring" 
                                                    x-model="field.validation" 
                                                    placeholder="required|max:255">
                                            </div>
                                        </div>

                                        <div>
                                            <label class="inline-flex items-center cursor-pointer">
                                                <input type="checkbox" 
                                                    x-model="field.nullable"
                                                    class="w-4 h-4 text-primary border-input rounded focus:ring-2 focus:ring-ring">
                                                <span class="ml-2 text-sm text-foreground">Nullable (allows NULL in database)</span>
                                            </label>
                                        </div>

                                        <div class="visibility-section space-y-2" x-show="!apiMode" x-transition>
                                            <label class="text-sm font-medium text-foreground">Show field in:</label>
                                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                                <label class="flex items-center justify-center space-x-2 p-3 border border-input rounded-md cursor-pointer hover:bg-accent transition-colors" :class="field.visibility.create ? 'bg-blue-50 border-primary' : ''">
                                                    <input type="checkbox" 
                                                        x-model="field.visibility.create"
                                                        class="w-4 h-4 text-primary border-input rounded focus:ring-2 focus:ring-ring">
                                                    <span class="text-sm">
                                                        <i class="fas fa-plus-circle text-green-500 mr-1"></i>Create
                                                    </span>
                                                </label>
                                                <label class="flex items-center justify-center space-x-2 p-3 border border-input rounded-md cursor-pointer hover:bg-accent transition-colors" :class="field.visibility.edit ? 'bg-blue-50 border-primary' : ''">
                                                    <input type="checkbox" 
                                                        x-model="field.visibility.edit"
                                                        class="w-4 h-4 text-primary border-input rounded focus:ring-2 focus:ring-ring">
                                                    <span class="text-sm">
                                                        <i class="fas fa-edit text-amber-500 mr-1"></i>Edit
                                                    </span>
                                                </label>
                                                <label class="flex items-center justify-center space-x-2 p-3 border border-input rounded-md cursor-pointer hover:bg-accent transition-colors" :class="field.visibility.index ? 'bg-blue-50 border-primary' : ''">
                                                    <input type="checkbox" 
                                                        x-model="field.visibility.index"
                                                        class="w-4 h-4 text-primary border-input rounded focus:ring-2 focus:ring-ring">
                                                    <span class="text-sm">
                                                        <i class="fas fa-table text-blue-500 mr-1"></i>Table
                                                    </span>
                                                </label>
                                                <label class="flex items-center justify-center space-x-2 p-3 border border-input rounded-md cursor-pointer hover:bg-accent transition-colors" :class="field.visibility.show ? 'bg-blue-50 border-primary' : ''">
                                                    <input type="checkbox" 
                                                        x-model="field.visibility.show"
                                                        class="w-4 h-4 text-primary border-input rounded focus:ring-2 focus:ring-ring">
                                                    <span class="text-sm">
                                                        <i class="fas fa-eye text-cyan-500 mr-1"></i>Detail
                                                    </span>
                                                </label>
                                            </div>
                                        </div>

                                        <div class="visibility-section space-y-2" x-show="!apiMode" x-transition>
                                            <label class="text-sm font-medium text-foreground">Table Column Options:</label>
                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                                <label class="inline-flex items-center cursor-pointer">
                                                    <input type="checkbox" 
                                                        x-model="field.table.sortable"
                                                        class="w-4 h-4 text-primary border-input rounded focus:ring-2 focus:ring-ring">
                                                    <span class="ml-2 text-sm text-foreground">
                                                        <i class="fas fa-sort mr-1 text-muted-foreground"></i>Sortable
                                                    </span>
                                                </label>
                                                <label class="inline-flex items-center cursor-pointer">
                                                    <input type="checkbox" 
                                                        x-model="field.table.searchable"
                                                        class="w-4 h-4 text-primary border-input rounded focus:ring-2 focus:ring-ring">
                                                    <span class="ml-2 text-sm text-foreground">
                                                        <i class="fas fa-search mr-1 text-muted-foreground"></i>Searchable
                                                    </span>
                                                </label>
                                                <div class="space-y-1">
                                                    <label class="text-xs text-muted-foreground">Formatter:</label>
                                                    <select class="w-full px-3 py-2 border border-input rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-ring" 
                                                        x-model="field.table.formatter"
                                                        @change="toggleBadgeColors(field.id)">
                                                        <template x-for="[key, label] in Object.entries(formatters)" :key="key">
                                                            <option :value="key" x-text="label"></option>
                                                        </template>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div :id="'badge-colors-' + field.id" class="visibility-section space-y-2" x-show="field.table.formatter === 'badge' && !apiMode" x-transition>
                                            <label class="text-sm font-medium text-foreground">Badge Mapping (value → color + text)</label>
                                            <p class="text-xs text-muted-foreground mb-2">Map values to badge colors and display text</p>
                                            <div :id="'badge-mappings-' + field.id" class="space-y-2">
                                                <template x-for="(mapping, mIndex) in field.table.badgeMappings" :key="mIndex">
                                                    <div class="flex gap-2">
                                                        <input type="text" 
                                                            class="flex-1 px-3 py-2 border border-input rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-ring" 
                                                            x-model="mapping.value" placeholder="Value (e.g., 1, active)">
                                                        <select class="flex-1 px-3 py-2 border border-input rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-ring" 
                                                            x-model="mapping.color">
                                                            <template x-for="color in badgeColors" :key="color">
                                                                <option :value="color" x-text="color"></option>
                                                            </template>
                                                        </select>
                                                        <input type="text" 
                                                            class="flex-1 px-3 py-2 border border-input rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-ring" 
                                                            x-model="mapping.text" placeholder="Display text (e.g., Active)">
                                                        <button type="button" 
                                                            @click="removeBadgeMapping(field.id, mIndex)" 
                                                            class="px-3 py-2 border border-red-200 text-red-500 rounded-md hover:bg-red-50 transition-colors">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </div>
                                                </template>
                                            </div>
                                            <button type="button" 
                                                @click="addBadgeMapping(field.id)" 
                                                class="text-sm text-primary hover:text-primary/80 flex items-center">
                                                <i class="fas fa-plus mr-1"></i>Add Badge Mapping
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Generate Button -->
                    <div class="flex justify-center mt-8">
                        <button type="submit" 
                            :disabled="isGenerating"
                            class="inline-flex items-center px-8 py-3 bg-primary text-primary-foreground text-lg font-semibold rounded-md hover:bg-primary/90 transition-all shadow-lg hover:shadow-xl disabled:opacity-70 disabled:cursor-not-allowed">
                            <i class="fas" :class="isGenerating ? 'fa-spinner fa-spin' : 'fa-magic'"></i>
                            <span x-text="isGenerating ? 'Generating...' : 'Generate CRUD'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Results Section -->
        <div id="results" class="bg-background rounded-lg shadow-sm border border-border" x-show="showResults" x-transition>
            <div class="border-b border-border px-6 py-4">
                <h3 class="text-xl font-semibold text-foreground flex items-center">
                    <i class="fas fa-check-circle text-green-500 mr-2"></i>Generation Results
                </h3>
            </div>
            <div class="p-6" id="results-content">
                <template x-if="resultMessage">
                    <div class="p-4 rounded-md" :class="resultType === 'success' ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'">
                        <p class="font-semibold" :class="resultType === 'success' ? 'text-green-800' : 'text-red-800'">
                            <span x-text="resultType === 'success' ? '✓ Success!' : '✗ Error!'"></span>
                        </p>
                        <p class="" :class="resultType === 'success' ? 'text-green-700' : 'text-red-700'" x-text="resultMessage"></p>
                    </div>
                </template>
                
                <template x-if="resultType === 'success' && resultFiles">
                    <div class="mt-4">
                        <h4 class="font-semibold text-foreground mb-3">Generated Files:</h4>
                        <div class="space-y-2">
                            <template x-for="(files, type) in resultFiles" :key="type">
                                <template x-if="Array.isArray(files)">
                                    <template x-for="file in files" :key="file">
                                        <div class="flex items-center justify-between p-3 bg-muted/50 rounded-md border border-border">
                                            <span class="text-sm"><i class="fas fa-file-code mr-2 text-primary"></i><span x-text="file"></span></span>
                                            <span class="px-2 py-1 text-xs bg-secondary text-secondary-foreground rounded-md" x-text="type"></span>
                                        </div>
                                    </template>
                                </template>
                                <template x-if="!Array.isArray(files)">
                                    <div class="flex items-center justify-between p-3 bg-muted/50 rounded-md border border-border">
                                        <span class="text-sm"><i class="fas fa-file-code mr-2 text-primary"></i><span x-text="files"></span></span>
                                        <span class="px-2 py-1 text-xs bg-secondary text-secondary-foreground rounded-md" x-text="type"></span>
                                    </div>
                                </template>
                            </template>
                        </div>
                        <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-md">
                            <h4 class="font-semibold text-blue-900 mb-2"><i class="fas fa-info-circle mr-2"></i>Next Steps:</h4>
                            <ol class="list-decimal list-inside space-y-1 text-sm text-blue-800">
                                <li>Run <code class="px-2 py-1 bg-blue-100 rounded text-xs">php artisan migrate</code> to create the database table</li>
                                <li>Visit your application to see the CRUD in action</li>
                                <li>Customize the generated files as needed</li>
                            </ol>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Quick Templates -->
        <div class="bg-background rounded-lg shadow-sm border border-border mt-6">
            <div class="border-b border-border px-6 py-4">
                <h3 class="text-lg font-semibold text-foreground flex items-center">
                    <i class="fas fa-bolt text-yellow-500 mr-2"></i>Quick Start Templates
                </h3>
            </div>
            <div class="p-6">
                <p class="text-sm text-muted-foreground mb-4">Load pre-configured templates to get started quickly:</p>
                <div class="flex flex-wrap gap-3">
                    <button type="button" @click="loadTemplate('blog')" 
                        class="inline-flex items-center px-4 py-2 border border-border rounded-md hover:bg-accent transition-colors">
                        <i class="fas fa-blog mr-2 text-blue-500"></i>Blog Post
                    </button>
                    <button type="button" @click="loadTemplate('product')" 
                        class="inline-flex items-center px-4 py-2 border border-border rounded-md hover:bg-accent transition-colors">
                        <i class="fas fa-box mr-2 text-green-500"></i>Product
                    </button>
                    <button type="button" @click="loadTemplate('user')" 
                        class="inline-flex items-center px-4 py-2 border border-border rounded-md hover:bg-accent transition-colors">
                        <i class="fas fa-user mr-2 text-purple-500"></i>User
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="mt-12 py-6 border-t border-border bg-background">
        <div class="container mx-auto px-4 text-center">
            <p class="text-sm text-muted-foreground">
                <i class="fas fa-code mr-2"></i>Laravel CRUD Generator - Making development faster and easier
            </p>
        </div>
    </footer>

    <script>
        function crudGenerator() {
            return {
                fieldTypes: @json($fieldTypes),
                formatters: {
                    'text': 'Plain Text',
                    'badge': 'Badge (Status)',
                    'link': 'Clickable Link',
                    'date': 'Formatted Date',
                    'boolean': 'Yes/No Badge'
                },
                badgeColors: ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark'],
                
                modelName: '',
                tableName: '',
                layout: 'layouts.app',
                withMigration: true,
                withSeeder: false,
                apiMode: false,
                
                fieldCounter: 0,
                fields: [],
                
                showResults: false,
                resultMessage: '',
                resultType: '',
                resultFiles: null,
                
                isGenerating: false,

                init() {
                    this.addField('name', 'string', 'required|string|max:255');
                    this.addField('description', 'text', 'nullable|string');
                },

                autoGenerateTableName() {
                    this.tableName = this.modelName.toLowerCase() + 's';
                },

                getFieldTypeLabel(type) {
                    return this.fieldTypes[type] || type;
                },

                addField(name = '', type = 'string', validation = '') {
                    this.fieldCounter++;
                    const id = this.fieldCounter;
                    
                    const field = {
                        id: id,
                        name: name || 'New Field',
                        type: type,
                        validation: validation,
                        nullable: false,
                        visibility: {
                            create: true,
                            edit: true,
                            index: true,
                            show: true
                        },
                        table: {
                            sortable: true,
                            searchable: true,
                            formatter: 'text',
                            badgeMappings: []
                        },
                        collapsed: false
                    };
                    
                    this.fields.push(field);
                    
                    this.$nextTick(() => {
                        const el = document.getElementById(`field-${id}`);
                        if (el) {
                            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            el.classList.add('ring-2', 'ring-primary', 'ring-offset-2');
                            setTimeout(() => {
                                el.classList.remove('ring-2', 'ring-primary', 'ring-offset-2');
                            }, 1500);
                        }
                    });

                    if (name.includes('status') || name.includes('state')) {
                        field.table.formatter = 'badge';
                        field.table.badgeMappings = [
                            { value: 'active', color: 'success', text: 'Active' },
                            { value: 'inactive', color: 'secondary', text: 'Inactive' },
                            { value: 'pending', color: 'warning', text: 'Pending' }
                        ];
                    }
                    
                    if (type === 'boolean') {
                        field.table.formatter = 'boolean';
                        field.table.badgeMappings = [
                            { value: '1', color: 'success', text: 'Active' },
                            { value: '0', color: 'secondary', text: 'Inactive' }
                        ];
                    }
                },

                toggleField(fieldId) {
                    const field = this.fields.find(f => f.id === fieldId);
                    if (field) {
                        field.collapsed = !field.collapsed;
                    }
                },

                updateFieldHeader(index) {
                },

                toggleBadgeColors(fieldId) {
                },

                addBadgeMapping(fieldId) {
                    const field = this.fields.find(f => f.id === fieldId);
                    if (field) {
                        field.table.badgeMappings.push({ value: '', color: 'primary', text: '' });
                    }
                },

                removeBadgeMapping(fieldId, index) {
                    const field = this.fields.find(f => f.id === fieldId);
                    if (field) {
                        field.table.badgeMappings.splice(index, 1);
                    }
                },

                removeField(fieldId) {
                    if (confirm('Remove this field?')) {
                        const field = this.fields.find(f => f.id === fieldId);
                        if (field) {
                            const idx = this.fields.indexOf(field);
                            this.fields.splice(idx, 1);
                        }
                    }
                },

                loadTemplate(templateName) {
                    const templates = {
                        blog: [
                            { name: 'title', type: 'string', validation: 'required|string|max:255' },
                            { name: 'slug', type: 'string', validation: 'required|string|unique:posts' },
                            { name: 'content', type: 'text', validation: 'required|string' },
                            { name: 'status', type: 'string', validation: 'required|in:draft,published' },
                            { name: 'published_at', type: 'datetime', validation: 'nullable|date' }
                        ],
                        product: [
                            { name: 'name', type: 'string', validation: 'required|string|max:255' },
                            { name: 'sku', type: 'string', validation: 'required|string|unique:products' },
                            { name: 'price', type: 'integer', validation: 'required|numeric|min:0' },
                            { name: 'stock', type: 'integer', validation: 'required|integer|min:0' },
                            { name: 'status', type: 'string', validation: 'required|in:active,inactive' }
                        ],
                        user: [
                            { name: 'name', type: 'string', validation: 'required|string|max:255' },
                            { name: 'email', type: 'email', validation: 'required|email|unique:users' },
                            { name: 'phone', type: 'string', validation: 'nullable|string' },
                            { name: 'status', type: 'string', validation: 'required|in:active,inactive,suspended' }
                        ]
                    };
                    
                    if (templates[templateName]) {
                        this.fields = [];
                        this.fieldCounter = 0;
                        templates[templateName].forEach(field => {
                            this.addField(field.name, field.type, field.validation);
                        });
                    }
                },

                async generateCrud() {
                    this.isGenerating = true;
                    
                    const data = {
                        model_name: this.modelName,
                        table_name: this.tableName,
                        layout: this.layout || 'layouts.app',
                        with_migration: this.withMigration,
                        with_seeder: this.withSeeder,
                        api_mode: this.apiMode,
                        fields: this.fields.map(f => ({
                            name: f.name,
                            type: f.type,
                            validation: f.validation,
                            nullable: f.nullable,
                            visibility: f.visibility,
                            table: {
                                sortable: f.table.sortable,
                                searchable: f.table.searchable,
                                formatter: f.table.formatter,
                                badgeColors: f.table.badgeMappings.reduce((acc, m) => {
                                    if (m.value) acc[m.value] = m.color;
                                    return acc;
                                }, {}),
                                badgeTexts: f.table.badgeMappings.reduce((acc, m) => {
                                    if (m.value && m.text) acc[m.value] = m.text;
                                    return acc;
                                }, {})
                            }
                        }))
                    };

                    try {
                        const response = await fetch('{{ route("crud-generator.generate") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify(data)
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            this.resultMessage = result.message;
                            this.resultFiles = result.files;
                            this.resultType = 'success';
                        } else {
                            this.resultMessage = result.message;
                            this.resultFiles = null;
                            this.resultType = 'error';
                        }
                        
                        this.showResults = true;
                        this.$nextTick(() => {
                            document.getElementById('results')?.scrollIntoView({ behavior: 'smooth' });
                        });
                    } catch (error) {
                        console.error('Error:', error);
                        this.resultMessage = 'An error occurred while generating CRUD';
                        this.resultFiles = null;
                        this.resultType = 'error';
                        this.showResults = true;
                    } finally {
                        this.isGenerating = false;
                    }
                }
            };
        }
    </script>
</body>
</html>