Maak een nieuw flex content blok aan voor dit Radicle WordPress project.

Volg deze stappen:

1. **Vraag de naam** van het blok (kebab-case, bijv. `hero-banner`, `text-image`)

2. **Vraag welke Content fields** het blok nodig heeft (tab: Content)

3. **Vraag of er Settings fields nodig zijn** (tab: Instellingen) — typisch: achtergrondkleur, padding, tekstkleur, etc. Alleen een Instellingen-tab aanmaken als er ook echt instellingen zijn.

4. **Genereer de ACF layout definitie** om toe te voegen aan de `layouts` array in de betreffende `app/Acf/` klasse:
   - Elke layout heeft altijd twee tabs: `Content` en `Instellingen`
   - Bij stijl-opties (achtergrond, kleur): gebruik de volledige Tailwind class als ACF select-waarde
   - Bij vrije waarden (kleurpicker, pixel-invoer): gebruik een text/color-picker field (wordt later als inline style gebruikt)
   - Gebruik unieke `key` waarden met een prefix gebaseerd op de layout-naam

5. **Genereer de Blade view** in `resources/views/blocks/[naam].blade.php`:
   - Gebruik inline assignment in `@if`: `@if ($title = get_sub_field('title'))`
   - Geen `@php` blokken bovenin de template
   - Tailwind classes die uit ACF komen, direct injecteren: `class="{{ get_sub_field('background') ?: 'bg-white' }}"`
   - Dynamische waarden als inline style: `style="background-color: {{ get_sub_field('color') }};"`

6. **Controleer** of de `@while (have_rows('sections'))` render-loop al aanwezig is in de pagina-template. Zo niet, voeg de juiste include toe.

---

## Voorbeeld output

### ACF layout definitie (toevoegen aan `layouts` in `app/Acf/PageContent.php`):

```php
'hero_banner' => [
    'key' => 'layout_hero_banner',
    'label' => 'Hero Banner',
    'sub_fields' => [
        // Tab: Content
        ['key' => 'field_hb_tab_content', 'label' => 'Content', 'name' => '', 'type' => 'tab'],
        ['key' => 'field_hb_heading', 'label' => 'Heading', 'name' => 'heading', 'type' => 'text'],
        ['key' => 'field_hb_subheading', 'label' => 'Subheading', 'name' => 'subheading', 'type' => 'textarea'],
        [
            'key' => 'field_hb_image',
            'label' => 'Image',
            'name' => 'image',
            'type' => 'image',
            'return_format' => 'array',
        ],

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
        [
            'key' => 'field_hb_padding',
            'label' => 'Padding',
            'name' => 'padding',
            'type' => 'select',
            'choices' => [
                'py-8' => 'Small',
                'py-16' => 'Medium',
                'py-24' => 'Large',
            ],
            'default_value' => 'py-16',
        ],
    ],
],
```

### Blade view (`resources/views/blocks/hero-banner.blade.php`):

```blade
<section class="{{ get_sub_field('background') ?: 'bg-white' }} {{ get_sub_field('padding') ?: 'py-16' }} px-4">
    @if ($heading = get_sub_field('heading'))
        <h2 class="text-3xl font-bold mb-4">{{ $heading }}</h2>
    @endif

    @if ($subheading = get_sub_field('subheading'))
        <p class="text-lg mb-6">{{ $subheading }}</p>
    @endif

    @if ($image = get_sub_field('image'))
        <img src="{{ $image['url'] }}" alt="{{ $image['alt'] }}" class="w-full object-cover">
    @endif
</section>
```

### Render loop in pagina-template (bijv. `resources/views/page.blade.php`):

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
