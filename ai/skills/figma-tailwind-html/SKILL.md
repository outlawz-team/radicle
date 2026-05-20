---
name: figma-tailwind-html
description: Werkwijze voor het omzetten van een Figma JSON-dump (input.json met node-tree, fills, text styles, auto-layout) naar een standalone HTML-bestand met Tailwind v4 via CDN. Activeer deze skill samen met de tailwind-v4 skill bij elke Figma → HTML conversie.
---

# Figma → Tailwind HTML

Deze skill beschrijft hoe je een Figma JSON-node (de input zoals dat uit de Figma API komt, met `document`, `nodes`, `children`, `fills`, `style`, `layoutMode`, etc.) omzet naar een standalone HTML-bestand dat Tailwind v4 via CDN gebruikt.

Werk altijd in combinatie met de **`tailwind-v4`** skill. Die regelt de Tailwind-conventies (spacing-schaal, kleur-tokens, text-tokens, `size-*`, opacity-modifiers, geen `oklch`, etc.). Deze skill regelt specifiek de mapping van Figma-properties naar HTML + Tailwind-klassen.

---

## Vereiste output-structuur

Elke gegenereerde HTML volgt **letterlijk** dit template:

```html
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
<link
  href="https://fonts.googleapis.com/css2?family={Display}:wght@{weights}&family={Sans}:wght@{weights}&display=swap"
  rel="stylesheet"
/>

<style type="text/tailwindcss">
  @theme {
  	--font-display: '{Display}', ui-sans-serif, system-ui, sans-serif;
  	--font-sans: '{Sans}', ui-sans-serif, system-ui, sans-serif;

  	--color-primary: #...;
  	--color-on-primary: #...;
  	--color-surface: #...;
  	--color-on-surface: #...;
  	/* …meer kleur-tokens… */

  	--text-{size}-{lh}-{weight}: {size}px;
  	--text-{size}-{lh}-{weight}--line-height: {lh}px;
  	--text-{size}-{lh}-{weight}--font-weight: {weight};
  	/* …meer text-tokens… */
  }
</style>

<div class="bg-surface text-on-surface font-sans">
  <div class="max-w-... mx-auto ...">
    <!-- content -->
  </div>
</div>
```

**Verplicht:**

- Eerst `<script>` van `@tailwindcss/browser@4`, daarna `<link>` voor Google Fonts, daarna het `<style type="text/tailwindcss">` blok met `@theme`, daarna pas de content.
- De buitenste content-`<div>` zet de body-defaults: `bg-surface text-on-surface font-sans`.
- Content wordt gecentreerd met `mx-auto` + `max-w-...` zodra de Figma-root een vaste totaal-breedte heeft (bv. `absoluteBoundingBox.width: 1216` → `max-w-304`).
- Inspringen met tabs, niet spaties (zoals beide voorbeelden in dataset).

---

## Figma → Tailwind mapping-tabel

### Auto-layout

| Figma-property                         | Tailwind-klasse                                                                                |
| -------------------------------------- | ---------------------------------------------------------------------------------------------- |
| `layoutMode: HORIZONTAL`               | `flex`                                                                                         |
| `layoutMode: VERTICAL`                 | `flex flex-col`                                                                                |
| `itemSpacing: N` (px)                  | `gap-{N/4}` (4px-schaal, decimale multiplier indien nodig)                                     |
| `paddingLeft/Right: N`                 | `px-{N/4}`                                                                                     |
| `paddingTop/Bottom: N`                 | `py-{N/4}`                                                                                     |
| Alle 4 padding gelijk: N               | `p-{N/4}`                                                                                      |
| `primaryAxisAlignItems: CENTER`        | `justify-center`                                                                               |
| `primaryAxisAlignItems: SPACE_BETWEEN` | `justify-between`                                                                              |
| `counterAxisAlignItems: CENTER`        | `items-center`                                                                                 |
| `counterAxisAlignItems: BASELINE`      | `items-baseline`                                                                               |
| `layoutWrap: WRAP`                     | `flex-wrap`                                                                                    |
| `layoutPositioning: ABSOLUTE` (child)  | `absolute` + `top/left/right/bottom-{N}` (uit `absoluteBoundingBox`), parent krijgt `relative` |

