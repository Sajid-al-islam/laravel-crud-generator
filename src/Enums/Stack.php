<?php

declare(strict_types=1);

namespace SajidUlIslam\CrudGenerator\Enums;

enum Stack: string
{
    case Blade = 'blade';
    case Api = 'api';
    case React = 'react';
    case Vue = 'vue';
    case Svelte = 'svelte';
    case Livewire = 'livewire';
    case Nova = 'nova';
    case Filament = 'filament';

    public function label(): string
    {
        return match ($this) {
            self::Blade => 'Blade (traditional)',
            self::Api => 'API only (JSON)',
            self::React => 'React (Inertia + shadcn/ui)',
            self::Vue => 'Vue (Inertia + shadcn-vue)',
            self::Svelte => 'Svelte (Inertia + shadcn-svelte)',
            self::Livewire => 'Livewire (Flux UI)',
            self::Nova => 'Laravel Nova',
            self::Filament => 'Laravel Filament',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Blade => 'Server-rendered Blade views with Bootstrap UI.',
            self::Api => 'JSON API controllers and API Resources.',
            self::React => 'Inertia 2 + React 19 + TypeScript + shadcn/ui.',
            self::Vue => 'Inertia 2 + Vue 3 Composition API + TypeScript + shadcn-vue.',
            self::Svelte => 'Inertia 2 + Svelte 5 + TypeScript + shadcn-svelte.',
            self::Livewire => 'Livewire 4 components with Flux UI.',
            self::Nova => 'Laravel Nova admin panel resources.',
            self::Filament => 'Filament v3 admin panel resources.',
        };
    }

    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
