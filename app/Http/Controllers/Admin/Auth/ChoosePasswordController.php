<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * The page a link opens, an invitation or a forgotten password, to choose a password.
 */
final class ChoosePasswordController extends Controller
{
    public function __invoke(int $account, string $token, string $purpose): View
    {
        return view('admin.auth.choose-password', [
            'account' => $account,
            'token' => $token,
            'purpose' => $purpose,
        ]);
    }
}