Decoratieve blob-/gradient-shapes (vrijstaande cirkels met blur, vaak achter de content):

```html
<div class="relative overflow-hidden">
  <div
    class="bg-blob-pink blur-blob -right-45 -top-60.5 size-175 pointer-events-none absolute rounded-full"
  ></div>
  <div class="relative z-10">…content…</div>
</div>
```

- `pointer-events-none` op puur decoratieve absolute elementen.
- `overflow-hidden` op de section-/page-wrapper als de blob over de rand loopt.
- `blur-{token}` definieer je in `@theme` (zie tailwind-v4 skill, sectie "Niet-standaard tokens").

### Sizing

| Figma-property                               | Tailwind-klasse                                  |
| -------------------------------------------- | ------------------------------------------------ |
| `layoutSizingHorizontal: FIXED` + `width: N` | `w-{N/4}` (of `max-w-{N/4}` als beperking)       |
| `layoutSizingHorizontal: FILL`               | `w-full` (eventueel met `shrink-0` of `flex-1`)  |
| `layoutSizingHorizontal: HUG`                | geen breedte-klasse                              |
| `layoutSizingVertical: FIXED` + `height: N`  | `h-{N/4}`                                        |
| Vierkant frame (w == h)                      | `size-{N/4}`                                     |
| `cornerRadius: N` (klein)                    | `rounded-{token}` (`rounded-xl` voor 12px, etc.) |
| `cornerRadius` >= halve breedte              | `rounded-full`                                   |

Extra sizing-hulpmiddelen:

- **`max-w-full`** combineren met een vast `w-N` zodra het element op smallere containers niet mag overflowen: `<div class="w-162.5 max-w-full ...">`.
- **`min-w-0`** op een flex-kind dat lange tekst bevat — zonder `min-w-0` blijft de default `min-width: auto` staan waardoor de tekst de flex-container kan oprekken in plaats van te wrappen.
- **Sectie-spacing**: de buitenste flex-col gebruikt vaak een grote `gap-N` (bv. `gap-76` ≈ 304px) tussen secties. Lees dit uit `itemSpacing` op de root.

### Tekst

| Figma-property                                              | Tailwind / HTML                                                                                                        |
| ----------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------- |
| `style.fontSize: 40`, `lineHeightPx: 40`, `fontWeight: 700` | token `text-40-40-700`                                                                                                 |
| `style.fontFamily: 'Oxanium'` (display-font in design)      | `--font-display`, klasse `font-display`                                                                                |
| `style.fontFamily: 'Lexend'` (bodytekst)                    | `--font-sans`, klasse `font-sans`                                                                                      |
| `style.textCase: UPPER`                                     | klasse `uppercase`                                                                                                     |
| `style.textCase: LOWER`                                     | klasse `lowercase`                                                                                                     |
| `style.textAlignHorizontal: CENTER`                         | klasse `text-center` (combineer met `flex flex-col items-center` op de wrapper als ook het blok zelf gecentreerd moet) |
| `characters: "..."`                                         | tekstinhoud van het element                                                                                            |
| `characterStyleOverrides` + `styleOverrideTable`            | inline `<span>` met afwijkende kleur/grootte                                                                           |

### Kleur (fills)

| Figma-property                         | Tailwind                                                    |
| -------------------------------------- | ----------------------------------------------------------- |
| `fills[0].color: {r,g,b}` (0–1 floats) | hex `#rrggbb`, opnemen als token in `@theme`                |
| `fills[0].opacity: 0.6`                | `/60` opacity-modifier op het kleur-token                   |
| `node.opacity: 0.5`                    | `/50` opacity-modifier (of `opacity-50` als hele node fade) |
| `strokes[0].color`                     | `border-{token}` + `border`                                 |

### Effects

