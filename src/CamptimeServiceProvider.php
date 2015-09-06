<?php

namespace Camptime;

use Illuminate\Support\ServiceProvider;

class CamptimeServiceProvider extends ServiceProvider
{
    /**
     * Array of commands to register with the service provider.
     *
     * @var array
     */
    protected $commands = [
        "Kimbia\Camptime\LogTimeCommand"
    ];

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->commands($this->commands);
    }
}
