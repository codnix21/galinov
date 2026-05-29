// Сборка фронтенда: CSS и JS из resources/ подключаются в Blade через @vite
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/properties-map.js',
            ],
            refresh: true,
        }),
    ],
});
