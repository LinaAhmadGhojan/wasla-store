<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAlert;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function poll(Request $request)
    {
        $afterId = (int) $request->query('after_id', 0);

        $unread = AdminAlert::query()
            ->whereNull('read_at')
            ->orderByDesc('id')
            ->limit(30)
            ->get();

        $fresh = AdminAlert::query()
            ->when($afterId > 0, fn ($q) => $q->where('id', '>', $afterId))
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        $latestId = (int) (AdminAlert::query()->max('id') ?? 0);

        return response()->json([
            'latest_id' => $latestId,
            'unread_count' => AdminAlert::query()->whereNull('read_at')->count(),
            'unread' => $unread->map->toPollArray()->values(),
            'fresh' => $fresh->map->toPollArray()->values(),
        ]);
    }

    public function markRead(Request $request, AdminAlert $alert)
    {
        $alert->markRead();

        return response()->json(['ok' => true, 'id' => $alert->id]);
    }

    public function markAllRead()
    {
        AdminAlert::query()->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }
}
