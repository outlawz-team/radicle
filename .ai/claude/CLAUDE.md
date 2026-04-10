# Instructies — Radicle WordPress projecten

## Stack

- **Framework**: [Roots Radicle](https://roots.io/radicle/) — Bedrock + Acorn, aangevuld met eigen composer package [outlawz-team/radicle](https://github.com/outlawz-team/radicle)
- **Templating**: Laravel Blade (via Roots Acorn)
- **CSS**: Tailwind CSS v4 — geconfigureerd via Vite plugin, geen `tailwind.config.js`
- **JS**: Alpine.js v3 — geïmporteerd in `resources/js/app.js`
- **ACF**: Advanced Custom Fields Pro v6.7
- **WooCommerce**: optioneel aanwezig — templates in `resources/views/woocommerce/`
- **Build**: Vite 8
- **PHP**: ≥8.4

## Communicatie

- Antwoord altijd in het **Nederlands**
- Code, comments, variabelenamen en bestandsnamen altijd in het **Engels**

## Design implementatie — pixel perfect

Implementeer designs altijd **pixel perfect**:

- Gebruik exact de maten, ruimtes, en groottes uit het design — niet afronden of aanpassen
- Controleer padding, margin, gap, width en height nauwkeurig aan de hand van het design
- Verzin **nooit** zelf designkeuzes — alleen overnemen wat expliciet zichtbaar is in het ontwerp
- Twijfel je of iets in het design staat? Vraag het, verzin het niet zelf
- Gebruik `px`-waarden via inline style of Tailwind bracket-notatie wanneer een exacte pixelwaarde niet in de Tailwind schaal valt (geen canonical class beschikbaar)

## Bestandsstructuur

```
app/
  Acf/             # ACF field group klassen (gegenereerd via wp acorn make:acf)
  Blocks/          # PHP block-klassen met render callbacks
  Models/          # Eloquent WordPress models
  Providers/       # Service providers (Theme, Assets, Blocks, WooCommerce, etc.)
  View/Composers/  # Data injecteren in Blade views
config/
  acf.php          # ACF options pages
  post-types.php   # CPT en taxonomy registraties (Extended CPTs)
resources/
  views/
    blocks/        # Blade views voor flex content blokken
    components/    # Herbruikbare Blade components (<x-component-name>)
    layouts/       # Layout templates
    sections/      # Header, footer
    woocommerce/   # WooCommerce template overrides (spiegelt WooCommerce structuur)
```

## Naming conventions

- Bestandsnamen: **kebab-case** (`hero-banner.blade.php`, `text-image.blade.php`)
- PHP klassen: **PascalCase** (`HeroBanner.php`)

## Regels

- **Altijd Blade** — gebruik nooit plain PHP templates (`.php`), altijd `.blade.php`
- **Geen jQuery** — altijd vanilla JS of Alpine.js
- **Geen `@php` blokken bovenin Blade templates** — logica hoort in View Composers. Gebruik `@php` alleen direct bij de plek waar het nodig is.
- **Geen npm of composer packages installeren** zonder expliciete toestemming
- **Geen `@apply`** — gebruik Tailwind utilities altijd direct in templates. Uitzondering: `@apply` mag alleen voor het overschrijven van WordPress plugin-stijlen die je niet zelf kunt aanpassen:

```css
/* ✅ OK — plugin override */
.woocommerce-checkout .some-plugin-class {
    @apply text-sm p-4;
}
```

- **Output altijd escapen** — gebruik `{{ }}` voor alle output. Gebruik `{!! !!}` alleen als je bewust ongefilterde HTML wilt renderen:

```blade
{{-- GOED --}}
{{ $title }}

{{-- Alleen als HTML output gewenst is --}}
{!! $content !!}
```

- **Vertaalfuncties** — gebruik altijd `__()` voor gebruikersteksten:

```blade
{{ __('Meer lezen', 'radicle') }}
```

- **SVG's altijd met `currentColor`** — gebruik nooit hardcoded kleurwaarden in SVG's (`fill="#1a1a1a"`, `stroke="white"`). Gebruik `fill="currentColor"` of `stroke="currentColor"` en stel de kleur in via een Tailwind-kleurklasse op het parent-element:

```blade
{{-- GOED --}}
<span class="text-on-surface">
    <svg width="15" height="25" viewBox="0 0 15 25" fill="none">
        <polygon points="0,0 15,12.5 0,25" fill="currentColor"/>
    </svg>
</span>

{{-- FOUT --}}
<svg width="15" height="25" viewBox="0 0 15 25" fill="none">
    <polygon points="0,0 15,12.5 0,25" fill="#1a1a1a"/>
</svg>
```

## Alpine.js

Gebruik `x-cloak` altijd bij elementen die pas na Alpine-initialisatie zichtbaar mogen zijn, om layout shift te voorkomen:

```blade
<div x-data="{ open: false }">
    <button @click="open = !open">Toggle</button>
    <div x-show="open" x-cloak x-transition>Content</div>
</div>
```

Voeg in de CSS toe:
```css
[x-cloak] { display: none !important; }
```

## Icons

Gebruik Heroicons via de Blade UI Kit component:

```blade
<x-heroicon-o-arrow-right class="w-5 h-5" />
<x-heroicon-s-check class="w-4 h-4 text-primary" />
```

Gebruik nooit hardcoded `<svg>` voor iconen als er een Heroicon beschikbaar is.

## Semantische HTML en toegankelijkheid

- Gebruik altijd semantisch correcte HTML-elementen (`<nav>`, `<main>`, `<article>`, `<section>`, etc.)
- Voeg ARIA-attributen toe waar nodig (`aria-label`, `aria-expanded`, `aria-hidden`, etc.)
- Interactieve elementen zoals modals en dropdowns krijgen altijd de juiste ARIA-rollen en keyboard-ondersteuning

## PHP conventies

- **`camelCase` voor variabelen en methods** — ook al gebruikt WordPress core `$snake_case`, gebruik in eigen code altijd `$camelCase` en `camelCase()`:

```php
// ✅ Goed
$postTypes = ['post', 'page'];
public function getPublishedPosts(): Collection { ... }

// ❌ Fout
$post_types = ['post', 'page'];
public function get_published_posts(): array { ... }
```

- **`declare(strict_types=1)`** — altijd bovenaan elk PHP-bestand plaatsen

- **Early returns** — gebruik guard clauses om nesting te vermijden:

```php
// ✅ Goed
public function getTitle($post): string
{
    if (! $post) {
        return 'Untitled';
    }

    return $post->post_title;
}

// ❌ Fout
public function getTitle($post): string
{
    if ($post) {
        return $post->post_title;
    } else {
        return 'Untitled';
    }
}
```

- **Voorkeur voor Laravel helpers** — gebruik `collect()`, `Str::`, `Arr::` en andere Laravel utilities boven native PHP-equivalenten

- **Geen `admin-ajax.php`** — gebruik altijd WordPress REST API endpoints:

```php
// ✅ Goed
add_action('rest_api_init', function () {
    register_rest_route('app/v1', '/posts', [
        'methods' => 'GET',
        'callback' => fn () => get_posts(['numberposts' => 10]),
        'permission_callback' => fn () => current_user_can('read'),
    ]);
});

// ❌ Nooit doen
add_action('wp_ajax_get_posts', function () { ... });
```

- **Na elke PHP-wijziging `./vendor/bin/pint` uitvoeren** — draai Pint altijd direct na het aanpassen van een PHP-bestand om de code style automatisch te fixen:

```bash
./vendor/bin/pint
```

- **Geen `wp_localize_script()`** — gebruik `wp_add_inline_script()` om data aan JavaScript door te geven:

```php
// ✅ Goed
wp_add_inline_script(
    'app',
    'const appData = ' . wp_json_encode(['apiUrl' => home_url('/wp-json/app/v1')]) . ';',
    'before'
);

// ❌ Nooit doen
wp_localize_script('app', 'appData', ['apiUrl' => home_url('/wp-json/app/v1')]);
```

## Custom Post Types

Registreer CPTs in `config/post-types.php` via Extended CPTs:

```php
'post_types' => [
    'project' => [
        [
            'public' => true,
            'has_archive' => true,
            'supports' => ['title', 'editor', 'thumbnail'],
            'show_in_rest' => true,
        ],
        [
            'singular' => 'Project',
            'plural'   => 'Projecten',
            'slug'     => 'projecten',
        ]
    ]
]
```

Maak daarna:
- Model in `app/Models/[ModelName].php` (extend `App\Models\Post`)
- Blade views: `single-[slug].blade.php` en `archive-[slug].blade.php`
- View Composer als data-injectie nodig is

## WooCommerce

- Template overrides in `resources/views/woocommerce/` — spiegelt de WooCommerce template-structuur
- Hooks en filters in een aparte `app/Providers/WooCommerceServiceProvider.php`
- ACF fields op WooCommerce post types via `location()` in de Acf-klasse

## Nuttige commands

```bash
npm run dev           # Vite dev server starten
npm run build         # Production build
wp acorn make:acf     # ACF field group klasse genereren in app/Acf/
composer run lint     # PHP code style controleren
composer run lint:fix # PHP code style automatisch fixen
composer run test     # Tests uitvoeren (Pest)
```
