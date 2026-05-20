# Onderhoud — `figma-to-blade`

De `figma-to-blade` workflow draait op drie AI-tools: **Claude**, **Cursor** en **Codex**. Elke tool heeft eigen native paden en formaten, dus dezelfde workflow staat op meerdere plekken in het project. Deze README beschrijft hoe je ze synchroon houdt.

## Bestanden

| Tool | Pad | Formaat | Rol |
|------|-----|---------|-----|
| Claude | [`.claude/skills/figma-to-blade/SKILL.md`](../../skills/figma-to-blade/SKILL.md) | SKILL.md + YAML-frontmatter | **Bron-of-truth** |
| Claude | [`.claude/commands/figma-to-blade.md`](../../commands/figma-to-blade.md) | Plain markdown | Dun entrypoint dat naar de skill verwijst |
| Cursor | [`.cursor/commands/figma-to-blade.md`](../../../.cursor/commands/figma-to-blade.md) | Plain markdown | Self-contained: intro + complete skill body |
| Codex | [`.agents/skills/figma-to-blade/SKILL.md`](../../../.agents/skills/figma-to-blade/SKILL.md) | SKILL.md + YAML-frontmatter | Identieke kopie van Claude SKILL.md |

## Bron-of-truth

[`.claude/skills/figma-to-blade/SKILL.md`](SKILL.md) is altijd leidend. Begin daar bij elke wijziging aan de workflow.

## Sync-checklist (bij elke wijziging aan de workflow)

1. **Bewerk eerst** [`.claude/skills/figma-to-blade/SKILL.md`](SKILL.md). Test in Claude.
2. **Kopieer naar Codex** (identiek formaat — volledige file overschrijven):
   ```bash
   cp .claude/skills/figma-to-blade/SKILL.md .agents/skills/figma-to-blade/SKILL.md
   ```
3. **Update Cursor command** (self-contained). De top van [`.cursor/commands/figma-to-blade.md`](../../../.cursor/commands/figma-to-blade.md) bevat een intro + `$ARGUMENTS` blok dat je behoudt. Vervang alles ná de eerste `---` separator door de body van Claude SKILL.md (zonder YAML-frontmatter):
   ```bash
   # Maak het opnieuw op vanaf de intro die al in .cursor/commands/figma-to-blade.md staat:
   # bewaar regels 1 t/m de eerste "---" + lege regel, append daarna de body uit Claude SKILL.md
   awk '/^---$/{c++; next} c>=2{print}' .claude/skills/figma-to-blade/SKILL.md > /tmp/skill-body.md
   # Vervolgens handmatig de body achter de bestaande intro plakken, of de hele file regenereren.
   ```

## Sync-checklist (bij wijziging aan de intro / argumenten)

Alleen [`.claude/commands/figma-to-blade.md`](../../commands/figma-to-blade.md) en de top van [`.cursor/commands/figma-to-blade.md`](../../../.cursor/commands/figma-to-blade.md) (alles vóór de `---` separator) raken elkaar hier. Codex heeft geen aparte command-laag — de skill self-bootstrapt.

## Werkfolder

Alle Figma-screenshots, context-dumps en Playwright renders worden door alle drie de tools naar dezelfde tool-agnostische folder geschreven:

```
storage/figma-references/
├── {block-name}-figma.png        # Figma MCP screenshot (bron-of-truth)
├── {block-name}-context.json     # Figma metadata (tokens, dimensies)
├── {block-name}-rendered-N.png   # Playwright render per iteratie
└── {block-name}-computed-N.json  # Computed styles per iteratie
```

Deze folder is gitignored (zie [`.gitignore`](../../../.gitignore)) maar de folder zelf blijft bestaan via [`.gitkeep`](../../../storage/figma-references/.gitkeep).

## Drift voorkomen

Met drie bestanden die dezelfde body bevatten loopt dit op termijn uit sync als je niet consequent alles bijwerkt. Mogelijke vervolgstappen als drift een probleem wordt:

- Symlinks vanuit de Claude SKILL.md naar de andere twee paden (niet alle tools volgen symlinks consistent).
- Een sync-script (bv. `composer run sync:skills`) dat de bron-of-truth naar alle locaties kopieert.
- Eén centrale folder (`.ai/skills/`) met tool-specifieke launchers ernaartoe.

Voor nu: handmatig syncen via deze checklist.
