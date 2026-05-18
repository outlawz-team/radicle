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

### Niet-standaard tokens

Naast `--color-*`, `--font-*`, `--text-*`, `--spacing-*`, `--radius-*` en `--breakpoint-*` mag je elk Tailwind utility-namespace via `@theme` uitbreiden. Voorbeeld: een `blur-blob` utility voor een decoratieve blob-shape:

```css
@theme {
  --blur-blob: 118px;
}
```

```html
<div class="bg-blob-pink blur-blob size-175 absolute rounded-full"></div>
```

Gebruik dit pad voor elke waarde die je consequent hergebruikt en die geen schaal-equivalent heeft.

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

### Decimale multipliers

Voor pixelwaardes die níet deelbaar zijn door 4 (zoals 70px, 15px, 554px) gebruik je een **decimale multiplier** op de spacing-schaal — niet `[]`. De multiplier is `pixels / 4`.

| Pixels | Tailwind class |
| ------ | -------------- |
| 15px   | `*-3.75`       |
| 70px   | `*-17.5`       |
| 554px  | `*-138.5`      |

**Correct:**

```html
<div class="gap-17.5 max-w-138.5">...</div>
<!-- 70px, 554px -->
<div class="gap-3.75">...</div>
<!-- 15px -->
```

**Fout:**

```html
<div class="gap-[70px] max-w-[554px]">...</div>
```

### Wanneer mag `[]` wel?

Volgorde van voorkeur bij een pixelwaarde:

1. **Integer-schaal** als deelbaar door 4 (`w-[200px]` → `w-50`).
2. **Decimale multiplier** als niet deelbaar door 4 (`w-[70px]` → `w-17.5`).
3. **Speciaal token** als Tailwind dat heeft (`w-[1px]` → `w-px`, `border-[1px]` → `border`).
4. Pas dán `[]`.

`[]` is alleen toegestaan als er echt geen alternatief is. Controleer altijd eerst of Tailwind de waarde direct ondersteunt — `border-*` werkt bijvoorbeeld op pixel-basis, dus `border-[3px]` is gewoon `border-3`. Documenteer bij gebruik van `[]` kort waarom.

### Uitzondering: `shadow-[…]`

Voor box-shadows is er geen Tailwind-schaal die offset/blur/spread/kleur in één klasse vangt. Arbitraire shadow-syntax met de exacte Figma-waarden is daarom **toegestaan** en hoeft geen `@theme`-token te krijgen:

```html
<div class="shadow-[0_0_16px_0_rgba(0,0,0,0.04)]">…</div>
```

Definieer pas een `--shadow-{name}` token als dezelfde shadow op meerdere plekken voorkomt.

---

## Borders

Een border vereist altijd twéé klassen samen: `border` (de breedte) en `border-{color-token}` (de kleur). Alleen `border-primary` rendert niets — er staat geen breedte op.

```html
<button class="border border-primary">…</button>
<div class="border border-muted">…</div>
```

`border-{N}` (bv. `border-2`) gebruik je alleen waar Figma een `strokeWeight` ≠ 1px geeft.

---

## Positionering

