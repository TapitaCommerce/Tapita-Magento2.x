<?php

namespace Tapita\Tpbuilder\Block;

use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Cms\Model\PageFactory;

class Preview extends \Magento\Framework\View\Element\Template
{
    protected $scopeConfig;
    protected $storeManager;
    protected $cmsPageFactory;
    protected $filterProvider;
    protected $productModel;
    protected $objManager;
    protected $registry;


    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Cms\Model\Template\FilterProvider $filterProvider,
        PageFactory $cmsPageFactory,
        \Magento\Catalog\Model\ProductFactory $productModel,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\ObjectManagerInterface $objManager,
        array $data = []
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->cmsPageFactory = $cmsPageFactory;
        $this->filterProvider = $filterProvider;
        $this->productModel = $productModel;
        $this->registry = $registry;
        $this->objManager = $objManager;
        parent::__construct($context, $data);
    }

    public function getPreloadPreviewWidgets()
    {
        $params = $this->getRequest()->getParams();
        $maskedId = false;
        if ($params && isset($params['maskedid'])) {
            $maskedId = $params['maskedid'];
        } else if ($params && isset($params['maskedId'])) {
            $maskedId = $params['maskedId'];
        }
        $preloadedWidgets = '';
        if ($maskedId) {
            try {
                $previewDataUrl = 'https://tapita.io/pb/graphql/?query=%0A%20query%20getPbItem(%24pageMaskedId%3A%20String)' .
                    '%20%7B%0A%20spb_page(pageMaskedId%3A%20%24pageMaskedId)%20%7B%0A%20total_count%0A%20items%20%7B%0A%20%0A%20' .
                    'priority%0A%20entity_id%0A%20name%0A%20status%0A%20masked_id%0A%20custom_css%0A%20custom_js%0A%20' .
                    'keywords%0A%20title%0A%20desc%0A%20is_rtl%0A%20storeview_visibility%0A%0A%20%7D%0A%20%7D%0A%20spb_item(pageMaskedId%3A%20%24pageMaskedId)' .
                    '%20%7B%0A%20total_count%0A%20items%20%7B%0A%20%0A%20entity_id%0A%20page_id%0A%20parent_id%0A%20styles%0A%20data%0A%20name%0A%20' .
                    'class_name%0A%20type%0A%20status%0A%20visibility%0A%20storeview_visibility%0A%20sort_order%0A%0A%20%7D%0A%20%7D%0A%20' .
                    'catalog_builder_page(pageMaskedId%3A%20%24pageMaskedId)%20%7B%0A%20total_count%0A%20items%20%7B%0A%20%0A%20priority%0A%20entity_id%0A%20name%0A%20status%0A%20' .
                    'masked_id%0A%20custom_css%0A%20custom_js%0A%20keywords%0A%20title%0A%20desc%0A%20is_rtl%0A%20' .
                    'storeview_visibility%0A%0A%20%7D%0A%20%7D%0A%20catalog_builder_item(pageMaskedId%3A%20%24pageMaskedId)%20%7B%0A%20' .
                    'total_count%0A%20items%20%7B%0A%20%0A%20entity_id%0A%20page_id%0A%20parent_id%0A%20styles%0A%20data%0A%20name%0A%20' .
                    'class_name%0A%20type%0A%20status%0A%20visibility%0A%20storeview_visibility%0A%20sort_order%0A%0A%20%7D%0A%20%7D%0A%20%7D%0A&variables=%7B%22pageMaskedId%22%3A%22' .
                    $maskedId .
                    '%22%7D&operationName=getPbItem';
                $previewData = file_get_contents($previewDataUrl);
                $previewData = json_decode($previewData, true);
                $allowedTypes = ['product_grid', 'product_scroll', 'product_scroll_1'];
                if (isset($previewData['data']['spb_item']['items'][0])) {
                    $foundItems = $previewData['data']['spb_item']['items'];
                    foreach ($foundItems as $publishedItem) {
                        if (in_array($publishedItem['type'], $allowedTypes)) {
                            $itmData = json_decode($publishedItem['data'], true);
                            if ($itmData && (isset($itmData['openCategoryProducts']) || isset($itmData['openProductsWidthSKUs']))) {
                                $productCount = isset($itmData['openProductsWidthSortPageSize']) ? $itmData['openProductsWidthSortPageSize'] : 10;
                                $productListAttribute = isset($itmData['openProductsWidthSKUs']) ? 'sku' : 'category_ids';
                                $productListValue = isset($itmData['openProductsWidthSKUs']) ? $itmData['openProductsWidthSKUs'] : $itmData['openCategoryProducts'];
                                $listBlockContent = '';
                                if ($publishedItem['type'] === 'product_scroll' || $publishedItem['type'] === 'product_scroll_1') {
                                    $sliderBlock = $this->getLayout()->createBlock("Tapita\Tpbuilder\Block\Widget\Slider")
                                        ->setProductsCount($productCount)
                                        ->setData('cache_tag', $publishedItem['entity_id']);
                                    if ($productListAttribute === 'sku') {
                                        $productSKUs = str_replace(" ", "", $productListValue);
                                        $sliderBlock->setData('product_skus', $productSKUs);
                                        $sliderBlock->setData('product_type', 'custom');
                                    } else {
                                        $sliderBlock->setData('product_type', 'category')
                                            ->setData('categories_ids', $itmData['openCategoryProducts']);
                                    }
                                    $listBlockContent = $sliderBlock->toHtml();
                                } else {
                                    $listBlockContent = $this->getLayout()->createBlock("Magento\CatalogWidget\Block\Product\ProductsList")
                                        ->setData('conditions_encoded', '^[`1`:^[`type`:`Magento||CatalogWidget||Model||Rule||Condition||Combine`,' .
                                            '`aggregator`:`all`,`value`:`1`,`new_child`:``^],`1--1`:^[`type`:`Magento||CatalogWidget||Model||Rule||Condition||Product`,' .
                                            '`attribute`:`' . $productListAttribute . '`,`operator`:`==`,`value`:`' . $productListValue . '`^]^]')
                                        ->setProductsCount($productCount)->setTemplate("product/widget/content/grid.phtml")->toHtml();
                                }
                                $preloadedWidgets .= '
                                    <div style="display: none" id="pbwidget-id-' . $publishedItem['entity_id'] . '">
                                        ' . $listBlockContent . '
                                    </div>
                                    <script type="text/javascript">
                                        require([\'jquery\', \'Tapita_Tpbuilder/js/owl.carousel.min\'], function ($) {
                                            function applyContent' . $publishedItem['entity_id'] . '() {
                                                var sourceEl = document.getElementById("pbwidget-id-' . $publishedItem['entity_id'] . '");
                                                var pbEl = document.getElementById("pbitm-id-' . $publishedItem['entity_id'] . '");
                                                pbEl.style.display = "block";
                                                pbEl.innerHTML= sourceEl.innerHTML;
                                                try {
                                                    var toApplyOwlCarousel = document.querySelector("#pbitm-id-' . $publishedItem['entity_id'] . ' .owl-carousel");
                                                    if (toApplyOwlCarousel)
                                                        $(toApplyOwlCarousel).owlCarousel({loop:true,margin:10,nav:true,dots:false,lazyLoad:true,autoplay:false,autoplayTimeout:5000,autoplayHoverPause:false,});
                                                } catch (err) {
                                                }
                                            }

                                            setTimeout(function () {
                                                window.addEventListener("resize", function(event) {
                                                    applyContent' . $publishedItem['entity_id'] . '();
                                                }, true);

                                                var pbEl = document.getElementById("pbitm-id-' . $publishedItem['entity_id'] . '");
                                                if (pbEl)
                                                    applyContent' . $publishedItem['entity_id'] . '();
                                                else
                                                    setTimeout(function () {
                                                        applyContent' . $publishedItem['entity_id'] . '();
                                                    }, 3000);
                                            }, 300);
                                        });
                                    </script>
                                    ';
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
            }
        }
        return $preloadedWidgets;
    }
}
