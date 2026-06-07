<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laravel CRUD Generator — Generate CRUD for any stack</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            /* Surfaces */
            --canvas: #0a0a0a;
            --surface-1: #141414;
            --surface-2: #1a1a1a;
            --inverse-canvas: #ffffff;

            /* Text */
            --ink: #ffffff;
            --ink-muted: #999999;

            /* Brand */
            --primary: #ffffff;
            --on-primary: #000000;
            --accent-blue: #0099ff;

            /* Lines */
            --hairline: rgba(255, 255, 255, 0.10);
            --hairline-soft: rgba(255, 255, 255, 0.05);

            /* Gradients */
            --gradient-violet: linear-gradient(135deg, #6d28d9 0%, #8b5cf6 50%, #a78bfa 100%);
            --gradient-magenta: linear-gradient(135deg, #be185d 0%, #db2777 50%, #f472b6 100%);
            --gradient-orange: linear-gradient(135deg, #c2410c 0%, #f97316 50%, #fdba74 100%);
            --gradient-coral: linear-gradient(135deg, #e11d48 0%, #fb7185 50%, #fda4af 100%);

            /* Radius */
            --r-xs: 4px;
            --r-sm: 6px;
            --r-md: 10px;
            --r-lg: 15px;
            --r-xl: 20px;
            --r-xxl: 30px;
            --r-pill: 100px;

            /* Shadows */
            --shadow-light-edge: inset 0 0.5px 0 0 rgba(255, 255, 255, 0.10), 0 10px 30px 0 rgba(0, 0, 0, 0.25);
            --shadow-blue-ring: 0 0 0 1px rgba(0, 153, 255, 0.15);
        }

        * { box-sizing: border-box; }

        html, body {
            background: var(--canvas);
            color: var(--ink);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            font-size: 15px;
            line-height: 1.30;
            letter-spacing: -0.15px;
            margin: 0;
            padding: 0;
            scroll-behavior: smooth;
            font-feature-settings: "cv01", "cv05", "cv09", "cv11", "ss03", "ss07", "dlig";
            -webkit-font-smoothing: antialiased;
        }

        /* Display type — Geist as GT Walsheim substitute */
        .display, h1.display, h2.display, h3.display, h4.display {
            font-family: 'Geist', 'Inter', system-ui, sans-serif;
            font-weight: 500;
            line-height: 0.95;
            letter-spacing: -0.05em;
        }

        /* Headlines */
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

        /* Type scale */
        .d-xxl { font-size: clamp(48px, 8vw, 110px); font-weight: 500; line-height: 0.85; letter-spacing: -5.5px; }
        .d-xl  { font-size: clamp(40px, 6vw, 85px);  font-weight: 500; line-height: 0.95; letter-spacing: -4.25px; }
        .d-lg  { font-size: clamp(36px, 5vw, 62px);  font-weight: 500; line-height: 1.00; letter-spacing: -3.1px; }
        .d-md  { font-size: 32px; font-weight: 500; line-height: 1.13; letter-spacing: -1.0px; }
        .headline { font-size: 22px; font-weight: 700; line-height: 1.20; letter-spacing: -0.8px; }
        .subhead  { font-size: 24px; font-weight: 400; line-height: 1.30; letter-spacing: -0.01px; }
        .body-lg  { font-size: 18px; font-weight: 400; line-height: 1.30; letter-spacing: -0.18px; }
        .body     { font-size: 15px; font-weight: 400; line-height: 1.30; letter-spacing: -0.15px; }
        .body-sm  { font-size: 14px; font-weight: 500; line-height: 1.40; letter-spacing: -0.14px; }
        .caption  { font-size: 13px; font-weight: 500; line-height: 1.20; letter-spacing: -0.13px; }
        .micro    { font-size: 12px; font-weight: 400; line-height: 1.20; letter-spacing: -0.12px; }

        /* Container */
        .container { max-width: 1199px; margin: 0 auto; padding: 0 30px; }

        /* Buttons */
        .btn {
            display: inline-flex; align-items: center; gap: 8px;
            font-size: 14px; font-weight: 500; line-height: 1; letter-spacing: -0.14px;
            padding: 10px 15px; border-radius: var(--r-pill);
            border: 0; cursor: pointer; text-decoration: none; white-space: nowrap;
            transition: transform 0.12s ease;
        }
        .btn:hover { text-decoration: none; }
        .btn:active { transform: scale(0.97); }
        .btn-primary { background: var(--primary); color: var(--on-primary); }
        .btn-secondary { background: var(--surface-1); color: var(--ink); }
        .btn-translucent { background: var(--surface-2); color: var(--ink); border-radius: var(--r-xxl); padding: 8px 14px; }
        .btn-icon {
            display: inline-flex; align-items: center; justify-content: center;
            width: 40px; height: 40px; border-radius: var(--r-pill);
            background: var(--surface-1); color: var(--ink); border: 0; cursor: pointer;
        }

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
        .nav-links a { color: var(--ink-muted); font-size: 14px; }
        .nav-links a:hover { color: var(--ink); text-decoration: none; }
        .nav-cta { display: flex; align-items: center; gap: 8px; }

        /* Sections */
        .section { padding: 96px 0; }
        .section-tight { padding: 64px 0; }

        /* Cards */
        .card {
            background: var(--surface-1); color: var(--ink);
            border-radius: var(--r-xl); padding: 20px;
        }
        .card-featured { background: var(--surface-2); }
        .card-tight { padding: 12px; border-radius: var(--r-lg); }

        /* Gradient spotlight cards */
        .spotlight { padding: 32px; border-radius: var(--r-xxl); min-height: 360px; display: flex; flex-direction: column; justify-content: space-between; }
        .spotlight-violet { background: var(--gradient-violet); }
        .spotlight-magenta { background: var(--gradient-magenta); }
        .spotlight-orange { background: var(--gradient-orange); }
        .spotlight-coral { background: var(--gradient-coral); }

        /* Stack grid card */
        .stack-card {
            background: var(--surface-1);
            border-radius: var(--r-lg);
            padding: 20px;
            min-height: 180px;
            display: flex; flex-direction: column; gap: 12px;
            transition: transform 0.18s ease, background 0.18s ease;
            cursor: pointer;
        }
        .stack-card:hover { background: var(--surface-2); transform: translateY(-2px); }

        /* Feature card */
        .feature-card {
            background: var(--surface-1);
            border-radius: var(--r-xl);
            padding: 32px;
            display: flex; flex-direction: column; gap: 12px;
            min-height: 220px;
            transition: background 0.18s ease;
        }
        .feature-card:hover { background: var(--surface-2); }

        /* Terminal */
        .terminal {
            background: #000;
            border: 1px solid var(--hairline);
            border-radius: var(--r-lg);
            box-shadow: var(--shadow-light-edge);
            overflow: hidden;
        }
        .terminal-header {
            background: var(--surface-1);
            padding: 12px 16px;
            display: flex; align-items: center; gap: 6px;
            border-bottom: 1px solid var(--hairline-soft);
        }
        .terminal-dot { width: 10px; height: 10px; border-radius: 50%; }
        .dot-red { background: #ff5f57; }
        .dot-yellow { background: #febc2e; }
        .dot-green { background: #28c840; }
        .terminal-body {
            padding: 24px; font-family: 'JetBrains Mono', monospace; font-size: 13px;
            line-height: 1.7; min-height: 320px;
        }
        .terminal-line { opacity: 0; animation: fadeIn 0.3s forwards; }
        .terminal-prompt { color: var(--accent-blue); }
        .terminal-output { color: var(--ink); }
        .terminal-success { color: #28c840; }
        .terminal-fail { color: #f87171; }
        @keyframes fadeIn { to { opacity: 1; } }

        /* Code preview tabs */
        .code-tabs { display: flex; gap: 4px; padding: 4px; background: var(--surface-1); border-bottom: 1px solid var(--hairline-soft); }
        .code-tab {
            padding: 8px 16px; border-radius: var(--r-md); cursor: pointer;
            color: var(--ink-muted); font-size: 13px;
            transition: background 0.15s, color 0.15s;
        }
        .code-tab.active { background: var(--surface-2); color: var(--ink); }

        /* Install lines */
        .install-line {
            display: flex; align-items: center; gap: 12px;
            background: var(--surface-1); border-radius: var(--r-md);
            padding: 12px 16px; margin-bottom: 8px;
            border: 1px solid var(--hairline-soft);
        }
        .install-line .prompt { color: var(--accent-blue); }
        .install-line code { flex: 1; color: var(--ink); font-size: 13px; }
        .copy-btn {
            background: transparent; border: 1px solid var(--hairline);
            color: var(--ink-muted); padding: 4px 8px; border-radius: var(--r-sm);
            cursor: pointer; transition: all 0.15s;
        }
        .copy-btn:hover { border-color: var(--accent-blue); color: var(--accent-blue); }
        .copy-btn.copied { border-color: #28c840; color: #28c840; }

        /* Footer */
        .footer { padding: 64px 32px; border-top: 1px solid var(--hairline-soft); }
        .footer-grid { display: grid; grid-template-columns: 1.5fr repeat(4, 1fr); gap: 40px; }
        .footer-col h4 { font-size: 13px; font-weight: 500; color: var(--ink); margin-bottom: 12px; letter-spacing: -0.13px; }
        .footer-col a { display: block; color: var(--ink-muted); font-size: 13px; padding: 4px 0; }
        .footer-col a:hover { color: var(--ink); text-decoration: none; }

        /* Reveal */
        .section-reveal { opacity: 0; transform: translateY(20px); transition: opacity 0.6s, transform 0.6s; }
        .section-reveal.visible { opacity: 1; transform: translateY(0); }

        /* Eyebrow */
        .eyebrow {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: 12px; font-weight: 500; color: var(--ink-muted);
            letter-spacing: 0.5px; text-transform: uppercase;
        }

        /* Grids */
        .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
        .grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
        .grid-2-spotlight { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

        @media (max-width: 810px) {
            .grid-2, .grid-2-spotlight, .grid-3, .grid-4 { grid-template-columns: 1fr; }
            .footer-grid { grid-template-columns: 1fr 1fr; }
            .nav-links { display: none; }
            .d-xxl { letter-spacing: -3px; }
            .d-xl  { letter-spacing: -2.5px; }
            .d-lg  { letter-spacing: -1.5px; }
        }
    </style>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    <!-- Top nav -->
    <nav class="top-nav" id="topNav">
        <div class="container nav-row">
            <a href="{{ route('crud-generator.landing') }}" class="d-flex align-center" style="display:flex;align-items:center;gap:8px;color:var(--ink);font-family:'Geist',sans-serif;font-weight:500;font-size:15px;letter-spacing:-0.15px;">
                <span style="width:24px;height:24px;border-radius:6px;background:var(--ink);color:var(--on-primary);display:inline-flex;align-items:center;justify-content:center;font-size:12px;">⚡</span>
                CRUD Generator
            </a>
            <div class="nav-links">
                <a href="#stacks">Stacks</a>
                <a href="#features">Features</a>
                <a href="#demo">Demo</a>
                <a href="#install">Install</a>
            </div>
            <div class="nav-cta">
                <a href="https://github.com/Sajid-al-islam/laravel-crud-generator" target="_blank" class="btn-icon" aria-label="GitHub">
                    <i class="fab fa-github"></i>
                </a>
                <a href="{{ route('crud-generator.index') }}" class="btn btn-primary">
                    Open Generator <i class="fas fa-arrow-right" style="font-size:11px;"></i>
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero -->
    <header style="position:relative;overflow:hidden;">
        <div class="container" style="padding-top:96px;padding-bottom:96px;">
            <div style="display:grid;grid-template-columns:1.05fr 1fr;gap:48px;align-items:center;" class="hero-grid">
                <div class="section-reveal">
                    <div class="eyebrow" style="margin-bottom:24px;">
                        <span style="width:6px;height:6px;border-radius:50%;background:var(--accent-blue);"></span>
                        v3.0 — 8 stacks · pluggable architecture
                    </div>
                    <h1 class="d-xxl" style="margin-bottom:24px;">
                        Generate<br>complete<br>CRUD for<br>any stack.
                    </h1>
                    <p class="body-lg ink-muted" style="max-width:520px;margin-bottom:32px;">
                        One artisan command scaffolds model, controller, service, repository, requests, migration, and frontend pages — for Blade, API, React, Vue, Svelte, Livewire, Nova, and Filament.
                    </p>
                    <div style="display:flex;flex-wrap:wrap;gap:10px;">
                        <a href="{{ route('crud-generator.index') }}" class="btn btn-primary">
                            Get Started <i class="fas fa-arrow-right" style="font-size:11px;"></i>
                        </a>
                        <a href="https://github.com/Sajid-al-islam/laravel-crud-generator" target="_blank" class="btn btn-secondary">
                            <i class="fab fa-github"></i> View on GitHub
                        </a>
                    </div>
                </div>
                <div class="section-reveal">
                    <div class="terminal">
                        <div class="terminal-header">
                            <span class="terminal-dot dot-red"></span>
                            <span class="terminal-dot dot-yellow"></span>
                            <span class="terminal-dot dot-green"></span>
                            <span class="micro ink-muted mono" style="margin-left:8px;">~/my-app</span>
                        </div>
                        <div class="terminal-body" id="terminal">
                            <div class="terminal-line"><span class="terminal-prompt">$</span> <span class="terminal-output">php artisan make:crud</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Stacks grid -->
    <section id="stacks" class="section" style="border-top:1px solid var(--hairline-soft);">
        <div class="container">
            <div class="section-reveal" style="text-align:center;margin-bottom:48px;">
                <div class="eyebrow" style="margin-bottom:16px;">01 — Stacks</div>
                <h2 class="d-xl" style="margin-bottom:16px;">Eight stacks.<br>One command.</h2>
                <p class="body-lg ink-muted" style="max-width:560px;margin:0 auto;">Switch frameworks without rewriting the world. The same generation logic, tailored to each stack.</p>
            </div>
            <div class="grid-2-spotlight section-reveal">
                @php
                    $stacks = [
                        ['key' => 'blade', 'icon' => '🧩', 'title' => 'Blade', 'desc' => 'Server-rendered views with Bootstrap. The default.'],
                        ['key' => 'api', 'icon' => '🔌', 'title' => 'API', 'desc' => 'JSON API controllers + API Resources.'],
                        ['key' => 'react', 'icon' => '⚛️', 'title' => 'React', 'desc' => 'Inertia 2 + React 19 + shadcn/ui.'],
                        ['key' => 'vue', 'icon' => '🟢', 'title' => 'Vue', 'desc' => 'Inertia 2 + Vue 3 + shadcn-vue.'],
                        ['key' => 'svelte', 'icon' => '🔥', 'title' => 'Svelte', 'desc' => 'Inertia 2 + Svelte 5 + shadcn-svelte.'],
                        ['key' => 'livewire', 'icon' => '⚡', 'title' => 'Livewire', 'desc' => 'Livewire 4 + Flux UI.'],
                        ['key' => 'nova', 'icon' => '🚀', 'title' => 'Nova', 'desc' => 'Laravel Nova Resources.'],
                        ['key' => 'filament', 'icon' => '🧱', 'title' => 'Filament', 'desc' => 'Filament v3 Resources.'],
                    ];
                @endphp
                @foreach (array_slice($stacks, 0, 6) as $s)
                    <div class="stack-card">
                        <div style="font-size:28px;">{{ $s['icon'] }}</div>
                        <div>
                            <div class="headline" style="font-size:20px;font-weight:500;letter-spacing:-0.6px;">{{ $s['title'] }}</div>
                            <div class="body-sm ink-muted" style="margin-top:4px;">{{ $s['desc'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="grid-2-spotlight section-reveal" style="margin-top:16px;">
                <div class="spotlight spotlight-violet">
                    <div>
                        <div class="caption ink-muted" style="opacity:0.8;">Spotlight</div>
                        <h3 class="d-md" style="margin-top:8px;">{{ $stacks[6]['title'] }} — {{ $stacks[6]['icon'] }}</h3>
                        <p class="body" style="margin-top:12px;opacity:0.9;max-width:340px;">{{ $stacks[6]['desc'] }} Generates a full admin panel from a single model definition.</p>
                    </div>
                    <div class="caption" style="opacity:0.7;">laravel-crud-generator → nova</div>
                </div>
                <div class="spotlight spotlight-orange">
                    <div>
                        <div class="caption ink-muted" style="opacity:0.8;">Spotlight</div>
                        <h3 class="d-md" style="margin-top:8px;">{{ $stacks[7]['title'] }} — {{ $stacks[7]['icon'] }}</h3>
                        <p class="body" style="margin-top:12px;opacity:0.9;max-width:340px;">{{ $stacks[7]['desc'] }} Resources, table columns, and form fields, all wired up.</p>
                    </div>
                    <div class="caption" style="opacity:0.7;">laravel-crud-generator → filament</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section id="features" class="section" style="border-top:1px solid var(--hairline-soft);">
        <div class="container">
            <div class="section-reveal" style="text-align:center;margin-bottom:48px;">
                <div class="eyebrow" style="margin-bottom:16px;">02 — Features</div>
                <h2 class="d-xl" style="margin-bottom:16px;">Built for real apps.</h2>
                <p class="body-lg ink-muted" style="max-width:560px;margin:0 auto;">Beyond simple scaffolding. A clean architecture foundation that scales with your project.</p>
            </div>
            <div class="grid-3 section-reveal">
                <div class="feature-card">
                    <div style="font-size:28px;margin-bottom:16px;">🔌</div>
                    <h3 class="d-md" style="font-size:20px;">Pluggable Stacks</h3>
                    <p class="body ink-muted" style="margin:0;">8 first-party drivers. Build your own by implementing a single interface.</p>
                </div>
                <div class="feature-card">
                    <div style="font-size:28px;margin-bottom:16px;">🏗️</div>
                    <h3 class="d-md" style="font-size:20px;">Service Layer</h3>
                    <p class="body ink-muted" style="margin:0;">Optional <code class="mono" style="color:var(--ink);">PostService</code> class extracts business logic from your controller.</p>
                </div>
                <div class="feature-card">
                    <div style="font-size:28px;margin-bottom:16px;">📦</div>
                    <h3 class="d-md" style="font-size:20px;">Repository Pattern</h3>
                    <p class="body ink-muted" style="margin:0;">Interface + Eloquent implementation, bound automatically in the service container.</p>
                </div>
                <div class="feature-card">
                    <div style="font-size:28px;margin-bottom:16px;">🎨</div>
                    <h3 class="d-md" style="font-size:20px;">Custom Stubs</h3>
                    <p class="body ink-muted" style="margin:0;">Publish stubs, edit them, drop your own <code class="mono" style="color:var(--ink);">crud-generator.json</code>. Your rules, your output.</p>
                </div>
                <div class="feature-card">
                    <div style="font-size:28px;margin-bottom:16px;">🖥️</div>
                    <h3 class="d-md" style="font-size:20px;">Interactive CLI</h3>
                    <p class="body ink-muted" style="margin:0;"><code class="mono" style="color:var(--ink);">php artisan make:crud</code> opens a guided wizard powered by <code class="mono" style="color:var(--ink);">laravel/prompts</code>.</p>
                </div>
                <div class="feature-card">
                    <div style="font-size:28px;margin-bottom:16px;">🧪</div>
                    <h3 class="d-md" style="font-size:20px;">Syntactically Valid</h3>
                    <p class="body ink-muted" style="margin:0;">Generated PHP, TS, and Vue files all parse. Stubs are unit-tested for correctness.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Demo -->
    <section id="demo" class="section" style="border-top:1px solid var(--hairline-soft);">
        <div class="container">
            <div class="section-reveal" style="text-align:center;margin-bottom:48px;">
                <div class="eyebrow" style="margin-bottom:16px;">03 — Demo</div>
                <h2 class="d-xl" style="margin-bottom:16px;">From command to<br>full file tree.</h2>
                <p class="body-lg ink-muted" style="max-width:560px;margin:0 auto;">One artisan command. Ten files. A complete CRUD module for any stack.</p>
            </div>
            <div class="grid-2-spotlight section-reveal">
                <div class="card" style="padding:24px;">
                    <div class="caption ink-muted mono" style="margin-bottom:16px;">shell</div>
                    <pre class="mono" style="font-size:13px;line-height:1.7;margin:0;color:var(--ink);white-space:pre-wrap;"><span style="color:var(--accent-blue);">$</span> php artisan make:crud posts --stack=react --service

<span class="ink-muted">Which table?</span> posts
<span class="ink-muted">Which stack?</span>  react
<span class="ink-muted">Generate service class?</span> <span class="terminal-success">yes</span>

<span class="terminal-success">✔</span> app/Models/Post.php
<span class="terminal-success">✔</span> app/Http/Controllers/PostController.php
<span class="terminal-success">✔</span> app/Http/Requests/StorePostRequest.php
<span class="terminal-success">✔</span> app/Http/Requests/UpdatePostRequest.php
<span class="terminal-success">✔</span> app/Services/PostService.php
<span class="terminal-success">✔</span> resources/js/pages/Posts/Index.tsx
<span class="terminal-success">✔</span> resources/js/pages/Posts/Create.tsx
<span class="terminal-success">✔</span> resources/js/pages/Posts/Edit.tsx
<span class="terminal-success">✔</span> resources/js/pages/Posts/Show.tsx
<span class="terminal-success">✔</span> resources/js/types/post.ts
<span style="color:#febc2e;">~</span> routes/web.php  (appended)

<span class="ink-muted">Generated 10 files in 0.3s</span></pre>
                </div>
                <div class="card" style="padding:0;overflow:hidden;">
                    <div class="code-tabs">
                        <div class="code-tab active" data-target="t1">Index.tsx</div>
                        <div class="code-tab" data-target="t2">PostService.php</div>
                        <div class="code-tab" data-target="t3">migration.php</div>
                    </div>
                    <div class="code-panel" data-id="t1" style="padding:24px;">
                        <pre class="mono" style="font-size:13px;line-height:1.7;margin:0;white-space:pre-wrap;color:var(--ink);"><span style="color:#c084fc;">import</span> { Head, Link, useForm } <span style="color:#c084fc;">from</span> <span style="color:#86efac;">'@inertiajs/react'</span>;
<span style="color:#c084fc;">import</span> { Button, Input, Table } <span style="color:#c084fc;">from</span> <span style="color:#86efac;">'@/components/ui'</span>;
<span style="color:#c084fc;">import</span> AppLayout <span style="color:#c084fc;">from</span> <span style="color:#86efac;">'@/layouts/app-layout'</span>;

<span style="color:#c084fc;">export default function</span> <span style="color:#fde68a;">Index</span>({ posts }: Props) {
  <span style="color:#c084fc;">const</span> form = <span style="color:#fde68a;">useForm</span>({});
  <span style="color:#c084fc;">const</span> handleDelete = (id: number) =&gt; {
    <span style="color:#c084fc;">if</span> (confirm(<span style="color:#86efac;">'Are you sure?'</span>))
      form.<span style="color:#fde68a;">delete</span>(`posts/${id}`);
  };

  <span style="color:#c084fc;">return</span> (
    &lt;<span style="color:#7dd3fc;">AppLayout</span>&gt;
      &lt;<span style="color:#7dd3fc;">Table</span>&gt;
        {posts.data.map((post) =&gt; (
          &lt;<span style="color:#7dd3fc;">TableRow</span> key={post.id}&gt;
            &lt;<span style="color:#7dd3fc;">TableCell</span>&gt;{post.title}&lt;/<span style="color:#7dd3fc;">TableCell</span>&gt;
          &lt;/<span style="color:#7dd3fc;">TableRow</span>&gt;
        ))}
      &lt;/<span style="color:#7dd3fc;">Table</span>&gt;
    &lt;/<span style="color:#7dd3fc;">AppLayout</span>&gt;
  );
}</pre>
                    </div>
                    <div class="code-panel" data-id="t2" style="padding:24px;display:none;">
                        <pre class="mono" style="font-size:13px;line-height:1.7;margin:0;white-space:pre-wrap;color:var(--ink);"><span style="color:#c084fc;">class</span> <span style="color:#fde68a;">PostService</span>
{
    <span style="color:#c084fc;">public function</span> <span style="color:#fde68a;">paginate</span>(<span style="color:#c084fc;">int</span> $perPage = <span style="color:#fde68a;">15</span>)
    {
        <span style="color:#c084fc;">return</span> Post::<span style="color:#fde68a;">latest</span>()-&gt;<span style="color:#fde68a;">paginate</span>($perPage);
    }

    <span style="color:#c084fc;">public function</span> <span style="color:#fde68a;">store</span>(<span style="color:#c084fc;">array</span> $data): Post
    {
        <span style="color:#c084fc;">return</span> Post::<span style="color:#fde68a;">create</span>($data);
    }
}</pre>
                    </div>
                    <div class="code-panel" data-id="t3" style="padding:24px;display:none;">
                        <pre class="mono" style="font-size:13px;line-height:1.7;margin:0;white-space:pre-wrap;color:var(--ink);"><span style="color:#c084fc;">return new class extends</span> Migration
{
    <span style="color:#c084fc;">public function</span> <span style="color:#fde68a;">up</span>(): <span style="color:#c084fc;">void</span>
    {
        Schema::<span style="color:#fde68a;">create</span>(<span style="color:#86efac;">'posts'</span>, function (Blueprint $t) {
            $t-&gt;<span style="color:#fde68a;">id</span>();
            $t-&gt;<span style="color:#fde68a;">string</span>(<span style="color:#86efac;">'title'</span>);
            $t-&gt;<span style="color:#fde68a;">text</span>(<span style="color:#86efac;">'body'</span>)-&gt;<span style="color:#fde68a;">nullable</span>();
            $t-&gt;<span style="color:#fde68a;">timestamps</span>();
        });
    }
};</pre>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Install -->
    <section id="install" class="section-tight" style="border-top:1px solid var(--hairline-soft);">
        <div class="container" style="max-width:720px;">
            <div class="section-reveal" style="text-align:center;margin-bottom:32px;">
                <div class="eyebrow" style="margin-bottom:16px;">04 — Install</div>
                <h2 class="d-lg">Install in 30 seconds.</h2>
                <p class="body-lg ink-muted" style="margin-top:12px;">Three commands. Zero config. Run the wizard.</p>
            </div>
            <div class="section-reveal">
                <div class="install-line">
                    <span class="prompt mono">$</span>
                    <code class="mono">composer require sajidul-islam/laravel-crud-generator --dev</code>
                    <button class="copy-btn" onclick="copyInstall(this)"><i class="fas fa-copy"></i></button>
                </div>
                <div class="install-line">
                    <span class="prompt mono">$</span>
                    <code class="mono">php artisan vendor:publish --tag=crud-generator</code>
                    <button class="copy-btn" onclick="copyInstall(this)"><i class="fas fa-copy"></i></button>
                </div>
                <div class="install-line">
                    <span class="prompt mono">$</span>
                    <code class="mono">php artisan make:crud</code>
                    <button class="copy-btn" onclick="copyInstall(this)"><i class="fas fa-copy"></i></button>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div>
                    <a href="{{ route('crud-generator.landing') }}" style="display:flex;align-items:center;gap:8px;color:var(--ink);font-family:'Geist',sans-serif;font-weight:500;font-size:15px;margin-bottom:12px;text-decoration:none;">
                        <span style="width:24px;height:24px;border-radius:6px;background:var(--ink);color:var(--on-primary);display:inline-flex;align-items:center;justify-content:center;font-size:12px;">⚡</span>
                        CRUD Generator
                    </a>
                    <p class="caption ink-muted" style="max-width:240px;">Generate complete CRUD for any Laravel stack.</p>
                </div>
                <div class="footer-col">
                    <h4>Product</h4>
                    <a href="#stacks">Stacks</a>
                    <a href="#features">Features</a>
                    <a href="#demo">Demo</a>
                    <a href="#install">Install</a>
                </div>
                <div class="footer-col">
                    <h4>Resources</h4>
                    <a href="https://github.com/Sajid-al-islam/laravel-crud-generator" target="_blank">GitHub</a>
                    <a href="https://packagist.org/packages/sajidul-islam/laravel-crud-generator" target="_blank">Packagist</a>
                    <a href="{{ route('crud-generator.index') }}">Generator</a>
                </div>
                <div class="footer-col">
                    <h4>Stacks</h4>
                    <a href="#stacks">Blade</a>
                    <a href="#stacks">React</a>
                    <a href="#stacks">Vue</a>
                    <a href="#stacks">Livewire</a>
                </div>
                <div class="footer-col">
                    <h4>Legal</h4>
                    <a href="#">MIT License</a>
                    <a href="#">Changelog</a>
                </div>
            </div>
            <div style="margin-top:48px;padding-top:24px;border-top:1px solid var(--hairline-soft);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
                <p class="micro ink-muted">© {{ date('Y') }} Laravel CRUD Generator. MIT licensed.</p>
                <p class="micro ink-muted">v3.0.0</p>
            </div>
        </div>
    </footer>

    <script>
        // Terminal typing animation
        (function() {
            const lines = [
                { text: '<span class="terminal-prompt">$</span> <span class="terminal-output">php artisan make:crud</span>' },
                { text: '<span class="ink-muted">  Which table?</span> <span style="color:#7dd3fc;">posts</span>' },
                { text: '<span class="ink-muted">  Which stack?</span> <span style="color:#7dd3fc;">react (Inertia + shadcn/ui)</span>' },
                { text: '<span class="ink-muted">  Generate service class?</span> <span class="terminal-success">yes</span>' },
                { text: '' },
                { text: '<span class="terminal-success">  ✔</span> app/Models/Post.php' },
                { text: '<span class="terminal-success">  ✔</span> app/Http/Controllers/PostController.php' },
                { text: '<span class="terminal-success">  ✔</span> app/Http/Requests/StorePostRequest.php' },
                { text: '<span class="terminal-success">  ✔</span> app/Services/PostService.php' },
                { text: '<span class="terminal-success">  ✔</span> resources/js/pages/Posts/Index.tsx' },
                { text: '<span class="terminal-success">  ✔</span> resources/js/pages/Posts/Create.tsx' },
                { text: '<span class="terminal-success">  ✔</span> resources/js/pages/Posts/Edit.tsx' },
                { text: '<span class="terminal-success">  ✔</span> resources/js/pages/Posts/Show.tsx' },
                { text: '<span class="terminal-success">  ✔</span> resources/js/types/post.ts' },
                { text: '<span style="color:#febc2e;">  ~</span> routes/web.php  (appended)' },
                { text: '' },
                { text: '<span class="terminal-prompt">$</span> <span class="ink-muted">Generated 10 files in 0.3s</span>' },
            ];
            const terminal = document.getElementById('terminal');
            let i = 0;
            function next() {
                if (i >= lines.length) return;
                const div = document.createElement('div');
                div.className = 'terminal-line';
                div.innerHTML = lines[i].text === '' ? '&nbsp;' : lines[i].text;
                terminal.appendChild(div);
                i++;
                setTimeout(next, Math.random() * 200 + 60);
            }
            setTimeout(next, 400);
        })();

        // Section reveal
        (function() {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) entry.target.classList.add('visible');
                });
            }, { threshold: 0.1 });
            document.querySelectorAll('.section-reveal').forEach(el => observer.observe(el));
        })();

        // Code tabs
        document.querySelectorAll('.code-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                tab.parentElement.querySelectorAll('.code-tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                const id = tab.getAttribute('data-target');
                document.querySelectorAll('.code-panel').forEach(p => {
                    p.style.display = p.getAttribute('data-id') === id ? 'block' : 'none';
                });
            });
        });

        // Copy to clipboard
        function copyInstall(btn) {
            const code = btn.previousElementSibling.textContent.trim();
            navigator.clipboard.writeText(code).then(() => {
                btn.classList.add('copied');
                btn.innerHTML = '<i class="fas fa-check"></i>';
                setTimeout(() => {
                    btn.classList.remove('copied');
                    btn.innerHTML = '<i class="fas fa-copy"></i>';
                }, 1500);
            });
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
    <style>
        @media (max-width: 810px) {
            .hero-grid { grid-template-columns: 1fr !important; gap: 32px !important; }
        }
    </style>
</body>
</html>
