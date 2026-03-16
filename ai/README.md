# AI Docs

Esta carpeta contiene documentación breve y operativa para asistentes de IA.

## Estructura
- `../AGENTS.md`: contexto general del proyecto
- `./skills.yaml`: índice de skills disponibles
- `./DEVELOPMENT.md`: guía para desarrolladores
- `./skills/*.md`: instrucciones específicas por tipo de tarea

## Regla práctica
- Lo global y estable va en `AGENTS.md`
- Lo específico de una tarea va en una skill
- No duplicar reglas en demasiados archivos

## Cómo añadir una nueva skill
1. Crear un archivo en `skills/`
2. Añadir su entrada en `skills.yaml`
3. Mantenerla corta, concreta y accionable

## Cuándo crear una skill nueva
Crear una skill nueva si:
- una tarea se repite a menudo,
- requiere pasos específicos,
- los agentes suelen equivocarse en ese tipo de cambio.