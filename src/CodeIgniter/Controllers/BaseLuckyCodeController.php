<?php

namespace LuckyCode\IntegrationHelper\CodeIgniter\Controllers;

use CodeIgniter\Controller;
use LuckyCode\IntegrationHelper\Services\LuckyCodeService;
use LuckyCode\IntegrationHelper\Models\PullCodeRequest;
use LuckyCode\IntegrationHelper\Models\RevealCodeRequest;
use LuckyCode\IntegrationHelper\Models\RedeemCodeRequest;
use LuckyCode\IntegrationHelper\Models\CustomerPakageLogQuery;

class BaseLuckyCodeController extends Controller
{
    protected $luckyCodeService;
    
    public function __construct()
    {
       // parent::__construct();

        $this->luckyCodeService = new LuckyCodeService(
            baseUrl: env('LUCKYCODE_BASE_URL') ?: '',
            apiKey: env('LUCKYCODE_API_KEY') ?: '',
            clientId: env('LUCKYCODE_CLIENT_ID') ?: '',
            sslVerify: env('LUCKYCODE_SSL_VERIFY', true)
        );
    }
    
    /**
     * POST /luckycode/pull
     */
    public function pull()
    {
        $input = $this->getRequestInput();
        $dto = new PullCodeRequest($input);
        $response = $this->luckyCodeService->pullCode($dto);
        return $this->response->setJSON($response);
    }
    
    /**
     * POST /luckycode/reveal
     */
    public function reveal()
    {
        $input = $this->getRequestInput();
        $dto = new RevealCodeRequest($input);
        $response = $this->luckyCodeService->revealCode($dto);
        return $this->response->setJSON($response);
    }
    
    /**
     * POST /luckycode/redeem
     */
    public function redeem()
    {
        $input = $this->getRequestInput();
        $dto = new RedeemCodeRequest($input);
        $response = $this->luckyCodeService->redeemCode($dto);
        return $this->response->setJSON($response);
    }
    
    /**
     * POST /luckycode/multi-pull
     */
    public function multiPull()
    {
        $input = $this->getRequestInput();
        $dto = new PullCodeRequest($input);
        $response = $this->luckyCodeService->multiPull($dto);
        return $this->response->setJSON($response);
    }
    
    /**
     * GET /luckycode/check-serialcode?serialCode=XXX
     */
    public function checkSerialCode()
    {
        $serialCode = $this->request->getGet('serialCode')
                    ?? $this->request->getGet('serialcode')
                    ?? '';
        
        $response = $this->luckyCodeService->checkSerialCode($serialCode);
        return $this->response->setJSON($response);
    }
    
    /**
     * GET /luckycode/customer-log?customerRef=XXX&page=1&pageSize=30
     */
    public function getCustomersLog()
    {
        $query = new CustomerPakageLogQuery([
            'page' => $this->request->getGet('page') ?? 1,
            'pageSize' => $this->request->getGet('pageSize') ?? 30,
            'customerRef' => $this->request->getGet('customerRef')
                         ?? $this->request->getGet('customerref')
                         ?? ''
        ]);
        
        $response = $this->luckyCodeService->getCustomersLog($query);
        return $this->response->setJSON($response);
    }
    
    /**
     * Get request input (JSON or POST)
     */
    private function getRequestInput()
    {
        $input = $this->request->getJSON(true);
        if (!$input) {
            $input = $this->request->getPost();
        }
        return $input;
    }
}
