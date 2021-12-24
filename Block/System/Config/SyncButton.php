<?php


namespace Tapita\Tpbuilder\Block\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Backend\Block\Template\Context;

class SyncButton extends Field
{
    private $urlHelper;

    public function __construct(
        \Magento\Framework\Url $urlHelper,
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->urlHelper = $urlHelper;
    }

    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->getButtonHtml();
    }

    public function getButtonHtml()
    {
        $actionHtml = '';
        $hookToken = $this->_scopeConfig->getValue('tpbuilder/general/hook_token');
        if ($hookToken) {
            $syncUrl =  $this->urlHelper->getUrl('tpbuilder/hook/index', ['hook_token' => $hookToken, '_nosid' => true]);
            $actionHtml = '<a href=' . $syncUrl . '>' . $syncUrl . '</a>';
        }
        return $actionHtml;
    }
}
