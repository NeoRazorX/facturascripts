Core de **FacturaScripts**: ERP y programa de facturación de código abierto, ampliable mediante plugins.

Antes de tocar nada lee [`Doc/Context/README.md`](Doc/Context/README.md): es el índice del contexto (temas y
decisiones) y dice qué archivo abrir para cada tema; no hace falta leerlo todo.

## Documentación

| Ruta | Para quién | Qué guarda |
|---|---|---|
| [`Doc/User/`](Doc/User/) | Quien usa el programa | Páginas `.md` que explican cómo se usa |
| [`Doc/Dev/`](Doc/Dev/) | Quien programa | Páginas `.md` que explican cómo personalizarlo desde un plugin |
| [`Doc/Context/`](Doc/Context/) | El equipo y los agentes | Por qué el código es como es: las decisiones tomadas, con su índice en `README.md` |
| [`Doc/Assets/`](Doc/Assets/) | Toda la documentación | Imágenes, vídeos y otros archivos enlazados desde las páginas |
| [`Doc/forja.json`](Doc/forja.json) | La forja de facturascripts.com | Datos de cada página de `User` y `Dev` para publicarlas |

- La documentación se actualiza en el mismo trabajo que cambia el código; una decisión nueva se anota en
  `Doc/Context/` y en su índice.
- `Doc/forja.json` agrupa en `User` y `Dev` las páginas de esas carpetas por su nombre de archivo, con campos
  opcionales: `title`, `title_short`, `parent`, `ordernum`, `youtubeid`, `imageurl`, `icon`, `color`, `keywords`,
  `controllers` y `visibility`.
- `CLAUDE.md` solo importa este archivo (`@AGENTS.md`). Las instrucciones locales que no se versionan van en
  `CLAUDE.local.md` (Claude Code) o `AGENTS.override.md` (Codex).

## Documentación de los plugins

Cada plugin de `Plugins/<Plugin>/` puede tener su propio árbol de documentación con esta misma estructura: su
`AGENTS.md` y su carpeta `Doc/`. Cuando el trabajo toque un plugin, o un cambio del core afecte a los plugins que lo
usan, lee su `AGENTS.md` y el índice de su `Doc/Context/README.md` antes de cambiar nada: sus reglas y decisiones se
aplican dentro de su carpeta.
