<?php

namespace Tapita\Tpbuilder\Controller\Hook;

use Tapita\Tpbuilder\Helper\Data;

class Index extends \Magento\Framework\App\Action\Action
{
    public $tapitaHelper;

    public function __construct(
        Data $tapitaHelper,
        \Magento\Framework\App\Action\Context $context
    ) {

        parent::__construct($context);
        $this->tapitaHelper = $tapitaHelper;
    }


    public function execute()
    {
        $arr = [];
        $result = $this->tapitaHelper->fetchPages();
        if ($result && isset($result[0]) && isset($result[1])) {
            $arr['status'] = 'success';
            $arr['created'] = $result[0];
            $arr['modified'] = $result[1];
        } else {
            $arr['status'] = 'failure';
        }
        $this->getResponse()->setHeader('Content-type', 'application/json', true);
        return $this->getResponse()->setBody(json_encode($arr));
    }
}
