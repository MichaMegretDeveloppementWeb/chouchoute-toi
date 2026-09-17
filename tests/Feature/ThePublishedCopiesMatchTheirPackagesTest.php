<?php

declare(strict_types=1);

namespace Tests\Feature;

use FilesystemIterator;
use Illuminate\Support\ServiceProvider;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

/**
 * Les copies publiées correspondent aux paquets qui les livrent.
 *
 * Un paquet de la suite compile sa feuille et son script, et les livre déjà
 * compilés. Cette application en publie une copie sous `public/vendor/` et sert
 * celle-là — et **elle les enregistre dans le dépôt**, parce que son
 * déploiement ne republie pas.
 *
 * **C'est un choix, et il se paie par cet essai.** La règle de la suite le dit :
 * un hôte ne commite pas les copies publiées par défaut, et s'il le fait, une
 * chaîne de contrôle vérifie qu'elles correspondent aux paquets installés.
 * Jamais un oubli rendu impossible « par convention ».
 *
 * **Le défaut qu'il attrape.** On modifie une vue d'un paquet, on reconstruit le
 * paquet, on commite — et on oublie de republier ici. La copie enregistrée est
 * alors plus vieille que le fichier livré, elle part en production, et l'écran
 * sort avec les styles d'avant. Rien ne lève : un fichier présent et lisible ne
 * se plaint pas d'être périmé.
 *
 * Le garde d'exécution du kit couvre le même défaut au rendu, mais seulement là
 * où les deux fichiers existent. Celui-ci le voit **avant** le commit.
 */
final class ThePublishedCopiesMatchTheirPackagesTest extends TestCase
{
    public function test_every_published_copy_matches_the_package_that_ships_it(): void
    {
        $checked = 0;

        foreach ($this->publishedPairs() as $source => $target) {
            foreach ($this->filesUnder($source) as $relative) {
                $published = $target.DIRECTORY_SEPARATOR.$relative;

                $this->assertFileExists(
                    $published,
                    $this->shortPath($published)." est absent.\n"
                    .'Lancez `php artisan vendor:publish --tag=laravel-assets --force`, '
                    .'puis enregistrez `public/vendor/` avec votre modification.',
                );

                $this->assertSame(
                    hash_file('xxh128', $source.DIRECTORY_SEPARATOR.$relative),
                    hash_file('xxh128', $published),
                    $this->shortPath($published)." ne correspond plus au fichier que le paquet livre.\n"
                    ."Le paquet a été reconstruit et cette copie ne l'a pas suivi.\n"
                    .'Lancez `php artisan vendor:publish --tag=laravel-assets --force`, '
                    .'puis enregistrez `public/vendor/` avec votre modification.',
                );

                $checked++;
            }
        }

        $this->assertGreaterThan(
            0,
            $checked,
            'Aucun fichier comparé · le contrôle ne prouve rien dans cet état.',
        );
    }

    /**
     * Aucune copie orpheline, c'est-à-dire qu'aucun paquet ne livre plus.
     *
     * Un fichier renommé — une entrée qui change de nom, un morceau nommé par
     * son contenu — laisse l'ancien derrière lui. Il n'est plus servi par
     * personne, mais il part quand même, et il finit par faire croire qu'il
     * sert encore.
     */
    public function test_no_published_copy_is_an_orphan(): void
    {
        foreach ($this->publishedPairs() as $source => $target) {
            if (! is_dir($target)) {
                continue;
            }

            foreach ($this->filesUnder($target) as $relative) {
                $this->assertFileExists(
                    $source.DIRECTORY_SEPARATOR.$relative,
                    $this->shortPath($target.DIRECTORY_SEPARATOR.$relative)
                    ." n'est plus livré par son paquet.\n"
                    .'Supprimez-le · une copie que plus personne ne sert reste servie par le serveur.',
                );
            }
        }
    }

    /**
     * Les couples « ce qu'un paquet livre » → « où cette application le publie »,
     * tels que les fournisseurs les déclarent eux-mêmes.
     *
     * Lus dans le registre du cadre plutôt que devinés : c'est le paquet qui
     * décide où sa copie atterrit, et le nom du dossier publié ne suit pas
     * toujours celui du paquet.
     *
     * @return array<string, string>
     */
    private function publishedPairs(): array
    {
        $pairs = ServiceProvider::pathsToPublish(null, 'laravel-assets');

        $this->assertNotSame([], $pairs, 'Aucun paquet ne déclare de fichiers à publier.');

        return array_filter(
            $pairs,
            // Un paquet absent de cette installation n'a rien à prouver ici.
            static fn (string $target, string $source): bool => is_dir($source),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    /**
     * @return list<string>
     */
    private function filesUnder(string $directory): array
    {
        $found = [];

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        );

        foreach ($files as $file) {
            $found[] = str_replace(
                $directory.DIRECTORY_SEPARATOR,
                '',
                (string) $file->getPathname(),
            );
        }

        sort($found);

        return $found;
    }

    private function shortPath(string $path): string
    {
        return str_replace(base_path().DIRECTORY_SEPARATOR, '', $path);
    }
}
