<?php

namespace Tapita\Tpbuilder\Block\Widget;

use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Url\EncoderInterface;
use Magento\Framework\View\LayoutFactory;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\Catalog\Model\CategoryFactory;

class Slider extends AbstractSlider
{
    protected $_template = "Tapita_Tpbuilder::productslider.phtml";

    /**
     * Slider constructor.
     * @param Context $context
     * @param CollectionFactory $productCollectionFactory
     * @param Visibility $catalogProductVisibility
     * @param DateTime $dateTime
     * @param HttpContext $httpContext
     * @param EncoderInterface $urlEncoder
     * @param Grouped $grouped
     * @param Configurable $configurable
     * @param LayoutFactory $layoutFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        CollectionFactory $productCollectionFactory,
        Visibility $catalogProductVisibility,
        DateTime $dateTime,
        HttpContext $httpContext,
        EncoderInterface $urlEncoder,
        Grouped $grouped,
        Configurable $configurable,
        LayoutFactory $layoutFactory,
        CategoryFactory $categoryFactory,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $productCollectionFactory,
            $catalogProductVisibility,
            $dateTime,
            $httpContext,
            $urlEncoder,
            $grouped,
            $configurable,
            $layoutFactory,
            $data
        );
        $this->productCollectionFactory = $productCollectionFactory;
        $this->categoryFactory = $categoryFactory;
    }

    /**
     * @inheritdoc
     */
    public function _construct()
    {
        parent::_construct();

        $this->setTemplate('Tapita_Tpbuilder::productslider.phtml');
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    public function getProductCollection()
    {
        $collection = $this->productCollectionFactory->create();
        if ($this->getData('product_type') === 'custom') {
            $productSKUs = explode(',', $this->getData('product_skus'));
            $collection->addFieldToFilter('sku', ['in' => $productSKUs]);
        } else {
            $cateIds = explode(',', $this->getData('categories_ids'));
            $productIds = $this->getProductIdsByCategory($cateIds);
            $collection->addIdFilter(array('in' => $productIds));
        }
        if ($collection && $collection->getSize()) {
            $collection->setPageSize($this->getPageSize())->setCurPage($this->getCurrentPage());
        }
        $this->_addProductAttributesAndPrices($collection);
        return $collection;
    }



    /**
     * Get ProductIds by Category
     *
     * @return array
     */
    public function getProductIdsByCategory($catIds)
    {
        $productIds = [];
        $productId = [];

        foreach ($catIds as $cat) {
            $collection = $this->productCollectionFactory->create();
            $category = $this->categoryFactory->create()->load($cat);
            $collection->addAttributeToSelect('*')->addCategoryFilter($category);

            foreach ($collection as $item) {
                $productId[] = $item->getData('entity_id');
            }

            $productIds = array_merge($productIds, $productId);
        }
        $keys = array_keys($productIds);
        shuffle($keys);
        $productIdsRandom = [];

        foreach ($keys as $key => $value) {
            $productIdsRandom[] = $productIds[$value];
            if ($key >= ($this->getProductsCount() - 1)) {
                break;
            }
        }
        return $productIdsRandom;
    }

    /**
     * Retrieve how many products should be displayed on page
     *
     * @return int
     */
    protected function getPageSize()
    {
        return $this->getProductsCount();
    }

    /**
     * Get limited number
     * @return int|mixed
     */
    public function getProductsCount()
    {
        return $this->getData('products_count') ?: 10;
    }

    /**
     * Get number of current page based on query value
     *
     * @return int
     */
    public function getCurrentPage()
    {
        return abs((int)$this->getRequest()->getParam($this->getData('page_var_name')));
    }

    /**
     * Get key pieces for caching block content
     *
     * @return array
     */
    public function getCacheKeyInfo()
    {
        $params = json_encode($this->getRequest()->getParams()) .
            $this->getData('cache_tag') .
            $this->getData('categories_ids') .
            $this->getData('product_skus') .
            $this->getData('product_type');

        return array_merge(
            parent::getCacheKeyInfo(),
            [
                $this->getData('page_var_name'),
                (int)$this->getRequest()->getParam($this->getData('page_var_name'), 1),
                $params
            ]
        );
    }
}
