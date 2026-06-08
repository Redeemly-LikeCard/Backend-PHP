<?php

namespace LuckyCode\IntegrationHelper\CodeIgniter;

class Installer
{
    public static function postInstall()
    {
        $projectRoot = self::getProjectRoot();
        
        // Skip Laravel projects (critical!)
        if (file_exists($projectRoot . 'artisan') || 
            file_exists($projectRoot . 'bootstrap' . DIRECTORY_SEPARATOR . 'app.php')) {
            return;
        }
        
        // Only run for CodeIgniter projects
        if (!file_exists($projectRoot . 'spark') && 
            !file_exists($projectRoot . 'app' . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Routes.php')) {
            return;
        }
        
        echo "\n\033[32m==============================================\033[0m\n";
        echo "\033[32mLuckyCode Package - Installing for CodeIgniter 4\033[0m\n";
        echo "\033[32m==============================================\033[0m\n\n";
        
        self::publishConfig($projectRoot);
        self::publishController($projectRoot);
        self::addRoutes($projectRoot);
        self::publishEnv($projectRoot);
        
        echo "\n\033[32m✓ LuckyCode Package is READY TO USE!\033[0m\n";
        echo "\n\033[33mNext steps:\033[0m\n";
        echo "1. Add your API credentials to .env file\n";
        echo "2. Run: php spark serve\n";
        echo "3. API endpoints ready at: http://localhost:8080/luckycode/*\n";
        echo "\n\033[32m==============================================\033[0m\n\n";
    }
    
    private static function publishConfig(string $projectRoot)
    {
        $target = $projectRoot . 'app' . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'LuckyCode.php';
        
        if (file_exists($target)) {
            echo "  • Config already exists\n";
            return;
        }
        
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
        echo "  ✓ Created: app/Config/LuckyCode.php\n";
    }
    
    private static function publishController(string $projectRoot)
    {
        $target = $projectRoot . 'app' . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . 'LuckyCodeController.php';
        
        if (file_exists($target)) {
            echo "  • Controller already exists\n";
            return;
        }
        
        $controllerTemplate = '<?php

namespace App\Controllers;

use LuckyCode\IntegrationHelper\Services\LuckyCodeService;
use LuckyCode\IntegrationHelper\Models\PullCodeRequest;
use LuckyCode\IntegrationHelper\Models\RevealCodeRequest;
use LuckyCode\IntegrationHelper\Models\RedeemCodeRequest;
use LuckyCode\IntegrationHelper\Models\CustomerPakageLogQuery;

class LuckyCodeController extends BaseController
{
    protected $luckyCodeService;
    
    public function __construct()
    {
        $this->luckyCodeService = new LuckyCodeService(
            baseUrl: env("LUCKYCODE_BASE_URL") ?: "",
            apiKey: env("LUCKYCODE_API_KEY") ?: "",
            clientId: env("LUCKYCODE_CLIENT_ID") ?: "",
            sslVerify: env("LUCKYCODE_SSL_VERIFY", true)
        );
    }
    
    public function pull()
    {
        $input = $this->getInput();
        $dto = new PullCodeRequest($input);
        return $this->response->setJSON($this->luckyCodeService->pullCode($dto));
    }
    
    public function reveal()
    {
        $input = $this->getInput();
        $dto = new RevealCodeRequest($input);
        return $this->response->setJSON($this->luckyCodeService->revealCode($dto));
    }
    
    public function redeem()
    {
        $input = $this->getInput();
        $dto = new RedeemCodeRequest($input);
        return $this->response->setJSON($this->luckyCodeService->redeemCode($dto));
    }
    
    public function multiPull()
    {
        $input = $this->getInput();
        $dto = new PullCodeRequest($input);
        return $this->response->setJSON($this->luckyCodeService->multiPull($dto));
    }
    
    public function checkSerialCode()
    {
        $serialCode = $this->request->getGet("serialCode") 
                    ?? $this->request->getGet("serialcode")
                    ?? "";
        
        return $this->response->setJSON($this->luckyCodeService->checkSerialCode($serialCode));
    }
    
    public function getCustomersLog()
    {
        $query = new CustomerPakageLogQuery([
            "page" => $this->request->getGet("page") ?? 1,
            "pageSize" => $this->request->getGet("pageSize") ?? 30,
            "customerRef" => $this->request->getGet("customerRef") ?? ""
        ]);
        
        return $this->response->setJSON($this->luckyCodeService->getCustomersLog($query));
    }
    
    private function getInput()
    {
        $input = $this->request->getJSON(true);
        if (!$input) {
            $input = $this->request->getPost();
        }
        return $input;
    }
}
';
        file_put_contents($target, $controllerTemplate);
        echo "  ✓ Created: app/Controllers/LuckyCodeController.php\n";
    }
    
    private static function addRoutes(string $projectRoot)
    {
        $routesFile = $projectRoot . 'app' . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Routes.php';
        
        if (!file_exists($routesFile)) {
            return;
        }
        
        $routesCode = "\n\n// LuckyCode Routes - Auto-added by package\n";
        $routesCode .= "\$routes->group('luckycode', function(\$routes) {\n";
        $routesCode .= "    \$routes->post('pull', 'LuckyCodeController::pull');\n";
        $routesCode .= "    \$routes->post('reveal', 'LuckyCodeController::reveal');\n";
        $routesCode .= "    \$routes->post('redeem', 'LuckyCodeController::redeem');\n";
        $routesCode .= "    \$routes->post('multi-pull', 'LuckyCodeController::multiPull');\n";
        $routesCode .= "    \$routes->get('check-serialcode', 'LuckyCodeController::checkSerialCode');\n";
        $routesCode .= "    \$routes->get('customer-log', 'LuckyCodeController::getCustomersLog');\n";
        $routesCode .= "});\n";
        
        $content = file_get_contents($routesFile);
        
        if (strpos($content, 'LuckyCode Routes') === false) {
            file_put_contents($routesFile, $content . $routesCode);
            echo "  ✓ Added routes to: app/Config/Routes.php\n";
        } else {
            echo "  • Routes already exist\n";
        }
    }
    
    private static function publishEnv(string $projectRoot)
    {
        $envFile = $projectRoot . '.env';
        
        if (!file_exists($envFile)) {
            return;
        }
        
        $envContent = file_get_contents($envFile);
        $varsToAdd = [];
        
        if (strpos($envContent, 'LUCKYCODE_BASE_URL') === false) {
            $varsToAdd[] = 'LUCKYCODE_BASE_URL=https://your-api.com';
        }
        if (strpos($envContent, 'LUCKYCODE_API_KEY') === false) {
            $varsToAdd[] = 'LUCKYCODE_API_KEY=your-api-key';
        }
        if (strpos($envContent, 'LUCKYCODE_CLIENT_ID') === false) {
            $varsToAdd[] = 'LUCKYCODE_CLIENT_ID=your-client-id';
        }
        if (strpos($envContent, 'LUCKYCODE_SSL_VERIFY') === false) {
            $varsToAdd[] = 'LUCKYCODE_SSL_VERIFY=true';
        }
        
        if (!empty($varsToAdd)) {
            $envContent .= "\n\n# LuckyCode Configuration\n" . implode("\n", $varsToAdd) . "\n";
            file_put_contents($envFile, $envContent);
            echo "  ✓ Added environment variables to .env\n";
        } else {
            echo "  • Environment variables already exist\n";
        }
    }
    
    private static function getProjectRoot(): string
    {
        // Check common locations
        $paths = [
            getcwd(),
            dirname(getcwd()),
            dirname(dirname(getcwd())),
            dirname(dirname(dirname(getcwd())))
        ];
        
        foreach ($paths as $path) {
            if (file_exists($path . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Paths.php')) {
                return rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            }
        }
        
        return rtrim(getcwd(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }
}