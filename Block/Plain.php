<?php

namespace Tapita\Tpbuilder\Block;

use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Cms\Model\PageFactory;

class Plain extends \Magento\Framework\View\Element\Template
{
    protected $scopeConfig;
    protected $storeManager;
    protected $cmsPageFactory;
    protected $filterProvider;


    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Cms\Model\Template\FilterProvider $filterProvider,
        PageFactory $cmsPageFactory,
        array $data = []
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->cmsPageFactory = $cmsPageFactory;
        $this->filterProvider = $filterProvider;
        parent::__construct($context, $data);
    }
    public function toHtml()
    {
        $params = $this->getRequest()->getParams();
        $objectManger = \Magento\Framework\App\ObjectManager::getInstance();
        $pageId = $this->scopeConfig->getValue('tpbuilder/plain_page/default', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if (isset($params['identifier']))
            $pageId = $params['identifier'];
        $cmsPage = $this->cmsPageFactory->create()->getCollection()->addFieldToFilter('identifier', $pageId)->getFirstItem();
        if ($cmsPage && $cmsPage->getId())
            return $this->getContentFromStaticBlock($cmsPage->getContent());
        return '';
    }
    public function getContentFromStaticBlock($content)
    {
        $storeId = $this->storeManager->getStore()->getId();
        return $this->filterProvider->getBlockFilter()->setStoreId($storeId)->filter($content);
    }
}
