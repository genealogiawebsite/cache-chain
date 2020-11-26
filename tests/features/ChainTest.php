<?php

use Illuminate\Contracts\Cache\Store;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;
use LaravelEnso\CacheChain\Exceptions\Chain as Exception;

class ChainTest extends TestCase
{
    /** @test */
    public function should_cache_on_all_configured_adapters()
    {
        $adapters = ['array', 'file'];
        Cache::store('chain')->adapters($adapters);

        $this->assertTrue(Cache::store('chain')->put('foo', 'bar'));

        Collection::wrap($adapters)->each(fn ($adapter) => $this
            ->assertEquals('bar', Cache::store($adapter)->get('foo')));
    }

    /** @test */
    public function should_get_from_first_layer_when_available()
    {
        Cache::store('chain')->adapters(['array', Mockery::mock(Store::class)]);

        $this->assertTrue(Cache::store('array')->put('foo', 'bar'));

        $this->assertEquals('bar', Cache::store('chain')->get('foo'));
    }

    /** @test */
    public function should_get_from_superior_layer_when_first_not_available()
    {
        Cache::store('chain')->adapters([Mockery::spy(Store::class), 'array']);

        $this->assertTrue(Cache::store('array')->put('foo', 'bar'));

        $this->assertEquals('bar', Cache::store('chain')->get('foo'));
    }

    /** @test */
    public function should_cache_inferior_layers_on_get_when_superior_exists()
    {
        Cache::store('chain')->adapters(['array', 'file']);

        Cache::store('file')->put('foo', 'bar');

        $this->assertNull(Cache::store('array')->get('foo'));
        $this->assertEquals('bar', Cache::store('chain')->get('foo'));
        $this->assertEquals('bar', Cache::store('array')->get('foo'));
    }

    /** @test */
    public function can_flush()
    {
        $adapters = ['file', 'array'];
        Cache::store('chain')->adapters($adapters);

        Cache::store('chain')->put('foo', 'bar');
        Cache::store('chain')->put('bar', 'foo');
        $this->assertTrue(Cache::store('chain')->flush());

        Collection::wrap($adapters)->each(fn ($adapter) => tap($this)
            ->assertFalse(Cache::store($adapter)->has('foo'))
            ->assertFalse(Cache::store($adapter)->has('bar')));
    }

    /** @test */
    public function can_forget()
    {
        $adapters = ['file', 'array'];
        Cache::store('chain')->adapters($adapters);

        Cache::store('chain')->put('foo', 'bar');
        Cache::store('chain')->put('bar', 'foo');
        $this->assertTrue(Cache::store('chain')->forget('foo'));

        Collection::wrap($adapters)->each(fn ($adapter) => tap($this)
            ->assertFalse(Cache::store($adapter)->has('foo'))
            ->assertTrue(Cache::store($adapter)->has('bar')));
    }

    /** @test */
    public function can_increment()
    {
        $adapters = ['file', 'array'];
        Cache::store('chain')->adapters($adapters);

        Cache::store('chain')->put('number', 1);
        $this->assertEquals(3, Cache::store('chain')->increment('number', 2));

        Collection::wrap($adapters)->each(fn ($adapter) => $this
            ->assertEquals(3, Cache::store($adapter)->get('number')));
    }

    /** @test */
    public function can_decrement()
    {
        $adapters = ['file', 'array'];
        Cache::store('chain')->adapters($adapters);

        Cache::store('chain')->put('number', 3);
        $this->assertEquals(1, Cache::store('chain')->decrement('number', 2));

        Collection::wrap($adapters)->each(fn ($adapter) => $this
            ->assertEquals(1, Cache::store($adapter)->get('number')));
    }

    /** @test */
    public function should_throw_exception_with_empty_adapters()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(Exception::adapters()->getMessage());
        Cache::store('chain')->adapters([]);
    }

    protected function tearDown(): void
    {
        Cache::store('file')->flush();

        parent::tearDown();
    }
}
