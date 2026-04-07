<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HealthCheckController extends Controller
{
    public function __invoke(Request $request)
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'app_key' => $this->checkAppKey(),
            'storage' => $this->checkStorage(),
            'php_extensions' => $this->checkPhpExtensions(),
            'migrations' => $this->checkMigrations(),
        ];

        $allPassed = collect($checks)->every(fn (array $check) => $check['passed']);

        // JSON response for automated health checks (Docker, load balancers)
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'healthy' => $allPassed,
                'checks' => $checks,
            ], $allPassed ? 200 : 503);
        }

        return view('health-check', [
            'checks' => $checks,
            'allPassed' => $allPassed,
        ]);
    }

    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();

            return [
                'passed' => true,
                'label' => 'Database Connection',
                'message' => 'Connected to '.config('database.default').' database.',
            ];
        } catch (\Throwable $e) {
            return [
                'passed' => false,
                'label' => 'Database Connection',
                'message' => 'Cannot connect to database: '.$e->getMessage(),
                'fix' => 'Check DB_CONNECTION, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, and DB_PASSWORD in your .env file. Make sure the database server is running.',
            ];
        }
    }

    private function checkAppKey(): array
    {
        $key = config('app.key');

        if (empty($key)) {
            return [
                'passed' => false,
                'label' => 'Application Key',
                'message' => 'APP_KEY is not set.',
                'fix' => 'Run: php artisan key:generate',
            ];
        }

        return [
            'passed' => true,
            'label' => 'Application Key',
            'message' => 'APP_KEY is configured.',
        ];
    }

    private function checkStorage(): array
    {
        $paths = [
            storage_path('app'),
            storage_path('framework/cache'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('logs'),
        ];

        $unwritable = [];
        foreach ($paths as $path) {
            if (! is_dir($path) || ! is_writable($path)) {
                $unwritable[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $path);
            }
        }

        if (! empty($unwritable)) {
            return [
                'passed' => false,
                'label' => 'Storage Directories',
                'message' => 'These directories are not writable: '.implode(', ', $unwritable),
                'fix' => 'Run: chmod -R 775 storage bootstrap/cache && chown -R www-data:www-data storage bootstrap/cache',
            ];
        }

        return [
            'passed' => true,
            'label' => 'Storage Directories',
            'message' => 'All storage directories are writable.',
        ];
    }

    private function checkPhpExtensions(): array
    {
        $required = ['pdo', 'mbstring', 'xml', 'curl', 'zip', 'gd', 'bcmath'];
        $missing = [];

        foreach ($required as $ext) {
            if (! extension_loaded($ext)) {
                $missing[] = $ext;
            }
        }

        if (! empty($missing)) {
            return [
                'passed' => false,
                'label' => 'PHP Extensions',
                'message' => 'Missing extensions: '.implode(', ', $missing),
                'fix' => 'Install the missing PHP extensions. On Ubuntu: sudo apt install '.implode(' ', array_map(fn ($ext) => "php8.3-{$ext}", $missing)),
            ];
        }

        return [
            'passed' => true,
            'label' => 'PHP Extensions',
            'message' => 'All required PHP extensions are installed (PHP '.PHP_VERSION.').',
        ];
    }

    private function checkMigrations(): array
    {
        try {
            if (! Schema::hasTable('migrations')) {
                return [
                    'passed' => false,
                    'label' => 'Database Migrations',
                    'message' => 'Migrations have not been run yet.',
                    'fix' => 'Run: php artisan migrate --seed',
                ];
            }

            return [
                'passed' => true,
                'label' => 'Database Migrations',
                'message' => 'Migrations table exists.',
            ];
        } catch (\Throwable) {
            return [
                'passed' => false,
                'label' => 'Database Migrations',
                'message' => 'Cannot check migrations (database unreachable).',
                'fix' => 'Fix the database connection first, then run: php artisan migrate --seed',
            ];
        }
    }
}
