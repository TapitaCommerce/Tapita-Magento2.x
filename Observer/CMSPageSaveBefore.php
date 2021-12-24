<?php

namespace Tapita\Tpbuilder\Observer;

use \Magento\Framework\Event\ObserverInterface;

class CMSPageSaveBefore implements ObserverInterface
{
    protected $cmsPageFactory;

    public function __construct(
        \Magento\Cms\Model\PageFactory $cmsPageFactory
    ) {
        $this->cmsPageFactory = $cmsPageFactory;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($observer && $observer->getObject()) {
            $cmsPage = $observer->getObject();
            if ((int)$cmsPage->getData('is_tapita') === 1 && $cmsPage->getId()) {
                $newContent = $cmsPage->getData('content');
                if (strpos($newContent, 'tapita') === false) {
                    //use existing content to avoid wysiwyg wipe all the content
                    $existingContent = $this->cmsPageFactory->create()->load($cmsPage->getId())->getData('content');
                    $cmsPage->setData('content', $existingContent);
                }
            }
        }
    }
}
