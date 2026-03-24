Maak een nieuw Custom Post Type aan voor dit Radicle WordPress project.

Volg deze stappen:

1. **Vraag de naam** van het CPT (enkelvoud, Engels, lowercase — bijv. `project`, `event`, `team-member`)

2. **Vraag de labels** in het Nederlands:
   - Enkelvoud (bijv. `Project`)
   - Meervoud (bijv. `Projecten`)

3. **Vraag de slug** voor de URL (bijv. `projecten`, `evenementen`)

4. **Vraag of er taxonomieën nodig zijn** (bijv. categorie, tag, of custom taxonomy)

5. **Vraag of er een ACF field group bij moet** (en zo ja, volg dan ook het `/new-flex-block` patroon voor de fields)

---

Genereer daarna de volgende bestanden:

### Stap A — `config/post-types.php`

Voeg toe aan `post_types`:

```php
'project' => [
    [
        'public' => true,
        'has_archive' => true,
        'supports' => ['title', 'editor', 'thumbnail'],
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-portfolio',
    ],
    [
        'singular' => 'Project',
        'plural'   => 'Projecten',
        'slug'     => 'projecten',
    ]
],
```

Voor een custom taxonomy, voeg toe aan `taxonomies`:

```php
'project_category' => [
    ['project'],
    [
        'singular' => 'Project categorie',
        'plural'   => 'Project categorieën',
        'slug'     => 'project-categorie',
    ]
],
```

### Stap B — Model (`app/Models/Project.php`)

```php
<?php

namespace App\Models;

class Project extends Post
{
    /**
     * The post type for this model.
     */
    public static string $postType = 'project';
}
```

### Stap C — Blade views

**`resources/views/single-project.blade.php`**:
```blade
@extends('layouts.app')

@section('content')
    @while (have_posts())
        @php the_post(); @endphp

        <article @php post_class('container mx-auto py-12') @endphp>
            <h1 class="text-4xl font-bold mb-6">{{ get_the_title() }}</h1>

            @if (has_post_thumbnail())
                <figure class="mb-8">
                    {!! get_the_post_thumbnail(null, 'large', ['class' => 'w-full object-cover']) !!}
                </figure>
            @endif

            <div class="prose max-w-none">
                {!! get_the_content() !!}
            </div>
        </article>
    @endwhile
@endsection
```

**`resources/views/archive-project.blade.php`**:
```blade
@extends('layouts.app')

@section('content')
    <section class="container mx-auto py-12">
        <h1 class="text-4xl font-bold mb-8">{{ post_type_archive_title('', false) }}</h1>

        @if (have_posts())
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @while (have_posts())
                    @php the_post(); @endphp
                    <article @php post_class('border border-black') @endphp>
                        @if (has_post_thumbnail())
                            <a href="{{ get_permalink() }}">
                                {!! get_the_post_thumbnail(null, 'medium', ['class' => 'w-full object-cover aspect-video']) !!}
                            </a>
                        @endif
                        <div class="p-4">
                            <h2 class="text-xl font-bold mb-2">
                                <a href="{{ get_permalink() }}" class="hover:underline">{{ get_the_title() }}</a>
                            </h2>
                        </div>
                    </article>
                @endwhile
            </div>
        @else
            <p>Geen projecten gevonden.</p>
        @endif
    </section>
@endsection
```

### Stap D — View Composer (optioneel)

Alleen aanmaken als extra data-injectie nodig is. Maak `app/View/Composers/Project.php` en registreer in `app/Providers/ThemeServiceProvider.php` of een aparte provider.