| Figma-effect                          | Tailwind                                                                   |
| ------------------------------------- | -------------------------------------------------------------------------- |
| `DROP_SHADOW` met offset/radius/color | `shadow-[X_Y_blur_spread_rgba(...)]` (arbitrair) of bestaande shadow-token |
| `INNER_SHADOW`                        | `inset shadow-[...]`                                                       |
| `LAYER_BLUR`                          | `blur-*` (`blur-sm`, `blur-md`, ...)                                       |

### Componenten / iconen

| Figma                                                                                  | HTML                                                                                                                                                   |
| -------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `INSTANCE` met naam `material-symbols:call-outline`, `mail-outline`, `arrow-...`, etc. | inline `<svg>` met het **bekende material-symbols path** (niet de Figma VECTOR-data)                                                                   |
| `INSTANCE` van een Button-component                                                    | `<a href="...">` of `<button type="button">` met `inline-flex h-{N/4} items-center justify-center px-{N/4} rounded-full`                               |
| `RECTANGLE` met `fills.type: IMAGE`                                                    | `<img src="{figma-asset-url}" alt="" class="object-cover ...">` met de URL die `get_design_context` als asset-constant teruggaf (zie "Image-handling") |
| Decoratieve `VECTOR` (uniek, geen icon-lib equivalent)                                 | letterlijk `<svg viewBox="..."><path d="..."/></svg>` overnemen                                                                                        |

---

## Kleurenextractie

1. **RGB → hex.** Figma geeft kleuren als `{r, g, b, a}` met floats 0–1. Reken om: `#${toHex(r*255)}${toHex(g*255)}${toHex(b*255)}`. Geen `oklch`, geen `rgba()` in `@theme`.
2. **Semantische naam.** Kies een naam op basis van de **rol** in het ontwerp, niet de kleurnaam:
   - Hoofd-accent (knoppen, links) → `--color-primary` + `--color-on-primary` (contrasterende tekstkleur).
   - Tweede accent → `--color-secondary` + `--color-on-secondary`.
   - Pagina-achtergrond → `--color-surface` + `--color-on-surface`.
   - Card / paneel-achtergrond → `--color-surface-container`.
   - Borders / dimming → `--color-muted` of `--color-neutral`.
   - Secundaire tekst (grijzig) → `--color-on-surface2` of `--color-neutral-700`.
3. **Opacity nooit als apart token.** Een tekst die in Figma op 60% opacity staat krijgt `text-on-surface/60`, niet een `--color-on-surface-60` token.
4. **Verwijderen van dubbele kleuren.** Als dezelfde hex op meerdere plekken voorkomt, gebruik één token. Hergebruik tokens over de hele HTML.

---

## Typografie-extractie

Voor élke unieke combinatie van `fontSize` + `lineHeightPx` + `fontWeight` in de Figma-tree definieer je één token in `@theme`:

```css
--text-{fontSize}-{lineHeightPx}-{fontWeight}: {fontSize}px;
--text-{fontSize}-{lineHeightPx}-{fontWeight}--line-height: {lineHeightPx}px;
--text-{fontSize}-{lineHeightPx}-{fontWeight}--font-weight: {fontWeight};
```

Voorbeelden uit dataset:

- `fontSize: 40, lineHeightPx: 40, fontWeight: 700` → `text-40-40-700`
- `fontSize: 16, lineHeightPx: 24, fontWeight: 400` → `text-16-24-400`
- `fontSize: 14, lineHeightPx: 17.5, fontWeight: 400` → `text-14-17-400` (rond `17.5` af naar `17` in de tokennaam; de waarde mag exact blijven)

**Regels:**

- Altijd **px**, geen `rem`.
- Aparte properties voor `--line-height` en `--font-weight` (Tailwind v4 syntax). Nooit als tuple `1.25rem / 1.5rem`.
- Nooit losse `leading-{n}` of `font-{weight}` klassen in HTML — alles in het token.
- **Wel** mag je `font-display` / `font-sans` los toevoegen omdat fontfamily niet in het text-token zit.

### Fontfamilies