Auto-layout vangt de meeste gevallen, maar voor decoratieve elementen (blobs, gelaagde foto's, badges over een card) gebruik je expliciete positionering. Patroon: `relative` op de parent, `absolute` op de child met `top/left/right/bottom-{N}`. Negatieve offsets met `-top-N`, `-left-N` etc. zijn gewone schaal-klassen.

```html
<div class="relative overflow-hidden">
  <div
    class="bg-blob-pink blur-blob -right-45 -top-60.5 size-175 pointer-events-none absolute rounded-full"
  ></div>
  <div class="relative z-10">…content…</div>
</div>
```

- `pointer-events-none` op puur decoratieve elementen zodat hover/klik door de parent loopt.
- `overflow-hidden` op de outer wrapper als blobs over de viewport-rand mogen lopen.
- `z-{N}` alleen waar stacking-volgorde anders niet klopt.

---

## `shrink-0` — canonieke vorm

Tailwind v4 ondersteunt zowel `shrink-0` als `flex-shrink-0`. Gebruik altijd de korte vorm: `shrink-0`.

```html
<div class="flex">
  <img class="size-10 shrink-0" src="..." />
  <p class="min-w-0">…lange tekst die mag wrappen…</p>
</div>
```

---

## `size-*` — width en height in één klasse

Wanneer width en height gelijk zijn (icoon-containers, avatars, dots, badges), gebruik `size-N` als shorthand voor `w-N h-N`. Volgt dezelfde 4px-schaal en accepteert ook decimale multipliers.

| Klasse    | Equivalent  | Pixels  |
| --------- | ----------- | ------- |
| `size-4`  | `w-4 h-4`   | 16×16px |
| `size-5`  | `w-5 h-5`   | 20×20px |
| `size-10` | `w-10 h-10` | 40×40px |

**Correct:**

```html
<div class="size-10 rounded-full bg-primary">...</div>
```

**Fout:**

```html
<div class="w-10 h-10 rounded-full bg-primary">...</div>
```

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

## Opacity modifiers op kleuren

Elk custom kleur-token combineer je met een opacity-modifier via `/{nummer}`. Werkt op `bg-`, `text-`, `border-`, `fill-`, `stroke-` en alle andere kleur-utilities.

```html
<span class="text-on-surface/60">Subtiele tekst</span>
<div class="bg-primary/20">Lichte achtergrond</div>
<div class="border-secondary/40">Zachte rand</div>
```

De waarde achter `/` is een percentage (0–100). Combineer dit met je bestaande tokens — definieer **niet** aparte tokens zoals `--color-on-surface-60` voor opacity-varianten.

---

## Veelgemaakte fouten

1. **Nog een `tailwind.config.js` aanmaken** — niet nodig en kan conflicten geven. Gebruik `@theme`.
2. **`@tailwind utilities` schrijven** — dit is v3 syntax. Gebruik `@import "tailwindcss"`.
3. **Standaard Tailwind kleuren gebruiken** (`bg-blue-600`, `text-red-500`) — gebruik altijd custom kleur-tokens. De standaard kleuren hoef je niet te verwijderen, maar gebruik ze nooit.
4. **Kleuren als `oklch()` opgeven** — wij gebruiken altijd hex (`#rrggbb`) voor kleuren in `@theme`. Geen `oklch()`, geen `rgb()`.
5. **Content-paden handmatig instellen** — niet nodig tenzij je bestanden buiten het project staan.
6. **Arbitraire waarden gebruiken** (`mb-[20px]`, `p-[16px]`, `gap-[24px]`) — gebruik altijd de 4px-schaal (`mb-5`, `p-4`, `gap-6`). Als de waarde niet deelbaar is door 4, gebruik een **decimale multiplier** (`gap-17.5` voor 70px, `max-w-138.5` voor 554px) — niet `[]`. Uitzondering: `shadow-[…]` is toegestaan omdat er geen shadow-schaal is.
7. **Inline font sizes gebruiken** (`text-[20px] leading-[24px]`) — gebruik altijd de `text-{size}-{lineHeight}-{weight}` naamconventie via `@theme`.
8. **Tekst tokens als rem of tuple definiëren** (`--text-20-24-400: 1.25rem / 1.5rem`) — gebruik altijd px met aparte `--line-height` en `--font-weight` properties.
9. **Raw CSS schrijven in `.css`-bestanden** — gebruik altijd `@apply` met Tailwind utilities. Alleen uitzondering: stijlen zonder Tailwind equivalent, dan met comment documenteren waarom.
10. **`@layer` gebruiken** — gebruik nooit `@layer`. Schrijf classes gewoon direct in CSS.
11. **`w-N h-N` schrijven voor gelijke maten** — gebruik `size-N` als shorthand (`size-10` in plaats van `w-10 h-10`).
12. **Aparte kleur-tokens definiëren voor opacity-varianten** (`--color-on-surface-60`) — gebruik de `/opacity` modifier op het bestaande token (`text-on-surface/60`).

---

## Wanneer deze skill toepassen

- Tailwind toevoegen aan een project → gebruik v4 installatie-instructies
- Kleuren/fonts/spacing aanpassen → `@theme` in CSS, niet in een config-bestand
- Dark mode implementeren → controleer of class-gebaseerd of OS-gebaseerd gewenst is
- Container queries gebruiken → ingebouwd in v4, geen plugin nodig
- Migratie van v3 naar v4 → config omzetten naar `@theme`, imports updaten
