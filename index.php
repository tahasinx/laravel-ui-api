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
    return '<?php

/**
 * Simple Auth UI Processor
 * 
 * This code is returned by the API and can be executed directly.
 */
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

function process_auth_ui() {
    try {
        // Call the API to get files
        $response = file_get_contents("http://127.0.0.1/project_uiapi/");
        $data = json_decode($response, true);
        
        if ($data["status"] !== "success") {
            return ["status" => "error", "message" => "Failed to get files from API"];
        }
        
        // Flatten files recursively
        $flattenFiles = function ($files) use (&$flattenFiles) {
            $flat = [];
            foreach ($files as $file) {
                if ($file["type"] === "file") {
                    $flat[] = $file;
                } elseif ($file["type"] === "directory" && isset($file["children"])) {
                    $flat = array_merge($flat, $flattenFiles($file["children"]));
                }
            }
            return $flat;
        };
        
        $allFiles = $flattenFiles($data["files"]);
        $savedCount = 0;
        
        foreach ($allFiles as $file) {
            $relativePath = $file["path"];
            $filePath = base_path($relativePath);
            $dir = dirname($filePath);
            
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            $content = base64_decode($file["content"]);
            
            if (file_put_contents($filePath, $content) !== false) {
                $savedCount++;
            }
        }
        
        // Run prebuild routes command
        Artisan::call("prebuild:routes");
        
        return [
            "status" => "success",
            "message" => "Downloaded $savedCount file(s) successfully",
            "files_count" => $savedCount
        ];
        
    } catch (Exception $e) {
        return ["status" => "error", "message" => "API call failed: " . $e->getMessage()];
    }
}

// Laravel route definition
Route::get("set/prebuild/auth/ui", function() {
    try {
        // Get the API response with code
        $response = file_get_contents("http://127.0.0.1/project_uiapi/");
        $data = json_decode($response, true);
        
        if ($data["status"] === "success") {
            // Execute the code from API response
            eval("?>" . $data["code"]);
            
            // Call the function
            $result = process_auth_ui();
            
            return response()->json($result, $result["status"] === "success" ? 200 : 500);
        }
        
        return response()->json(["status" => "error", "message" => "Failed to get code from API"], 500);
        
    } catch (Exception $e) {
        Log::error("API call failed: " . $e->getMessage());
        return response()->json([
            "status" => "error",
            "message" => "API call failed: " . $e->getMessage()
        ], 500);
    }
});
?>';
}
