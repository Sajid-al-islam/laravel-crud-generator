<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRUD Generator</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        :root {
            --canvas: #0a0a0a;
            --surface-1: #141414;
            --surface-2: #1a1a1a;
            --inverse-canvas: #ffffff;

            --ink: #ffffff;
            --ink-muted: #999999;

            --primary: #ffffff;
            --on-primary: #000000;
            --accent-blue: #0099ff;

            --hairline: rgba(255, 255, 255, 0.10);
            --hairline-soft: rgba(255, 255, 255, 0.05);

            --gradient-violet: linear-gradient(135deg, #6d28d9 0%, #8b5cf6 50%, #a78bfa 100%);
            --gradient-magenta: linear-gradient(135deg, #be185d 0%, #db2777 50%, #f472b6 100%);
            --gradient-orange: linear-gradient(135deg, #c2410c 0%, #f97316 50%, #fdba74 100%);
            --gradient-coral: linear-gradient(135deg, #e11d48 0%, #fb7185 50%, #fda4af 100%);

            --r-xs: 4px;
            --r-sm: 6px;
            --r-md: 10px;
            --r-lg: 15px;
            --r-xl: 20px;
            --r-xxl: 30px;
            --r-pill: 100px;

            --shadow-light-edge: inset 0 0.5px 0 0 rgba(255, 255, 255, 0.10), 0 10px 30px 0 rgba(0, 0, 0, 0.25);
            --shadow-blue-ring: 0 0 0 1px rgba(0, 153, 255, 0.15);

            --success: #22c55e;
            --error: #ef4444;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        html, body {
            background: var(--canvas);
            color: var(--ink);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            font-size: 15px;
            line-height: 1.30;
            letter-spacing: -0.15px;
            scroll-behavior: smooth;
            font-feature-settings: "cv01", "cv05", "cv09", "cv11", "ss03", "ss07", "dlig";
            -webkit-font-smoothing: antialiased;
        }

        .display, h1.display, h2.display, h3.display, h4.display {
            font-family: 'Geist', 'Inter', system-ui, sans-serif;
            font-weight: 500;
            line-height: 0.95;
            letter-spacing: -0.05em;
        }

        h1, h2, h3, h4 {
            font-family: 'Geist', 'Inter', system-ui, sans-serif;
            font-weight: 500;
            margin: 0;
        }

        code, pre, .mono { font-family: 'JetBrains Mono', 'Geist Mono', ui-monospace, monospace; }

        a { color: var(--accent-blue); text-decoration: none; }
        a:hover { text-decoration: underline; }

        .ink { color: var(--ink); }
        .ink-muted { color: var(--ink-muted); }

        .container { max-width: 1199px; margin: 0 auto; padding: 0 30px; }

        /* Top nav */
        .top-nav {
            position: sticky; top: 0; z-index: 50;
            height: 56px; background: var(--canvas);
            border-bottom: 1px solid transparent;
            display: flex; align-items: center;
        }
        .top-nav.scrolled { border-bottom: 1px solid var(--hairline-soft); }
        .nav-row { display: flex; align-items: center; justify-content: space-between; width: 100%; }
        .nav-links { display: flex; align-items: center; gap: 24px; }
        .nav-links a { color: var(--ink-muted); font-size: 14px; text-decoration: none; }
        .nav-links a:hover { color: var(--ink); text-decoration: none; }
        .nav-cta { display: flex; align-items: center; gap: 8px; }

        /* Buttons */
        .btn {
            display: inline-flex; align-items: center; gap: 8px;
            font-size: 14px; font-weight: 500; line-height: 1; letter-spacing: -0.14px;
            padding: 10px 15px; border-radius: var(--r-pill);
            border: 0; cursor: pointer; text-decoration: none; white-space: nowrap;
            transition: transform 0.12s ease, background 0.15s ease, opacity 0.15s ease;
        }
        .btn:hover { text-decoration: none; }
        .btn:active { transform: scale(0.97); }
        .btn-primary { background: var(--primary); color: var(--on-primary); }
        .btn-primary:hover { opacity: 0.9; }
        .btn-secondary { background: var(--surface-1); color: var(--ink); }
        .btn-secondary:hover { background: var(--surface-2); }
        .btn-ghost { background: transparent; color: var(--ink-muted); padding: 8px 12px; }
        .btn-ghost:hover { color: var(--ink); background: var(--surface-1); }
        .btn-danger { background: rgba(239, 68, 68, 0.15); color: #f87171; }
        .btn-danger:hover { background: rgba(239, 68, 68, 0.25); }
        .btn-icon {
            display: inline-flex; align-items: center; justify-content: center;
            width: 40px; height: 40px; border-radius: var(--r-pill);
            background: var(--surface-1); color: var(--ink); border: 0; cursor: pointer;
        }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; }

        /* Sections */
        .section { padding: 48px 0; }

        /* Cards */
        .card {
            background: var(--surface-1); color: var(--ink);
            border-radius: var(--r-xl); padding: 20px;
            border: 1px solid var(--hairline-soft);
        }

        /* Stack selector */
        .stack-card {
            background: var(--surface-1);
            border: 1.5px solid var(--hairline);
            border-radius: var(--r-lg);
            padding: 16px;
            cursor: pointer;
            transition: all 0.18s ease;
        }
        .stack-card:hover { background: var(--surface-2); transform: translateY(-2px); }
        .stack-card.active {
            border-color: var(--accent-blue);
            background: var(--surface-2);
            box-shadow: var(--shadow-blue-ring);
        }

        /* Field cards */
        .field-card {
            background: var(--surface-2);
            border-radius: var(--r-lg);
            padding: 16px;
            border: 1px solid var(--hairline-soft);
            transition: border-color 0.15s ease;
        }
        .field-card:hover { border-color: var(--hairline); }

        /* Form inputs */
        .input {
            width: 100%;
            padding: 10px 14px;
            background: var(--surface-1);
            color: var(--ink);
            border: 1px solid var(--hairline);
            border-radius: var(--r-md);
            font-size: 14px;
            font-family: inherit;
            outline: none;
            transition: border-color 0.15s ease;
        }
        .input:focus { border-color: var(--accent-blue); }
        .input::placeholder { color: var(--ink-muted); opacity: 0.6; }

        .select {
            width: 100%;
            padding: 10px 14px;
            background: var(--surface-1);
            color: var(--ink);
            border: 1px solid var(--hairline);
            border-radius: var(--r-md);
            font-size: 14px;
            font-family: inherit;
            outline: none;
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23999' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            transition: border-color 0.15s ease;
        }
        .select:focus { border-color: var(--accent-blue); }

        .checkbox-wrap {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 14px;
            background: var(--surface-1);
            border: 1px solid var(--hairline-soft);
            border-radius: var(--r-md);
            cursor: pointer;
            transition: border-color 0.15s ease;
        }
        .checkbox-wrap:hover { border-color: var(--hairline); }
        .checkbox-wrap input[type="checkbox"] {
            width: 16px; height: 16px; accent-color: var(--accent-blue); cursor: pointer;
        }
        .checkbox-wrap label { font-size: 14px; color: var(--ink); cursor: pointer; flex: 1; }

        .label { font-size: 13px; font-weight: 500; color: var(--ink); margin-bottom: 6px; display: block; }
        .label-muted { font-size: 12px; color: var(--ink-muted); margin-top: 4px; }

        /* Panel card */
        .panel {
            background: var(--surface-1);
            border: 1px solid var(--hairline-soft);
            border-radius: var(--r-xl);
            overflow: hidden;
        }
        .panel-header {
            padding: 16px 24px;
            border-bottom: 1px solid var(--hairline-soft);
            display: flex; align-items: center; justify-content: space-between;
        }
        .panel-body { padding: 24px; }

        /* Code block */
        .code-block {
            background: #000;
            border: 1px solid var(--hairline);
            border-radius: var(--r-lg);
            padding: 20px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            line-height: 1.7;
            color: var(--ink);
            overflow-x: auto;
            white-space: pre-wrap;
        }

        /* Result glyphs */
        .glyph-created { color: var(--success); }
        .glyph-modified { color: #f59e0b; }
        .glyph-skipped { color: var(--ink-muted); }
        .glyph-failed { color: var(--error); }

        /* Eyebrow */
        .eyebrow {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: 12px; font-weight: 500; color: var(--ink-muted);
            letter-spacing: 0.5px; text-transform: uppercase;
        }

        /* Grids */
        .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
        .grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }

        @media (max-width: 810px) {
            .grid-2, .grid-4 { grid-template-columns: 1fr; }
            .nav-links { display: none; }
        }

        [x-cloak] { display: none !important; }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--hairline); border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.2); }
    </style>
</head>
<body x-data="crudGenerator()">
    <!-- Top nav -->
    <nav class="top-nav" id="topNav">
        <div class="container nav-row">
            <a href="{{ route('crud-generator.landing') }}" style="display:flex;align-items:center;gap:8px;color:var(--ink);font-family:'Geist',sans-serif;font-weight:500;font-size:15px;letter-spacing:-0.15px;text-decoration:none;">
                <span style="width:24px;height:24px;border-radius:6px;background:var(--ink);color:var(--on-primary);display:inline-flex;align-items:center;justify-content:center;font-size:12px;">⚡</span>
                CRUD Generator
            </a>
            <div class="nav-links">
                <a href="{{ route('crud-generator.landing') }}">Home</a>
                <a href="{{ route('crud-generator.landing') }}#stacks">Stacks</a>
                <a href="{{ route('crud-generator.landing') }}#features">Features</a>
            </div>
            <div class="nav-cta">
                <a href="https://github.com/Sajid-al-islam/laravel-crud-generator" target="_blank" class="btn-icon" aria-label="GitHub">
                    <i class="fab fa-github"></i>
                </a>
            </div>
        </div>
    </nav>

    <div class="container" style="padding-top:48px;padding-bottom:96px;">
        <!-- Page header -->
        <div style="margin-bottom:40px;">
            <div class="eyebrow" style="margin-bottom:12px;">
                <span style="width:6px;height:6px;border-radius:50%;background:var(--accent-blue);"></span>
                Generator
            </div>
            <h1 style="font-size:clamp(32px,5vw,56px);font-weight:500;line-height:0.95;letter-spacing:-2.5px;margin-bottom:12px;">Build your CRUD.</h1>
            <p class="ink-muted" style="font-size:18px;letter-spacing:-0.18px;">Pick a stack, define your fields, generate everything.</p>
        </div>

        <form id="crudForm" @submit.prevent="generateCrud()">
            @csrf

            <!-- Stack selector -->
            <div class="panel" style="margin-bottom:24px;">
                <div class="panel-header">
                    <h2 style="font-size:16px;font-weight:500;display:flex;align-items:center;gap:8px;">
                        <span style="font-size:18px;">🧩</span> Choose Stack
                    </h2>
                </div>
                <div class="panel-body">
                    <div class="grid-4">
                        <template x-for="(label, key) in stacks" :key="key">
                            <label class="stack-card" :class="stack === key ? 'active' : ''">
                                <input type="radio" name="stack" :value="key" x-model="stack" class="sr-only" style="position:absolute;opacity:0;pointer-events:none;">
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <span style="font-size:22px;" x-text="stackIcons[key] || '⚡'"></span>
                                    <div>
                                        <div style="font-size:14px;font-weight:500;" x-text="key.charAt(0).toUpperCase() + key.slice(1)"></div>
                                        <div class="ink-muted" style="font-size:12px;" x-text="label"></div>
                                    </div>
                                </div>
                            </label>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Configuration -->
            <div class="panel" style="margin-bottom:24px;">
                <div class="panel-header">
                    <h2 style="font-size:16px;font-weight:500;display:flex;align-items:center;gap:8px;">
                        <span style="font-size:18px;">⚙️</span> Configuration
                    </h2>
                </div>
                <div class="panel-body">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
                        <div>
                            <label class="label">Model Name</label>
                            <input type="text" name="model_name" x-model="modelName" @input="autoGenerateTableName()"
                                placeholder="Post" required class="input">
                            <p class="label-muted">Singular, PascalCase</p>
                        </div>
                        <div>
                            <label class="label">Table Name</label>
                            <input type="text" name="table_name" x-model="tableName"
                                placeholder="posts" required class="input">
                            <p class="label-muted">Plural, snake_case</p>
                        </div>
                    </div>

                    <div x-show="stack === 'blade'" style="margin-bottom:20px;">
                        <label class="label">Layout</label>
                        <input type="text" name="layout" x-model="layout" placeholder="layouts.app" class="input" style="max-width:400px;">
                    </div>

                    <!-- Toggles -->
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px;">
                        <div class="checkbox-wrap">
                            <input type="checkbox" id="with_migration" x-model="withMigration">
                            <label for="with_migration">Generate migration</label>
                        </div>
                        <div class="checkbox-wrap">
                            <input type="checkbox" id="with_service" x-model="withService">
                            <label for="with_service">Generate service class</label>
                        </div>
                        <div class="checkbox-wrap" x-show="withService">
                            <input type="checkbox" id="with_repository" x-model="withRepository">
                            <label for="with_repository">Generate repository (implies service)</label>
                        </div>
                        <div class="checkbox-wrap">
                            <input type="checkbox" id="force" x-model="force">
                            <label for="force">Overwrite existing files</label>
                        </div>
                    </div>

                    <div>
                        <label class="label">Custom stubs path <span class="ink-muted" style="font-weight:400;">(optional)</span></label>
                        <input type="text" name="custom_stubs" x-model="customStubs"
                            placeholder="./crud-stubs" class="input" style="max-width:500px;">
                        <p class="label-muted">Absolute or project-relative path. Resolved before package built-in stubs.</p>
                    </div>
                </div>
            </div>

            <!-- Fields -->
            <div class="panel" style="margin-bottom:24px;">
                <div class="panel-header">
                    <h2 style="font-size:16px;font-weight:500;display:flex;align-items:center;gap:8px;">
                        <span style="font-size:18px;">📋</span> Model Fields
                    </h2>
                    <button type="button" @click="addField()" class="btn btn-primary">
                        <i class="fas fa-plus" style="font-size:11px;"></i> Add Field
                    </button>
                </div>
                <div class="panel-body">
                    <div style="display:flex;flex-direction:column;gap:12px;">
                        <template x-for="(field, index) in fields" :key="field.id">
                            <div class="field-card">
                                <div style="display:grid;grid-template-columns:1.2fr 1fr 1.2fr auto;gap:12px;align-items:end;">
                                    <div>
                                        <label class="label" style="font-size:12px;color:var(--ink-muted);">Name</label>
                                        <input type="text" class="input" x-model="field.name" placeholder="title" required>
                                    </div>
                                    <div>
                                        <label class="label" style="font-size:12px;color:var(--ink-muted);">Type</label>
                                        <select class="select" x-model="field.type">
                                            <template x-for="[key, label] in Object.entries(fieldTypes)" :key="key">
                                                <option :value="key" x-text="label"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="label" style="font-size:12px;color:var(--ink-muted);">Validation</label>
                                        <input type="text" class="input" x-model="field.validation" placeholder="required|max:255">
                                    </div>
                                    <div style="display:flex;align-items:center;gap:12px;">
                                        <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:var(--ink-muted);cursor:pointer;white-space:nowrap;">
                                            <input type="checkbox" x-model="field.nullable" style="width:14px;height:14px;accent-color:var(--accent-blue);"> Nullable
                                        </label>
                                        <button type="button" @click="removeField(field.id)" class="btn btn-danger" style="padding:8px 10px;">
                                            <i class="fas fa-trash" style="font-size:11px;"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;">
                <button type="button" @click="preview()" class="btn btn-secondary">
                    <i class="fas fa-eye" style="font-size:11px;"></i> Preview
                </button>
                <button type="submit" :disabled="loading" class="btn btn-primary" style="margin-left:auto;">
                    <i class="fas fa-magic" style="font-size:11px;"></i>
                    <span x-show="!loading">Generate CRUD</span>
                    <span x-show="loading">Generating…</span>
                </button>
            </div>
        </form>

        <!-- Result -->
        <div x-show="result || error" x-cloak style="margin-bottom:24px;">
            <div class="panel">
                <div class="panel-header">
                    <h2 style="font-size:16px;font-weight:500;display:flex;align-items:center;gap:8px;" x-show="result">
                        <span class="glyph-created" style="font-size:18px;">✔</span> Generated
                    </h2>
                    <h2 style="font-size:16px;font-weight:500;display:flex;align-items:center;gap:8px;" x-show="error">
                        <span class="glyph-failed" style="font-size:18px;">✗</span> Error
                    </h2>
                </div>
                <div class="panel-body">
                    <pre class="code-block" x-text="error || formatResult(result)"></pre>
                </div>
            </div>
        </div>

        <!-- Preview -->
        <div x-show="previewData" x-cloak>
            <div class="panel">
                <div class="panel-header">
                    <h2 style="font-size:16px;font-weight:500;display:flex;align-items:center;gap:8px;">
                        <span style="font-size:18px;">🔍</span> Dry-run preview
                    </h2>
                </div>
                <div class="panel-body">
                    <pre class="code-block" x-text="JSON.stringify(previewData, null, 2)"></pre>
                </div>
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
                            value.forEach(v => {
                                if (v.path) {
                                    const icon = v.status === 'created' ? '✔' : v.status === 'modified' ? '~' : v.status === 'skipped' ? '⚠' : '✗';
                                    const cls = v.status === 'created' ? 'glyph-created' : v.status === 'modified' ? 'glyph-modified' : v.status === 'skipped' ? 'glyph-skipped' : 'glyph-failed';
                                    lines.push(`<span class="${cls}">${icon}</span> ${v.path}`);
                                }
                            });
                        } else if (value && value.path) {
                            const icon = value.status === 'created' ? '✔' : value.status === 'modified' ? '~' : '⚠';
                            const cls = value.status === 'created' ? 'glyph-created' : value.status === 'modified' ? 'glyph-modified' : 'glyph-skipped';
                            lines.push(`<span class="${cls}">${icon}</span> ${value.path}`);
                        }
                    }
                    return lines.join('\n');
                },
            };
        }

        // Sticky nav scroll border
        (function() {
            const nav = document.getElementById('topNav');
            window.addEventListener('scroll', () => {
                if (window.scrollY > 8) nav.classList.add('scrolled');
                else nav.classList.remove('scrolled');
            });
        })();
    </script>
</body>
</html>
