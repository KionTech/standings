<?php

declare(strict_types=1);

use App\Models\Alliance;
use App\Models\Attribute;
use App\Models\Bloodline;
use App\Models\Category;
use App\Models\Celestial;
use App\Models\Character;
use App\Models\Constellation;
use App\Models\Corporation;
use App\Models\Effect;
use App\Models\EffectModifier;
use App\Models\Faction;
use App\Models\Graphic;
use App\Models\Group;
use App\Models\Icon;
use App\Models\MarketGroup;
use App\Models\MetaGroup;
use App\Models\OperationService;
use App\Models\Race;
use App\Models\Region;
use App\Models\Service;
use App\Models\Solarsystem;
use App\Models\SolarsystemConnection;
use App\Models\Stargate;
use App\Models\Station;
use App\Models\StationOperation;
use App\Models\Type;
use App\Models\TypeAttribute;
use App\Models\TypeEffect;
use App\Models\Unit;

return [
    'models' => [
        'Alliance' => Alliance::class,
        'Attribute' => Attribute::class,
        'Bloodline' => Bloodline::class,
        'Category' => Category::class,
        'Celestial' => Celestial::class,
        'Character' => Character::class,
        'Constellation' => Constellation::class,
        'Corporation' => Corporation::class,
        'Effect' => Effect::class,
        'EffectModifier' => EffectModifier::class,
        'Faction' => Faction::class,
        'Graphic' => Graphic::class,
        'Group' => Group::class,
        'Icon' => Icon::class,
        'MarketGroup' => MarketGroup::class,
        'MetaGroup' => MetaGroup::class,
        'OperationService' => OperationService::class,
        'Race' => Race::class,
        'Region' => Region::class,
        'Service' => Service::class,
        'Solarsystem' => Solarsystem::class,
        'SolarsystemConnection' => SolarsystemConnection::class,
        'Stargate' => Stargate::class,
        'Station' => Station::class,
        'StationOperation' => StationOperation::class,
        'Type' => Type::class,
        'TypeAttribute' => TypeAttribute::class,
        'TypeEffect' => TypeEffect::class,
        'Unit' => Unit::class,
    ],
];
