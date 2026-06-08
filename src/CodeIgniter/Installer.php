<?php

namespace LuckyCode\IntegrationHelper\CodeIgniter;

class Installer
{
    /**
     * This runs automatically when Composer installs/updates the package
     */
    public static function postInstall()
    {
        // Only run if in CodeIgniter project
        if (!defined('ROOTPATH') && !defined('FCPATH')) {
            return;
        }
        
        self::publishConfig();
        self::publishController();
        self::addRoutes();
        self::addSparkCommand();
        self::displaySuccess();
    }
    
    private static function publishConfig()
    {
        $target = ROOTPATH . 'app/Config/LuckyCode.php';
        
        if (!file_exists($target)) {
            $configTemplate = '<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class LuckyCode extends BaseConfig
{
    public string $baseUrl = "";
    public string $apiKey = "";
    public string $clientId = "";
    public bool $sslVerify = true;
    
    public function __construct()
    {
        parent::__construct();
        
        $this->baseUrl = env("LUCKYCODE_BASE_URL", "");
        $this->apiKey = env("LUCKYCODE_API_KEY", "");
        $this->clientId = env("LUCKYCODE_CLIENT_ID", "");
        $this->sslVerify = env("LUCKYCODE_SSL_VERIFY", true);
    }
}
';
            file_put_contents($target, $configTemplate);
            echo "✓ Created config: app/Config/LuckyCode.php\n";
        }
    }
    
    private static function publishController()
    {
        $target = ROOTPATH . 'app/Controllers/LuckyCodeController.php';
        
        if (!file_exists($target)) {
            $controllerTemplate = '<?php

namespace App\Controllers;

use LuckyCode\IntegrationHelper\CodeIgniter\Controllers\BaseLuckyCodeController;

class LuckyCodeController extends BaseLuckyCodeController
{
    // Add your custom logic here
    // Or use the built-in methods from the package
}
';
            file_put_contents($target, $controllerTemplate);
            echo "✓ Created controller: app/Controllers/LuckyCodeController.php\n";
        }
    }
    
    private static function addRoutes()
    {
        $routesFile = ROOTPATH . 'app/Config/Routes.php';
        $routesCode = '\n\n// LuckyCode Package Routes (Auto-added)\n$routes->group("luckycode", ["namespace" => "LuckyCode\\IntegrationHelper\\CodeIgniter\\Controllers"], function($routes) {\n    $routes->post("pull", "LuckyCodeController::pull");\n    $routes->post("reveal", "LuckyCodeController::reveal");\n    $routes->post("redeem", "LuckyCodeController::redeem");\n    $routes->post("multi-pull", "LuckyCodeController::multiPull");\n    $routes->get("check-serialcode", "LuckyCodeController::checkSerialCode");\n    $routes->get("customer-log", "LuckyCodeController::getCustomersLog");\n});\n';
        $content = file_get_contents($routesFile);
        
        if (strpos($content, 'LuckyCode Package Routes') === false) {
            file_put_contents($routesFile, $content . $routesCode);
            echo "✓ Added routes to: app/Config/Routes.php\n";
        }
    }
    
    private static function addSparkCommand()
    {
        $commandsFile = ROOTPATH . 'app/Config/Commands.php';
        
        if (file_exists($commandsFile)) {
            $content = file_get_contents($commandsFile);
            $commandLine = '    \\LuckyCode\\IntegrationHelper\\CodeIgniter\\Commands\\PublishCommand::class,';
            
            if (strpos($content, 'PublishCommand') === false) {
                $content = str_replace(
                    'protected $commands = [',
                    "protected $commands = [\n        $commandLine",
                    $content
                );
                file_put_contents($commandsFile, $content);
                echo "✓ Registered spark command\n";
            }
        }
    }
    
    private static function displaySuccess()
    {
        echo "\n==============================================\n";
        echo "LuckyCode Package v2.2.0 Installed for CI4!\n";
        echo "==============================================\n";
        echo "\nNext steps:\n";
        echo "1. Add to .env:\n";
        echo "   LUCKYCODE_BASE_URL=https://your-api.com\n";
        echo "   LUCKYCODE_API_KEY=your-api-key\n";
        echo "   LUCKYCODE_CLIENT_ID=your-client-id\n";
        echo "\n2. Run: php spark serve\n";
        echo "3. API ready at: http://localhost:8080/luckycode/*\n";
        echo "==============================================\n\n";
    }
}
