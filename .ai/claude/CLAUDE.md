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

## ACF in Blade — inline assignment stijl

Gebruik altijd de inline assignment in `@if` voor cleane templates:

```blade
@if ($title = get_sub_field('title'))
    <h2 class="text-3xl font-bold">{{ $title }}</h2>
@endif

@if ($image = get_sub_field('image'))
    <img src="{{ $image['url'] }}" alt="{{ $image['alt'] }}" class="w-full object-cover">
@endif
```

## ACF — leesbare labels en instructies

Geef elk ACF field altijd een **duidelijk, leesbaar label** in het Nederlands dat de redacteur direct begrijpt. Geen technische namen of Engelse slugs als label.

```php
// ✅ Goed
['label' => 'Hoofdtitel', 'name' => 'heading', ...]
['label' => 'Achtergrondafbeelding', 'name' => 'background_image', ...]
['label' => 'Knoptekst', 'name' => 'button', ...]

// ❌ Fout
['label' => 'heading', 'name' => 'heading', ...]
['label' => 'bg_image', 'name' => 'background_image', ...]
```

Voeg waar nodig een **`instructions`** toe om de redacteur te helpen — zeker bij fields die niet vanzelfsprekend zijn (afbeeldingsverhoudingen, tekenlimieten, verwacht formaat, etc.):

```php
[
    'key'          => 'field_hb_heading',
    'label'        => 'Hoofdtitel',
    'name'         => 'heading',
    'type'         => 'text',
    'instructions' => 'Houd de titel kort (max. 60 tekens).',
],
[
    'key'          => 'field_hb_image',
    'label'        => 'Afbeelding',
    'name'         => 'image',
    'type'         => 'image',
    'instructions' => 'Gebruik een afbeelding in verhouding 16:9, minimaal 1280×720px.',
    'return_format' => 'array',
],
```

## ACF — tekstvelden altijd als Wysiwyg Editor

Gebruik voor tekst area blokken (lange tekst, beschrijvingen, body content) **altijd** het ACF field type `wysiwyg`. Gebruik nooit een `textarea` field als de redacteur opmaak nodig heeft (koppen, bold, lijsten, links, etc.).

```php
[
    'key'          => 'field_hb_content',
    'label'        => 'Content',
    'name'         => 'content',
    'type'         => 'wysiwyg',
    'toolbar'      => 'full',
    'media_upload' => 0,
],
```

In Blade — render altijd met `{!! !!}` omdat de output HTML bevat:

```blade
@if ($content = get_sub_field('content'))
    <div class="prose max-w-none">{!! $content !!}</div>
@endif
```

Gebruik een `textarea` field **alleen** als het gaat om puur plain text zonder opmaak (bijv. een meta-omschrijving of een alt-tekst invoerveld).

## ACF — knoppen altijd als link field

Gebruik voor knoppen **altijd** het ACF field type `link`. Dit geeft de redacteur drie velden: URL, tekst en target (`_blank` of niet). Gebruik nooit losse `text` fields voor URL en label.

```php
[
    'key'          => 'field_hb_button',
    'label'        => 'Button',
    'name'         => 'button',
    'type'         => 'link',
    'return_format' => 'array',
],
```

In Blade:

```blade
@if ($button = get_sub_field('button'))
    <a href="{{ $button['url'] }}"
       class="bg-primary text-on-primary px-6 py-3"
       {{ $button['target'] ? 'target="' . $button['target'] . '"' : '' }}>
        {{ $button['title'] }}
    </a>
@endif
```

## Custom font utilities

Gebruik **nooit** losse Tailwind classes voor typografie zoals `text-xl font-semibold leading-7`. Definieer in plaats daarvan altijd een custom utility class met het patroon:

```
text-{font-size}-{line-height}-{font-weight}
```

Alle waarden zijn in **pixels** (zonder eenheid in de naam). Voorbeelden:

| Class | font-size | line-height | font-weight |
|---|---|---|---|
| `text-12-16-400` | 12px | 16px | 400 |
| `text-16-24-600` | 16px | 24px | 600 |
| `text-32-40-700` | 32px | 40px | 700 |

Definieer deze utilities in `resources/css/app.css` via `@theme` (Tailwind v4):

```css
@theme {
    --text-16-24-600: 16px;
    --text-16-24-600--line-height: 24px;
    --text-16-24-600--font-weight: 600;
}
```

Gebruik de class vervolgens direct in Blade:

```blade
<h2 class="text-32-40-700">{{ $title }}</h2>
<p class="text-16-24-400">{{ $body }}</p>
```

