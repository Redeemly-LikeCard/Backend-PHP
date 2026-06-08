<?php

namespace LuckyCode\IntegrationHelper\CodeIgniter;

class Installer
{
    private const ROUTES_MARKER = 'LuckyCode Routes';

    /**
     * Composer script entry point. Composer only runs scripts from the root
     * project, so this is reliable when the consuming app explicitly calls it
     * or when this package is being developed as the root package.
     */
    public static function postInstall($event = null): void
    {
        self::write("\n[LuckyCode] Running installer...");

        $projectRoot = self::findProjectRoot();

        if (!$projectRoot) {
            self::write('[LuckyCode] CodeIgniter 4 project not detected, skipping.');
            return;
        }

        if (self::isLaravelProject($projectRoot)) {
            self::write('[LuckyCode] Laravel project detected, skipping CI4 installer.');
            return;
        }

        self::publish($projectRoot);
    }

    public static function publish(?string $projectRoot = null, ?callable $logger = null): array
    {
        $projectRoot ??= self::findProjectRoot();

        if (!$projectRoot) {
            throw new \RuntimeException('CodeIgniter 4 project root could not be found.');
        }

        if (self::isLaravelProject($projectRoot)) {
            throw new \RuntimeException('Laravel project detected. The CodeIgniter publisher was not run.');
        }

        $projectRoot = rtrim($projectRoot, DIRECTORY_SEPARATOR);
        $logger ??= static fn (string $message): bool => self::write($message);

        $logger('[LuckyCode] Installing for CodeIgniter 4...');

        $results = [
            'config' => self::createConfig($projectRoot, $logger),
            'controller' => self::createController($projectRoot, $logger),
            'routes' => self::addRoutes($projectRoot, $logger),
            'env' => self::addEnvVars($projectRoot, $logger),
        ];

        $logger('');
        $logger('[LuckyCode] LuckyCode package installed successfully.');
        $logger('[LuckyCode] API endpoints are available at /luckycode/*');

        return $results;
    }

    public static function findProjectRoot(?string $startPath = null): ?string
    {
        $startPath ??= getcwd() ?: __DIR__;
        $startPath = realpath($startPath) ?: $startPath;

        $paths = [];
        $current = $startPath;

        for ($i = 0; $i < 6; $i++) {
            $paths[] = $current;
            $parent = dirname($current);

            if ($parent === $current) {
                break;
            }

            $current = $parent;
        }

        foreach ($paths as $path) {
            if (self::isCodeIgniterProject($path)) {
                return $path;
            }
        }

        return null;
    }

    public static function isCodeIgniterProject(string $path): bool
    {
        return is_file(self::path($path, 'spark'))
            && is_file(self::path($path, 'app', 'Config', 'Paths.php'))
            && is_file(self::path($path, 'public', 'index.php'));
    }

    public static function isLaravelProject(string $path): bool
    {
        return is_file(self::path($path, 'artisan'))
            && is_file(self::path($path, 'bootstrap', 'app.php'));
    }

    public static function createConfig(string $root, ?callable $logger = null): string
    {
        $file = self::path($root, 'app', 'Config', 'LuckyCode.php');

        if (file_exists($file)) {
            self::log($logger, '  - Config already exists: app/Config/LuckyCode.php');
            return 'exists';
        }

        self::ensureDirectory(dirname($file));
        file_put_contents($file, self::configTemplate());

        self::log($logger, '  - Created: app/Config/LuckyCode.php');
        return 'created';
    }

    public static function createController(string $root, ?callable $logger = null): string
    {
        $file = self::path($root, 'app', 'Controllers', 'LuckyCodeController.php');

        if (file_exists($file)) {
            self::log($logger, '  - Controller already exists: app/Controllers/LuckyCodeController.php');
            return 'exists';
        }

        self::ensureDirectory(dirname($file));
        file_put_contents($file, self::controllerTemplate());

        self::log($logger, '  - Created: app/Controllers/LuckyCodeController.php');
        return 'created';
    }

