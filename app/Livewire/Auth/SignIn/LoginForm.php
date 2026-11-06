<?php

declare(strict_types=1);

namespace App\Livewire\Auth\SignIn;

use App\Services\Auth\SignIn\SignInService;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Sign-in form of the back-office · the attempts it allows are counted here, and
 * refused as a validation error on the address.
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
            'email.required' => 'Indiquez votre adresse e-mail.',
            'email.email' => 'Indiquez une adresse e-mail valide.',
            'password.required' => 'Indiquez votre mot de passe.',
        ];
    }

    /**
     * @throws ValidationException
     */
    public function login(SignInService $signIn): void
    {
        $this->validate();
        $this->assertIsNotRateLimited();

        $account = $signIn->attempt($this->email, $this->password, $this->remember);

        if ($account === null) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => 'Ces identifiants ne correspondent à aucun compte.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        $this->redirectIntended($signIn->homeOf($account), navigate: false);
    }

    public function render(): View
    {
        return view('livewire.auth.sign-in.login-form');
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
            'email' => "Trop de tentatives. Réessayez dans {$seconds} secondes.",
        ]);
    }

    private function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
}
