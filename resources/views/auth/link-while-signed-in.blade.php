<x-layout.auth :title="$purpose === 'invitation' ? 'Bienvenue' : 'Nouveau mot de passe'">
    <div class="space-y-4">
        <p class="text-[13px] text-secondary">
            Vous êtes connecté en tant que <span class="font-medium text-primary">{{ $name }}</span>.
            Pour ouvrir ce lien, déconnectez-vous d’abord.
        </p>

        <form method="POST" action="{{ route('auth.link.sign-out', ['purpose' => $purpose, 'account' => $account, 'token' => $token]) }}">
            @csrf
            <x-ui::button type="submit" class="w-full">Se déconnecter et ouvrir le lien</x-ui::button>
        </form>

        <x-ui::button variant="ghost" :href="route('admin.dashboard')" class="w-full">Rester connecté</x-ui::button>
    </div>
</x-layout.auth>
