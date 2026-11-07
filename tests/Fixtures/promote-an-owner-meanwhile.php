<?php

/**
 * A second process for TheOwnersAreHeldWhileAnAccountChangesTest: it holds an
 * owner, promotes another account to owner, says so through a witness file,
 * waits, then stamps the instant and commits.
 *
 * Plain PDO and nothing else, so it starts in a fraction of a second. The
 * database comes from the environment the test hands it.
 */

declare(strict_types=1);

[, $heldOwner, $promoted, $witness] = $_SERVER['argv'];

$pdo = new PDO(
    sprintf('mysql:host=%s;port=%s;dbname=%s', getenv('CHILD_DB_HOST'), getenv('CHILD_DB_PORT'), getenv('CHILD_DB_DATABASE')),
    (string) getenv('CHILD_DB_USERNAME'),
    (string) getenv('CHILD_DB_PASSWORD'),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
);

$pdo->beginTransaction();

$pdo->prepare('select id from admins where id = ? for update')->execute([$heldOwner]);
$pdo->prepare('update admins set role = ? where id = ?')->execute(['owner', $promoted]);

file_put_contents($witness.'.locked', (string) microtime(true));

// Long enough for the parent to start its write and queue behind the lock.
usleep(2_000_000);

// Stamped before the commit: the write cannot end before it, whereas a stamp
// taken after it can land past the end of a write that did wait.
file_put_contents($witness.'.committing', (string) microtime(true));

$pdo->commit();

file_put_contents($witness.'.committed', '');
