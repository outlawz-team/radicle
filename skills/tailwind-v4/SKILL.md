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
import tailwindcss from "@tailwindcss/vite";

export default {
  plugins: [tailwindcss()],
};
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
    "@tailwindcss/postcss": {},
  },
};
```

---

## @theme — design tokens in CSS

Aanpassen van het design system doe je via `@theme` in je CSS:

```css
@import "tailwindcss";

@theme {
  --font-sans: "Inter", sans-serif;
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

In Tailwind v4 definieer je font-size tokens met `--text-*`, aangevuld met aparte properties voor line-height en font-weight. Gebruik altijd **px-waarden**, geen rem.

```css
@theme {
  --text-20-24-400: 20px;
  --text-20-24-400--line-height: 24px;
  --text-20-24-400--font-weight: 400;

  --text-16-20-600: 16px;
  --text-16-20-600--line-height: 20px;
  --text-16-20-600--font-weight: 600;

  --text-14-18-400: 14px;
  --text-14-18-400--line-height: 18px;
  --text-14-18-400--font-weight: 400;
}
```

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

## Spacing — de 4px-regel

Tailwind's standaard spacing scale is gebaseerd op stappen van **4px** (1 unit = 4px). Gebruik altijd deze schaal en vermijd arbitraire waarden met `[]`.

| Pixels | Tailwind class |
| ------ | -------------- |
| 4px    | `*-1`          |
| 8px    | `*-2`          |
| 12px   | `*-3`          |
| 16px   | `*-4`          |
| 20px   | `*-5`          |
| 24px   | `*-6`          |
| 32px   | `*-8`          |
| 40px   | `*-10`         |
| 48px   | `*-12`         |
| 64px   | `*-16`         |

Geldt voor alle spacing-utilities: `m-`, `p-`, `gap-`, `space-`, `w-`, `h-`, `top-`, `left-`, etc.

**Correct:**

```html
<div class="mb-5 px-4 gap-6">...</div>
<!-- 20px, 16px, 24px -->
```

**Fout:**

```html
<div class="mb-[20px] px-[16px] gap-[24px]">...</div>
```

### Wanneer mag `[]` wel?

Bereken eerst of de waarde deelbaar is door 4 — zo ja, gebruik de schaal (`w-[200px]` → `w-50`). Controleer daarna of Tailwind een speciaal token heeft voor de waarde (`w-[1px]` → `w-px`, `border-[1px]` → `border`).

`[]` is alleen toegestaan als er echt geen alternatief is. Controleer altijd eerst of Tailwind de waarde direct ondersteunt — `border-*` werkt bijvoorbeeld op pixel-basis, dus `border-[3px]` is gewoon `border-3`. Documenteer bij gebruik van `[]` kort waarom.

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
  &::-webkit-scrollbar {
    display: none;
  }
}
```

### Custom kleuren toevoegen

Eigen kleuren definieer je via `@theme`. Standaard Tailwind kleuren hoef je niet te verwijderen — je gebruikt ze simpelweg niet.

```css
@theme {
  --color-primary: #3b6fd4;
  --color-secondary: #4caf8a;
  --color-accent: #f59e0b;
  --color-neutral: #6b7280;
}
```

---

## Breaking changes t.o.v. v3

| v3                                    | v4                                        |
| ------------------------------------- | ----------------------------------------- |
| `tailwind.config.js`                  | `@theme` in CSS                           |
| `@tailwind base/components/utilities` | `@import "tailwindcss"`                   |
| `content: [...]` voor purge           | Automatische brondetectie                 |
| `theme.extend` in config              | `@theme` met nieuwe waarden               |
| `rgba()` kleuren                      | `oklch()` kleurruimte (wij gebruiken hex) |
| `dark: class` toggle                  | `@variant dark` of `@custom-variant`      |
| PostCSS verplicht                     | Vite plugin beschikbaar                   |

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

## Kleuren — altijd custom, nooit standaard Tailwind

**Nooit** standaard Tailwind kleurklassen gebruiken:

- `bg-blue-600` ❌
- `text-red-500` ❌
- `border-gray-200` ❌
- `fill-green-400` ❌

**Altijd** custom tokens via `@theme`:

- `bg-primary` ✓
- `text-secondary` ✓
- `border-neutral` ✓
- `fill-accent` ✓

Reden: standaard Tailwind kleuren zijn generiek en niet afgestemd op het design. Door altijd custom tokens te gebruiken blijft het design consistent en beheerbaar. De standaard kleuren hoef je niet te verwijderen — je gebruikt ze gewoon niet.

### Naamgeving voor kleur-tokens

Kies semantische namen die de rol beschrijven, niet de kleur:

```css
@theme {
  /* Brand */
  --color-primary: #3b6fd4;
  --color-primary-hover: #2f5bb5;

  /* Neutrals */
  --color-neutral-100: #f5f5f5;
  --color-neutral-200: #e5e5e5;
  --color-neutral-700: #404040;
  --color-neutral-900: #171717;

  /* Feedback */
  --color-success: #4caf8a;
  --color-warning: #f59e0b;
  --color-error: #ef4444;

  /* Base */
  --color-white: #ffffff;
  --color-black: #000000;
}
```

---

## Veelgemaakte fouten

1. **Nog een `tailwind.config.js` aanmaken** — niet nodig en kan conflicten geven. Gebruik `@theme`.
2. **`@tailwind utilities` schrijven** — dit is v3 syntax. Gebruik `@import "tailwindcss"`.
3. **Standaard Tailwind kleuren gebruiken** (`bg-blue-600`, `text-red-500`) — gebruik altijd custom kleur-tokens. De standaard kleuren hoef je niet te verwijderen, maar gebruik ze nooit.
4. **Kleuren als `oklch()` opgeven** — wij gebruiken altijd hex (`#rrggbb`) voor kleuren in `@theme`. Geen `oklch()`, geen `rgb()`.
5. **Content-paden handmatig instellen** — niet nodig tenzij je bestanden buiten het project staan.
6. **Arbitraire waarden gebruiken** (`mb-[20px]`, `p-[16px]`, `gap-[24px]`) — gebruik altijd de 4px-schaal (`mb-5`, `p-4`, `gap-6`). Alleen gebruiken als de waarde niet deelbaar is door 4 of geen alternatief heeft.
7. **Inline font sizes gebruiken** (`text-[20px] leading-[24px]`) — gebruik altijd de `text-{size}-{lineHeight}-{weight}` naamconventie via `@theme`.
8. **Tekst tokens als rem of tuple definiëren** (`--text-20-24-400: 1.25rem / 1.5rem`) — gebruik altijd px met aparte `--line-height` en `--font-weight` properties.
9. **Raw CSS schrijven in `.css`-bestanden** — gebruik altijd `@apply` met Tailwind utilities. Alleen uitzondering: stijlen zonder Tailwind equivalent, dan met comment documenteren waarom.
10. **`@layer` gebruiken** — gebruik nooit `@layer`. Schrijf classes gewoon direct in CSS.

---

## Wanneer deze skill toepassen

- Tailwind toevoegen aan een project → gebruik v4 installatie-instructies
- Kleuren/fonts/spacing aanpassen → `@theme` in CSS, niet in een config-bestand
- Dark mode implementeren → controleer of class-gebaseerd of OS-gebaseerd gewenst is
- Container queries gebruiken → ingebouwd in v4, geen plugin nodig
- Migratie van v3 naar v4 → config omzetten naar `@theme`, imports updaten
