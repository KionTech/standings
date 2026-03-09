# EVE Starter Kit

A Laravel starter kit for building third-party EVE Online applications. Built on Laravel 12, Vue 3, and Inertia v2 with a modern, production-ready foundation.

## What It Provides

- **EVE Online SSO** -- Login exclusively through EVE Online's OAuth flow using [Socialite](https://laravel.com/docs/socialite) with the [EVE Online adapter](https://github.com/nullx27/eve-online-sso)
- **Character Management** -- Add, switch, and manage multiple EVE Online characters per account
- **Modern Frontend** -- Vue 3 SPA with Inertia v2, Tailwind CSS v4, and a full component library (Reka UI)
- **Type-Safe Routing** -- [Laravel Wayfinder](https://github.com/laravel/wayfinder) generates TypeScript functions for all your Laravel routes
- **Test Suite** -- Comprehensive Pest 4 tests covering authentication and more

## Companion Packages

This starter kit is designed to work with:

| Package | Description |
|---|---|
| [nicolaskion/sde](https://github.com/nicolaskion/sde) | EVE Online Static Data Export -- ships, items, regions, systems, and more as Eloquent models |
| [nicolaskion/eve](https://github.com/nicolaskion/eve) | EVE API integration -- a clean Laravel package for the ESI (EVE Swagger Interface) |

Together, they give you static game data, authenticated API access, and a ready-to-use application shell so you can focus on building your EVE tool.

## Requirements

- PHP 8.4+
- Node.js 20+
- Composer
- A database (SQLite works out of the box)
- An [EVE Online Developer Application](https://developers.eveonline.com/) for SSO credentials

## Installation

```bash
composer create-project nicolaskion/eve-starter-kit
cd eve-starter-kit
composer setup
```

The `setup` script installs dependencies, creates your `.env`, generates an app key, runs migrations, and builds frontend assets.

Then configure your EVE Online SSO credentials in `.env`:

```env
EVEONLINE_CLIENT_ID=your-client-id
EVEONLINE_CLIENT_SECRET=your-client-secret
EVEONLINE_REDIRECT=https://your-app.test/auth/eveonline/callback
```

## Development

```bash
# Start the dev server (requires Laravel Herd or Valet, or use php artisan serve)
composer run dev
```

This runs the Vite dev server for hot module replacement alongside your Laravel application.

## Testing

```bash
php artisan test --compact
```

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 12, PHP 8.4 |
| Frontend | Vue 3, Inertia v2, TypeScript |
| Styling | Tailwind CSS v4 |
| Components | Reka UI (headless), Lucide icons |
| Auth | Socialite (EVE Online SSO) |
| Testing | Pest 4 |
| Code Style | Laravel Pint, ESLint, Prettier |

## License

The EVE Starter Kit is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

---

EVE Online and all related trademarks are property of CCP hf.
