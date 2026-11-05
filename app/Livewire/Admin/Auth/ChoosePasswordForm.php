<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Auth;

use App\Models\Admin;
use App\Services\Team\TeamMessagesService;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Chooses a password from a link · the invitation of a new member, or a
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

    public function mount(int $account, string $token, string $purpose): void
    {
        $this->account = $account;
        $this->token = $token;
        $this->purpose = $purpose === 'invitation' ? 'invitation' : 'reset';

        $holder = $this->holder();

        $this->usable = $holder !== null && $this->broker()->tokenExists($holder, $token);
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
            'password.required' => 'Veuillez choisir un mot de passe.',
            'password.min' => 'Le mot de passe doit faire au moins 12 caractères.',
            'passwordConfirmation.required' => 'Veuillez confirmer le mot de passe.',
            'passwordConfirmation.same' => 'La confirmation ne correspond pas au mot de passe.',
        ];
    }

    /**
     * @throws ValidationException
     */
    public function choose(): void
    {
        $this->assertIsNotRateLimited();
        $this->validate();

        $holder = $this->holder();
        $status = $holder === null ? PasswordBroker::INVALID_USER : $this->broker()->reset([
            'email' => $holder->email,
            'token' => $this->token,
            'password' => $this->password,
        ], static function (Admin $account, string $password): void {
            $account->forceFill([
                'password' => $password,
                'remember_token' => Str::random(60),
                'email_verified_at' => $account->email_verified_at ?? CarbonImmutable::now(),
            ])->save();
        });

        if ($holder === null || $status !== PasswordBroker::PASSWORD_RESET) {
            RateLimiter::hit($this->throttleKey());
            $this->usable = false;

            return;
        }

        Auth::guard('admin')->login($holder);
        app(Store::class)->regenerate();

        $this->redirect(route($holder->role->home()), navigate: false);
    }

    public function render(): View
    {
        return view('livewire.admin.auth.choose-password-form');
    }

    /** The account the link names, unless it was suspended since. */
    private function holder(): ?Admin
    {
        $user = Auth::createUserProvider('admins')?->retrieveById($this->account);

        return $user instanceof Admin ? $user : null;
    }

    private function broker(): PasswordBroker
    {
        return app(TeamMessagesService::class)->links(
            $this->purpose === 'invitation' ? TeamMessagesService::INVITATIONS : TeamMessagesService::PASSWORD_RESETS,
        );
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
            'password' => 'Trop de tentatives. Veuillez réessayer dans '.RateLimiter::availableIn($this->throttleKey()).' secondes.',
        ]);
    }

    private function throttleKey(): string
    {
        return 'choose-password|'.$this->account.'|'.request()->ip();
    }
}
