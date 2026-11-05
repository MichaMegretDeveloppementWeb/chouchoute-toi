<x-layout.admin-guest :title="$purpose === 'invitation' ? 'Bienvenue' : 'Nouveau mot de passe'">
    <livewire:admin.auth.choose-password-form :account="$account" :token="$token" :purpose="$purpose" />
</x-layout.admin-guest>
