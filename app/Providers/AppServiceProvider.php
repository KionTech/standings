<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Alliance;
use App\Models\Character;
use App\Models\Corporation;
use App\Models\Faction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Resources\Json\JsonResource;
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

        // Resources are passed straight to Inertia props; never wrap them in a
        // "data" key.
        JsonResource::withoutWrapping();

        // EVE entity types as stored in polymorphic type columns (e.g.
        // source_contacts.contact_type) map onto these models.
        Relation::enforceMorphMap([
            'character' => Character::class,
            'corporation' => Corporation::class,
            'alliance' => Alliance::class,
            'faction' => Faction::class,
        ]);

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