- Eén "display" font (vaak `Oxanium`, `Lexend`, `Mulish` — herkenbaar door grotere/koppentekst gebruik) → `--font-display`.
- Eén "sans" font voor body-tekst → `--font-sans`.
- Default body-klasse `font-sans` op de outer wrapper; `font-display` op headings die expliciet de display-font moeten gebruiken.
- Google Fonts URL: combineer beide families in één `<link>` met de gebruikte weights (bv. `Lexend:wght@400;700&family=Oxanium:wght@700`).

---

## Layout-extractie (volgorde)

Voor elk frame met `layoutMode`:

1. **Richting**: `flex` (HORIZONTAL) of `flex flex-col` (VERTICAL).
2. **Gap**: `gap-{itemSpacing/4}`. Niet deelbaar door 4 → decimale multiplier (`itemSpacing: 15` → `gap-3.75`).
3. **Padding**: combineer `paddingLeft/Right/Top/Bottom` naar `p-`, `px-`/`py-`, of losse `pt-`/`pr-`/`pb-`/`pl-` naar gelang gelijkheid.
4. **Alignering**: `primaryAxisAlignItems` → `justify-*`, `counterAxisAlignItems` → `items-*`.
5. **Wrapping**: `layoutWrap: WRAP` → `flex-wrap`.
6. **Sizing van het frame zelf**: zie sizing-regels hierboven.
7. **Absolute kinderen**: voor elk kind met `layoutPositioning: ABSOLUTE` markeer de huidige frame `relative` en geef het kind `absolute` + de offsets uit `absoluteBoundingBox` (omgerekend naar de 4px-schaal, negatieve offsets met `-top-N` etc.).
8. **Sectie-spacing**: op de outer-root vaak een grote `gap-N` (bv. `gap-76`); lees direct uit `itemSpacing` van de root-VERTICAL frame.

Frames zonder `layoutMode` en zonder kinderen zijn vaak puur visueel (achtergrond-rect) — die mag je vaak weglaten en hun fill/cornerRadius toepassen op de ouder, óf inline houden als `<div>` met de juiste klassen.

---

## Semantische HTML-keuzes

| Inhoud                                                      | Element                                      |
| ----------------------------------------------------------- | -------------------------------------------- |
| Sectie-titel (grootste TEXT in een card/sectie)             | `<h2>`                                       |
| Sub-titel                                                   | `<h3>`                                       |
| Bodytekst-paragraaf (lange `characters`)                    | `<p>`                                        |
| Korte label / eyebrow / metadata                            | `<span>`                                     |
| Knop met telefoonnummer (`0320...`) als kind                | wrap in `<a href="tel:+31...">`              |
| Knop met e-mailadres                                        | `<a href="mailto:...">`                      |
| Knop "Offerte aanvragen", "Download brochure", "Meer lezen" | `<a href="#">` (placeholder href)            |
| Carrousel-pijlen, sluit-iconen, niet-link acties            | `<button type="button" aria-label="Vorige">` |
| Lijst van semantisch gelijkwaardige items (nav, opsomming)  | `<ul><li>`                                   |
| Grid van cards (visueel parallel maar zelfstandig)          | gewoon `<div>` met `flex`/`gap-*`            |

Voor accessibility: voeg `alt=""` toe aan decoratieve `<img>`, `aria-label` aan icon-only buttons.

### HTML-entities

Typografisch correcte leestekens uit Figma overnemen als entity, niet als raw character:

- `'` → `&lsquo;` / `&rsquo;` (links/rechts enkel)
- `"` → `&ldquo;` / `&rdquo;` (links/rechts dubbel)
- `&` → `&amp;`
- `–` → `&ndash;`, `—` → `&mdash;`

Voorbeeld: `Cattery van &lsquo;t Brackenhoff`.

---

## Iconen

### Material Symbols (`material-symbols:*`)

Als een Figma `INSTANCE` of `FRAME` heet zoals `material-symbols:call-outline`, `material-symbols:mail-outline`, `material-symbols:arrow-insert`, etc., gebruik dan het **standaard pad uit de Material Symbols library**, niet de geëxporteerde Figma VECTOR-data. Voorbeeld:

