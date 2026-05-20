Zet een Figma-design om naar een geverifieerd Blade-blok in dit Radicle WordPress project.

**Argument**: een Figma-URL — `https://www.figma.com/design/{fileKey}/{name}?node-id={nodeId}` (de URL die je kopieert via "Copy link to selection" in Figma op de root-frame van het blok).

## Wat je moet doen

Volg de **`figma-to-blade`** skill stap voor stap. Laad bij de start óók deze skills parallel zodat alle mapping- en token-conventies beschikbaar zijn:

- `figma-to-blade` — de orchestratie + verificatieloop (deze is leidend)
- `figma-tailwind-html` — Figma → Tailwind class-mapping
- `tailwind-v4` — `@theme`-conventies en 4px-schaal
- `acf` — ACF field group conventies

De skill regelt: Figma MCP context ophalen → intake bij de gebruiker → tokens synchroniseren in `resources/css/common/theme.css` → Blade-bestand genereren volgens de project-conventies (uit `AGENTS.md` / `CLAUDE.md`) → Playwright MCP verificatie op de dev-URL (dynamisch gelezen uit `.env` → `WP_HOME`) → screenshot + computed styles → iteratie tot binnen tolerance.

## Belangrijk

- **Niet committen** — gebruiker reviewt zelf.
- **Vite dev-server moet draaien** (`npm run dev`) voordat de Playwright-verificatie nuttig is.
- **Antwoord in het Nederlands** (project-conventie), code/comments/variabelen in het Engels.

## URL

$ARGUMENTS
