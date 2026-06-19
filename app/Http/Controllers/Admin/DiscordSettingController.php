<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateDiscordSettingRequest;
use App\Models\DiscordSetting;
use Illuminate\Http\RedirectResponse;

class DiscordSettingController extends Controller
{
    public function update(UpdateDiscordSettingRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DiscordSetting::query()->updateOrCreate([], [
            'webhook_url' => $validated['webhook_url'] ?? null,
            'role_id' => $validated['role_id'] ?? null,
        ]);

        return back()->with('success', 'Discord settings updated.');
    }
}
