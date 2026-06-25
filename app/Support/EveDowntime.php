<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;

class EveDowntime
{
    /**
     * EVE Online's daily downtime begins at 11:00 UTC.
     */
    private const START_TIME = '11:00';

    /**
     * ESI is typically unavailable or unstable for several minutes after
     * downtime begins, so treat the window as ending at 11:15 UTC.
     */
    private const END_TIME = '11:15';

    /**
     * Whether the given moment (now by default) falls inside EVE's daily
     * downtime window, during which ESI should not be called. The window is
     * inclusive of the start and exclusive of the end.
     */
    public static function isActive(?CarbonInterface $at = null): bool
    {
        $at = ($at ?? Date::now())->utc();

        $start = $at->copy()->setTimeFromTimeString(self::START_TIME);
        $end = $at->copy()->setTimeFromTimeString(self::END_TIME);

        return $at->greaterThanOrEqualTo($start) && $at->lessThan($end);
    }
}
