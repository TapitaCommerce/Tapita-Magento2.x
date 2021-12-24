<?php

namespace Tapita\Tpbuilder\Block;

use \Magento\Framework\App\Config\ScopeConfigInterface;

class ForceBody extends \Magento\Framework\View\Element\Template
{
    protected $scopeConfig;
    protected $storeManager;


    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        array $data = []
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        parent::__construct($context, $data);
    }

    protected function cmp($a, $b)
    {
        return $b['priority'] - $a['priority'];
    }

    public function thisPageData()
    {
        $enable = $this->scopeConfig->getValue('tpbuilder/general/enable');
        $pbData = $this->scopeConfig->getValue('tpbuilder/general/fetched_data');
        if ($enable == '1' && $pbData && $pbData !== '') {
            try {
                $pbData = json_decode($pbData, true);
                $storeCode = $this->storeManager->getStore()->getCode();
                $uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
                //remove subdomain
                $uri = explode('/', $uri);
                if ($uriPartsCount = count($uri)) {
                    $uri = '/' . $uri[$uriPartsCount - 1];
                }
                if ($uri && $pbData && count($pbData)) {
                    $filteredPb = [];
                    foreach ($pbData as $pbPage) {
                        if ($uri !== $pbPage['url_path'])
                            continue;
                        if ($pbPage['storeview_visibility'] && $pbPage['storeview_visibility'] !== '') {
                            $storeCodes = $pbPage['storeview_visibility'];
                            $storeCodes = str_replace(' ', '', $storeCodes);
                            $storeCodes = explode(',', $storeCodes);
                            if (in_array($storeCode, $storeCodes)) {
                                $filteredPb[] = $pbPage;
                            }
                        } else {
                            $filteredPb[] = $pbPage;
                        }
                    }
                    //var_dump($filteredPb);
                    if (count($filteredPb) > 1)
                        usort($filteredPb, array($this, 'cmp'));
                    if ($filteredPb)
                        return $filteredPb[0];
                }
            } catch (\Exception $e) {
            }
        }
    }
}
