<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;

class DemoDataController extends Controller
{
    /**
     * Wipes the database and reseeds it with fresh demo data. This is a
     * throwaway demo/test project, not a real store with real customers —
     * don't wire this up on an app that has to protect real data.
     */
    public function reset(): RedirectResponse
    {
        Artisan::call('migrate:fresh', ['--force' => true, '--seed' => true]);

        return redirect()->route('admin.dashboard')->with('success', 'Demo data has been reset.');
    }
}
