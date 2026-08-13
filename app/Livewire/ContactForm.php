<?php

namespace App\Livewire;

use App\Mail\ContactMail;
use Falcon\Analytics\Facades\Analytics;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Component;

class ContactForm extends Component
{
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $commune = '';

    public string $volume = '';

    public string $prestation = '';

    public string $message = '';

    public bool $sent = false;

    /** @return array<string, string|array<string>> */
    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'commune' => 'nullable|string|max:100',
            'volume' => 'nullable|string|max:100',
            'prestation' => 'nullable|string|max:100',
            'message' => 'nullable|string|max:2000',
        ];
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'name.required' => 'Veuillez indiquer votre nom.',
            'name.max' => 'Le nom ne doit pas dépasser 100 caractères.',
            'email.required' => 'Veuillez indiquer votre adresse email.',
            'email.email' => 'Veuillez entrer une adresse email valide.',
            'phone.required' => 'Veuillez indiquer votre numéro de téléphone.',
            'phone.max' => 'Le numéro de téléphone ne doit pas dépasser 20 caractères.',
            'message.max' => 'Le message ne doit pas dépasser 2000 caractères.',
        ];
    }

    public function mount(): void
    {
        $volume = request()->query('volume');

        if ($volume && $this->isValidVolume($volume)) {
            $this->volume = $volume;
        }
    }

    public function updatedVolume(): void
    {
        $this->prestation = '';
    }

    public function getPrestationOptionsProperty(): array
    {
        if (! $this->volume || $this->volume === 'indecise') {
            return [];
        }

        if ($this->volume === 'depose') {
            return [['value' => 'depose', 'label' => 'Dépose']];
        }

        $categories = config('tarifs.categories');
        $categorie = $categories[$this->volume] ?? null;

        if (! $categorie) {
            return [];
        }

        $options = [['value' => 'pose-complete', 'label' => 'Pose complète']];

        foreach ($categorie['remplissages'] as $remplissage) {
            $options[] = [
                'value' => Str::slug($remplissage['nom']),
                'label' => $remplissage['nom'],
            ];
        }

        return $options;
    }

    public function send(): void
    {
        $this->validate();

        Mail::to('dc.amandine@gmail.com')->send(new ContactMail([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'commune' => $this->commune,
            'volume' => $this->volume,
            'prestation' => $this->prestation,
            'message' => $this->message,
        ]));

        // Recorded here rather than on the click: only the server knows the mail
        // actually left. Deferred, no-op for excluded traffic, never throws.
        Analytics::record('contact.request.submitted', props: [
            'volume' => $this->volume ?: null,
            'prestation' => $this->prestation ?: null,
            'commune' => $this->commune ?: null,
        ]);

        $this->reset(['name', 'email', 'phone', 'commune', 'volume', 'prestation', 'message']);
        $this->sent = true;
    }

    public function resetForm(): void
    {
        $this->sent = false;
    }

    public function render()
    {
        return view('livewire.contact-form');
    }

    private function isValidVolume(string $volume): bool
    {
        $valid = array_keys(config('tarifs.categories'));
        $valid[] = 'depose';
        $valid[] = 'indecise';

        return in_array($volume, $valid);
    }
}
