---
name: figma-to-blade
description: Volledige workflow om een Figma-design om te zetten naar een geverifieerd Blade-blok in dit Radicle WordPress project. Haalt context op via Figma MCP, genereert een Blade-template volgens de project-conventies (Tailwind v4 @theme tokens, ACF flex blocks, __() vertaling, currentColor SVG's), en verifieert het resultaat via Playwright MCP op de lokale dev-URL (uit `.env` WP_HOME) met zowel screenshot-vergelijking als computed-style checks. Gebruik deze skill wanneer de gebruiker een Figma-URL aanlevert en die wil omzetten naar een Blade-blok in dit project. Activeer altijd samen met de skills `figma-tailwind-html`, `tailwind-v4` en `acf`.
---

# Figma → Blade workflow

Deze skill orchestreert het omzetten van een Figma-design naar een Blade-blok in dit Radicle WordPress project, en verifieert het resultaat geautomatiseerd. Doel: pixel-perfect Blade-output die binnen tolerance valt vergeleken met het origineel.

## Vereiste skills (altijd parallel laden)

Bij activatie van deze skill **moet** je ook deze skills lezen:

- **`figma-tailwind-html`** — de mapping van Figma-properties (auto-layout, sizing, kleuren, text-tokens, iconen, image-handling) naar Tailwind v4 klassen. Dit is je primaire referentie voor de class-output.
- **`tailwind-v4`** — `@theme`-conventies, 4px-schaal, decimale multipliers, `size-*`, opacity-modifiers `/N`, geen `oklch`, geen `@apply` in templates.
- **`acf`** — ACF field group conventies en `wp acorn make:acf` workflow.

De project-conventies uit [`AGENTS.md`](AGENTS.md) / [`CLAUDE.md`](CLAUDE.md) gelden bovenop deze skills: kebab-case bestandsnamen, PascalCase klassen, `camelCase` PHP-variabelen, `declare(strict_types=1);`, `__()` voor alle gebruikersteksten, `fill="currentColor"` op SVG's, geen `@apply` in templates, na elke PHP-wijziging `./vendor/bin/pint`.

---

## Vaste tooling

- **Figma MCP** (`plugin-figma-figma`):
  - `get_design_context` — React+Tailwind referentie + tokens + screenshot van de node
  - `get_screenshot` — losse PNG-export van de node
  - `get_metadata` — node-tree metadata (gebruik voor exacte `absoluteBoundingBox`, `paddingLeft/Right/Top/Bottom`, `style.fontSize`, `fills`)
- **Playwright MCP** (server `playwright`, geconfigureerd in [`.mcp.json`](.mcp.json), uit `@playwright/mcp@latest` in `--headless` mode):
  - `browser_navigate` — naar een URL
  - `browser_snapshot` — accessibility snapshot van de pagina (ref-IDs voor elementen)
  - `browser_take_screenshot` — screenshot van de hele pagina of één element (via ref / selector)
  - `browser_evaluate` — JavaScript uitvoeren (incl. `getComputedStyle`)
  - `browser_resize` — viewport-formaat zetten (gebruik vóór screenshots, bv. 1440×900 desktop)
  - `browser_close` — sessie sluiten aan het einde
- **Fallback** als de MCP server niet beschikbaar is: de `playwright-cli` skill (`playwright-cli open`, `playwright-cli goto`, `playwright-cli screenshot`, `playwright-cli eval`). De stappen blijven hetzelfde.

### Dev-URL bepalen

De basis-URL voor de Playwright verificatie wordt **altijd dynamisch gelezen** uit [`.env`](.env) — variabele `WP_HOME`. Hardcode nooit een hostname.

```bash
# Lees de dev-URL uit .env (strip quotes als die erom heen staan)
WP_HOME=$(grep -E "^WP_HOME=" .env | sed -E "s/^WP_HOME=//; s/^['\"]//; s/['\"]$//")
echo "$WP_HOME"
```

Bewaar de uitkomst als `$wpHome` voor alle Playwright-stappen. Vraag de gebruiker om correctie als de regel ontbreekt of leeg is.

Zorg daarnaast dat de Vite dev-server (`npm run dev`) draait, anders worden CSS-wijzigingen aan `theme.css` niet opgepikt tijdens iteratie.

---

## Werkfolder voor referenties

Sla alle Figma-screenshots en context-dumps op in [`storage/figma-references/`](storage/figma-references/) (gitignored). Bestandsnaamconventie per blok:

- `{block-name}-figma.png` — screenshot uit Figma MCP (bron-of-truth)
- `{block-name}-context.json` — output van `get_design_context` / `get_metadata` (tokens, dimensies, kleuren)
- `{block-name}-rendered-{iteration}.png` — Playwright screenshot per iteratie (`-1.png`, `-2.png`, `-3.png`)

---

## Stap 1 — Figma context ophalen

1. Parse de Figma-URL: `figma.com/design/{fileKey}/{name}?node-id={nodeIdWithDash}` → vervang `-` door `:` in nodeId.
2. Roep `get_design_context` aan met `fileKey` + `nodeId`. Bewaar:
   - Reference code (React+Tailwind) — **niet** letterlijk overnemen, alleen als inzicht
   - Design tokens (kleuren als CSS-variabelen, font-stacks)
   - Hints (Code Connect-snippets, component-docs, design-annotaties)
   - **Asset URLs** voor image-fills (de `imgFrame35 = "https://www.figma.com/api/mcp/asset/..."` constants). Sla iedere URL op tegen de naam van het Figma-element zodat je later in Stap 4 weet welke node welke `<img>` wordt. Deze URLs zijn 7 dagen geldig — download ze in Stap 4 direct naar `resources/images/` (zie "Image-handling").
3. Roep `get_screenshot` aan en sla op als `storage/figma-references/{block-name}-figma.png`.
4. Roep `get_metadata` aan en sla de relevante velden op als `storage/figma-references/{block-name}-context.json`. Verzamel:
   - Root `absoluteBoundingBox` (totale breedte / hoogte → bepalend voor `max-w-*`)
   - Per child: `layoutMode`, `itemSpacing`, `padding*`, `primaryAxisAlignItems`, `counterAxisAlignItems`, `layoutSizing*`
   - Alle unieke `fills[0].color` (hex via `r*255, g*255, b*255` afronding)
   - Alle unieke `(fontSize, lineHeightPx, fontWeight)` triples
   - Alle unieke `fontFamily`

Als de Figma MCP server een fout teruggeeft over authenticatie, stop en vraag de gebruiker om de plugin opnieuw te koppelen — ga niet verder.

---

## Stap 2 — Intake bij de gebruiker

Stel via **één** `AskQuestion`-bundel deze vragen:

1. **Naam** van het blok in kebab-case (bv. `hero-banner`, `text-image-grid`).
2. **Type**:
   - `flex_block` — een ACF flex content layout in `app/Acf/PageContent.php` (wordt geselecteerd in een pagina)
   - `section_include` — een los Blade-bestand in `resources/views/blocks/` dat met `@include('blocks.{name}')` in een template komt
   - `component` — een Blade component (`resources/views/components/{name}.blade.php`, gebruikt als `<x-{name}>`)
3. **Preview-pagina**: welk pad onder `$wpHome` bevat (of gaat bevatten) dit blok voor de verificatiestap (bijv. `/`, `/about`, `/blog/eerste-post`)? Default: `/` (homepage). Bouw de volledige URL pas vlak voor `browser_navigate` als `${wpHome}${path}`.

Bewaar deze keuzes als `$blockName`, `$blockType`, `$previewUrl` voor de rest van de workflow.

---

## Stap 3 — Tokens synchroniseren met `@theme`

Tokens leven in [`resources/css/common/theme.css`](resources/css/common/theme.css) (geïmporteerd door [`resources/css/app.css`](resources/css/app.css)).

### Kleuren

Voor elke unieke hex uit Stap 1:

1. Open [`resources/css/common/theme.css`](resources/css/common/theme.css) en check of het al bestaat onder een semantische naam.
2. Zo niet → voeg toe binnen het bestaande `@theme {}` blok met semantische naam (zie `figma-tailwind-html` skill voor naamgeving: `--color-primary`, `--color-on-primary`, `--color-surface`, `--color-on-surface`, `--color-surface-container`, `--color-muted`, etc.).
3. Hergebruik tokens. Geen dubbele hex onder twee namen.
4. **Geen** opacity-tokens — gebruik `/N` modifier op het base-token (`text-on-surface/60`).

### Typografie

Voor elke unieke `(fontSize, lineHeightPx, fontWeight)` triple:

```css
--text-{size}-{lh}-{weight}: {size}px;
--text-{size}-{lh}-{weight}--line-height: {lh}px;
--text-{size}-{lh}-{weight}--font-weight: {weight};
```

`lineHeightPx` met decimalen (bv. `17.5`) → afronden naar integer in de naam (`text-14-17-400`), exacte waarde behouden in `--line-height`.

### Fontfamilies

- Check `fontFamily` waardes tegen [`resources/css/common/fonts.css`](resources/css/common/fonts.css) en de bestaande `--font-sans` declaratie.
- Nieuwe familie nodig? Voeg `--font-display: 'Naam', ui-sans-serif, sans-serif;` toe en update de Google Fonts `@import` in `fonts.css` met de juiste weights.

### Niet-standaard tokens

Decoratieve blur-blobs (`--blur-blob`), schaduwen die je hergebruikt (`--shadow-card`), aspect-ratios of breakpoints: definieer ze in `@theme` zoals de `tailwind-v4` skill voorschrijft.

---

## Stap 4 — Blade-blok genereren

Volg de **`figma-tailwind-html`** mapping voor de class-output, maar de **HTML-structuur** moet voldoen aan de project-conventies hieronder.

### Algemene regels (uit `AGENTS.md`)

- `.blade.php` extensie
- Inline assignment in `@if`: `@if ($heading = get_sub_field('heading'))`
- **Geen** `@php`-blokken bovenin — logica hoort in een View Composer of inline `@php` direct bij de plek waar het nodig is
- Alle gebruikersteksten via `__('Tekst', 'radicle')`
- Output altijd escapen met `{{ }}`; alleen `{!! !!}` voor bewuste HTML-output (bijv. `the_content`)
- SVG's: `fill="currentColor"` of `stroke="currentColor"`, kleur via Tailwind text-klasse op parent
- Heroicons via `<x-heroicon-o-{naam}>` / `<x-heroicon-s-{naam}>` voor iconen die in de Heroicons-set zitten
- Geen hardcoded `<svg>` voor iconen die als Heroicon beschikbaar zijn
- Semantisch correcte HTML (`<section>`, `<article>`, `<h2>`, `<a>`, `<button>`, `<nav>`, `<ul>`, `<li>`), ARIA waar nodig (`aria-label`, `aria-expanded`)
- Alpine.js voor interactiviteit, altijd `x-cloak` bij `x-show`
- **Geen** standaard Tailwind kleuren (`bg-blue-500`, `text-gray-700`) — uitsluitend project-tokens

### Project-data-attribuut voor verificatie

De buitenste `<section>` (of `<div>` als section niet past) krijgt **altijd**:

```blade
<section data-block="{block-name}" class="...">
```

Dit attribuut gebruikt Playwright als selector. Gebruik dezelfde kebab-case als `$blockName`.

### Type-specifieke output

#### `flex_block`

1. **Blade view** in `resources/views/blocks/{block-name}.blade.php`. Velden via `get_sub_field()`.
2. **ACF layout** toevoegen aan de `layouts` array in `app/Acf/PageContent.php` (zie Stap 4b voor bootstrap als die klasse er nog niet is).
3. **Render loop** in de pagina-template moet het layout uitsluiten — zie Stap 4b.

Voorbeeld (zie `.claude/commands/new-flex-block.md` voor de volledige sjabloon):

```blade
<section data-block="hero-banner" class="{{ get_sub_field('background') ?: 'bg-surface' }} {{ get_sub_field('padding') ?: 'py-16' }}">
    <div class="mx-auto max-w-304 px-4">
        @if ($heading = get_sub_field('heading'))
            <h2 class="text-40-40-700 font-display mb-6">{{ $heading }}</h2>
        @endif

        @if ($subheading = get_sub_field('subheading'))
            <p class="text-16-24-400 text-on-surface/80">{{ $subheading }}</p>
        @endif
    </div>
</section>
```

#### `section_include`

1. **Blade view** in `resources/views/blocks/{block-name}.blade.php`. Data via variabelen (geen `get_sub_field`).
2. Gebruik in een template met `@include('blocks.{block-name}', ['heading' => '...'])`.

#### `component`

1. **Blade component** in `resources/views/components/{block-name}.blade.php` met `@props([...])`.
2. Te gebruiken als `<x-{block-name} :heading="$heading" />`.

```blade
@props(['heading' => null, 'subheading' => null])

<section data-block="hero-banner" {{ $attributes->merge(['class' => 'bg-surface py-16']) }}>
    ...
</section>
```

### Image-handling

**Figma-images worden altijd lokaal opgeslagen** in [`resources/images/`](resources/images/) en via `Vite::asset()` geladen — nooit Unsplash, nooit direct linken naar Figma asset URLs (die vervallen na 7 dagen).

#### Workflow per image-fill

Bij elke node met `fills.type: IMAGE` in de Figma-tree:

1. **Asset URL pakken** uit de `get_design_context` response. De server retourneert URLs zoals `imgFrame35 = "https://www.figma.com/api/mcp/asset/{uuid}"`. Verzamel ze samen met het natuurlijke `name` of `data-node-id` zodat je ze terug kunt mappen naar het juiste `<img>`.

2. **Bestandsnaam** in kebab-case onder `{block-name}-{omschrijving}.jpg`. Voorbeelden: `hero-banner-hero.jpg`, `intro-cta-hero.jpg`, `team-grid-avatar-1.jpg`.

3. **Downloaden + comprimeren** in één Bash-stap. JPG is altijd de output (foto's), tenzij de bron alpha/transparantie heeft (logo's, illustraties met transparante achtergrond) — die blijven PNG. Resize naar 2× de weergavebreedte (retina), quality 80:

   ```bash
   # foto's → JPG (resize, q=80)
   curl -s -o /tmp/figma-asset.png "https://www.figma.com/api/mcp/asset/{uuid}"
   sips -s format jpeg -s formatOptions 80 -Z {2x weergavebreedte} /tmp/figma-asset.png \
     --out resources/images/{block-name}-{omschrijving}.jpg >/dev/null

   # transparante PNG → PNG behouden, alleen resizen
   sips -Z {2x weergavebreedte} /tmp/figma-asset.png \
     --out resources/images/{block-name}-{omschrijving}.png >/dev/null
   ```

   `-Z N` schaalt zodat de langste zijde max `N` is, aspect ratio blijft behouden. Verwijder de tmp-file na conversie.

4. **In Blade verwijzen** via `Vite::asset()`:

   ```blade
   <img class="absolute inset-0 size-full object-cover"
        src="{{ Vite::asset('resources/images/intro-cta-hero.jpg') }}" alt="" />
   ```

   In dev wordt het bestand via de Vite-dev-server geserveerd (`http://[::1]:5173/build/resources/images/...`), in productie wordt het via `npm run build` naar `public/build/assets/{naam}-{hash}.{ext}` gehashed.

#### Alt-tekst

- `alt=""` voor decoratieve sfeerbeelden (foto in een hero-overlay, achtergrondfoto's).
- Beschrijvende `alt` alleen als de afbeelding informatieve waarde heeft (productfoto, persoonsportret naast quote).

#### ACF image-velden

Voor afbeeldingen die de redacteur moet kunnen wisselen: gebruik een ACF `image`-veld met `return_format = array`. Het lokale bestand wordt dan de **fallback** of vervalt helemaal:

```blade
@php $image = get_sub_field('image'); @endphp
<img src="{{ $image['url'] }}" alt="{{ $image['alt'] }}" class="..." />
```

Bij `flex_block` met optionele image: laat de Figma-export staan als hardcoded fallback, of vraag de gebruiker of de image altijd via ACF gevuld wordt.

### Na elke PHP-wijziging

Run direct `./vendor/bin/pint` om de code style te fixen.

---

## Stap 4b — Bootstrap-stappen voor flex blocks (eenmalig)

Voer alleen uit als `$blockType === 'flex_block'` **en** er nog geen flex-systeem is. Detectie:

```bash
# Bestaat de ACF klasse?
ls app/Acf/PageContent.php

# Heeft page.blade.php al een render-loop?
grep -l "have_rows('sections')" resources/views/page.blade.php
```

### Bootstrap A — `PageContent` ACF klasse genereren

Als `app/Acf/PageContent.php` ontbreekt:

```bash
wp acorn make:acf PageContent
```

Daarna `app/Acf/PageContent.php` aanvullen:

```php
<?php

declare(strict_types=1);

namespace App\Acf;

use Log1x\AcfComposer\Builder;
use Log1x\AcfComposer\Field;

class PageContent extends Field
{
    public function fields(): array
    {
        $fields = Builder::make('page_content');

        $fields
            ->setLocation('post_type', '==', 'page');

        $fields
            ->addFlexibleContent('sections', [
                'label' => 'Secties',
                'button_label' => 'Sectie toevoegen',
            ])
            // Layouts worden hier toegevoegd via fields-array
            ;

        return $fields->build();
    }
}
```

> **Let op**: in dit project gebruikt `outlawz-team/radicle` mogelijk de "raw array"-stijl (zoals in `CLAUDE.md` getoond) in plaats van de Builder. Check eerst hoe andere ACF-klassen (als ze er zijn) geschreven zijn en volg die stijl. Bij twijfel — gebruik de raw array zoals het voorbeeld in [`.claude/commands/new-flex-block.md`](.claude/commands/new-flex-block.md).

### Bootstrap B — Render-loop in `page.blade.php`

Huidige [`resources/views/page.blade.php`](resources/views/page.blade.php) is leeg. Uitbreiden tot:

```blade
@extends('layouts.app')

@section('content')
    @while (have_posts())
        @php the_post(); @endphp

        @if (have_rows('sections'))
            @while (have_rows('sections'))
                @php the_row(); @endphp

                @includeIf('blocks.' . str_replace('_', '-', get_row_layout()))
            @endwhile
        @else
            <article @php post_class('container mx-auto py-12') @endphp>
                {!! get_the_content() !!}
            </article>
        @endif
    @endwhile
@endsection
```

`str_replace('_', '-', ...)` mapt ACF layout-namen (`hero_banner`) naar kebab-case Blade-paden (`blocks.hero-banner`).

---

## Stap 5 — Test-content seeden

Het blok moet daadwerkelijk renderen op `$previewUrl` met representatieve inhoud uit de Figma. Drie opties — vraag de gebruiker welke:

1. **Handmatig via WP admin** — gebruiker vult de ACF-velden zelf in op de gekozen pagina. Snelst als de pagina al bestaat.
2. **Via WP-CLI** (alleen `flex_block`):
   ```bash
   wp post meta update {pageId} sections '...' --format=json
   ```
   Vereist dat je de exacte ACF flexible-content array opbouwt. Documenteer in de skill-output welk commando je gebruikt hebt zodat de gebruiker het kan herhalen.
3. **Hardcoded preview** — voor `section_include` of `component`: maak een tijdelijke include in `front-page.blade.php` met statische demo-data. Verwijder die in Stap 8.

---

## Stap 6 — Playwright verificatie

### 6.1 — Dev-server check

```bash
# Draait de Vite dev-server?
ps aux | grep "vite" | grep -v grep
```

Als die niet draait → vraag de gebruiker `npm run dev` te starten in een aparte terminal (CSS-wijzigingen aan `theme.css` zijn anders niet zichtbaar).

### 6.2 — Browser openen en navigeren

Met Playwright MCP (server `playwright`):

```
browser_resize → width: 1440, height: 900
browser_navigate → url: ${wpHome}${preview-path}    # wpHome komt uit .env (zie sectie "Dev-URL bepalen")
browser_snapshot → controleer dat het blok aanwezig is in de DOM (zoek naar data-block="{name}")
```

### 6.3 — Screenshot van het blok

```
browser_take_screenshot →
  - element: het <section data-block="{name}"> via CSS-selector `section[data-block="{block-name}"]`
  - filename: storage/figma-references/{block-name}-rendered-{iteration}.png
  - fullPage: false
```

### 6.4 — Computed style snapshot

```
browser_evaluate →
  function: () => {
    const el = document.querySelector('section[data-block="{block-name}"]');
    if (!el) return { error: 'block-not-found' };
    const rect = el.getBoundingClientRect();
    const cs = getComputedStyle(el);
    return {
      box: { width: rect.width, height: rect.height },
      padding: { top: cs.paddingTop, right: cs.paddingRight, bottom: cs.paddingBottom, left: cs.paddingLeft },
      background: cs.backgroundColor,
      color: cs.color,
      // Heading metrics
      heading: (() => {
        const h = el.querySelector('h1, h2, h3');
        if (!h) return null;
        const hcs = getComputedStyle(h);
        return { fontSize: hcs.fontSize, lineHeight: hcs.lineHeight, fontWeight: hcs.fontWeight, color: hcs.color, fontFamily: hcs.fontFamily };
      })(),
    };
  }
```

Bewaar het resultaat in `storage/figma-references/{block-name}-computed-{iteration}.json`.

### 6.5 — Vergelijken

**Visueel** (screenshots):

- Open `storage/figma-references/{block-name}-figma.png` en `storage/figma-references/{block-name}-rendered-{iteration}.png` met de Read tool (beide zijn images).
- Beoordeel: layout-volgorde, proporties, kleuren, typografie, spacing tussen elementen, alignment, decoratieve elementen (blobs, schaduwen, randen).

**Hard** (computed styles tegen Figma JSON):

| Metric | Figma-bron | Rendered-bron | Tolerance |
|--------|------------|---------------|-----------|
| `box.width` | root `absoluteBoundingBox.width` | `getBoundingClientRect().width` | ≤ 1px |
| `padding.*` | `paddingLeft/Right/Top/Bottom` | `getComputedStyle().padding*` | ≤ 1px |
| `background` | `fills[0].color` hex | `rgb(...)` → hex | exacte match |
| `heading.fontSize` | `style.fontSize` | `getComputedStyle().fontSize` | exact |
| `heading.lineHeight` | `style.lineHeightPx` | `getComputedStyle().lineHeight` | ≤ 0.5px |
| `heading.fontWeight` | `style.fontWeight` | `getComputedStyle().fontWeight` | exact |
| `heading.color` | tekst `fills[0].color` hex | `rgb(...)` → hex | exacte match |

Helper voor `rgb(...) → hex`-vergelijking:

```javascript
const rgbToHex = (rgb) => '#' + rgb.match(/\d+/g).slice(0, 3).map(n => parseInt(n).toString(16).padStart(2, '0')).join('');
```

---

## Stap 7 — Iteratieloop

Als één of meer metrics buiten tolerance vallen, of de visuele vergelijking laat duidelijke afwijkingen zien:

1. Identificeer **per element** wat verschilt (welke node in de Figma, welk element in de Blade).
2. Mogelijke oorzaken (in volgorde van waarschijnlijkheid):
   - Verkeerde Tailwind-klasse (`gap-4` ipv `gap-6`, `px-4` ipv `px-6`)
   - Ontbrekend of verkeerd benoemd `@theme`-token
   - Verkeerde tekst-token (verkeerde size/lineHeight/weight combinatie)
   - Verkeerde semantische HTML (heading-level dat default styling oppakt)
   - Missende `font-display` of `font-sans` klasse
   - Ontbrekende `mx-auto max-w-N` op de inner-wrapper
   - Pagina-styling die het blok overschrijft (zie `front-page.blade.php` / `layouts/app.blade.php`)
3. Pas de Blade aan (en/of `theme.css` als een token ontbreekt of fout staat).
4. Run `./vendor/bin/pint` bij PHP-wijzigingen.
5. Wacht 1–2 seconden zodat Vite HMR de CSS opnieuw bouwt.
6. Herhaal Stap 6 (incrementeer `iteration`).

**Stopcondities:**

- Alle harde metrics binnen tolerance + geen duidelijke visuele afwijkingen → **klaar**, ga naar Stap 8.
- 3 iteraties bereikt zonder convergentie → **stop**, ga naar Stap 8 en rapporteer expliciet welke metrics nog afwijken en wat je hebt geprobeerd.

---

## Stap 8 — Cleanup & rapport

### Cleanup

- Sluit de Playwright sessie: `browser_close`.
- Verwijder tijdelijke includes/preview-content die je in Stap 5 hebt toegevoegd.
- Behoud de screenshots en context-JSONs in `storage/figma-references/` (handig voor volgende sessies, en gitignored).

### Rapport aan de gebruiker

Toon een beknopte samenvatting:

- **Blade-bestand**: pad (klikbare markdown-link)
- **ACF-key** (alleen bij `flex_block`): bv. `layout_hero_banner`
- **Nieuwe `@theme` tokens**: lijst kleur- en text-tokens die zijn toegevoegd aan `theme.css`
- **Metrics**: tabel met Figma-waarde, Rendered-waarde, status (✓ / ✗) per gemeten metric
- **Iteraties**: hoeveel iteraties nodig waren
- **Openstaande punten**: als er ondanks 3 iteraties afwijkingen bleven, expliciet benoemen wat en waarom

### Geen automatische commit

Commit niet zelf — de gebruiker reviewt eerst en commit handmatig (volgens project-regels uit `AGENTS.md`).

---

## Veelgemaakte fouten

1. **`<svg>` letterlijk uit Figma kopiëren** — gebruik Heroicons of canonieke material-symbols paden (zie `figma-tailwind-html`).
2. **Arbitraire `[20px]` of `[#fff]` waarden** — altijd via `@theme` tokens of decimale schaal.
3. **`bg-blue-500` / `text-gray-700`** — verboden in dit project, altijd custom tokens.
4. **`@apply` in Blade-template `<style>` blok** — `@apply` mag alleen in `.css`-bestanden en uitsluitend voor plugin-overrides (zie `AGENTS.md`).
5. **Vergeten `data-block="{name}"`** — Playwright kan dan het element niet vinden in Stap 6.
6. **Vite dev-server niet draaiend** — nieuwe `@theme` tokens worden niet als utility-klassen beschikbaar; uitsluitend rebuild fixt dat.
7. **`get_sub_field()` buiten een `have_rows()` loop** — werkt alleen binnen de flexible-content render-loop. Bij `section_include` / `component` gebruik je gewone variabelen.
8. **Hardcoded `<header>`/`<footer>`/`<main>`** in een blok — een blok is een sectie, niet een page-layout. Begin op `<section>` of `<div>`.
9. **Computed style in oklch/rgb vergelijken met hex** — zet altijd om naar hex met de helper voordat je vergelijkt.
10. **Iteratie doorgaan zonder Vite-rebuild af te wachten** — wacht een paar honderd ms na CSS-wijzigingen.

---

## Snelle checklist (voor de agent)

- [ ] Figma URL geparseerd, `get_design_context` + `get_screenshot` + `get_metadata` opgehaald
- [ ] `{block-name}-figma.png` en `{block-name}-context.json` opgeslagen
- [ ] Intake voltooid (`$blockName`, `$blockType`, `$previewUrl`)
- [ ] Kleuren, text-tokens, fontfamilies toegevoegd aan `resources/css/common/theme.css`
- [ ] Figma image-fills gedownload naar `resources/images/` (curl + `sips -Z` resize), in Blade via `Vite::asset()`
- [ ] Blade-bestand aangemaakt met `data-block="{name}"`, `__()`, `currentColor`, Heroicons
- [ ] (Indien `flex_block`) ACF layout toegevoegd, render-loop in `page.blade.php` aanwezig
- [ ] `./vendor/bin/pint` gedraaid na PHP-wijzigingen
- [ ] Test-content geseed op `$previewUrl`
- [ ] Vite dev-server bevestigd draaiend
- [ ] Playwright sessie geopend op 1440×900
- [ ] Screenshot + computed styles opgeslagen per iteratie
- [ ] Visuele + harde vergelijking uitgevoerd, max 3 iteraties
- [ ] Cleanup gedaan, rapport getoond aan de gebruiker
