import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { capellViteInputs } from './vendor/capell-app/frontend/resources/js/capell-vite-inputs.js';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                ...capellViteInputs(),
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
