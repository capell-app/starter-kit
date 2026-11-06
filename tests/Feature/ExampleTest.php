<?php

declare(strict_types=1);

it('serves the authored stylesheet without a build server', function (): void {
    expect(file_get_contents(public_path('css/starter.css')))->toContain('--paper:', '@media');
});
