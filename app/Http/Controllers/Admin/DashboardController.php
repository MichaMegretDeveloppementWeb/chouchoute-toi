<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Providers\GateServiceProvider;
use App\Services\Admin\Navigation\OpenScreensService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    /** The dashboard, or the first screen of the bar for an account it does not open to. */
    public function __invoke(OpenScreensService $screens): View|RedirectResponse
    {
        $account = Auth::guard('admin')->user();

        if ($account instanceof Admin && Gate::forUser($account)->denies(GateServiceProvider::DASHBOARD)) {
            $home = $screens->homeOf($account);

            abort_if($home === null, 403);

            return redirect()->route($home['route']);
        }

        return view('admin.dashboard');
    }
}
