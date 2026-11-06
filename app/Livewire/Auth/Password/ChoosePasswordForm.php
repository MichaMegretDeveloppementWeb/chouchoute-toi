<?php

declare(strict_types=1);

namespace App\Livewire\Auth\Password;

use App\Services\Auth\Password\PasswordChoiceService;
use App\Services\Auth\SignIn\SignInService;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Chooses a password from a link · the invitation of a new account, or a
 * forgotten password. A link that cannot serve says so, whatever the reason.
 */
final class ChoosePasswordForm extends Component
{
    /** Tries allowed per visitor and per link before the form waits. */
    private const MAX_ATTEMPTS = 5;

    #[Locked]
    public int $account = 0;

    #[Locked]
    public string $token = '';

    #[Locked]
    public string $purpose = 'reset';

    /** Whether the link still serves · read when the page opens. */
    #[Locked]
    public bool $usable = false;

    /** The address to sign in with, shown once the link serves. */
    #[Locked]
    public string $email = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    public function mount(int $account, string $token, string $purpose, PasswordChoiceService $choice): void
    {
        $this->account = $account;
        $this->token = $token;
        $this->purpose = $purpose === 'invitation' ? 'invitation' : 'reset';

        $holder = $choice->holderOf($account);

        $this->usable = $choice->serves($holder, $token, $this->purpose);
        $this->email = $this->usable && $holder !== null ? $holder->email : '';
    }

    /** @return array<string, array<int, mixed>> */
    protected function rules(): array
    {
        return [
            'password' => ['required', 'string', PasswordRule::min(12)],
            'passwordConfirmation' => ['required', 'same:password'],
        ];
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'password.required' => 'Choisissez un mot de passe.',
            'password.min' => 'Le mot de passe doit faire au moins 12 caractères.',
            'passwordConfirmation.required' => 'Confirmez le mot de passe.',
            'passwordConfirmation.same' => 'La confirmation ne correspond pas au mot de passe.',
        ];
    }

    /**
     * @throws ValidationException
     */
    public function choose(PasswordChoiceService $choice, SignInService $signIn): void
    {
        $this->assertIsNotRateLimited();
        $this->validate();

        $holder = $choice->holderOf($this->account);

        if ($holder === null || ! $choice->choose($holder, $this->token, $this->purpose, $this->password)) {
            RateLimiter::hit($this->throttleKey());
            $this->usable = false;

            return;
        }

        $signIn->signIn($holder);

        $this->redirect($signIn->home(), navigate: false);
    }

    public function render(): View
    {
        return view('livewire.auth.password.choose-password-form');
    }

    /**
     * @throws ValidationException
     */
    private function assertIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
            return;
        }

        throw ValidationException::withMessages([
            'password' => 'Trop de tentatives. Réessayez dans '.RateLimiter::availableIn($this->throttleKey()).' secondes.',
        ]);
    }

    private function throttleKey(): string
    {
        return 'choose-password|'.$this->account.'|'.request()->ip();
    }
}
