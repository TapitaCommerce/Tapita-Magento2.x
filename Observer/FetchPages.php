<?php

namespace Tapita\Tpbuilder\Observer;

use \Magento\Framework\Event\ObserverInterface;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\Math\Random;
use Tapita\Tpbuilder\Helper\Data;

class FetchPages implements ObserverInterface
{

    protected $tapitaHelper;
    protected $scopeConfig;
    protected $cache;
    protected $configWriter;
    protected $cmsPageFactory;
    protected $messageManager;
    protected $random;

    public function __construct(
        WriterInterface $configWriter,
        TypeListInterface $cache,
        \Magento\Cms\Model\PageFactory $cmsPageFactory,
        MessageManagerInterface $messageManager,
        Random $random,
        Data $tapitaHelper,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->configWriter = $configWriter;
        $this->cache = $cache;
        $this->tapitaHelper = $tapitaHelper;
        $this->scopeConfig = $scopeConfig;
        $this->cmsPageFactory = $cmsPageFactory;
        $this->random = $random;
        $this->messageManager = $messageManager;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $enable = $this->scopeConfig->getValue('tpbuilder/general/enable');
        $token = $this->scopeConfig->getValue('tpbuilder/general/integration_token');
        if ($enable == '1' && $token && isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'tpbuilder') !== false) {
            $hook_token = $this->scopeConfig->getValue('tpbuilder/general/hook_token');
            if (!$hook_token) {
                $hook_token = $this->random->getRandomString(18);
                $this->configWriter->save('tpbuilder/general/hook_token', $hook_token);
                $this->cache->cleanType('config');
            }
            $result = $this->tapitaHelper->fetchPages();
            if ($result && isset($result[0]) && isset($result[1])) {
                $this->messageManager->addSuccessMessage('Updated the Tapita Pages: Created ' . $result[0] .
                    ' page(s). Modified ' . $result[1] . ' page(s).');
            }
        }
    }
}
