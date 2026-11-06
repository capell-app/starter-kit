<?php

declare(strict_types=1);

$path = dirname(__DIR__).'/composer.json';
$composer = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
$changed = false;
foreach ($composer['require'] as $package => $constraint) {
    if (! str_starts_with($package, 'capell-app/')) {
        continue;
    }
    $response = file_get_contents('https://repo.packagist.org/p2/'.$package.'.json');
    if ($response === false) {
        throw new RuntimeException('Cannot read released versions for '.$package);
    }
    $releases = json_decode($response, true, flags: JSON_THROW_ON_ERROR)['packages'][$package];
    $versions = array_column($releases, 'version');
    $versions = array_values(array_filter($versions, fn (string $version): bool => preg_match('/^v?[0-9]+\.[0-9]+\.[0-9]+$/', $version) === 1));
    usort($versions, fn (string $left, string $right): int => version_compare(ltrim($right, 'v'), ltrim($left, 'v')));
    if ($versions === []) {
        throw new RuntimeException('No stable release for '.$package);
    }
    $latest = ltrim($versions[0], 'v');
    if ($constraint !== $latest) {
        fwrite(STDOUT, $package.': '.$constraint.' → '.$latest.PHP_EOL);
        $composer['require'][$package] = $latest;
        $changed = true;
    }
}
if (in_array('--write', $argv, true)) {
    file_put_contents($path, json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
} elseif ($changed) {
    fwrite(STDERR, "Run php scripts/pin-capell-release.php --write and composer update to verify the latest release.\n");
    exit(1);
}
