<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRUD Generator</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
<body class="bg-muted/30 min-h-screen">
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
                <form id="crudForm">
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
                                    onchange="toggleApiMode()"
                                    class="w-5 h-5 text-indigo-600 border-indigo-300 rounded focus:ring-2 focus:ring-indigo-500">
                                <span class="ml-3 text-sm font-semibold text-indigo-900">
                                    <i class="fas fa-plug mr-1"></i>API Only Mode
                                </span>
                                <span class="ml-2 text-xs text-indigo-600">
                                    Generates API controller, resources &amp; api routes
                                </span>
                            </label>
                        </div>

                        <div id="layout-options" class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div class="space-y-2">
                                <label for="layout" class="text-sm font-medium text-foreground">Layout</label>
                                <input type="text" 
                                    id="layout" 
                                    name="layout" 
                                    placeholder="layouts.app" 
                                    value="layouts.app"
                                    class="w-full px-3 py-2 border border-input rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent">
                                <p class="text-xs text-muted-foreground">Dot notation (e.g. layouts.admin). Auto-created if missing.</p>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-4">
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" id="with_migration" name="with_migration" checked 
                                    class="w-4 h-4 text-primary border-input rounded focus:ring-2 focus:ring-ring">
                                <span class="ml-2 text-sm text-foreground">
                                    <i class="fas fa-database mr-1 text-muted-foreground"></i>Generate Migration
                                </span>
                            </label>
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" id="with_seeder" name="with_seeder" 
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
                                onclick="addField()" 
                                class="inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors shadow-sm">
                                <i class="fas fa-plus mr-2"></i>Add Field
                            </button>
                        </div>
                        
                        <div id="fields-container" class="space-y-4"></div>
                    </div>

                    <!-- Generate Button -->
                    <div class="flex justify-center mt-8">
                        <button type="submit" 
                            class="inline-flex items-center px-8 py-3 bg-primary text-primary-foreground text-lg font-semibold rounded-md hover:bg-primary/90 transition-all shadow-lg hover:shadow-xl">
                            <i class="fas fa-magic mr-2"></i>Generate CRUD
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Results Section -->
        <div id="results" class="bg-background rounded-lg shadow-sm border border-border hidden">
            <div class="border-b border-border px-6 py-4">
                <h3 class="text-xl font-semibold text-foreground flex items-center">
                    <i class="fas fa-check-circle text-green-500 mr-2"></i>Generation Results
                </h3>
            </div>
            <div class="p-6" id="results-content"></div>
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
                    <button type="button" onclick="loadTemplate('blog')" 
                        class="inline-flex items-center px-4 py-2 border border-border rounded-md hover:bg-accent transition-colors">
                        <i class="fas fa-blog mr-2 text-blue-500"></i>Blog Post
                    </button>
                    <button type="button" onclick="loadTemplate('product')" 
                        class="inline-flex items-center px-4 py-2 border border-border rounded-md hover:bg-accent transition-colors">
                        <i class="fas fa-box mr-2 text-green-500"></i>Product
                    </button>
                    <button type="button" onclick="loadTemplate('user')" 
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
        const fieldTypes = @json($fieldTypes);
        const formatters = {
            'text': 'Plain Text',
            'badge': 'Badge (Status)',
            'link': 'Clickable Link',
            'date': 'Formatted Date',
            'boolean': 'Yes/No Badge'
        };
        
        const badgeColors = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark'];
        let fieldCounter = 0;

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            addField('name', 'string', 'required|string|max:255');
            addField('description', 'text', 'nullable|string');
            
            // Auto-generate table name
            document.getElementById('model_name').addEventListener('input', function() {
                const modelName = this.value;
                const tableName = modelName.toLowerCase() + 's';
                document.getElementById('table_name').value = tableName;
            });
        });

        function addField(name = '', type = 'string', validation = '') {
            fieldCounter++;
            const container = document.getElementById('fields-container');
            const isApiMode = document.getElementById('api_mode')?.checked || false;
            const visSectionStyle = isApiMode ? 'display:none' : '';
            
            const fieldHtml = `
                <div class="field-card bg-muted/50 rounded-lg border border-border p-5 hover:border-primary/50 transition-all" id="field-${fieldCounter}">
                    <div class="flex items-center justify-between mb-4 cursor-pointer" onclick="toggleField(${fieldCounter})">
                        <div class="flex items-center space-x-3">
                            <span class="field-name-display text-lg font-semibold text-foreground">${name || 'New Field'}</span>
                            <span class="field-type-display px-2 py-1 bg-secondary text-secondary-foreground text-xs font-medium rounded-md">${type}</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-chevron-down text-muted-foreground transition-transform collapse-icon-${fieldCounter}"></i>
                            <button type="button" onclick="removeField(${fieldCounter}, event)" 
                                class="p-2 text-red-500 hover:bg-red-50 rounded-md transition-colors">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div id="field-content-${fieldCounter}" class="space-y-4">
                        <!-- Basic Field Info -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-foreground">Field Name</label>
                                <input type="text" 
                                    class="field-name-input w-full px-3 py-2 border border-input rounded-md focus:outline-none focus:ring-2 focus:ring-ring" 
                                    name="fields[${fieldCounter}][name]" 
                                    value="${name}" 
                                    placeholder="field_name" 
                                    onchange="updateFieldHeader(${fieldCounter})" required>
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-foreground">Type</label>
                                <select class="field-type-input w-full px-3 py-2 border border-input rounded-md focus:outline-none focus:ring-2 focus:ring-ring" 
                                    name="fields[${fieldCounter}][type]" 
                                    onchange="updateFieldHeader(${fieldCounter})" required>
                                    ${Object.entries(fieldTypes).map(([key, label]) => 
                                        `<option value="${key}" ${key === type ? 'selected' : ''}>${label}</option>`
                                    ).join('')}
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-foreground">Validation Rules</label>
                                <input type="text" 
                                    class="w-full px-3 py-2 border border-input rounded-md focus:outline-none focus:ring-2 focus:ring-ring" 
                                    name="fields[${fieldCounter}][validation]" 
                                    value="${validation}" 
                                    placeholder="required|max:255">
                            </div>
                        </div>

                        <div>
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" 
                                    name="fields[${fieldCounter}][nullable]" 
                                    id="nullable-${fieldCounter}"
                                    class="w-4 h-4 text-primary border-input rounded focus:ring-2 focus:ring-ring">
                                <span class="ml-2 text-sm text-foreground">Nullable (allows NULL in database)</span>
                            </label>
                        </div>

                        <!-- Visibility Controls -->
                        <div class="visibility-section space-y-2" style="${visSectionStyle}">
                            <label class="text-sm font-medium text-foreground">Show field in:</label>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                <label class="flex items-center justify-center space-x-2 p-3 border border-input rounded-md cursor-pointer hover:bg-accent transition-colors has-[:checked]:bg-blue-50 has-[:checked]:border-primary">
                                    <input type="checkbox" 
                                        name="fields[${fieldCounter}][visibility][create]" 
                                        id="vis-create-${fieldCounter}" checked
                                        class="w-4 h-4 text-primary border-input rounded focus:ring-2 focus:ring-ring">
                                    <span class="text-sm">
                                        <i class="fas fa-plus-circle text-green-500 mr-1"></i>Create
                                    </span>
                                </label>
                                <label class="flex items-center justify-center space-x-2 p-3 border border-input rounded-md cursor-pointer hover:bg-accent transition-colors has-[:checked]:bg-blue-50 has-[:checked]:border-primary">
                                    <input type="checkbox" 
                                        name="fields[${fieldCounter}][visibility][edit]" 
                                        id="vis-edit-${fieldCounter}" checked
                                        class="w-4 h-4 text-primary border-input rounded focus:ring-2 focus:ring-ring">
                                    <span class="text-sm">
                                        <i class="fas fa-edit text-amber-500 mr-1"></i>Edit
                                    </span>
                                </label>
                                <label class="flex items-center justify-center space-x-2 p-3 border border-input rounded-md cursor-pointer hover:bg-accent transition-colors has-[:checked]:bg-blue-50 has-[:checked]:border-primary">
                                    <input type="checkbox" 
                                        name="fields[${fieldCounter}][visibility][index]" 
                                        id="vis-index-${fieldCounter}" checked
                                        class="w-4 h-4 text-primary border-input rounded focus:ring-2 focus:ring-ring">
                                    <span class="text-sm">
                                        <i class="fas fa-table text-blue-500 mr-1"></i>Table
                                    </span>
                                </label>
                                <label class="flex items-center justify-center space-x-2 p-3 border border-input rounded-md cursor-pointer hover:bg-accent transition-colors has-[:checked]:bg-blue-50 has-[:checked]:border-primary">
                                    <input type="checkbox" 
                                        name="fields[${fieldCounter}][visibility][show]" 
                                        id="vis-show-${fieldCounter}" checked
                                        class="w-4 h-4 text-primary border-input rounded focus:ring-2 focus:ring-ring">
                                    <span class="text-sm">
                                        <i class="fas fa-eye text-cyan-500 mr-1"></i>Detail
                                    </span>
                                </label>
                            </div>
                        </div>

                        <!-- Table Configuration -->
                        <div class="visibility-section space-y-2" style="${visSectionStyle}">
                            <label class="text-sm font-medium text-foreground">Table Column Options:</label>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="checkbox" 
                                        name="fields[${fieldCounter}][table][sortable]" 
                                        id="sortable-${fieldCounter}" checked
                                        class="w-4 h-4 text-primary border-input rounded focus:ring-2 focus:ring-ring">
                                    <span class="ml-2 text-sm text-foreground">
                                        <i class="fas fa-sort mr-1 text-muted-foreground"></i>Sortable
                                    </span>
                                </label>
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="checkbox" 
                                        name="fields[${fieldCounter}][table][searchable]" 
                                        id="searchable-${fieldCounter}" checked
                                        class="w-4 h-4 text-primary border-input rounded focus:ring-2 focus:ring-ring">
                                    <span class="ml-2 text-sm text-foreground">
                                        <i class="fas fa-search mr-1 text-muted-foreground"></i>Searchable
                                    </span>
                                </label>
                                <div class="space-y-1">
                                    <label class="text-xs text-muted-foreground">Formatter:</label>
                                    <select class="w-full px-3 py-2 border border-input rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-ring" 
                                        name="fields[${fieldCounter}][table][formatter]" 
                                        id="formatter-${fieldCounter}"
                                        onchange="toggleBadgeColors(${fieldCounter})">
                                        ${Object.entries(formatters).map(([key, label]) => 
                                            `<option value="${key}">${label}</option>`
                                        ).join('')}
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Badge Mapping -->
                        <div id="badge-colors-${fieldCounter}" class="visibility-section space-y-2 hidden" style="${visSectionStyle}">
                            <label class="text-sm font-medium text-foreground">Badge Mapping (value → color + text)</label>
                            <p class="text-xs text-muted-foreground mb-2">Map values to badge colors and display text</p>
                            <div id="badge-mappings-${fieldCounter}" class="space-y-2"></div>
                            <button type="button" 
                                onclick="addBadgeMapping(${fieldCounter})" 
                                class="text-sm text-primary hover:text-primary/80 flex items-center">
                                <i class="fas fa-plus mr-1"></i>Add Badge Mapping
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', fieldHtml);
            
            // Auto-scroll to the new field
            setTimeout(() => {
                const newField = document.getElementById(`field-${fieldCounter}`);
                if (newField) {
                    newField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    // Add a brief highlight effect
                    newField.classList.add('ring-2', 'ring-primary', 'ring-offset-2');
                    setTimeout(() => {
                        newField.classList.remove('ring-2', 'ring-primary', 'ring-offset-2');
                    }, 1500);
                }
            }, 100);
            
            // Add default badge mappings for status fields
            if (name.includes('status') || name.includes('state')) {
                toggleBadgeColors(fieldCounter);
                document.getElementById(`formatter-${fieldCounter}`).value = 'badge';
                addBadgeMapping(fieldCounter, 'active', 'success', 'Active');
                addBadgeMapping(fieldCounter, 'inactive', 'secondary', 'Inactive');
                addBadgeMapping(fieldCounter, 'pending', 'warning', 'Pending');
            }
            
            // Add default badge mappings for boolean fields
            if (type === 'boolean') {
                toggleBadgeColors(fieldCounter);
                document.getElementById(`formatter-${fieldCounter}`).value = 'boolean';
                addBadgeMapping(fieldCounter, '1', 'success', 'Active');
                addBadgeMapping(fieldCounter, '0', 'secondary', 'Inactive');
            }
        }

        function toggleField(fieldId) {
            const content = document.getElementById(`field-content-${fieldId}`);
            const icon = document.querySelector(`.collapse-icon-${fieldId}`);
            content.classList.toggle('hidden');
            icon.classList.toggle('rotate-180');
        }

        function updateFieldHeader(fieldId) {
            const field = document.getElementById(`field-${fieldId}`);
            const nameInput = field.querySelector('.field-name-input');
            const typeInput = field.querySelector('.field-type-input');
            const nameDisplay = field.querySelector('.field-name-display');
            const typeDisplay = field.querySelector('.field-type-display');
            
            nameDisplay.textContent = nameInput.value || 'New Field';
            typeDisplay.textContent = typeInput.options[typeInput.selectedIndex].text;
        }

        function toggleBadgeColors(fieldId) {
            const formatter = document.getElementById(`formatter-${fieldId}`).value;
            const badgeColorsDiv = document.getElementById(`badge-colors-${fieldId}`);
            badgeColorsDiv.classList.toggle('hidden', formatter !== 'badge');
        }

        let badgeMappingCounter = {};

        function addBadgeMapping(fieldId, value = '', color = 'primary', text = '') {
            if (!badgeMappingCounter[fieldId]) badgeMappingCounter[fieldId] = 0;
            badgeMappingCounter[fieldId]++;
            
            const mappingId = `${fieldId}-${badgeMappingCounter[fieldId]}`;
            const container = document.getElementById(`badge-mappings-${fieldId}`);
            
            const mappingHtml = `
                <div id="badge-mapping-${mappingId}" class="flex gap-2">
                    <input type="text" 
                        class="flex-1 px-3 py-2 border border-input rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-ring" 
                        name="fields[${fieldId}][table][badgeMappings][values][]" 
                        value="${value}" placeholder="Value (e.g., 1, active)">
                    <select class="flex-1 px-3 py-2 border border-input rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-ring" 
                        name="fields[${fieldId}][table][badgeMappings][colors][]">
                        ${badgeColors.map(c => 
                            `<option value="${c}" ${c === color ? 'selected' : ''}>${c}</option>`
                        ).join('')}
                    </select>
                    <input type="text" 
                        class="flex-1 px-3 py-2 border border-input rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-ring" 
                        name="fields[${fieldId}][table][badgeMappings][texts][]" 
                        value="${text}" placeholder="Display text (e.g., Active)">
                    <button type="button" 
                        onclick="removeBadgeMapping('${mappingId}')" 
                        class="px-3 py-2 border border-red-200 text-red-500 rounded-md hover:bg-red-50 transition-colors">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', mappingHtml);
        }

        function removeBadgeMapping(mappingId) {
            document.getElementById(`badge-mapping-${mappingId}`).remove();
        }

        function removeField(fieldId, event) {
            event.stopPropagation();
            if (confirm('Remove this field?')) {
                const field = document.getElementById(`field-${fieldId}`);
                field.style.opacity = '0';
                field.style.transform = 'scale(0.95)';
                setTimeout(() => field.remove(), 200);
            }
        }

        // Form submission
        document.getElementById('crudForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = {
                model_name: formData.get('model_name'),
                table_name: formData.get('table_name'),
                layout: formData.get('layout') || 'layouts.app',
                with_migration: document.getElementById('with_migration').checked,
                with_seeder: document.getElementById('with_seeder').checked,
                api_mode: document.getElementById('api_mode').checked,
                fields: []
            };
            
            // Parse fields
            const fieldIndices = new Set();
            for (let [key] of formData.entries()) {
                const match = key.match(/fields\[(\d+)\]/);
                if (match) fieldIndices.add(match[1]);
            }
            
            fieldIndices.forEach(index => {
                const field = {
                    name: formData.get(`fields[${index}][name]`),
                    type: formData.get(`fields[${index}][type]`),
                    validation: formData.get(`fields[${index}][validation]`) || '',
                    nullable: formData.get(`fields[${index}][nullable]`) === 'on',
                    visibility: {
                        create: formData.get(`fields[${index}][visibility][create]`) === 'on',
                        edit: formData.get(`fields[${index}][visibility][edit]`) === 'on',
                        index: formData.get(`fields[${index}][visibility][index]`) === 'on',
                        show: formData.get(`fields[${index}][visibility][show]`) === 'on'
                    },
                    table: {
                        sortable: formData.get(`fields[${index}][table][sortable]`) === 'on',
                        searchable: formData.get(`fields[${index}][table][searchable]`) === 'on',
                        formatter: formData.get(`fields[${index}][table][formatter]`) || 'text',
                        badgeColors: {},
                        badgeTexts: {}
                    }
                };
                
                // Parse badge mappings (combined color and text)
                const badgeMappingValues = formData.getAll(`fields[${index}][table][badgeMappings][values][]`);
                const badgeMappingColors = formData.getAll(`fields[${index}][table][badgeMappings][colors][]`);
                const badgeMappingTexts = formData.getAll(`fields[${index}][table][badgeMappings][texts][]`);
                
                badgeMappingValues.forEach((value, i) => {
                    if (value) {
                        field.table.badgeColors[value] = badgeMappingColors[i];
                        if (badgeMappingTexts[i]) {
                            field.table.badgeTexts[value] = badgeMappingTexts[i];
                        }
                    }
                });
                
                data.fields.push(field);
            });
            
            // Show loading
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Generating...';
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-70');
            
           // Send request
            fetch('{{ route("crud-generator.generate") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showResults(data.message, data.files, 'success');
                } else {
                    showResults(data.message, null, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showResults('An error occurred while generating CRUD', null, 'error');
            })
            .finally(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-70');
            });
        });

        function showResults(message, files, type) {
            const resultsDiv = document.getElementById('results');
            const contentDiv = document.getElementById('results-content');
            
            let html = `<div class="p-4 rounded-md ${type === 'success' ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'}">
                <p class="font-semibold ${type === 'success' ? 'text-green-800' : 'text-red-800'}">
                    ${type === 'success' ? '✓ Success!' : '✗ Error!'}
                </p>
                <p class="${type === 'success' ? 'text-green-700' : 'text-red-700'}">${message}</p>
            </div>`;
            
            if (files && type === 'success') {
                html += '<div class="mt-4"><h4 class="font-semibold text-foreground mb-3">Generated Files:</h4><div class="space-y-2">';
                Object.entries(files).forEach(([type, file]) => {
                    if (Array.isArray(file)) {
                        file.forEach(f => {
                            html += `<div class="flex items-center justify-between p-3 bg-muted/50 rounded-md border border-border">
                                <span class="text-sm"><i class="fas fa-file-code mr-2 text-primary"></i>${f}</span>
                                <span class="px-2 py-1 text-xs bg-secondary text-secondary-foreground rounded-md">${type}</span>
                            </div>`;
                        });
                    } else {
                        html += `<div class="flex items-center justify-between p-3 bg-muted/50 rounded-md border border-border">
                            <span class="text-sm"><i class="fas fa-file-code mr-2 text-primary"></i>${file}</span>
                            <span class="px-2 py-1 text-xs bg-secondary text-secondary-foreground rounded-md">${type}</span>
                        </div>`;
                    }
                });
                html += '</div></div>';
                html += `<div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-md">
                    <h4 class="font-semibold text-blue-900 mb-2"><i class="fas fa-info-circle mr-2"></i>Next Steps:</h4>
                    <ol class="list-decimal list-inside space-y-1 text-sm text-blue-800">
                        <li>Run <code class="px-2 py-1 bg-blue-100 rounded text-xs">php artisan migrate</code> to create the database table</li>
                        <li>Visit your application to see the CRUD in action</li>
                        <li>Customize the generated files as needed</li>
                    </ol>
                </div>`;
            }
            
            contentDiv.innerHTML = html;
            resultsDiv.classList.remove('hidden');
            resultsDiv.scrollIntoView({ behavior: 'smooth' });
        }

        // Quick templates
        function loadTemplate(templateName) {
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
                document.getElementById('fields-container').innerHTML = '';
                fieldCounter = 0;
                templates[templateName].forEach(field => {
                    addField(field.name, field.type, field.validation);
                });
            }
        }

        // Toggle API mode UI
        function toggleApiMode() {
            const isApi = document.getElementById('api_mode').checked;
            const layoutOptions = document.getElementById('layout-options');
            const visibilitySections = document.querySelectorAll('.visibility-section');

            // Hide/show layout selector
            layoutOptions.style.display = isApi ? 'none' : '';

            // Hide/show visibility controls on existing fields
            visibilitySections.forEach(el => {
                el.style.display = isApi ? 'none' : '';
            });
        }
    </script>
</body>
</html>