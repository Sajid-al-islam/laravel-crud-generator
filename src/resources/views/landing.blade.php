<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laravel CRUD Generator — Generate CRUD for any stack</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;700&family=Geist:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        :root {
            --bg: #0a0a0f;
            --bg-elev: #11111a;
            --bg-soft: #1a1a26;
            --border: #2a2a3a;
            --text: #ffffff;
            --text-muted: #a0a0b0;
            --accent: #7c3aed;
            --accent-2: #06b6d4;
            --accent-glow: rgba(124, 58, 237, 0.4);
        }

        * { box-sizing: border-box; }

        html, body {
            background: var(--bg);
            color: var(--text);
            font-family: 'Geist', system-ui, -apple-system, sans-serif;
            margin: 0;
            padding: 0;
            scroll-behavior: smooth;
        }

        code, pre, .mono { font-family: 'JetBrains Mono', monospace; }

        .gradient-text {
            background: linear-gradient(135deg, #7c3aed 0%, #06b6d4 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .glow-button {
            background: linear-gradient(135deg, #7c3aed 0%, #06b6d4 100%);
            box-shadow: 0 0 20px var(--accent-glow);
            transition: all 0.2s;
        }
        .glow-button:hover { transform: translateY(-1px); box-shadow: 0 0 30px var(--accent-glow); }

        .terminal {
            background: #000;
            border: 1px solid var(--border);
            border-radius: 0.75rem;
            box-shadow: 0 20px 60px -10px rgba(124, 58, 237, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.05);
            overflow: hidden;
        }
        .terminal-header {
            background: #1a1a26;
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border-bottom: 1px solid var(--border);
        }
        .terminal-dot { width: 12px; height: 12px; border-radius: 50%; }
        .dot-red { background: #ff5f57; }
        .dot-yellow { background: #febc2e; }
        .dot-green { background: #28c840; }
        .terminal-body {
            padding: 1.25rem 1.5rem;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.9rem;
            line-height: 1.7;
            min-height: 280px;
        }
        .terminal-line { opacity: 0; animation: fadeIn 0.3s forwards; }
        .terminal-prompt { color: var(--accent-2); }
        .terminal-output { color: #a0a0b0; }
        .terminal-success { color: #28c840; }
        @keyframes fadeIn { to { opacity: 1; } }

        .stack-card {
            background: var(--bg-elev);
            border: 1px solid var(--border);
            border-radius: 0.75rem;
            padding: 1.5rem;
            transition: all 0.25s;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        .stack-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(124, 58, 237, 0.1), transparent);
            opacity: 0;
            transition: opacity 0.25s;
        }
        .stack-card:hover { transform: translateY(-4px); border-color: var(--accent); }
        .stack-card:hover::before { opacity: 1; }
        .stack-card:hover .stack-icon { transform: scale(1.1); }
        .stack-icon { transition: transform 0.25s; }

        .feature-card {
            background: var(--bg-elev);
            border: 1px solid var(--border);
            border-radius: 0.75rem;
            padding: 2rem;
            transition: all 0.25s;
        }
        .feature-card:hover { border-color: var(--accent); transform: translateY(-2px); }

        .section-reveal {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.6s, transform 0.6s;
        }
        .section-reveal.visible { opacity: 1; transform: translateY(0); }

        .code-tab {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            cursor: pointer;
            color: var(--text-muted);
            transition: all 0.2s;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.85rem;
        }
        .code-tab.active { background: var(--accent); color: white; }

        .install-line {
            background: var(--bg-elev);
            border: 1px solid var(--border);
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.5rem;
        }
        .install-line code { flex: 1; }
        .copy-btn {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text-muted);
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            cursor: pointer;
            transition: all 0.15s;
        }
        .copy-btn:hover { border-color: var(--accent); color: var(--accent); }
        .copy-btn.copied { border-color: #28c840; color: #28c840; }

        .grid-bg {
            background-image:
                linear-gradient(rgba(124, 58, 237, 0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(124, 58, 237, 0.05) 1px, transparent 1px);
            background-size: 50px 50px;
        }
    </style>
</head>
<body>
    <!-- Nav -->
    <nav class="border-b border-zinc-800 sticky top-0 bg-zinc-950/80 backdrop-blur z-50">
        <div class="container mx-auto px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-md bg-gradient-to-br from-violet-600 to-cyan-500 flex items-center justify-center">
                    <i class="fas fa-bolt text-white text-sm"></i>
                </div>
                <span class="font-semibold text-lg">Laravel CRUD Generator</span>
            </div>
            <div class="hidden md:flex items-center gap-6 text-sm">
                <a href="#stacks" class="text-zinc-400 hover:text-white transition">Stacks</a>
                <a href="#features" class="text-zinc-400 hover:text-white transition">Features</a>
                <a href="#demo" class="text-zinc-400 hover:text-white transition">Demo</a>
                <a href="#install" class="text-zinc-400 hover:text-white transition">Install</a>
            </div>
            <div class="flex items-center gap-3">
                <a href="https://github.com/Sajid-al-islam/laravel-crud-generator" target="_blank" class="text-zinc-400 hover:text-white text-sm">
                    <i class="fab fa-github text-lg"></i>
                </a>
                <a href="{{ route('crud-generator.index') }}" class="glow-button text-white text-sm px-4 py-2 rounded-md">
                    Open Generator →
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero -->
    <header class="grid-bg relative overflow-hidden">
        <div class="container mx-auto px-6 py-20 lg:py-32">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div class="section-reveal">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-violet-500/10 border border-violet-500/20 text-violet-300 text-xs mb-6">
                        <span class="w-2 h-2 rounded-full bg-violet-400 animate-pulse"></span>
                        v3.0 — 8 stacks, pluggable architecture
                    </div>
                    <h1 class="text-5xl lg:text-6xl font-bold leading-tight mb-6">
                        Generate <span class="gradient-text">complete CRUD</span><br>
                        for any stack.<br>
                        <span class="text-zinc-400">Zero boilerplate.</span>
                    </h1>
                    <p class="text-lg text-zinc-400 mb-8 max-w-xl">
                        A Laravel package that scaffolds model, controller, service, repository, requests, migration, and frontend pages — for Blade, API, React, Vue, Svelte, Livewire, Nova, and Filament.
                    </p>
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('crud-generator.index') }}" class="glow-button text-white px-6 py-3 rounded-md font-medium">
                            Get Started <i class="fas fa-arrow-right ml-2"></i>
                        </a>
                        <a href="https://github.com/Sajid-al-islam/laravel-crud-generator" target="_blank"
                            class="px-6 py-3 rounded-md font-medium border border-zinc-700 hover:border-zinc-500 transition">
                            <i class="fab fa-github mr-2"></i>View on GitHub
                        </a>
                    </div>
                </div>
                <div class="section-reveal">
                    <div class="terminal">
                        <div class="terminal-header">
                            <span class="terminal-dot dot-red"></span>
                            <span class="terminal-dot dot-yellow"></span>
                            <span class="terminal-dot dot-green"></span>
                            <span class="ml-2 text-zinc-500 text-xs mono">~/my-app</span>
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
    <section id="stacks" class="py-24 border-t border-zinc-800">
        <div class="container mx-auto px-6">
            <div class="text-center mb-12 section-reveal">
                <h2 class="text-4xl font-bold mb-4">Eight stacks, <span class="gradient-text">one command</span></h2>
                <p class="text-zinc-400 max-w-2xl mx-auto">Switch between backend and frontend frameworks without rewriting the world. The same generation logic, tailored to each stack.</p>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 section-reveal">
                @php
                    $stacks = [
                        ['key' => 'blade', 'icon' => '🧩', 'title' => 'Blade', 'desc' => 'Server-rendered views with Bootstrap'],
                        ['key' => 'api', 'icon' => '🔌', 'title' => 'API', 'desc' => 'JSON API + API Resources'],
                        ['key' => 'react', 'icon' => '⚛️', 'title' => 'React', 'desc' => 'Inertia 2 + React 19 + shadcn/ui'],
                        ['key' => 'vue', 'icon' => '🟢', 'title' => 'Vue', 'desc' => 'Inertia 2 + Vue 3 + shadcn-vue'],
                        ['key' => 'svelte', 'icon' => '🔥', 'title' => 'Svelte', 'desc' => 'Inertia 2 + Svelte 5 + shadcn-svelte'],
                        ['key' => 'livewire', 'icon' => '⚡', 'title' => 'Livewire', 'desc' => 'Livewire 4 + Flux UI'],
                        ['key' => 'nova', 'icon' => '🚀', 'title' => 'Nova', 'desc' => 'Laravel Nova Resources'],
                        ['key' => 'filament', 'icon' => '🧱', 'title' => 'Filament', 'desc' => 'Filament v3 Resources'],
                    ];
                @endphp
                @foreach ($stacks as $s)
                    <div class="stack-card">
                        <div class="stack-icon text-3xl mb-3">{{ $s['icon'] }}</div>
                        <h3 class="font-semibold text-lg mb-1">{{ $s['title'] }}</h3>
                        <p class="text-sm text-zinc-400">{{ $s['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Features -->
    <section id="features" class="py-24 border-t border-zinc-800 bg-zinc-950">
        <div class="container mx-auto px-6">
            <div class="text-center mb-12 section-reveal">
                <h2 class="text-4xl font-bold mb-4">Built for <span class="gradient-text">real apps</span></h2>
                <p class="text-zinc-400 max-w-2xl mx-auto">Beyond simple scaffolding. A clean architecture foundation that scales with your project.</p>
            </div>
            <div class="grid md:grid-cols-3 gap-6 section-reveal">
                <div class="feature-card">
                    <div class="text-3xl mb-4">🔌</div>
                    <h3 class="text-xl font-semibold mb-2">Pluggable Stacks</h3>
                    <p class="text-zinc-400 text-sm">8 first-party drivers. Build your own by implementing a single interface.</p>
                </div>
                <div class="feature-card">
                    <div class="text-3xl mb-4">🏗️</div>
                    <h3 class="text-xl font-semibold mb-2">Service Layer</h3>
                    <p class="text-zinc-400 text-sm">Optional <code class="mono text-violet-300">PostService</code> class extracts business logic from your controller.</p>
                </div>
                <div class="feature-card">
                    <div class="text-3xl mb-4">📦</div>
                    <h3 class="text-xl font-semibold mb-2">Repository Pattern</h3>
                    <p class="text-zinc-400 text-sm">Interface + Eloquent implementation, bound automatically in the service container.</p>
                </div>
                <div class="feature-card">
                    <div class="text-3xl mb-4">🎨</div>
                    <h3 class="text-xl font-semibold mb-2">Custom Stubs</h3>
                    <p class="text-zinc-400 text-sm">Publish stubs, edit them, drop your own <code class="mono text-violet-300">crud-generator.json</code>. Your rules, your output.</p>
                </div>
                <div class="feature-card">
                    <div class="text-3xl mb-4">🖥️</div>
                    <h3 class="text-xl font-semibold mb-2">Interactive CLI</h3>
                    <p class="text-zinc-400 text-sm"><code class="mono text-violet-300">php artisan make:crud</code> opens a guided wizard powered by <code class="mono text-violet-300">laravel/prompts</code>.</p>
                </div>
                <div class="feature-card">
                    <div class="text-3xl mb-4">🧪</div>
                    <h3 class="text-xl font-semibold mb-2">Syntactically Valid</h3>
                    <p class="text-zinc-400 text-sm">Generated PHP, TS, and Vue files all parse. Stubs are unit-tested for correctness.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Demo -->
    <section id="demo" class="py-24 border-t border-zinc-800">
        <div class="container mx-auto px-6">
            <div class="text-center mb-12 section-reveal">
                <h2 class="text-4xl font-bold mb-4">From command to <span class="gradient-text">full file tree</span></h2>
                <p class="text-zinc-400 max-w-2xl mx-auto">One artisan command. Ten files. A complete CRUD module for any stack.</p>
            </div>
            <div class="grid lg:grid-cols-2 gap-6 section-reveal">
                <div class="bg-zinc-900 border border-zinc-800 rounded-lg p-6">
                    <div class="text-xs text-zinc-500 mb-3 mono">shell</div>
                    <pre class="mono text-sm leading-relaxed"><code><span class="text-cyan-400">$</span> php artisan make:crud posts --stack=react --service

<span class="text-zinc-500">Which table?</span> posts
<span class="text-zinc-500">Which stack?</span>  react
<span class="text-zinc-500">Generate service class?</span> <span class="text-green-400">yes</span>

<span class="text-green-400">✔</span> app/Models/Post.php
<span class="text-green-400">✔</span> app/Http/Controllers/PostController.php
<span class="text-green-400">✔</span> app/Http/Requests/StorePostRequest.php
<span class="text-green-400">✔</span> app/Http/Requests/UpdatePostRequest.php
<span class="text-green-400">✔</span> app/Services/PostService.php
<span class="text-green-400">✔</span> resources/js/pages/Posts/Index.tsx
<span class="text-green-400">✔</span> resources/js/pages/Posts/Create.tsx
<span class="text-green-400">✔</span> resources/js/pages/Posts/Edit.tsx
<span class="text-green-400">✔</span> resources/js/pages/Posts/Show.tsx
<span class="text-green-400">✔</span> resources/js/types/post.ts
<span class="text-yellow-400">~</span> routes/web.php  (appended)

<span class="text-zinc-500">Generated 10 files in 0.3s</span></code></pre>
                </div>
                <div class="bg-zinc-900 border border-zinc-800 rounded-lg overflow-hidden">
                    <div class="flex border-b border-zinc-800 bg-zinc-950">
                        <div class="code-tab active">Index.tsx</div>
                        <div class="code-tab">PostService.php</div>
                        <div class="code-tab">migration.php</div>
                    </div>
                    <pre class="mono text-sm p-6 overflow-x-auto leading-relaxed"><code><span class="text-violet-400">import</span> { Head, Link, router, useForm } <span class="text-violet-400">from</span> <span class="text-green-300">'@inertiajs/react'</span>;
<span class="text-violet-400">import</span> { Button, Input, Table, TableHeader } <span class="text-violet-400">from</span> <span class="text-green-300">'@/components/ui'</span>;
<span class="text-violet-400">import</span> AppLayout <span class="text-violet-400">from</span> <span class="text-green-300">'@/layouts/app-layout'</span>;

<span class="text-violet-400">export default function</span> <span class="text-yellow-300">Index</span>({ posts }: Props) {
  <span class="text-violet-400">const</span> form = <span class="text-yellow-300">useForm</span>({});
  <span class="text-violet-400">const</span> handleDelete = (id: number) => {
    <span class="text-violet-400">if</span> (confirm(<span class="text-green-300">'Are you sure?'</span>)) form.<span class="text-yellow-300">delete</span>(`posts/${id}`);
  };

  <span class="text-violet-400">return</span> (
    &lt;<span class="text-blue-400">AppLayout</span>&gt;
      &lt;<span class="text-blue-400">Table</span>&gt;
        {posts.data.map((post) =&gt; (
          &lt;<span class="text-blue-400">TableRow</span> key={post.id}&gt;
            &lt;<span class="text-blue-400">TableCell</span>&gt;{post.title}&lt;/<span class="text-blue-400">TableCell</span>&gt;
          &lt;/<span class="text-blue-400">TableRow</span>&gt;
        ))}
      &lt;/<span class="text-blue-400">Table</span>&gt;
    &lt;/<span class="text-blue-400">AppLayout</span>&gt;
  );
}</code></pre>
                </div>
            </div>
        </div>
    </section>

    <!-- Install -->
    <section id="install" class="py-24 border-t border-zinc-800 bg-zinc-950">
        <div class="container mx-auto px-6 max-w-3xl">
            <div class="text-center mb-12 section-reveal">
                <h2 class="text-4xl font-bold mb-4">Install in <span class="gradient-text">30 seconds</span></h2>
                <p class="text-zinc-400">Three commands. Zero config. Run the wizard.</p>
            </div>
            <div class="section-reveal">
                <div class="install-line">
                    <span class="text-cyan-400 mono">$</span>
                    <code class="mono text-sm text-zinc-200">composer require sajidul-islam/laravel-crud-generator --dev</code>
                    <button class="copy-btn" onclick="copyInstall(this)"><i class="fas fa-copy"></i></button>
                </div>
                <div class="install-line">
                    <span class="text-cyan-400 mono">$</span>
                    <code class="mono text-sm text-zinc-200">php artisan vendor:publish --tag=crud-generator</code>
                    <button class="copy-btn" onclick="copyInstall(this)"><i class="fas fa-copy"></i></button>
                </div>
                <div class="install-line">
                    <span class="text-cyan-400 mono">$</span>
                    <code class="mono text-sm text-zinc-200">php artisan make:crud</code>
                    <button class="copy-btn" onclick="copyInstall(this)"><i class="fas fa-copy"></i></button>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="border-t border-zinc-800 py-12">
        <div class="container mx-auto px-6">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="text-zinc-500 text-sm">
                    © {{ date('Y') }} Laravel CRUD Generator. MIT licensed.
                </div>
                <div class="flex items-center gap-6 text-sm text-zinc-400">
                    <a href="https://github.com/Sajid-al-islam/laravel-crud-generator" target="_blank" class="hover:text-white transition">
                        <i class="fab fa-github mr-1"></i>GitHub
                    </a>
                    <a href="https://packagist.org/packages/sajidul-islam/laravel-crud-generator" target="_blank" class="hover:text-white transition">
                        <i class="fas fa-box mr-1"></i>Packagist
                    </a>
                    <a href="{{ route('crud-generator.index') }}" class="hover:text-white transition">
                        <i class="fas fa-magic mr-1"></i>Generator
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Terminal typing animation
        (function() {
            const lines = [
                { text: '<span class="terminal-prompt">$</span> <span class="terminal-output">php artisan make:crud</span>' },
                { text: '<span class="text-zinc-500">  Which table?</span> <span class="text-cyan-300">posts</span>' },
                { text: '<span class="text-zinc-500">  Which stack?</span> <span class="text-cyan-300">react (Inertia + shadcn/ui)</span>' },
                { text: '<span class="text-zinc-500">  Generate service class?</span> <span class="terminal-success">yes</span>' },
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
                { text: '<span class="text-yellow-400">  ~</span> routes/web.php  (appended)' },
                { text: '' },
                { text: '<span class="terminal-prompt">$</span> <span class="text-zinc-500">Generated 10 files in 0.3s</span>' },
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
                setTimeout(next, Math.random() * 220 + 80);
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

        // Code tabs (visual only)
        document.querySelectorAll('.code-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                tab.parentElement.querySelectorAll('.code-tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
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
    </script>
</body>
</html>
