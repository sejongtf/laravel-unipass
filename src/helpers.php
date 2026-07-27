<?php

declare(strict_types=1);

use Sejongtf\LaravelUnipass\Unipass;

if (! function_exists('unipass')) {
    /**
     * UNI-PASS 서비스 컨테이너 취득.
     */
    function unipass(): Unipass
    {
        return resolve('unipass');
    }
}
