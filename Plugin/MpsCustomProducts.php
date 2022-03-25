<?php

namespace Tapita\Tpbuilder\Plugin;

class MpsCustomProducts
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

    public function aroundGetProductCollection($customProductsBlock, $proceed)
    {
        $sliderModel = $this->_coreRegistry->registry('tp_pslider_product_slide_model');
        if ($sliderModel) {
            $customProductsBlock->setSlider($sliderModel);
        }
        $result = $proceed();
        return $result;
    }
}
