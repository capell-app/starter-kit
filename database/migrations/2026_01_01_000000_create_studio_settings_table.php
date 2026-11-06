<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('studio_settings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('site_id')->nullable();
            $table->string('name', 100);
            $table->string('contact_email');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('studio_settings');
    }
};
