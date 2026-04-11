---
name: tailwind-v4
description: Kennis en werkwijze voor Tailwind CSS v4 — de CSS-first aanpak, nieuwe syntax, configuratie via @theme, breaking changes t.o.v. v3, en hoe je er effectief mee werkt in projecten.
---

# Tailwind CSS v4

Tailwind v4 is een complete herziening van hoe Tailwind werkt. De grootste verandering: **alles gaat via CSS**, niet meer via een JS config-bestand.

## Kern van v4: CSS-first configuratie

### Installatie (Vite)
```bash
npm install tailwindcss @tailwindcss/vite
```

`vite.config.ts`:
```ts
import tailwindcss from '@tailwindcss/vite'

export default {
  plugins: [tailwindcss()]
}
```

`app.css`:
```css
@import "tailwindcss";
```

Dat is alles. Geen `tailwind.config.js`, geen PostCSS config, geen content-paden opgeven.

### Installatie (PostCSS / andere bundlers)
```bash
npm install tailwindcss @tailwindcss/postcss
```

`postcss.config.js`:
```js
export default {
  plugins: {
    '@tailwindcss/postcss': {}
  }
}
```

---

## @theme — design tokens in CSS

Aanpassen van het design system doe je via `@theme` in je CSS:

```css
@import "tailwindcss";

@theme {
  --font-sans: 'Inter', sans-serif;
  --color-brand: #3b6fd4;
  --spacing-18: 4.5rem;
  --radius-xl: 1rem;
  --breakpoint-3xl: 1920px;
}
```

Alle waarden die je hier definieert worden:
1. Automatisch beschikbaar als utility-klassen (`text-brand`, `p-18`, `rounded-xl`)
2. Beschikbaar als CSS-variabele (`var(--color-brand)`)

---

## Typografie — naamgeving en conventies

### ALTIJD via `@theme` definiëren

Font sizes worden **nooit** inline geschreven met `text-[20px]` of losse `leading-`/`font-`-klassen. Alles gaat via named tokens in `@theme`.

### Naamconventie: `text-{size}-{lineHeight}-{weight}`

Het patroon is: **font size** — **line height** — **font weight**.

Voorbeelden:
- `text-20-24-400` → 20px, line-height 24px, font-weight 400
- `text-16-20-600` → 16px, line-height 20px, font-weight 600
- `text-14-18-400` → 14px, line-height 18px, font-weight 400

### Definiëren in `@theme`

In Tailwind v4 definieer je font-size tokens met `--text-*`. Per token stel je ook `--leading` in via een tuple:

```css
@theme {
  /* --text-{naam}: {font-size} / {line-height} */
  --text-20-24-400: 1.25rem / 1.5rem;
  --text-16-20-600: 1rem / 1.25rem;
  --text-14-18-400: 0.875rem / 1.125rem;
}
```

> De font-weight zit in de naam als documentatie, maar wordt apart toegepast als utility in HTML.

### Gebruiken in HTML

```html
<p class="text-20-24-400 font-normal">Bodytekst</p>
<h2 class="text-16-20-600 font-semibold">Sectietitel</h2>
```

### Regels

- **Nooit** `text-[20px]`, `leading-[24px]` of `font-[400]` inline gebruiken
- **Altijd** een nieuw tekststijl definiëren in `@theme` met de `text-{size}-{lineHeight}-{weight}` naam
- Font-weight staat in de naam zodat de intentie direct leesbaar is in HTML

---

## Styling in CSS — altijd via `@apply`

Wanneer je in een `.css`-bestand styling schrijft, gebruik je **altijd `@apply`** met Tailwind utility-klassen. Nooit raw CSS properties schrijven. Gebruik **geen `@layer`** — schrijf classes gewoon direct.

**Correct:**
```css
.card {
  @apply bg-white rounded-xl shadow-md p-6;
}

.btn-primary {
  @apply bg-brand text-white text-16-20-600 px-4 py-2 rounded-lg hover:opacity-90;
}
```

**Fout:**
```css
.card {
  background-color: white;
  border-radius: 0.75rem;
  padding: 1.5rem;
}
```

### Uitzondering

Raw CSS mag alleen wanneer een stijl **onmogelijk** uit te drukken is met Tailwind utilities, zoals complexe animaties of vendor-specifieke eigenschappen. Documenteer dan waarom met een comment.

```css
.scrollbar-hide {
  /* Geen Tailwind equivalent beschikbaar */
  scrollbar-width: none;
  &::-webkit-scrollbar { display: none; }
}
```

