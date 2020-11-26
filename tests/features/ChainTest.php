<?php

use Illuminate\Contracts\Cache\Store;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ChainTest extends TestCase
{
    /** @test */
    public function should_cache_on_all_configured_adapters()
    {
        $adapters = ['array', 'file'];
        $this->setAdapters($adapters);

        Cache::store('chain')->put('foo', 'bar');

        Collection::wrap($adapters)->each(fn ($adapter) => $this
            ->assertEquals('bar', Cache::store($adapter)->get('foo')));
    }

    /** @test */
    public function should_get_from_first_layer_when_available()
    {
        $this->setAdapters(['array', Mockery::mock(Store::class)]);

        Cache::store('array')->put('foo', 'bar');

        $this->assertEquals('bar', Cache::store('chain')->get('foo'));
    }

    /** @test */
    public function should_get_from_superior_layer_when_first_not_available()
    {
        $this->setAdapters([Mockery::spy(Store::class), 'array']);

        Cache::store('array')->put('foo', 'bar');

        $this->assertEquals('bar', Cache::store('chain')->get('foo'));
    }

    /** @test */
    public function should_cache_inferior_layers_on_get_when_superior_exists()
    {
        $this->setAdapters(['array', 'file']);

        Cache::store('file')->put('foo', 'bar');

        $this->assertNull(Cache::store('array')->get('foo'));
        $this->assertEquals('bar', Cache::store('chain')->get('foo'));
        $this->assertEquals('bar', Cache::store('array')->get('foo'));
    }

    /** @test */
    public function can_flush()
    {
        $adapters = ['file', 'array'];
        $this->setAdapters($adapters);

        Cache::store('chain')->put('foo', 'bar');
        Cache::store('chain')->put('bar', 'foo');
        Cache::store('chain')->flush();

        Collection::wrap($adapters)->each(fn ($adapter) => tap($this)
            ->assertFalse(Cache::store($adapter)->has('foo'))
            ->assertFalse(Cache::store($adapter)->has('bar')));
    }

    /** @test */
    public function can_forget()
    {
        $adapters = ['file', 'array'];
        $this->setAdapters($adapters);

        Cache::store('chain')->put('foo', 'bar');
        Cache::store('chain')->put('bar', 'foo');
        Cache::store('chain')->forget('foo');

        Collection::wrap($adapters)->each(fn ($adapter) => tap($this)
            ->assertFalse(Cache::store($adapter)->has('foo'))
            ->assertTrue(Cache::store($adapter)->has('bar')));
    }

    /** @test */
    public function can_increment()
    {
        $adapters = ['file', 'array'];
        $this->setAdapters($adapters);

        Cache::store('chain')->put('number', 1);
        Cache::store('chain')->increment('number', 2);

        Collection::wrap($adapters)->each(fn ($adapter) => $this
            ->assertEquals(3, Cache::store($adapter)->get('number')));
    }

    /** @test */
    public function can_decrement()
    {
        $adapters = ['file', 'array'];
        $this->setAdapters($adapters);

        Cache::store('chain')->put('number', 3);
        Cache::store('chain')->decrement('number', 2);

        Collection::wrap($adapters)->each(fn ($adapter) => $this
            ->assertEquals(1, Cache::store($adapter)->get('number')));
    }

    protected function tearDown(): void
    {
        Cache::store('file')->flush();

        parent::tearDown();
    }

    private function setAdapters($adapters): void
    {
        Config::set('cache.stores.chain.adapters', $adapters);
    }
}
