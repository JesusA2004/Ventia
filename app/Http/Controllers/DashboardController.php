<?php

namespace App\Http\Controllers;

use App\Services\DashboardMetricsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardMetricsService $metrics) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('dashboard.view'), 403);

        return Inertia::render('Dashboard', [
            'metrics' => $this->metrics->build($request->user()),
        ]);
    }
}
