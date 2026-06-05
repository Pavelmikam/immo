<?php

namespace App\Events;

use App\Models\Property;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PropertyRejected
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Property $property) {}
}
