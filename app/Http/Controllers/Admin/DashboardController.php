<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Providers\GateServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    /** The dashboard, or the page of the role for an account it does not open to. */
    public function __invoke(): View|RedirectResponse
    {
        $account = Auth::guard('admin')->user();

        if ($account instanceof Admin && Gate::forUser($account)->denies(GateServiceProvider::DASHBOARD)) {
            return redirect()->route($account->role->home());
        }

        return view('admin.dashboard');
    }
}
