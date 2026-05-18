---
name: acf
description: Kennis en werkwijze voor ACF field groups in het Outlawz Radicle project — aanmaken via wp acorn make:acf, structuur van fields, location en options.
---

# ACF Field Groups (Radicle)

ACF field groups worden aangemaakt via het Acorn commando en leven in `app/Acf/`. De basis class `OutlawzTeam\Radicle\Support\Acf` regelt automatisch het genereren van keys voor alle fields.

## Aanmaken

```bash
wp acorn make:acf NaamVanDeKlasse
```

Dit genereert `app/Acf/NaamVanDeKlasse.php` met namespace `App\Acf`.

---

## Basisstructuur

```php
<?php

namespace App\Acf;

use OutlawzTeam\Radicle\Support\Acf;

class PageContent extends Acf
{
    protected $key = 'page_content';
    protected $title = 'Page Content';

    public function fields()
    {
        return [];
    }

    public function location()
    {
        return [];
    }

    public function options()
    {
        return [];
    }
}
```

---

## Key en title

- `$key` — unieke identifier voor de field group, snake_case
- `$title` — weergavenaam in het WP admin

---

## Fields

Fields worden gedefinieerd zoals standaard ACF, maar **zonder `key`** — die wordt automatisch aangemaakt door de tool op basis van de group key en de `name`.

### Verplichte properties per field

| Property | Beschrijving |
|---|---|
| `label` | Nederlandse naam voor de redacteur |
| `name` | Engelse snake_case identifier (wordt gebruikt in Twig/PHP) |
| `type` | ACF field type |

### Voorbeeld — eenvoudige fields

```php
public function fields()
{
    return [
        [
            'label' => 'Titel',
            'name'  => 'title',
            'type'  => 'text',
        ],
        [
            'label' => 'Introductietekst',
            'name'  => 'intro',
            'type'  => 'wysiwyg',
        ],
        [
            'label' => 'Afbeelding',
            'name'  => 'image',
            'type'  => 'image',
        ],
        [
            'label' => 'Knop',
            'name'  => 'button',
            'type'  => 'link',
        ],
    ];
}
```

### Voorbeeld — tabs met sub_fields

```php
public function fields()
{
    return [
        [
            'label' => 'Content',
            'name'  => '',
            'type'  => 'tab',
        ],
        [
            'label' => 'Titel',
            'name'  => 'title',
            'type'  => 'text',
        ],
        [
            'label' => 'Instellingen',
            'name'  => '',
            'type'  => 'tab',
        ],
        [
            'label'         => 'Achtergrond',
            'name'          => 'background',
            'type'          => 'select',
            'choices'       => [
                'bg-white'          => 'Wit',
                'bg-black text-white' => 'Zwart',
                'bg-gray-100'       => 'Grijs',
            ],
            'default_value' => 'bg-white',
        ],
    ];
}
```

### Voorbeeld — flexible_content

```php
public function fields()
{
    return [
        [
            'label'   => 'Secties',
            'name'    => 'sections',
            'type'    => 'flexible_content',
            'layouts' => [
                'hero_banner' => [
                    'label'      => 'Hero Banner',
                    'sub_fields' => [
                        [
                            'label' => 'Content',
                            'name'  => '',
                            'type'  => 'tab',
                        ],
                        [
                            'label' => 'Heading',
                            'name'  => 'heading',
                            'type'  => 'text',
                        ],
                    ],
                ],
            ],
        ],
    ];
}
```

### Voorbeeld — repeater

```php
[
    'label'      => 'Items',
    'name'       => 'items',
    'type'       => 'repeater',
    'sub_fields' => [
        [
            'label' => 'Titel',
            'name'  => 'title',
            'type'  => 'text',
        ],
        [
            'label' => 'Omschrijving',
            'name'  => 'description',
            'type'  => 'wysiwyg',
        ],
    ],
],
```

### Conventies voor fields

- **Labels altijd Nederlands** — de redacteur leest dit
- **Names altijd Engels snake_case** — worden gebruikt in Twig/PHP templates
- **Gebruik zo veel mogelijk `flexible_content`** voor pagina-inhoud — geeft redacteuren maximale flexibiliteit
- Gebruik `wysiwyg` voor tekstgebieden, nooit `textarea`
- Gebruik `link` voor knoppen (bevat tekst + url + target in één field)
- Voeg `instructions` toe aan niet-voor-de-hand-liggende fields

---

## Location

Bepaalt voor welk post type of welke options page de field group geldt.

