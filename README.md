<div align="center">

# Bluebook

**One standings list, every character.**

Sync EVE Online standings from a single source onto the in-game contacts of every registered character.

[![Tests](https://github.com/KionTech/bluebook/actions/workflows/tests.yml/badge.svg)](https://github.com/KionTech/bluebook/actions/workflows/tests.yml)
[![Lint](https://github.com/KionTech/bluebook/actions/workflows/lint.yml/badge.svg)](https://github.com/KionTech/bluebook/actions/workflows/lint.yml)
[![Static Analysis](https://github.com/KionTech/bluebook/actions/workflows/static.yml/badge.svg)](https://github.com/KionTech/bluebook/actions/workflows/static.yml)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

Built with Laravel 13, Vue 3, Inertia v3, and Tailwind CSS v4.

</div>

## How It Works

1. An admin picks a **source**: the character, corporation, or alliance whose contacts define the canonical standings.
2. Pilots log in with EVE Online SSO and add all their characters, alts included. A setup wizard walks new pilots through it.
3. The app mirrors the source's standings onto every syncing character's in-game contact list and keeps them fresh.

> **ESI constraint:** EVE's API only offers a write endpoint for *character* contacts. Corporation and alliance contact lists are read-only, so the sync always writes to member characters individually. Corp and alliance contacts can still be read and used as a source.

## Features

- **EVE Online SSO**: log in and add characters through EVE's OAuth flow, no passwords
- **Automatic sync**: standings are copied to every opted-in character and refreshed continuously
- **Standing requests**: pilots who are not blue yet can request a standing, with optional Discord notifications for admins
- **Admin console**: overview with live stats, request triage, source and notification settings
- **Pilot roster**: searchable list of every account grouped by main character, corporation, or alliance
- **Privacy by eligibility**: standings stay hidden until one of your characters is covered by the source or has a positive standing
- **Setup wizard**: first login walks new pilots through adding alts and picking a main character

## Requirements

- PHP 8.4+
- MySQL 8.0+
- Node.js 20+
- Composer
- An [EVE Online developer application](https://developers.eveonline.com/) for SSO credentials

## Quickstart

```bash
git clone https://github.com/KionTech/bluebook.git
cd standings
composer setup
```

The `setup` script installs dependencies, creates your `.env`, generates an app key, runs migrations, and builds the frontend.

### EVE SSO credentials

Register an application at the [EVE Online Developers Portal](https://developers.eveonline.com/) and add the credentials to your `.env`:

```env
EVEONLINE_CLIENT_ID=your-client-id
EVEONLINE_CLIENT_SECRET=your-client-secret
EVEONLINE_REDIRECT_URI="${APP_URL}/eve/callback"

# EVE character_ids of the admins (comma-separated)
EVE_ADMIN_CHARACTER_ID=
```

Required ESI scopes: `esi-characters.read_contacts.v1` and `esi-characters.write_contacts.v1`, plus `esi-corporations.read_contacts.v1` / `esi-alliances.read_contacts.v1` for corp or alliance sources.

### Database

Point `.env` at your MySQL database, then seed:

```bash
php artisan db:seed
```

This seeds the ESI scope definitions and imports the EVE Static Data Export (types, regions, solar systems, and more). The initial import takes a few minutes.

When CCP ships a new SDE version, update with:

```bash
php artisan sde:download
php artisan sde:seed
```

## Development

```bash
composer run dev
```

Starts the web server, queue worker, log viewer, and Vite dev server concurrently.

## Testing

```bash
php artisan test --compact
```

Runs the full Pest suite, including real-browser tests via Playwright.

## Companion Packages

| Package | Description |
|---|---|
| [nicolaskion/eve](https://github.com/NicolasKion/Esi) | EVE API integration for the ESI (EVE Swagger Interface) |
| [nicolaskion/sde](https://github.com/NicolasKion/SDE) | EVE Static Data Export as Eloquent models |

## License

Released under the [MIT License](LICENSE).
