<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class StudioSetting extends Model
{
    protected $fillable = ['name', 'contact_email', 'site_id'];
}
