<?php

namespace LuckyCode\IntegrationHelper\CodeIgniter\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class PublishCommand extends BaseCommand
{
    protected $group = 'LuckyCode';
    protected $name = 'luckycode:publish';
    protected $description = 'Publish LuckyCode package configuration and assets';
    
    public function run(array $params)
    {
        CLI::write('Publishing LuckyCode package...', 'green');
        
        // Publish config
        $this->publishConfig();
        
        // Publish controller
        $this->publishController();
        
        // Add routes
        $this->addRoutes();
        
        CLI::write("\n✓ LuckyCode package published successfully!", 'green');
        CLI::write("\nNext steps:", 'yellow');
        CLI::write("1. Add to .env: LUCKYCODE_BASE_URL, LUCKYCODE_API_KEY, LUCKYCODE_CLIENT_ID");
        CLI::write("2. Run: php spark serve");
        CLI::write("3. API endpoints available at /luckycode/*\n");
    }
    
    private function publishConfig()
    {
        $target = ROOTPATH . 'app/Config/LuckyCode.php';
        
        if (!file_exists($target)) {
            $content = '<?php

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
            file_put_contents($target, $content);
            CLI::write('  ✓ Created: app/Config/LuckyCode.php', 'green');
        } else {
            CLI::write('  • Config already exists: app/Config/LuckyCode.php', 'yellow');
        }
    }
    
    private function publishController()
    {
        $target = ROOTPATH . 'app/Controllers/LuckyCodeController.php';
        
        if (!file_exists($target)) {
            $content = '<?php

namespace App\Controllers;

use LuckyCode\IntegrationHelper\CodeIgniter\Controllers\BaseLuckyCodeController;

class LuckyCodeController extends BaseLuckyCodeController
{
    // Your custom logic here
}
';
            file_put_contents($target, $content);
            CLI::write('  ✓ Created: app/Controllers/LuckyCodeController.php', 'green');
        } else {
            CLI::write('  • Controller already exists: app/Controllers/LuckyCodeController.php', 'yellow');
        }
    }
    
    private function addRoutes()
    {
        $routesFile = ROOTPATH . 'app/Config/Routes.php';
        $routesCode = "\n// LuckyCode Routes (Added by publish command)\n";
        $routesCode .= "\$routes->group('luckycode', ['namespace' => 'LuckyCode\\IntegrationHelper\\CodeIgniter\\Controllers'], function(\$routes) {\n";
        $routesCode .= "    \$routes->post('pull', 'BaseLuckyCodeController::pull');\n";
        $routesCode .= "    \$routes->post('reveal', 'BaseLuckyCodeController::reveal');\n";
        $routesCode .= "    \$routes->post('redeem', 'BaseLuckyCodeController::redeem');\n";
        $routesCode .= "    \$routes->post('multi-pull', 'BaseLuckyCodeController::multiPull');\n";
        $routesCode .= "    \$routes->get('check-serialcode', 'BaseLuckyCodeController::checkSerialCode');\n";
        $routesCode .= "    \$routes->get('customer-log', 'BaseLuckyCodeController::getCustomersLog');\n";
        $routesCode .= "});\n";
        
        $content = file_get_contents($routesFile);
        
        if (strpos($content, 'LuckyCode Routes') === false) {
            file_put_contents($routesFile, $content . $routesCode);
            CLI::write('  ✓ Added routes to: app/Config/Routes.php', 'green');
        } else {
            CLI::write('  • Routes already exist', 'yellow');
        }
    }
}
