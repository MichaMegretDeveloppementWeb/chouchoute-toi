<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Les huit couches, déclarées dans l'ordre par nos deux feuilles.
 *
 * C'est le contrat qui fait cohabiter la feuille du kit, celle d'un paquet et
 * la nôtre : **la première déclaration que le document rencontre fixe l'ordre
 * pour toute la page**. Sur un écran où rien de la suite n'est chargé, notre
 * feuille est la seule à le dire — et une navigation sans rechargement peut y
 * amener un paquet ensuite, dans un document dont les couches sont déjà
 * établies. L'ordre y est alors acquis, et nos deux dernières couches doivent
 * déjà être les dernières.
 *
 * Le compilateur a le droit de découper la déclaration, et d'ajouter la sienne
 * pour les propriétés qu'il enregistre. Ce qui est lu ici est donc l'ordre
 * **relatif** des huit, qui est le contrat, et non la liste brute.
 *
 * Ce que cet essai tient tient à un détail de position : la ligne qui importe
 * la déclaration doit ouvrir l'entrée. Posée plus bas, le compilateur n'en
 * garde qu'une déclaration partielle et la place avant les couches qu'il
 * remplit lui-même.
 */
final class TheEightLayersKeepTheirOrderTest extends TestCase
{
    /** @var list<string> */
    private const CONTRACT = [
        'theme', 'base', 'ui-components', 'ui-utilities',
        'pkg-components', 'pkg-utilities', 'components', 'utilities',
    ];

    public function test_both_entries_declare_the_eight_layers_in_the_agreed_order(): void
    {
        foreach (['resources/css/admin.css', 'resources/css/web.css'] as $entry) {
            $this->assertSame(
                self::CONTRACT,
                array_values(array_intersect($this->layersDeclaredIn($entry), self::CONTRACT)),
                "L'ordre des couches de {$entry} n'est plus celui de la suite.\n"
                ."La ligne qui importe `layers.css` doit ouvrir le fichier, avant\n"
                ."`@import 'tailwindcss'` et avant tout import distant.",
            );
        }
    }

    /**
     * Les couches nommées par la feuille, dans l'ordre de leur première mention.
     *
     * @return list<string>
     */
    private function layersDeclaredIn(string $entry): array
    {
        preg_match_all(
            '/@layer\s+([a-z0-9, -]+?)\s*[{;]/',
            $this->compiledSheetFor($entry),
            $matches,
        );

        $seen = [];

        foreach ($matches[1] as $declaration) {
            foreach (explode(',', $declaration) as $layer) {
                $layer = trim($layer);

                if ($layer !== '' && ! in_array($layer, $seen, true)) {
                    $seen[] = $layer;
                }
            }
        }

        return $seen;
    }

    /** Le fichier produit pour une entrée, nommé par le manifeste du build. */
    private function compiledSheetFor(string $entry): string
    {
        $manifestPath = public_path('build/manifest.json');

        $this->assertFileExists($manifestPath, 'Aucun manifeste de build : lancez `npm run build`.');

        /** @var array<string, array{file?: string}> $manifest */
        $manifest = (array) json_decode((string) file_get_contents($manifestPath), true);

        $this->assertArrayHasKey($entry, $manifest, $entry.' n’est pas une entrée du build.');

        $file = $manifest[$entry]['file'] ?? '';

        $this->assertNotSame('', $file, 'Le manifeste ne nomme aucun fichier pour '.$entry.'.');

        $path = public_path('build/'.$file);

        $this->assertFileExists($path);

        return (string) file_get_contents($path);
    }
}
