<?php

declare(strict_types=1);

namespace Tests\Feature\Shared\Cache;

use App\Services\Shared\Cache\ExpiredCacheService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Lottery;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The cache table loses its expired rows as the site is visited · the framework
 * forgets a row only when its own key is read again, so a key never asked twice
 * would stay for good.
 */
final class TheExpiredCacheIsForgottenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'database']);

        Cache::put('expired', 1, now()->addMinute());
        Cache::put('valid', 1, now()->addHour());
        Cache::forever('forever', 1);

        $this->travel(10)->minutes();
    }

    public function test_a_pass_forgets_the_expired_rows_and_keeps_the_others(): void
    {
        $forgotten = app(ExpiredCacheService::class)->forgetExpired();

        $this->assertSame(1, $forgotten);
        $this->assertSame(['forever', 'valid'], $this->keysLeft());
    }

    public function test_a_pass_is_one_statement_however_many_rows_expired(): void
    {
        $this->assertSame([1, 1], [$this->statementsToForget(3), $this->statementsToForget(30)]);
    }

    public function test_a_pass_touches_nothing_when_the_cache_lives_elsewhere(): void
    {
        config(['cache.default' => 'array']);

        $this->assertSame(0, app(ExpiredCacheService::class)->forgetExpired());
        $this->assertSame(['expired', 'forever', 'valid'], $this->keysLeft());
    }

    public function test_a_visit_that_wins_the_draw_forgets_the_expired_rows(): void
    {
        Lottery::alwaysWin();

        $this->get('/up')->assertOk();

        $this->assertSame(['forever', 'valid'], $this->keysLeft());
    }

    public function test_a_visit_that_loses_the_draw_forgets_nothing(): void
    {
        Lottery::alwaysLose();

        $this->get('/up')->assertOk();

        $this->assertSame(['expired', 'forever', 'valid'], $this->keysLeft());
    }

    public function test_a_pass_that_fails_is_reported_and_never_reaches_the_page(): void
    {
        Exceptions::fake();
        Lottery::alwaysWin();
        config(['cache.stores.database.table' => 'no_such_cache_table']);

        $this->get('/up')->assertOk();

        Exceptions::assertReported(QueryException::class);
    }

    /** The statements a pass sends once so many more rows have expired. */
    private function statementsToForget(int $expired): int
    {
        for ($row = 0; $row < $expired; $row++) {
            Cache::put("expired-{$row}", 1, now()->addMinute());
        }

        $this->travel(2)->minutes();

        $statements = 0;
        DB::listen(static function () use (&$statements): void {
            $statements++;
        });

        app(ExpiredCacheService::class)->forgetExpired();

        return $statements;
    }

    /**
     * The rows still in the table, read without going through the store, which
     * would forget an expired row on reading it.
     *
     * @return list<string>
     */
    private function keysLeft(): array
    {
        /** @var list<string> $keys */
        $keys = DB::table('cache')->orderBy('key')->pluck('key')->all();

        return array_map(static fn (string $key): string => Str::chopStart($key, config()->string('cache.prefix')), $keys);
    }
}
