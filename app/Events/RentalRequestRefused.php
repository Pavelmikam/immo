<?php

namespace App\Events;

use App\Models\RentalRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RentalRequestRefused
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly RentalRequest $rentalRequest) {}
}