### Standaard tokens overschrijven
```css
@theme {
  --color-*: initial; /* wis alle standaard kleuren */
  --color-primary: #3b6fd4;
  --color-secondary: #4caf8a;
}
```

---

## Breaking changes t.o.v. v3

| v3 | v4 |
|----|----|
| `tailwind.config.js` | `@theme` in CSS |
| `@tailwind base/components/utilities` | `@import "tailwindcss"` |
| `content: [...]` voor purge | Automatische brondetectie |
| `theme.extend` in config | `@theme` met nieuwe waarden |
| `rgba()` kleuren | `oklch()` kleurruimte (wij gebruiken hex) |
| `dark: class` toggle | `@variant dark` of `@custom-variant` |
| PostCSS verplicht | Vite plugin beschikbaar |

---

## Automatische brondetectie

In v4 hoef je geen `content`-paden meer op te geven. Tailwind scant automatisch alle bestanden in je project (exclusief `node_modules`, `.git`, en binaire bestanden).

Wil je iets uitsluiten of expliciet toevoegen?
```css
@source not "./src/legacy/**";
@source "./node_modules/mijn-ui-lib/**/*.js";
```

---

## Nieuwe utilities in v4

### 3D transforms
```html
<div class="rotate-x-45 perspective-500 transform-3d">...</div>
```

### Container queries (ingebouwd)
```html
<div class="@container">
  <p class="@sm:text-lg @lg:text-2xl">Responsive op container</p>
</div>
```

### `field-sizing`
```html
<textarea class="field-sizing-content">...</textarea>
```

### Gradient via hoek
```html
<div class="bg-linear-45 from-blue-500 to-purple-600">...</div>
```

### `not-*` variant
```html
<p class="not-last:mb-4">...</p>
```

### `in-*` variant (context-based)
```html
<li class="in-[.active-list]:font-bold">...</li>
```

---

## Dark mode

Dark mode werkt standaard via OS-voorkeur (`prefers-color-scheme`). Wil je class-gebaseerde dark mode:

```css
@import "tailwindcss";

@variant dark (&:where(.dark, .dark *));
```

Dan werkt `dark:bg-gray-900` op basis van een `.dark` class op de root.

---

## Eigen varianten aanmaken

```css
@custom-variant hovered (&:hover, &:focus-visible);
@custom-variant sidebar (.sidebar &);
```

Gebruik:
```html
<button class="hovered:bg-blue-600">Hover mij</button>
<p class="sidebar:text-sm">Kleiner in sidebar</p>
```

---

## CSS-variabelen gebruiken in utilities

Elke `@theme` waarde is ook een CSS-variabele:
```css
.mijn-component {
  color: var(--color-brand);
  padding: var(--spacing-6);
}
```

En andersom — externe CSS-variabelen gebruiken in Tailwind:
```html
<div style="--accent: #a855f7;" class="bg-[--accent]">...</div>
```

---

## Veelgemaakte fouten

1. **Nog een `tailwind.config.js` aanmaken** — niet nodig en kan conflicten geven. Gebruik `@theme`.
2. **`@tailwind utilities` schrijven** — dit is v3 syntax. Gebruik `@import "tailwindcss"`.
3. **Kleuren als `oklch()` opgeven** — wij gebruiken altijd hex (`#rrggbb`) voor kleuren in `@theme`. Geen `oklch()`, geen `rgb()`.
4. **Content-paden handmatig instellen** — niet nodig tenzij je bestanden buiten het project staan.
5. **Inline font sizes gebruiken** (`text-[20px] leading-[24px]`) — gebruik altijd de `text-{size}-{lineHeight}-{weight}` naamconventie via `@theme`.
6. **Raw CSS schrijven in `.css`-bestanden** — gebruik altijd `@apply` met Tailwind utilities. Alleen uitzondering: stijlen zonder Tailwind equivalent, dan met comment documenteren waarom.
7. **`@layer` gebruiken** — gebruik nooit `@layer`. Schrijf classes gewoon direct in CSS.

---

## Wanneer deze skill toepassen

- Tailwind toevoegen aan een project → gebruik v4 installatie-instructies
- Kleuren/fonts/spacing aanpassen → `@theme` in CSS, niet in een config-bestand
- Dark mode implementeren → controleer of class-gebaseerd of OS-gebaseerd gewenst is
- Container queries gebruiken → ingebouwd in v4, geen plugin nodig
- Migratie van v3 naar v4 → config omzetten naar `@theme`, imports updaten
