<?php

namespace App\Scrapers;

use App\Models\Source;
use App\Scrapers\Adapters\ExampleSiteAdapter;
use Illuminate\Support\Facades\App;

class AdapterFactory
{
    /**
     * Resolve an adapter instance for the given source.
     */
    public static function make(Source $source): AdapterInterface
    {
        $adapterClass = $source->adapter_class ?: ExampleSiteAdapter::class;

        if (! class_exists($adapterClass)) {
            throw new \RuntimeException("Adapter class {$adapterClass} not found.");
        }

        $adapter = App::make($adapterClass);

        if (! $adapter instanceof AdapterInterface) {
            throw new \RuntimeException("Class {$adapterClass} must implement AdapterInterface.");
        }

        return $adapter;
    }
}
