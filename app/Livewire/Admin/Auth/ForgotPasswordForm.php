<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Auth;

use App\Services\Team\TeamMessagesService;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Asks for a link to choose a new password. The answer is the same whether the
 * address is an account's or not · nobody learns from it who has one.
 */
final class ForgotPasswordForm extends Component
{
    /** Requests allowed per visitor and per minute. */
    private const MAX_REQUESTS = 3;

    public string $email = '';

    public bool $sent = false;

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
            'email.required' => 'Veuillez indiquer votre adresse email.',
            'email.email' => 'Veuillez entrer une adresse email valide.',
        ];
    }

    /**
     * @throws ValidationException
     */
    public function send(TeamMessagesService $messages): void
    {
        $this->validate();

        $key = 'password-link|'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_REQUESTS)) {
            throw ValidationException::withMessages([
                'email' => 'Trop de demandes. Veuillez réessayer dans '.RateLimiter::availableIn($key).' secondes.',
            ]);
        }

        RateLimiter::hit($key);

        $messages->sendThePasswordLink($this->email);

        $this->sent = true;
    }

    public function render(): View
    {
        return view('livewire.admin.auth.forgot-password-form');
    }
}
