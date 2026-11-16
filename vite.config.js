import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { capellViteInputs } from './vendor/capell-app/frontend/resources/js/capell-vite-inputs.js';

const inputs = capellViteInputs();

if (inputs.length === 0) {
    throw new Error(
        'Capell has not generated the frontend stylesheet inputs yet. Run "php artisan capell:frontend-after-install --apply", then build again.',
    );
}

export default defineConfig({
    plugins: [
        laravel({
            input: inputs,
            refresh: true,
        }),
        tailwindcss(),
    ],
});