```html
<!-- material-symbols:call-outline -->
<svg
  class="size-4"
  viewBox="0 0 24 24"
  fill="none"
  xmlns="http://www.w3.org/2000/svg"
>
  <path
    d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"
    fill="currentColor"
  />
</svg>
```

Reden: de Figma VECTOR-paden zijn vaak vereenvoudigd of geschaald; de canonieke material-symbols paden geven een schoner resultaat. Gebruik `fill="currentColor"` zodat de kleur via een Tailwind text-klasse op de container kan komen.

### Pijl-iconen (carrousel, "ga verder")

Gebruik een eenvoudige stroke-based SVG in Lucide-stijl:

```html
<svg
  class="size-5"
  viewBox="0 0 24 24"
  fill="none"
  stroke="currentColor"
  stroke-width="2"
  stroke-linecap="round"
  stroke-linejoin="round"
>
  <path d="M5 12h14" />
  <path d="m12 5 7 7-7 7" />
</svg>
```

Voor "vorige" voeg je `rotate-180` toe.

### Decoratieve, unieke vectoren

Voor iconen die geen standaard library-equivalent hebben (bv. een illustratief peper-/chili-icoon), neem de `<svg>` letterlijk over uit de Figma VECTOR-data: `viewBox`, `fill-rule`, `clip-rule`, `d`. **Vervang álle hardcoded `fill="#..."` door `fill="currentColor"`** en zet de kleur via een `text-{token}` klasse op de SVG of een parent:

```html
<svg
  class="text-primary size-12"
  viewBox="0 0 40 40"
  xmlns="http://www.w3.org/2000/svg"
>
  <path fill-rule="evenodd" clip-rule="evenodd" d="..." fill="currentColor" />
</svg>
```

Dit geldt voor élk type icoon (material-symbols, lucide-pijl, decoratief). Geen Figma-hex behouden in de `<path>`.

---

## Image-handling

Figma `IMAGE`-fills bevatten enkel een ref (`imageRef`) die je niet direct als `src` kunt gebruiken. Wél geeft de Figma MCP server bij `get_design_context` per image-fill een asset-URL terug als constant in het reference-snippet:

```js
const imgFrame35 =
  "https://www.figma.com/api/mcp/asset/089a5c7b-24d2-42b5-8d28-f36d39eb47c9";
```

Gebruik die URL direct als `src`:

```html
<img
  class="h-50 w-full object-cover"
  src="https://www.figma.com/api/mcp/asset/089a5c7b-24d2-42b5-8d28-f36d39eb47c9"
  alt=""
/>
```