### Post type

```php
public function location()
{
    return [
        [
            ['param' => 'post_type', 'operator' => '==', 'value' => 'page'],
        ],
    ];
}
```

### Custom post type

```php
public function location()
{
    return [
        [
            ['param' => 'post_type', 'operator' => '==', 'value' => 'project'],
        ],
    ];
}
```

### Options page

```php
public function location()
{
    return [
        [
            ['param' => 'options_page', 'operator' => '==', 'value' => 'acf-options'],
        ],
    ];
}
```

### Meerdere post types (OR-logica — aparte arrays)

```php
public function location()
{
    return [
        [['param' => 'post_type', 'operator' => '==', 'value' => 'page']],
        [['param' => 'post_type', 'operator' => '==', 'value' => 'post']],
    ];
}
```

### AND-logica (meerdere rules in één array)

```php
public function location()
{
    return [
        [
            ['param' => 'post_type', 'operator' => '==', 'value' => 'page'],
            ['param' => 'page_template', 'operator' => '==', 'value' => 'template-home.blade.php'],
        ],
    ];
}
```

---

## Options

Altijd de normale WordPress content (editor) verbergen:

```php
public function options()
{
    return [
        'hide_on_screen' => ['the_content'],
    ];
}
```

### Beschikbare `hide_on_screen` waarden

| Waarde | Wat het verbergt |
|---|---|
| `the_content` | De standaard WordPress editor (Gutenberg/TinyMCE) |
| `excerpt` | Samenvatting veld |
| `discussion` | Reacties instelling |
| `comments` | Reacties sectie |
| `revisions` | Revisies |
| `slug` | Slug veld |
| `author` | Auteur veld |
| `format` | Berichtformaat |
| `page_attributes` | Pagina-attributen (volgorde, parent) |
| `featured_image` | Uitgelichte afbeelding |
| `categories` | Categorieën |
| `tags` | Tags |

### Andere handige opties

```php
public function options()
{
    return [
        'hide_on_screen'    => ['the_content'],
        'label_placement'   => 'top',   // 'top' of 'left'
        'instruction_placement' => 'label', // 'label' of 'field'
        'menu_order'        => 0,
    ];
}
```

---

## Volledig voorbeeld — page field group

```php
<?php

namespace App\Acf;

use OutlawzTeam\Radicle\Support\Acf;

class PageContent extends Acf
{
    protected $key = 'page_content';
    protected $title = 'Pagina content';

    public function fields()
    {
        return [
            [
                'label' => 'Content',
                'name'  => '',
                'type'  => 'tab',
            ],
            [
                'label'   => 'Secties',
                'name'    => 'sections',
                'type'    => 'flexible_content',
                'layouts' => [
                    'text_block' => [
                        'label'      => 'Tekstblok',
                        'sub_fields' => [
                            [
                                'label' => 'Titel',
                                'name'  => 'title',
                                'type'  => 'text',
                            ],
                            [
                                'label' => 'Tekst',
                                'name'  => 'content',
                                'type'  => 'wysiwyg',
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
            [
                ['param' => 'post_type', 'operator' => '==', 'value' => 'page'],
            ],
        ];
    }

    public function options()
    {
        return [
            'hide_on_screen' => ['the_content'],
        ];
    }
}
```

---

## Volledig voorbeeld — options page

```php
<?php

namespace App\Acf;

use OutlawzTeam\Radicle\Support\Acf;

class SiteSettings extends Acf
{
    protected $key = 'site_settings';
    protected $title = 'Site instellingen';

    public function fields()
    {
        return [
            [
                'label' => 'Algemeen',
                'name'  => '',
                'type'  => 'tab',
            ],
            [
                'label' => 'Telefoonnummer',
                'name'  => 'phone',
                'type'  => 'text',
            ],
            [
                'label' => 'E-mailadres',
                'name'  => 'email',
                'type'  => 'email',
            ],
        ];
    }

    public function location()
    {
        return [
            [
                ['param' => 'options_page', 'operator' => '==', 'value' => 'acf-options'],
            ],
        ];
    }

    public function options()
    {
        return [
            'hide_on_screen' => ['the_content'],
        ];
    }
}
```

---

## Wanneer deze skill toepassen

- Nieuwe ACF field group aanmaken → `wp acorn make:acf`
- Fields definiëren → geen `key` nodig, wordt auto-gegenereerd
- Location bepalen → post type of options page
- Options → altijd `the_content` verbergen
