<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

final class ContactData extends Data
{
    public function __construct(public string $name, public string $email, public string $message) {}
}
