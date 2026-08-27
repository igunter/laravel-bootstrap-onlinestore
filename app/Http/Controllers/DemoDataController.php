<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;

class DemoDataController extends Controller
{
    /**
     * Wipes the database and reseeds it with fresh demo data. Deliberately
     * public, no auth required — this is a throwaway demo/test project, not
     * a real store with real customers. Don't wire this up on an app that has
     * to protect real data.
     */
    public function reset(): RedirectResponse
    {
        Artisan::call('migrate:fresh', ['--force' => true, '--seed' => true]);

        // Sends them back to wherever they clicked the button from — it's
        // reachable from both the public nav and the admin panel.
        return redirect()->back()->with('success', 'Demo data has been reset.');
    }
}
