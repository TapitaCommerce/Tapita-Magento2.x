<?php

namespace Tapita\Tpbuilder\Block;

use \Magento\Framework\App\Config\ScopeConfigInterface;

class Footer extends \Magento\Framework\View\Element\Template
{
    protected $scopeConfig;
    protected $storeManager;
    protected $blockModelFactory;


    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Cms\Model\BlockFactory $blockModelFactory,
        array $data = []
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->blockModelFactory = $blockModelFactory;
        parent::__construct($context, $data);
    }
    public function toHtml()
    {
        $block = $this->scopeConfig->getValue('tpbuilder/footer_block/block');
        if ($block) {
            return $this->getLayout()->createBlock('Magento\Cms\Block\Block')->setBlockId($block)->toHtml();
        }
    }
}
