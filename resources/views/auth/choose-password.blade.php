<x-layout.auth :title="$purpose === 'invitation' ? 'Bienvenue' : 'Nouveau mot de passe'">
    <livewire:auth.password.choose-password-form :account="$account" :token="$token" :purpose="$purpose" />
</x-layout.auth>
