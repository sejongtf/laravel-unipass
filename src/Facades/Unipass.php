<?php

declare(strict_types=1);

namespace Sejongtf\LaravelUnipass\Facades;

use Illuminate\Support\Facades\Facade;
use Sejongtf\LaravelUnipass\Unipass as UnipassManager;

/**
 * @method static \Sejongtf\LaravelUnipass\Resources\CargoClearance cargoClearance()
 * @method static \Sejongtf\LaravelUnipass\Resources\ExportFulfillment exportFulfillment()
 * @method static \Sejongtf\LaravelUnipass\Resources\SeaArrivalReport seaArrivalReport()
 * @method static \Sejongtf\LaravelUnipass\Resources\Container containers()
 * @method static \Sejongtf\LaravelUnipass\Contracts\Client client()
 *
 * @see \Sejongtf\LaravelUnipass\Unipass
 */
class Unipass extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return UnipassManager::class;
    }
}
