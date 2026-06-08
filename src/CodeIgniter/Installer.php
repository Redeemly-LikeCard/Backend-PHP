<?php

namespace LuckyCode\IntegrationHelper\CodeIgniter;

class Installer
{
    public static function postInstall()
    {
        echo "\n[LuckyCode] Running installer...\n";
        
        $projectRoot = self::findProjectRoot();
        
        if (!$projectRoot) {
            echo "[LuckyCode] Not in a CodeIgniter project, skipping.\n";
            return;
        }
        
        // Skip Laravel
        if (file_exists($projectRoot . '/artisan')) {
            echo "[LuckyCode] Laravel project detected, skipping CI4 installer.\n";
            return;
        }
        
        echo "[LuckyCode] Installing for CodeIgniter 4...\n";
        
        self::createConfig($projectRoot);
        self::createController($projectRoot);
        self::addRoutes($projectRoot);
        self::addEnvVars($projectRoot);
        
        echo "\n✅ LuckyCode Package v2.3.0 installed successfully!\n";
        echo "   API endpoints available at: /luckycode/*\n\n";
    }
    
    private static function findProjectRoot()
    {
        $paths = [
            getcwd(),
            dirname(getcwd()),
            dirname(dirname(getcwd())),
        ];
        
        foreach ($paths as $path) {
            if (file_exists($path . '/app/Config/Paths.php') && 
                file_exists($path . '/public/index.php')) {
                return $path;
            }
        }
        
        return null;
    }
    
    private static function createConfig($root)
    {
        $file = $root . '/app/Config/LuckyCode.php';
        if (file_exists($file)) {
            echo "  • Config already exists\n";
            return;
        }
        
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
        file_put_contents($file, $content);
        echo "  ✓ Created: app/Config/LuckyCode.php\n";
    }
    
    private static function createController($root)
    {
        $file = $root . '/app/Controllers/LuckyCodeController.php';
        if (file_exists($file)) {
            echo "  • Controller already exists\n";
            return;
        }
        
        $content = '<?php

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
        $serialCode = $this->request->getGet("serialCode") ?? $this->request->getGet("serialcode") ?? "";
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
        file_put_contents($file, $content);
        echo "  ✓ Created: app/Controllers/LuckyCodeController.php\n";
    }
    
    private static function addRoutes($root)
    {
        $file = $root . '/app/Config/Routes.php';
        $routesCode = "\n\n// LuckyCode Routes\n\$routes->group('luckycode', function(\$routes) {\n";
        $routesCode .= "    \$routes->post('pull', 'LuckyCodeController::pull');\n";
        $routesCode .= "    \$routes->post('reveal', 'LuckyCodeController::reveal');\n";
        $routesCode .= "    \$routes->post('redeem', 'LuckyCodeController::redeem');\n";
        $routesCode .= "    \$routes->post('multi-pull', 'LuckyCodeController::multiPull');\n";
        $routesCode .= "    \$routes->get('check-serialcode', 'LuckyCodeController::checkSerialCode');\n";
        $routesCode .= "    \$routes->get('customer-log', 'LuckyCodeController::getCustomersLog');\n";
        $routesCode .= "});\n";
        
        $content = file_get_contents($file);
        if (strpos($content, 'LuckyCode Routes') === false) {
            file_put_contents($file, $content . $routesCode);
            echo "  ✓ Added routes to: app/Config/Routes.php\n";
        }
    }
    
    private static function addEnvVars($root)
    {
        $file = $root . '/.env';
        if (!file_exists($file)) {
            return;
        }
        
        $content = file_get_contents($file);
        $vars = [
            "\n# LuckyCode Configuration",
            "LUCKYCODE_BASE_URL=https://your-api.com",
            "LUCKYCODE_API_KEY=your-api-key",
            "LUCKYCODE_CLIENT_ID=your-client-id",
            "LUCKYCODE_SSL_VERIFY=true"
        ];
        
        if (strpos($content, 'LUCKYCODE_BASE_URL') === false) {
            file_put_contents($file, $content . "\n" . implode("\n", $vars) . "\n");
            echo "  ✓ Added environment variables to .env\n";
        }
    }
}