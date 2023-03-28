<?php

namespace Tapita\Tpbuilder\Helper;

use \Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\View\Asset\Repository as AssetRepository;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $scopeConfig;
    protected $cache;
    protected $cmsPageFactory;
    protected $assetRepository;
    protected $random;
    protected $storeManager;
    protected $layout;
    protected $registry;
    protected $productModel;
    protected $objManager;

    public function __construct(
        TypeListInterface $cache,
        \Magento\Cms\Model\PageFactory $cmsPageFactory,
        AssetRepository $assetRepository,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\View\LayoutInterface $layout,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Model\ProductFactory $productModel,
        \Magento\Framework\ObjectManagerInterface $objManager,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->cache = $cache;
        $this->assetRepository = $assetRepository;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->layout = $layout;
        $this->registry = $registry;
        $this->productModel = $productModel;
        $this->objManager = $objManager;
        $this->cmsPageFactory = $cmsPageFactory;
    }

    public function fetchPages()
    {
        $enable = $this->scopeConfig->getValue('tpbuilder/general/enable');
        $token = $this->scopeConfig->getValue('tpbuilder/general/integration_token');
        if ($enable == '1' && $token) {
            $jsLibPath = $this->assetRepository->createAsset(
                'Tapita_Tpbuilder::js/simi-pagebuilder-react@1.6.6.umd.js',
                ['area' => 'frontend']
            );
            $jsLibPath = $jsLibPath->getUrl();
            $pbData = file_get_contents('https://tapita.io/pb/publishedpb/?integrationToken=' . $token);
            $pbDataObj = json_decode($pbData, true);
            $storeManager = $this->storeManager;
            if ($pbDataObj && isset($pbDataObj['data']['spb_page']['items'])) {
                $createdPages = 0;
                $updatedPages = 0;
                $cmspages = $this->cmsPageFactory->create()->getCollection()->addFieldToFilter('is_active', 1)->toArray();
                foreach ($pbDataObj['data']['spb_page']['items'] as $pbItem) {
                    $urlPath = $pbItem['original_url_path'];
                    $matched = false;
                    $cmsPageToCreate = $this->cmsPageFactory->create();
                    if ($urlPath && $urlPath !== '') {
                        $urlPath = ltrim($urlPath, $urlPath[0]);
                        foreach ($cmspages['items'] as $cmspage) {
                            if (
                                $cmspage['identifier'] === $urlPath
                            ) {
                                $matched = $cmspage;
                            } elseif ($cmspage['identifier'] === 'home' && $urlPath === '') {
                                $urlPath = 'home';
                                $matched = $cmspage;
                            }
                        }
                        if ($matched && $matched['page_id']) {
                            if ((int)$matched['is_tapita'] !== 1) {
                                if ($this->scopeConfig->getValue('tpbuilder/general/override') == '1') {
                                    $updatedPages++;
                                    $cmsPageToCreate->load($matched['page_id']);
                                } else {
                                    continue;
                                }
                            } else {
                                $updatedPages++;
                                $cmsPageToCreate->load($matched['page_id']);
                            }
                        } else {
                            $createdPages++;
                        }

                        $storeview_visibility = $pbItem['storeview_visibility'];
                        $storeview_visibility = explode(',', $storeview_visibility);
                        $storeViews = [];
                        foreach ($storeview_visibility as $storeview) {
                            $storecode = $storeview; //storecode here
                            if ($storecode == "") {
                                $storeViews[] = 0;
                            } else {
                                $stores = $storeManager->getStores(true, true);
                                if (isset($stores[$storecode])) {
                                    $store_id = $stores[$storecode]->getId();
                                    $storeViews[] = $store_id;
                                }
                            }
                        }
                        if (count($storeViews) == 0) {
                            $storeViews[] = 0;
                        }
                        $cmsContent = '
                            <div id="tp_pb_ctn"></div>
                            <script type="text/javascript">
                                require([
                                    "' . $jsLibPath . '"
                                ], function(PageBuilderComponent) {
                                    var pageDataToRender = ' . json_encode($pbItem) . ';
                                    PageBuilderComponent.renderForIdWithProps("tp_pb_ctn", {
                                        endPoint: "https://tapita.io/pb/graphql/",
                                        maskedId: "' . $pbItem['masked_id'] . '",
                                        pageData: pageDataToRender,
                                    })
                                });
                            </script>
                        ';
                        $publishedItems = json_decode($pbItem['publish_items'], true);
                        if ($publishedItems && count($publishedItems)) {
                            foreach ($publishedItems as $publishedItem) {
                                if (
                                    $publishedItem['type'] === 'product_scroll' ||
                                    $publishedItem['type'] === 'product_scroll_1' ||
                                    $publishedItem['type'] === 'product_grid'
                                ) {
                                    $itmData = json_decode($publishedItem['data'], true);
                                    if ($itmData && (isset($itmData['openCategoryProducts']) || isset($itmData['openProductsWidthSKUs']))) {
                                        /* openCategoryProducts , openProductsWidthSKUs , openProductsWidthSortPageSize,
                                        openProductsWidthSortAtt, openProductsWidthSortDir */
                                        $productCount = isset($itmData['openProductsWidthSortPageSize']) ? $itmData['openProductsWidthSortPageSize'] : 10;
                                        $productListAttribute = isset($itmData['openProductsWidthSKUs']) ? 'sku' : 'category_ids';
                                        $productListValue = isset($itmData['openProductsWidthSKUs']) ? $itmData['openProductsWidthSKUs'] : $itmData['openCategoryProducts'];
                                        $listBlockContent = '';
                                        if ($publishedItem['type'] === 'product_scroll' || $publishedItem['type'] === 'product_scroll_1') {
                                            if ($productListAttribute === 'sku') {
                                                $productSKUs = str_replace(" ", "", $productListValue);
                                                $listBlockContent = '{{widget type="Tapita\Tpbuilder\Block\Widget\Slider" show_pager="0" products_count="' . $productCount . '" ' .
                                                    ' product_skus="' .  $productSKUs . '" cache_tag="' . $publishedItem['entity_id'] . '"' .
                                                    ' product_type="custom"}}';
                                            } else {
                                                $listBlockContent = '{{widget type="Tapita\Tpbuilder\Block\Widget\Slider" show_pager="0" products_count="' . $productCount . '" ' .
                                                    ' categories_ids="' .  $itmData['openCategoryProducts'] . '" ' . '" cache_tag="' . $publishedItem['entity_id'] . '"' .
                                                    ' product_type="category"}}';
                                            }
                                        } else {
                                            $listBlockContent = '{{widget type="Magento\CatalogWidget\Block\Product\ProductsList" show_pager="0" products_count="' . $productCount . '" ' .
                                                'template="Magento_CatalogWidget::product/widget/content/grid.phtml" conditions_encoded="^[`1`:^[`type`:`Magento||CatalogWidget||Model||Rule||Condition||Combine`,' .
                                                '`aggregator`:`all`,`value`:`1`,`new_child`:``^],`1--1`:^[`type`:`Magento||CatalogWidget||Model||Rule||Condition||Product`,' .
                                                '`attribute`:`' . $productListAttribute . '`,`operator`:`==`,`value`:`' . $productListValue . '`^]^]"}}';
                                        }

                                        $widgetToAdd = '
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
                                        $cmsContent .= $widgetToAdd;
                                    }
                                }
                            }
                        }

                        $cmsPageToCreate
                            ->setData('identifier', $urlPath)
                            ->setData('title', $pbItem['name'])
                            ->setData('meta_title', $pbItem['title'])
                            ->setData('meta_description', $pbItem['desc'])
                            ->setData('meta_keywords', $pbItem['keywords'])
                            ->setData('sort_order', $pbItem['priority'])
                            ->setData('page_layout', '1column')
                            ->setData('is_tapita', 1)
                            ->setData('stores', $storeViews)
                            ->setData('content', $cmsContent)
                            ->save();
                    }
                }
                return [
                    $createdPages,
                    $updatedPages
                ];
            }
        }
    }
}
