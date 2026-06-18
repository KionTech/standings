# Standings

Automatically sync EVE Online standings from a single source onto every registered character. Define your standings once on a source entity, and every character that authorizes the app has their in-game contacts kept in sync. Built on Laravel 13, Vue 3, and Inertia v3.

## How It Works

- A **source** (a character, corporation, or alliance) defines the canonical set of standings.
- Pilots log in with EVE Online SSO and register their **characters**, granting the `write_contacts` scope.
- The app mirrors the source's standings onto each registered character's in-game contact list and keeps them in sync.

> **ESI constraint:** EVE's ESI only exposes a **write** endpoint for *character* contacts. Corporation and alliance contact lists are **read-only** over the API (they can only be edited in-game by directors/diplomats). So corporations and alliances can register and have standings applied — but the sync writes to their **member characters individually**, never to the corp/alliance contact list itself. Corp/alliance contacts are still readable and can be used as a standings **source**.

## What It Provides

- **EVE Online SSO** -- Login through EVE Online's OAuth flow using [Socialite](https://laravel.com/docs/socialite) with the [EVE Online adapter](https://github.com/nullx27/eve-online-sso)
- **Character Management** -- Add, switch, and manage multiple EVE Online characters per account
- **Modern Frontend** -- Vue 3 SPA with Inertia v3, Tailwind CSS v4, and a full component library (Reka UI)
- **Type-Safe Routing** -- [Laravel Wayfinder](https://github.com/laravel/wayfinder) generates TypeScript functions for all your Laravel routes
- **Test Suite** -- Comprehensive Pest 4 tests

## Companion Packages

| Package | Description |
|---|---|
| [nicolaskion/sde](https://github.com/nicolaskion/sde) | EVE Online Static Data Export -- ships, items, regions, systems, and more as Eloquent models |
| [nicolaskion/eve](https://github.com/nicolaskion/eve) | EVE API integration -- a clean Laravel package for the ESI (EVE Swagger Interface) |

## Requirements

- PHP 8.4+
- MySQL 8.0+
- Node.js 20+
- Composer
- An [EVE Online Developer Application](https://developers.eveonline.com/) for SSO credentials

## Installation

```bash
git clone https://github.com/KionTech/standings.git
cd standings
composer setup
```

The `setup` script installs dependencies, creates your `.env`, generates an app key, runs migrations, and builds frontend assets.

### EVE SSO Credentials

Register your application at the [EVE Online Developers Portal](https://developers.eveonline.com/) and add the credentials to your `.env`. The app requires the contacts scopes to read sources and write character standings:

```env
EVEONLINE_CLIENT_ID=your-client-id
EVEONLINE_CLIENT_SECRET=your-client-secret
EVEONLINE_REDIRECT_URI="${APP_URL}/eve/callback"
```

Required ESI scopes: `esi-characters.read_contacts.v1`, `esi-characters.write_contacts.v1`, and optionally `esi-corporations.read_contacts.v1` / `esi-alliances.read_contacts.v1` for corp/alliance sources.

### Database

Configure your MySQL connection in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=standings
DB_USERNAME=root
DB_PASSWORD=
```

Then seed the database:

```bash
php artisan db:seed
```

This will:
1. Seed ESI scope definitions
2. Download the latest SDE data from CCP
3. Import all SDE tables (types, regions, solar systems, corporations, effects, etc.)

The initial seed may take a few minutes depending on your connection and database speed.

## Development

```bash
composer run dev
```

This starts the web server, queue worker, log viewer, and Vite dev server concurrently.

## SDE (Static Data Export)

The SDE contains EVE Online's static game data -- types, regions, solar systems, corporations, factions, and more.

### Updating SDE Data

When CCP releases a new SDE version (typically after game patches):

```bash
php artisan sde:download
php artisan sde:seed
```

The seeder uses upserts, so it's safe to re-run without clearing existing data.

### Individual Seeders

```bash
php artisan sde:seed:types
php artisan sde:seed:effects
php artisan sde:seed:regions
php artisan sde:seed:social
```

## Testing

```bash
php artisan test --compact
```

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 13, PHP 8.4 |
| Frontend | Vue 3, Inertia v3, TypeScript |
| Styling | Tailwind CSS v4 |
| Components | Reka UI (headless), Lucide icons |
| Auth | Socialite (EVE Online SSO) |
| Testing | Pest 4 |
| Code Style | Laravel Pint, ESLint, Prettier |

## License

Standings is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

---

EVE Online and all related trademarks are property of CCP hf.