**Belangrijk — vervaltermijn**: deze Figma asset URLs zijn maar **7 dagen geldig**. Voor een standalone preview-HTML (deze skill's primaire output) is dat prima — het is een snapshot voor experimenten/review. Voor productie of duurzame outputs zie de `figma-to-blade` skill: die downloadt assets naar `resources/images/` en serveert ze via `Vite::asset()`.

**Geen Unsplash-placeholders meer**. De Figma-export is altijd de juiste foto en is meteen beschikbaar — geen mismatch tussen design en output.

Regels:

- `src` = de exacte asset-URL die `get_design_context` teruggaf, geen herschrijven, geen `?w=…` parameter toevoegen.
- `alt=""` voor decoratieve foto's (sfeerbeelden), volwaardige tekst alleen als de afbeelding informatieve waarde heeft.
- `object-cover` voor sfeerfoto's die de container moeten vullen (en mogen croppen).
- `object-contain` voor logo's en illustraties waar de hele afbeelding zichtbaar moet blijven.
- Voor cirkelvormige foto's: `rounded-full object-cover` + vast `size-N` of `h-N w-N`.
- Als `get_design_context` geen asset-URL teruggeeft (bv. een vector-fill of een masker zonder bitmap-bron): laat een **lege placeholder** staan (`<div class="bg-muted h-50 w-full"></div>`) en noteer dat in je samenvatting — verzin geen Unsplash- of stockfoto-URL.

---

## Componentpatronen

### Knop met icoon-suffix

Voor buttons met inline pijl-cirkel rechts: asymmetrische padding (`py-1 pl-5 pr-1`) zodat de innerlijke `size-10`-cirkel netjes in de outer-`h-12` past.

```html
<a
  href="#"
  class="bg-primary text-on-primary text-16-24-400 inline-flex h-12 cursor-pointer items-center gap-3 self-start py-1 pl-5 pr-1 font-bold"
>
  <span>Meer lezen</span>
  <span
    class="bg-on-primary text-primary flex size-10 items-center justify-center"
  >
    <svg
      class="size-5"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      stroke-width="2"
      stroke-linecap="round"
      stroke-linejoin="round"
    >
      <path d="M5 12h14" />
      <path d="m12 5 7 7-7 7" />
    </svg>
  </span>
</a>
```

### Carrousel-dots

```html
<div class="flex items-center gap-2.5">
  <span class="bg-primary size-2.5 rounded-full"></span>
  <span class="bg-muted size-2.5 rounded-full"></span>
  <span class="bg-muted size-2.5 rounded-full"></span>
</div>
```

Actieve dot krijgt `bg-primary`, inactieve `bg-muted` (of je equivalent neutraal token).

### Carrousel-pijlknoppen

```html
<button
  type="button"
  class="text-on-surface border border-muted flex size-12 cursor-pointer items-center justify-center"
  aria-label="Vorige"
>
  <svg
    class="size-5 rotate-180"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="2"
    stroke-linecap="round"
    stroke-linejoin="round"
  >
    <path d="M5 12h14" />
    <path d="m12 5 7 7-7 7" />
  </svg>
</button>
```

Vorige-knop: dezelfde SVG met `rotate-180`. Altijd `aria-label`. `cursor-pointer` expliciet meegeven op interactieve `<a>`/`<button>`.

---

## Sizing — pixelwaarde naar klasse

Volgorde van voorkeur (zie ook tailwind-v4 skill):

1. **Deelbaar door 4** → integer-schaal: `width: 200` → `w-50`, `width: 592` → `w-148`, `width: 356` → `w-89`.
2. **Niet deelbaar door 4** → decimale multiplier: `width: 554` → `max-w-138.5`, `itemSpacing: 70` → `gap-17.5`, `itemSpacing: 15` → `gap-3.75`.
3. **Speciale tokens** waar Tailwind die heeft: `1px` → `border` of `*-px`.
4. Pas dán `[...]` met expliciete reden.

Veelvoorkomende Figma-waarden uit de dataset en hun klasse:

| px   | Klasse                     |
| ---- | -------------------------- |
| 12   | `*-3`                      |
| 15   | `*-3.75`                   |
| 16   | `*-4`                      |
| 24   | `*-6`                      |
| 40   | `*-10` (of `size-10`)      |
| 48   | `*-12`                     |
| 64   | `*-16`                     |
| 70   | `*-17.5`                   |
| 200  | `*-50`                     |
| 356  | `*-89`                     |
| 554  | `*-138.5`                  |
| 592  | `*-148`                    |
| 712  | `*-178`                    |
| 1216 | `*-304` (vaak `max-w-304`) |
| 1276 | `*-319`                    |

`size-N` voor vierkante elementen (icoon-cirkels, dots, avatars).

---

## Wat NIET te doen

1. **Geen `tailwind.config.js`** aanmaken — alle config in `@theme`.
2. **Geen `oklch()` of `rgba()`** in `@theme` — altijd hex.
3. **Geen `text-[20px] leading-[24px] font-[400]`** — altijd via `text-{size}-{lh}-{weight}` tokens.
4. **Geen losse `leading-*` of `font-{weight}`** als de font-weight al in het text-token zit.
5. **Geen standaard Tailwind kleuren** (`bg-blue-500`, `text-red-600`, `border-gray-200`) — altijd custom semantische tokens.
6. **Geen `[20px]` arbitraire spacing** als de 4px-schaal of decimale multiplier het kan: `gap-[15px]` is fout, `gap-3.75` goed.
7. **Geen aparte opacity-tokens** zoals `--color-on-surface-60` — gebruik `/60` modifier.
8. **Geen rauwe Figma VECTOR-paden voor material-symbols** — gebruik de canonieke library-paden.
9. **Geen `w-N h-N`** als ze gelijk zijn — gebruik `size-N`.
10. **Geen `@layer`** — schrijf klassen direct.
11. **Geen lege wrappers** kopiëren omdat ze in Figma als losse frame bestaan — voeg ze samen waar dat de HTML eenvoudiger maakt.
12. **Geen `absoluteBoundingBox` als positie-aanwijzing** lezen voor `left/top` binnen auto-layout — Figma's `layoutMode` bepaalt de plaatsing. Uitzondering: kinderen met `layoutPositioning: ABSOLUTE` (decoratieve blobs, gelaagde foto's) — daar is `absoluteBoundingBox` wél de bron voor de offsets.
13. **Geen `<header>`/`<section>`/`<footer>`/`<main>`/`<nav>` wrappers**. De output is een fragment dat in een bestaande pagina geplakt wordt; outer wrapper blijft `<div class="bg-surface text-on-surface font-sans">`. Sectie-`<div>`'s krijgen geen extra semantiek.
14. **Geen Figma-hex `fill="#..."` in SVG-paths laten staan**. Alles wordt `fill="currentColor"` + `text-{token}` op de SVG/parent.

