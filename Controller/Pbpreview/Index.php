<?php

namespace Tapita\Tpbuilder\Controller\Pbpreview;

use Tapita\Tpbuilder\Helper\Data;

class Index extends \Magento\Framework\App\Action\Action
{
    public $tapitaHelper;
    protected $_pageFactory;

    public function __construct(
        Data $tapitaHelper,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Framework\App\Action\Context $context
    ) {

        $this->_pageFactory = $pageFactory;
        parent::__construct($context);
        $this->tapitaHelper = $tapitaHelper;
    }


    public function execute()
    {
        return $this->_pageFactory->create();
    }
}
