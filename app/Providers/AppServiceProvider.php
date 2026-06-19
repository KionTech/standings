<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Eveonline\Provider;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureSocialite();
        $this->configureAuthorization();
    }

    /**
     * Register authorization gates.
     */
    protected function configureAuthorization(): void
    {
        Gate::define('standings.admin', static fn (User $user): bool => $user->isStandingsAdmin());
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Model::unguard();

        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );
    }

    /**
     * Register Socialite providers for EVE Online SSO.
     */
    protected function configureSocialite(): void
    {
        Event::listen(function (SocialiteWasCalled $event): void {
            $event->extendSocialite('eveonline', Provider::class);
        });
    }
}
