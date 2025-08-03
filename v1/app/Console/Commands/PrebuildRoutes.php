<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PrebuildRoutes extends Command
{
    protected $signature = 'prebuild:routes';
    protected $description = 'Register routes/auth.php in RouteServiceProvider if it exists';

    public function handle()
    {
        $authRoutePath = base_path('routes/auth.php');
        if (!File::exists($authRoutePath)) {
            $this->error('❌ routes/auth.php does not exist. Nothing to do.');
            return 1;
        }

        $this->registerMiddlewaresInKernel();
        $this->addRouteToServiceProvider();


        return 0;
    }

    protected function addRouteToServiceProvider()
    {
        $providerPath = base_path('app/Providers/RouteServiceProvider.php');
        $content = File::get($providerPath);

        if (strpos($content, "routes/auth.php") !== false) {
            $this->info('ℹ️ routes/auth.php is already registered.');
            return;
        }

        $insert = <<<PHP

        // Auto-registered from artisan command
        if (file_exists(base_path('routes/auth.php'))) {
            Route::middleware(['web', 'check.auth', 'prevent.back.history'])
                ->group(base_path('routes/auth.php'));
        }
PHP;

        // Insert after web.php group
        $newContent = str_replace(
            "->group(base_path('routes/web.php'));",
            "->group(base_path('routes/web.php'));" . $insert,
            $content
        );

        File::put($providerPath, $newContent);

        $this->info('✅ routes/auth.php registered successfully in RouteServiceProvider.');
    }

    protected function registerMiddlewaresInKernel()
    {
        $kernelPath = base_path('app/Http/Kernel.php');

        if (!File::exists($kernelPath)) {
            $this->error("❌ Kernel.php file not found at: $kernelPath");
            return;
        }

        $content = File::get($kernelPath);
        $this->info("📄 Reading Kernel.php file...");

        $middlewareToAdd = [
            "'check.auth' => \App\Http\Middleware\CheckAuth::class,",
            "'prevent.back.history' => \App\Http\Middleware\PreventBackHistory::class,",
        ];

        foreach ($middlewareToAdd as $middleware) {
            if (strpos($content, $middleware) !== false) {
                $this->info("ℹ️ Middleware already registered: $middleware");
                continue;
            }

            // Try different patterns for Laravel versions
            $patterns = [
                // Laravel 8+ pattern
                '/(protected\s+\$routeMiddleware\s*=\s*\[)(.*?)(\];)/s',
                // Laravel 9+ pattern (might be $middlewareAliases)
                '/(protected\s+\$middlewareAliases\s*=\s*\[)(.*?)(\];)/s',
                // Alternative pattern
                '/(\$routeMiddleware\s*=\s*\[)(.*?)(\];)/s',
            ];

            $found = false;
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $content, $matches)) {
                    $before = $matches[1];
                    $middlewareList = $matches[2];
                    $after = $matches[3];

                    // Add the new middleware to the list
                    $newMiddlewareList = $middlewareList . "        $middleware\n";

                    // Reconstruct the content
                    $newContent = $before . $newMiddlewareList . $after;

                    // Replace the entire content
                    $content = preg_replace($pattern, $newContent, $content, 1);

                    // Write back to file
                    File::put($kernelPath, $content);
                    $this->info("✅ Registered middleware: $middleware");
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $this->error("❌ Could not find middleware array in Kernel.php");
                $this->error("Please check if the file has \$routeMiddleware or \$middlewareAliases array");

                // Show the current content for debugging
                $this->info("📄 Current Kernel.php content preview:");
                $lines = explode("\n", $content);
                foreach ($lines as $i => $line) {
                    if (strpos($line, '$routeMiddleware') !== false || strpos($line, '$middlewareAliases') !== false) {
                        $this->info("Line " . ($i + 1) . ": " . trim($line));
                    }
                }
            }
        }
    }
}
