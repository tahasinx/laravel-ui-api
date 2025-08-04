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
        try {
            $authRoutePath = base_path('routes/auth.php');
            if (!File::exists($authRoutePath)) {
                $this->error('❌ routes/auth.php does not exist. Nothing to do.');
                return 1;
            }

            $this->registerMiddlewaresInKernel();
            $this->addRouteToServiceProvider();

            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Error during prebuild process: ' . $e->getMessage());
            return 1;
        }
    }

    protected function addRouteToServiceProvider()
    {
        $providerPath = base_path('app/Providers/RouteServiceProvider.php');

        if (!File::exists($providerPath)) {
            $this->info("📝 RouteServiceProvider.php not found. Creating it...");
            $this->createRouteServiceProvider($providerPath);
        }

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
            $this->info("📝 Kernel.php not found. Creating it...");
            $this->createKernel($kernelPath);
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

    protected function createRouteServiceProvider($providerPath)
    {
        // Ensure the Providers directory exists
        $providersDir = dirname($providerPath);
        if (!is_dir($providersDir)) {
            mkdir($providersDir, 0755, true);
        }

        $content = <<<'PHP'
<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));

            // Auto-registered from artisan command
            if (file_exists(base_path('routes/auth.php'))) {
                Route::middleware(['web', 'check.auth', 'prevent.back.history'])
                    ->group(base_path('routes/auth.php'));
            }
        });
    }
}
PHP;

        File::put($providerPath, $content);
        $this->info("✅ RouteServiceProvider.php created successfully.");
    }

    protected function createKernel($kernelPath)
    {
        // Ensure the Http directory exists
        $httpDir = dirname($kernelPath);
        if (!is_dir($httpDir)) {
            mkdir($httpDir, 0755, true);
        }

        $content = <<<'PHP'
<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array<int, class-string|string>
     */
    protected $middleware = [
        // \App\Http\Middleware\TrustHosts::class,
        \App\Http\Middleware\TrustProxies::class,
        \Illuminate\Http\Middleware\HandleCors::class,
        \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array<string, array<int, class-string|string>>
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        'api' => [
            // \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \Illuminate\Routing\Middleware\ThrottleRequests::class.':api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    /**
     * The application's middleware aliases.
     *
     * Aliases may be used instead of class names to conveniently assign middleware to routes and groups.
     *
     * @var array<string, class-string|string>
     */
    protected $middlewareAliases = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'precognitive' => \Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
        'signed' => \App\Http\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        'check.auth' => \App\Http\Middleware\CheckAuth::class,
        'prevent.back.history' => \App\Http\Middleware\PreventBackHistory::class,
    ];
}
PHP;

        File::put($kernelPath, $content);
        $this->info("✅ Kernel.php created successfully.");
    }
}