Wanneer je een nieuwe font utility nodig hebt die nog niet bestaat: **definieer hem eerst** in het CSS-bestand voordat je hem gebruikt in een Blade template.

## Kleurgebruik — semantische tokens

Gebruik **altijd** semantische kleur-tokens in plaats van hardcoded Tailwind-kleuren zoals `bg-gray-100`, `text-zinc-800` of `bg-white`. De tokens zijn gedefinieerd als CSS custom properties en beschikbaar als Tailwind utilities:

| Token | Gebruik |
|---|---|
| `primary` | Primaire merkkleur — knoppen, accenten |
| `on-primary` | Tekst/icoon op een `primary` achtergrond |
| `secondary` | Secundaire merkkleur |
| `on-secondary` | Tekst/icoon op een `secondary` achtergrond |
| `surface` | Standaard pagina-/kaartachtergrond |
| `on-surface` | Primaire tekst op een `surface` achtergrond |
| `on-surface-variant` | Secundaire/subtiele tekst op een `surface` achtergrond |
| `surface-container` | Verhoogde container bovenop `surface` |

Gebruik in Blade altijd de token-naam als Tailwind utility:

```blade
{{-- GOED --}}
<div class="bg-surface text-on-surface">
    <button class="bg-primary text-on-primary">Verstuur</button>
</div>

{{-- FOUT — gebruik geen hardcoded kleuren --}}
<div class="bg-white text-zinc-800">
    <button class="bg-blue-600 text-white">Verstuur</button>
</div>
```

Voor ACF select-velden met achtergrondkeuzes: gebruik de token-klasse als choice-waarde (bijv. `bg-surface`, `bg-primary`).

## Tailwind en dynamische waarden

Tailwind scant **statisch** op class-namen — dynamisch gebouwde classes via `{{ }}` werken niet:

```blade
{{-- FOUT — werkt niet --}}
<div class="bg-{{ $color }}-500">

{{-- GOED optie 1: sla de volledige class op als ACF select-waarde --}}
<div class="{{ get_sub_field('background') ?: 'bg-white' }}">

{{-- GOED optie 2: voor vrije waarden (kleurpicker, pixel-invoer) → inline style --}}
<div style="background-color: {{ get_sub_field('custom_color') }};">
```

Bij ACF select-velden voor stijlopties: gebruik de Tailwind class zelf als choice-waarde.

## Flex content blokken

Flex content layouts krijgen een tab `Instellingen` **alleen als er ook daadwerkelijk instellingen zijn** (bijv. achtergrondkleur, padding). Heb je alleen content fields, dan geen tabs nodig.

ACF field group klassen staan in `app/Acf/` en worden gegenereerd met:
```bash
wp acorn make:acf
```

De klasse extend `OutlawzTeam\Radicle\Support\Acf`:

```php
namespace App\Acf;

use OutlawzTeam\Radicle\Support\Acf;

class PageContent extends Acf
{
    protected $key = 'group_page_content';
    protected $title = 'Page Content';

    public function fields()
    {
        return [
            [
                'key' => 'field_sections',
                'label' => 'Sections',
                'name' => 'sections',
                'type' => 'flexible_content',
                'layouts' => [
                    'hero_banner' => [
                        'key' => 'layout_hero_banner',
                        'label' => 'Hero Banner',
                        'sub_fields' => [
                            // Tab: Content
                            ['key' => 'field_hb_tab_content', 'label' => 'Content', 'name' => '', 'type' => 'tab'],
                            ['key' => 'field_hb_heading', 'label' => 'Heading', 'name' => 'heading', 'type' => 'text'],

                            // Tab: Instellingen
                            ['key' => 'field_hb_tab_settings', 'label' => 'Instellingen', 'name' => '', 'type' => 'tab'],
                            [
                                'key' => 'field_hb_background',
                                'label' => 'Background',
                                'name' => 'background',
                                'type' => 'select',
                                'choices' => [
                                    'bg-white' => 'White',
                                    'bg-black text-white' => 'Black',
                                    'bg-gray-100' => 'Gray',
                                ],
                                'default_value' => 'bg-white',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function location()
    {
        return [
            [['param' => 'post_type', 'operator' => '==', 'value' => 'page']],
        ];
    }

    public function options()
    {
        return [];
    }
}
```

Flex content renderen in Blade:

```blade
@if (have_rows('sections'))
    @while (have_rows('sections'))
        @php the_row(); @endphp

        @if (get_row_layout() === 'hero_banner')
            @include('blocks.hero-banner')
        @endif
    @endwhile
@endif
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
