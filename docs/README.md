# Project Docs

This directory contains project-specific documentation for the application.

## Structure

- `docs/README.md`
  - root index for all project documentation
- `docs/auth/README.md`
  - auth and verification documentation index
- `docs/multiplayer/README.md`
  - multiplayer and match-flow documentation index

## Sections

### Auth
- `docs/auth/README.md`
- `docs/auth/google-sso.md`
- `docs/auth/player-email-verification.md`

### Multiplayer
- `docs/multiplayer/README.md`
- `docs/multiplayer/multiplayer-routes.md`

## Naming Conventions

- Group documents by domain under a dedicated folder such as `docs/auth/` or `docs/multiplayer/`
- Use `README.md` only as an index document for a folder
- Use `kebab-case` for document filenames
- Prefer descriptive names based on the main topic, such as:
  - `player-email-verification.md`
  - `multiplayer-routes.md`
  - `match-timeout-flow.md`
- Keep one primary concern per document; split unrelated topics into separate files
- If a new domain is introduced, create a new folder and add a local `README.md`

## Maintenance Notes

- Update this file when a new documentation section is added
- Update the relevant folder `README.md` when a new document is added inside that section
- Keep Mermaid diagrams close to the document that explains the underlying flow