    public static function addRoutes(string $root, ?callable $logger = null): string
    {
        $file = self::path($root, 'app', 'Config', 'Routes.php');

        if (!is_file($file)) {
            throw new \RuntimeException('CodeIgniter routes file was not found at app/Config/Routes.php.');
        }

        $content = file_get_contents($file);

        if ($content === false) {
            throw new \RuntimeException('Unable to read app/Config/Routes.php.');
        }

        if (str_contains($content, self::ROUTES_MARKER) || str_contains($content, "luckycode',")) {
            self::log($logger, '  - Routes already exist in app/Config/Routes.php');
            return 'exists';
        }

        file_put_contents($file, rtrim($content) . PHP_EOL . PHP_EOL . self::routesTemplate());

        self::log($logger, '  - Added routes to: app/Config/Routes.php');
        return 'updated';
    }

    public static function addEnvVars(string $root, ?callable $logger = null): string
    {
        $file = self::path($root, '.env');

        if (!is_file($file)) {
            $example = self::path($root, 'env');

            if (is_file($example)) {
                copy($example, $file);
            } else {
                file_put_contents($file, '');
            }
        }

        $content = file_get_contents($file);

        if ($content === false) {
            throw new \RuntimeException('Unable to read .env.');
        }

        $vars = [
            'LUCKYCODE_BASE_URL' => 'https://your-api.com',
            'LUCKYCODE_API_KEY' => 'your-api-key',
            'LUCKYCODE_CLIENT_ID' => 'your-client-id',
            'LUCKYCODE_SSL_VERIFY' => 'true',
        ];

        $linesToAdd = [];

        foreach ($vars as $key => $value) {
            if (!preg_match('/^\s*' . preg_quote($key, '/') . '\s*=/m', $content)) {
                $linesToAdd[] = $key . '=' . $value;
            }
        }

        if ($linesToAdd === []) {
            self::log($logger, '  - Environment variables already exist in .env');
            return 'exists';
        }

        $addition = PHP_EOL . '# LuckyCode Configuration' . PHP_EOL . implode(PHP_EOL, $linesToAdd) . PHP_EOL;
        file_put_contents($file, rtrim($content) . PHP_EOL . $addition);

        self::log($logger, '  - Added environment variables to .env');
        return 'updated';
    }

    public static function configTemplate(): string
    {
        return <<<'PHP'
<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class LuckyCode extends BaseConfig
{
    public string $baseUrl = '';
    public string $apiKey = '';
    public string $clientId = '';
    public bool $sslVerify = true;

    public function __construct()
    {
        parent::__construct();

        $this->baseUrl = (string) env('LUCKYCODE_BASE_URL', '');
        $this->apiKey = (string) env('LUCKYCODE_API_KEY', '');
        $this->clientId = (string) env('LUCKYCODE_CLIENT_ID', '');
        $this->sslVerify = filter_var(env('LUCKYCODE_SSL_VERIFY', true), FILTER_VALIDATE_BOOL);
    }
}

PHP;
    }

    public static function controllerTemplate(): string
    {
        return <<<'PHP'
<?php

namespace App\Controllers;

use LuckyCode\IntegrationHelper\CodeIgniter\Controllers\BaseLuckyCodeController;

class LuckyCodeController extends BaseLuckyCodeController
{
    // Extend this controller if the host app needs custom middleware or logic.
}

PHP;
    }

    public static function routesTemplate(): string
    {
        return <<<'PHP'
// LuckyCode Routes
$routes->group('luckycode', ['namespace' => 'App\Controllers'], static function ($routes) {
    $routes->post('pull', 'LuckyCodeController::pull');
    $routes->post('reveal', 'LuckyCodeController::reveal');
    $routes->post('redeem', 'LuckyCodeController::redeem');
    $routes->post('multi-pull', 'LuckyCodeController::multiPull');
    $routes->get('check-serialcode', 'LuckyCodeController::checkSerialCode');
    $routes->get('customer-log', 'LuckyCodeController::getCustomersLog');
});

PHP;
    }

    private static function path(string ...$parts): string
    {
        $path = array_shift($parts) ?? '';

        foreach ($parts as $part) {
            $path = rtrim($path, DIRECTORY_SEPARATOR . '/\\') . DIRECTORY_SEPARATOR . trim($part, DIRECTORY_SEPARATOR . '/\\');
        }

        return $path;
    }

    private static function ensureDirectory(string $directory): void
    {
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException('Unable to create directory: ' . $directory);
        }
    }

    private static function log(?callable $logger, string $message): void
    {
        if ($logger) {
            $logger($message);
            return;
        }

        self::write($message);
    }

    private static function write(string $message): bool
    {
        echo $message . PHP_EOL;
        return true;
    }
}