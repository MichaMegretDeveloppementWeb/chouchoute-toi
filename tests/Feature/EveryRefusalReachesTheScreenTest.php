<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Admin\Profile\UpdatePasswordForm;
use App\Livewire\Admin\Profile\UpdateProfileForm;
use App\Livewire\Auth\Password\ChoosePasswordForm;
use App\Livewire\Auth\Password\ForgotPasswordForm;
use App\Livewire\Auth\SignIn\LoginForm;
use App\Livewire\ContactForm;
use App\Models\Admin;
use App\Services\Auth\Password\PasswordLinksService;
use Carbon\CarbonImmutable;
use Closure;
use Dom\HTMLDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Component;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use PhpToken;
use PHPUnit\Framework\Attributes\DataProvider;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionMethod;
use Tests\Fixtures\Livewire\AFormRefusingByHand;
use Tests\TestCase;

use function Livewire\on;

/**
 * Every refusal a form of the site raises is written on its page · a field
 * refused with nothing said leaves its reader guessing why.
 *
 * Each field is refused alone, as a save refuses it · a rule's field, and a
 * field a form refuses by hand.
 */
final class EveryRefusalReachesTheScreenTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Refusals only a forged request reaches · the three lists of the contact
     * form offer fixed choices, and none is as long as their limit.
     *
     * @var array<class-string, list<string>>
     */
    private const array ONLY_A_FORGED_REQUEST = [
        ContactForm::class => ['town', 'volume', 'service'],
    ];

    /**
     * Each form of the site, and how its page opens it.
     *
     * @return array<string, array{0: class-string<Component>, 1: Closure(): Testable<Component>}>
     */
    public static function provideForms(): array
    {
        return [
            'contact' => [ContactForm::class, fn (): Testable => Livewire::test(ContactForm::class)],
            'signing in' => [LoginForm::class, fn (): Testable => Livewire::test(LoginForm::class)],
            'a forgotten password' => [ForgotPasswordForm::class, fn (): Testable => Livewire::test(ForgotPasswordForm::class)],
            'choosing a password' => [ChoosePasswordForm::class, fn (): Testable => self::aPasswordToChoose()],
            'the profile' => [UpdateProfileForm::class, fn (): Testable => Livewire::actingAs(Admin::factory()->create(), 'admin')->test(UpdateProfileForm::class)],
            'the password' => [UpdatePasswordForm::class, fn (): Testable => Livewire::actingAs(Admin::factory()->create(), 'admin')->test(UpdatePasswordForm::class)],
        ];
    }

    /** Every form that declares rules is checked here · one added tomorrow would be refused unseen. */
    public function test_every_form_that_declares_rules_is_checked(): void
    {
        $checked = array_map(static fn (array $case): string => $case[0], self::provideForms());
        $declaring = [];
        $source = app_path('Livewire');

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source)) as $file) {
            if (! str_ends_with($file->getFilename(), '.php')) {
                continue;
            }

            $class = 'App\\Livewire\\'.str_replace(['/', '\\'], '\\', substr($file->getPathname(), strlen($source) + 1, -4));

            if (class_exists($class) && ! (new ReflectionClass($class))->isAbstract() && method_exists($class, 'rules')) {
                $declaring[] = $class;
            }
        }

        $this->assertNotSame([], $declaring, 'The scan found no form: it reads nothing.');
        $this->assertSame([], array_values(array_diff($declaring, $checked)), 'These forms declare rules nobody checks.');
    }

    /**
     * The scan of the refusals raised by hand reads what a form raises · sounded
     * on forms known to raise one, and on a form whose trait raises one and whose
     * comment only quotes another.
     */
    public function test_the_refusals_raised_by_hand_are_read(): void
    {
        $this->assertSame(['email'], $this->keysRaisedByHand(LoginForm::class));
        $this->assertSame(['password'], $this->keysRaisedByHand(ChoosePasswordForm::class));
        $this->assertEqualsCanonicalizing(['raisedHere', 'raisedByTheTrait'], $this->keysRaisedByHand(AFormRefusingByHand::class));
    }

    /**
     * @param  class-string<Component>  $component
     * @param  Closure(): Testable<Component>  $open
     */
    #[DataProvider('provideForms')]
    public function test_every_refusal_reaches_the_screen(string $component, Closure $open): void
    {
        $unseen = [];

        foreach ($this->fieldsRefused($component, $open) as $field) {
            if (! str_contains($this->textOf($open(), $field, 'Refus ['.$field.'].'), 'Refus ['.$field.'].')) {
                $unseen[] = $field;
            }
        }

        $this->assertSame(self::ONLY_A_FORGED_REQUEST[$component] ?? [], $unseen, 'The refusals off the page are not the ones listed.');
    }

    /** The page of an invitation, opened by its link. */
    private static function aPasswordToChoose(): Testable
    {
        $member = Admin::factory()->collaborator()->create([
            'email_verified_at' => null,
            'invited_at' => CarbonImmutable::now(),
        ]);

        $token = app(PasswordLinksService::class)->links(PasswordLinksService::INVITATIONS)->createToken($member);

        return Livewire::test(ChoosePasswordForm::class, ['account' => $member->id, 'token' => $token, 'purpose' => 'invitation']);
    }

    /**
     * Every field a form refuses · those of its rules, and those it refuses by hand.
     *
     * @param  class-string<Component>  $component
     * @param  Closure(): Testable<Component>  $open
     * @return list<string>
     */
    private function fieldsRefused(string $component, Closure $open): array
    {
        $rules = (new ReflectionMethod($component, 'rules'))->invoke($open()->instance());
        $this->assertIsArray($rules);

        $fields = array_map(static fn (int|string $field): string => (string) $field, array_keys($rules));

        return array_values(array_unique([...$fields, ...$this->keysRaisedByHand($component)]));
    }

    /**
     * The fields a form refuses by hand, as its code writes them · in the class,
     * its parents and every trait they use, the site's own files only, read
     * without their comments.
     *
     * @param  class-string<Component>  $component
     * @return list<string>
     */
    private function keysRaisedByHand(string $component): array
    {
        $keys = [];

        foreach ($this->ownFilesOf($component) as $file) {
            $code = $this->codeOf($file);

            preg_match_all('/withMessages\(\[(.*?)\]\)/s', $code, $raised);

            foreach ($raised[1] as $messages) {
                preg_match_all("/'([^']+)'\s*=>/", $messages, $named);
                array_push($keys, ...$named[1]);
            }

            preg_match_all("/addError\(\s*'([^']+)'/", $code, $added);
            array_push($keys, ...$added[1]);
        }

        return array_values(array_unique($keys));
    }

    /**
     * The files of a component the site writes · the class, its parents and the
     * traits they use, a trait's own traits included.
     *
     * @param  class-string  $class
     * @return list<string>
     */
    private function ownFilesOf(string $class): array
    {
        $files = [];
        $pending = [new ReflectionClass($class)];

        while (($reflection = array_shift($pending)) !== null) {
            $files[] = (string) realpath((string) $reflection->getFileName());
            $pending = [...$pending, ...array_values($reflection->getTraits())];

            if ($reflection->getParentClass() !== false) {
                $pending[] = $reflection->getParentClass();
            }
        }

        $own = [(string) realpath(app_path()), (string) realpath(base_path('tests'))];

        return array_values(array_filter(
            array_unique($files),
            static fn (string $file): bool => str_starts_with($file, $own[0]) || str_starts_with($file, $own[1]),
        ));
    }

    /** A file's code without its comments · a comment that quotes a refusal raises none. */
    private function codeOf(string $file): string
    {
        $code = '';

        foreach (PhpToken::tokenize((string) file_get_contents($file)) as $token) {
            $code .= $token->is([T_COMMENT, T_DOC_COMMENT]) ? '' : $token->text;
        }

        return $code;
    }

    /**
     * The text of the page drawn with this field refused · laid while it draws,
     * on the bag the component shares with its view.
     *
     * @param  Testable<Component>  $form
     */
    private function textOf(Testable $form, string $field, string $message): string
    {
        $component = $form->instance()::class;

        $stop = on('render', static function (Component $drawn) use ($component, $field, $message): void {
            if ($drawn instanceof $component) {
                $drawn->addError($field, $message);
            }
        });

        try {
            $html = $form->call('$refresh')->html();
        } finally {
            $stop();
        }

        return (string) HTMLDocument::createFromString($html, LIBXML_NOERROR)->body?->textContent;
    }
}
