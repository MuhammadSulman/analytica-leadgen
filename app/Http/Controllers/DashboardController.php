<?php

namespace App\Http\Controllers;

use App\Http\Requests\LeadFilterRequest;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Show the admin dashboard with lead statistics, optionally for a period of the date received.
     */
    public function __invoke(LeadFilterRequest $request): Response
    {
        // Only the date range applies here; invalid dates have already been dropped by the request.
        $period = [
            'from' => $request->filters()['from'],
            'to' => $request->filters()['to'],
        ];

        $leads = fn (): Builder => Lead::query()->filter([...$period, 'timezone' => $request->timezone()]);

        return Inertia::render('Dashboard', [
            'filters' => $period,
            'totalLeads' => $leads()->count(),
            'leadsByStatus' => $leads()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
            'leadsByPlatform' => $leads()
                ->selectRaw('platform, count(*) as total')
                ->groupBy('platform')
                ->pluck('total', 'platform'),
            'recentLeads' => $leads()
                ->latest()
                ->limit(5)
                ->get(['id', 'name', 'email', 'company', 'platform', 'status', 'created_at']),
        ]);
    }
}
