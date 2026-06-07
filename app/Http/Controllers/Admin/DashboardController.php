<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Vendor;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'eventCount' => Event::count(),
            'pendingCount' => Vendor::where('status', 'pending')->count(),
            'vendorCount' => Vendor::where('status', 'approved')->count(),
            'recentPending' => Vendor::where('status', 'pending')->latest()->take(5)->get(),
        ]);
    }
}
