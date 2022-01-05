<?php

namespace Tapita\Tpbuilder\Plugin;

class PageBuilderConfig
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;


    public function __construct(
        \Magento\Framework\Registry $registry
    ) {
        $this->_coreRegistry = $registry;
    }

    public function afterIsEnabled($subject, $result)
    {
        $cmsPage = $this->_coreRegistry->registry('cms_page');
        if ($cmsPage && $cmsPage->getId() && ((int)$cmsPage->getData('is_tapita') === 1))
            return false;
        return $result;
    }
}
