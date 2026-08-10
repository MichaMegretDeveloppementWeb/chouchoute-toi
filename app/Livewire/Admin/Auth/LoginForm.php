<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Sign-in form of the back-office.
 *
 * No Action nor Service here on purpose: signing in is a single framework call
 * with no transaction, and the throttling below is form-level protection that
 * reports through the validation error bag. Extracting it would push a
 * ValidationException down into a business layer for no gain.
 */
final class LoginForm extends Component
{
    /** Failed attempts allowed per email and IP before the form locks. */
    private const MAX_ATTEMPTS = 5;

    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    /** @return array<string, array<int, string>> */
    protected function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'email.required' => 'Veuillez indiquer votre adresse email.',
            'email.email' => 'Veuillez entrer une adresse email valide.',
            'password.required' => 'Veuillez indiquer votre mot de passe.',
        ];
    }

    /**
     * @throws ValidationException
     */
    public function login(): void
    {
        $this->validate();
        $this->assertIsNotRateLimited();

        $credentials = ['email' => $this->email, 'password' => $this->password];

        if (! Auth::guard('admin')->attempt($credentials, $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => 'Ces identifiants ne correspondent à aucun compte.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        // Prevents session fixation: the pre-login session id must not survive.
        session()->regenerate();

        $this->redirectIntended(route('admin.dashboard'), navigate: false);
    }

    public function render(): View
    {
        return view('livewire.admin.auth.login-form');
    }

    /**
     * @throws ValidationException
     */
    private function assertIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => "Trop de tentatives. Veuillez réessayer dans {$seconds} secondes.",
        ]);
    }

    private function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
}
