<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRUD Generator</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .field-row {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 1rem;
            margin-bottom: 1rem;
            background-color: #f8f9fa;
        }
        .remove-field {
            cursor: pointer;
            color: #dc3545;
            color: white;
        }
        .add-field {
            cursor: pointer;
            color: #198754;
        }
        .generator-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border: 1px solid rgba(0, 0, 0, 0.125);
        }
        .btn-generate {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 0.75rem 2rem;
        }
        .btn-generate:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
        }
    </style>
</head>
<body>
    <div class="generator-header">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <h1><i class="fas fa-magic"></i> Laravel CRUD Generator</h1>
                    <p class="lead">Generate complete CRUD operations with a simple form</p>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title mb-0"><i class="fas fa-cogs"></i> CRUD Configuration</h3>
                    </div>
                    <div class="card-body">
                        <form id="crudForm">
                            @csrf
                            
                            <!-- Basic Information -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="model_name" class="form-label">Model Name</label>
                                    <input type="text" class="form-control" id="model_name" name="model_name" placeholder="e.g., Post" required>
                                    <div class="form-text">The name of your Eloquent model (singular, PascalCase)</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="table_name" class="form-label">Table Name</label>
                                    <input type="text" class="form-control" id="table_name" name="table_name" placeholder="e.g., posts" required>
                                    <div class="form-text">Database table name (plural, snake_case)</div>
                                </div>
                            </div>

                            <!-- Options -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="with_migration" name="with_migration" checked>
                                        <label class="form-check-label" for="with_migration">Generate Migration</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="with_seeder" name="with_seeder">
                                        <label class="form-check-label" for="with_seeder">Generate Seeder</label>
                                    </div>
                                </div>
                            </div>

                            <!-- Fields Section -->
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5><i class="fas fa-list"></i> Model Fields</h5>
                                    <button type="button" class="btn btn-success btn-sm add-field text-white" onclick="addField()">
                                        <i class="fas fa-plus"></i> Add Field
                                    </button>
                                </div>
                                
                                <div id="fields-container">
                                    <!-- Default fields will be added here -->
                                </div>
                            </div>

                            <!-- Generate Button -->
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary btn-generate btn-lg">
                                    <i class="fas fa-magic"></i> Generate CRUD
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Results Section -->
                <div id="results" class="card mt-4" style="display: none;">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-check-circle text-success"></i> Generation Results</h5>
                    </div>
                    <div class="card-body">
                        <div id="results-content"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // Field types from config
        const fieldTypes = @json($fieldTypes);
        let fieldCounter = 0;

        // Initialize with default fields
        document.addEventListener('DOMContentLoaded', function() {
            addField('name', 'string', 'required|string|max:255');
            addField('description', 'text', 'nullable|string');
            
            // Auto-generate table name from model name
            document.getElementById('model_name').addEventListener('input', function() {
                const modelName = this.value;
                const tableName = modelName.toLowerCase() + 's';
                document.getElementById('table_name').value = tableName;
            });
        });

        function addField(name = '', type = 'string', validation = '') {
            fieldCounter++;
            const container = document.getElementById('fields-container');
            
            const fieldHtml = `
                <div class="field-row" id="field-${fieldCounter}">
                    <div class="row">
                        <div class="col-md-3">
                            <label class="form-label">Field Name</label>
                            <input type="text" class="form-control" name="fields[${fieldCounter}][name]" value="${name}" placeholder="field_name" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Type</label>
                            <select class="form-select" name="fields[${fieldCounter}][type]" required>
                                ${Object.entries(fieldTypes).map(([key, label]) => 
                                    `<option value="${key}" ${key === type ? 'selected' : ''}>${label}</option>`
                                ).join('')}
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Validation Rules</label>
                            <input type="text" class="form-control" name="fields[${fieldCounter}][validation]" value="${validation}" placeholder="required|string|max:255">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Options</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="fields[${fieldCounter}][nullable]" id="nullable-${fieldCounter}">
                                <label class="form-check-label" for="nullable-${fieldCounter}">Nullable</label>
                            </div>
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="button" class="btn btn-danger btn-sm remove-field" onclick="removeField(${fieldCounter})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', fieldHtml);
        }

        function removeField(fieldId) {
            const field = document.getElementById(`field-${fieldId}`);
            if (field) {
                field.remove();
            }
        }

        // Form submission
        document.getElementById('crudForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = {};
            
            // Convert FormData to regular object
            for (let [key, value] of formData.entries()) {
                if (key.includes('[')) {
                    // Handle nested arrays (fields)
                    const matches = key.match(/(\w+)\[(\d+)\]\[(\w+)\]/);
                    if (matches) {
                        const [, arrayName, index, fieldName] = matches;
                        if (!data[arrayName]) data[arrayName] = {};
                        if (!data[arrayName][index]) data[arrayName][index] = {};
                        data[arrayName][index][fieldName] = value;
                    }
                } else {
                    data[key] = value;
                }
            }
            
            // Convert fields object to array
            if (data.fields) {
                data.fields = Object.values(data.fields);
            }
            
            // Add checkboxes that aren't submitted when unchecked
            data.with_migration = document.getElementById('with_migration').checked;
            data.with_seeder = document.getElementById('with_seeder').checked;
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
            submitBtn.disabled = true;
            
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
                // Reset button
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });

        function showResults(message, files, type) {
            const resultsDiv = document.getElementById('results');
            const contentDiv = document.getElementById('results-content');
            
            let html = `<div class="alert alert-${type === 'success' ? 'success' : 'danger'}" role="alert">
                <strong>${type === 'success' ? 'Success!' : 'Error!'}</strong> ${message}
            </div>`;
            
            if (files && type === 'success') {
                html += '<h6>Generated Files:</h6><ul class="list-group">';
                
                Object.entries(files).forEach(([type, file]) => {
                    if (Array.isArray(file)) {
                        file.forEach(f => {
                            html += `<li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-file-alt"></i> ${f}</span>
                                <span class="badge bg-primary rounded-pill">${type}</span>
                            </li>`;
                        });
                    } else {
                        html += `<li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-file-alt"></i> ${file}</span>
                            <span class="badge bg-primary rounded-pill">${type}</span>
                        </li>`;
                    }
                });
                
                html += '</ul>';
                
                html += `<div class="mt-3 p-3 bg-light rounded">
                    <h6><i class="fas fa-info-circle"></i> Next Steps:</h6>
                    <ol>
                        <li>Run <code>php artisan migrate</code> to create the database table</li>
                        <li>Visit your application to see the generated CRUD in action</li>
                        <li>Customize the generated files as needed</li>
                    </ol>
                </div>`;
            }
            
            contentDiv.innerHTML = html;
            resultsDiv.style.display = 'block';
            
            // Scroll to results
            resultsDiv.scrollIntoView({ behavior: 'smooth' });
        }

        // Quick templates
        function loadTemplate(templateName) {
            const templates = {
                blog: [
                    { name: 'title', type: 'string', validation: 'required|string|max:255' },
                    { name: 'slug', type: 'string', validation: 'required|string|unique:posts' },
                    { name: 'content', type: 'text', validation: 'required|string' },
                    { name: 'excerpt', type: 'text', validation: 'nullable|string' },
                    { name: 'published_at', type: 'datetime', validation: 'nullable|date' },
                    { name: 'is_published', type: 'boolean', validation: 'boolean' }
                ],
                product: [
                    { name: 'name', type: 'string', validation: 'required|string|max:255' },
                    { name: 'description', type: 'text', validation: 'nullable|string' },
                    { name: 'price', type: 'integer', validation: 'required|numeric|min:0' },
                    { name: 'sku', type: 'string', validation: 'required|string|unique:products' },
                    { name: 'stock_quantity', type: 'integer', validation: 'required|integer|min:0' },
                    { name: 'is_active', type: 'boolean', validation: 'boolean' }
                ],
                user: [
                    { name: 'name', type: 'string', validation: 'required|string|max:255' },
                    { name: 'email', type: 'email', validation: 'required|email|unique:users' },
                    { name: 'password', type: 'password', validation: 'required|string|min:8' },
                    { name: 'phone', type: 'string', validation: 'nullable|string' },
                    { name: 'address', type: 'text', validation: 'nullable|string' },
                    { name: 'is_active', type: 'boolean', validation: 'boolean' }
                ]
            };
            
            if (templates[templateName]) {
                // Clear existing fields
                document.getElementById('fields-container').innerHTML = '';
                fieldCounter = 0;
                
                // Add template fields
                templates[templateName].forEach(field => {
                    addField(field.name, field.type, field.validation);
                });
            }
        }
    </script>

    <!-- Quick Templates (Optional) -->
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-templates"></i> Quick Templates</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Use these pre-defined templates to quickly set up common CRUD structures:</p>
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="loadTemplate('blog')">
                                <i class="fas fa-blog"></i> Blog Post
                            </button>
                            <button type="button" class="btn btn-outline-success btn-sm" onclick="loadTemplate('product')">
                                <i class="fas fa-box"></i> Product
                            </button>
                            <button type="button" class="btn btn-outline-info btn-sm" onclick="loadTemplate('user')">
                                <i class="fas fa-user"></i> User Management
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="mt-5 py-4 bg-light">
        <div class="container text-center">
            <p class="text-muted mb-0">Laravel CRUD Generator - Making development faster and easier</p>
        </div>
    </footer>
</body>
</html>