---

## Werkwijze stap voor stap

1. **Tree lezen** — start bij de root-node, loop recursief door `children`. Negeer `RECTANGLE`-fills die puur als achtergrond dienen voor een ouder-frame; pas die fill toe op de ouder.
2. **Tokens verzamelen**:
   - Loop één keer door de tree, verzamel alle unieke `fills[0].color` hex-waarden → maak kleur-tokens met semantische namen.
   - Loop één keer door alle `TEXT`-nodes, verzamel unieke `(fontSize, lineHeightPx, fontWeight)` triples → maak text-tokens.
   - Verzamel unieke `fontFamily` waarden → wijs toe aan `--font-display` (grootste/koppen) en `--font-sans` (body).
3. **Output-header bouwen** — zet `<script>`, `<link>` (Google Fonts URL met alle gebruikte families + weights), en `<style type="text/tailwindcss">` met het `@theme` blok.
4. **HTML genereren** — loop door de tree en map elke node:
   - `FRAME` met `layoutMode` → `<div class="flex [flex-col] gap-N ...">` met juiste sizing/padding/alignering.
   - `TEXT` → `<h2>`/`<h3>`/`<p>`/`<span>` met de juiste text-token-klasse en kleur-klasse.
   - `INSTANCE` met material-symbols naam → inline SVG met canoniek pad.
   - `INSTANCE` Button-component → `<a>` of `<button>` met de juiste klassen.
   - Decoratieve `VECTOR` → letterlijk SVG.
5. **Controle** — scan de output:
   - Geen `[]`-waarden meer waar een schaal-klasse beschikbaar is.
   - Alle gebruikte kleur-klassen verwijzen naar in `@theme` gedefinieerde tokens.
   - Alle text-tokens die voorkomen in HTML zijn ook in `@theme` gedefinieerd (en omgekeerd geen ongebruikte tokens).
   - `font-display` staat alleen op koppen die expliciet display-font moeten zijn; de rest erft `font-sans` van de root.
   - Semantische HTML: `<h2>`/`<h3>`/`<p>`/`<a>`/`<button>` correct gekozen, niet alles `<div>`.

---

## Wanneer deze skill toepassen

- Gebruiker geeft een Figma JSON-input (bv. uit `dataset/*/input.json`) en wil een HTML-equivalent.
- Gebruiker plakt een Figma file-URL of node-ID en vraagt om "tailwind/html" of "convert to HTML".
- Combineren met **`tailwind-v4`** skill: deze regelt het _mappen_, die regelt de _Tailwind-conventies_. Laad beide.
