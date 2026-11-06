<?php

declare(strict_types=1);

namespace App\Livewire\Auth\Password;

use App\Services\Auth\Password\PasswordLinksService;
use App\Services\Auth\Password\PasswordResetService;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Asks for a link to choose a new password. The answer is the same whether the
 * address is an account's or not.
 */
final class ForgotPasswordForm extends Component
{
    /** Requests allowed per visitor and per minute. */
    private const MAX_REQUESTS = 3;

    public string $email = '';

    public bool $sent = false;

    private PasswordLinksService $links;

    public function boot(PasswordLinksService $links): void
    {
        $this->links = $links;
    }

    /** @return array<string, array<int, string>> */
    protected function rules(): array
    {
        return [
            'email' => ['required', 'email'],
        ];
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'email.required' => 'Indiquez votre adresse e-mail.',
            'email.email' => 'Indiquez une adresse e-mail valide.',
        ];
    }

    /**
     * @throws ValidationException
     */
    public function send(PasswordResetService $reset): void
    {
        $this->validate();

        $key = 'password-link|'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_REQUESTS)) {
            throw ValidationException::withMessages([
                'email' => 'Trop de demandes. Réessayez dans '.RateLimiter::availableIn($key).' secondes.',
            ]);
        }

        RateLimiter::hit($key);

        $reset->sendTheLinkTo($this->email);

        $this->sent = true;
    }

    public function render(): View
    {
        return view('livewire.auth.password.forgot-password-form', [
            'minutes' => $this->links->minutesOf(PasswordLinksService::PASSWORD_RESETS),
        ]);
    }
}
