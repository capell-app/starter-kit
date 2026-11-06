<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Str;
use RuntimeException;

final class CreateStarterAdministratorAction
{
    public function __construct(private FilesystemManager $filesystems) {}

    public function handle(string $email): bool
    {
        if (User::query()->where('email', $email)->exists()) {
            return false;
        }
        $password = Str::password(24);
        $disk = $this->filesystems->disk('local');
        throw_unless($disk->put('starter-admin-password', $password, ['visibility' => 'private']), RuntimeException::class, 'Cannot securely save the administrator password. No account was created.');
        throw_unless($disk->setVisibility('starter-admin-password', 'private'), RuntimeException::class, 'Cannot secure the administrator password. No account was created.');
        $admin = User::query()->create(['name' => 'Studio administrator', 'email' => $email, 'password' => $password]);
        $admin->assignRole('super_admin');

        return true;
    }
}
