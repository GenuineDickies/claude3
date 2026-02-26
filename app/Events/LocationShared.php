<?php

namespace App\Events;

use App\Models\ServiceRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LocationShared
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public ServiceRequest $serviceRequest,
        public float $latitude,
        public float $longitude,
    ) {}
}
