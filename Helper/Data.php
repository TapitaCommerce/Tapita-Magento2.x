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

    public function __construct(
        TypeListInterface $cache,
        \Magento\Cms\Model\PageFactory $cmsPageFactory,
        AssetRepository $assetRepository,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->cache = $cache;
        $this->assetRepository = $assetRepository;
        $this->scopeConfig = $scopeConfig;
        $this->cmsPageFactory = $cmsPageFactory;
    }

    public function fetchPages()
    {
        $enable = $this->scopeConfig->getValue('tpbuilder/general/enable');
        $token = $this->scopeConfig->getValue('tpbuilder/general/integration_token');
        if ($enable == '1' && $token && $_SERVER['HTTP_REFERER'] && strpos($_SERVER['HTTP_REFERER'], 'tpbuilder') !== false) {
            $jsLibPath = $this->assetRepository->createAsset(
                'Tapita_Tpbuilder::js/simi-pagebuilder-react@1.3.8.umd.js',
                ['area' => 'frontend']
            );
            $jsLibPath = $jsLibPath->getUrl();
            $pbData = file_get_contents('https://tapita.io/pb/publishedpb/?integrationToken=' . $token);
            $pbDataObj = json_decode($pbData, true);
            if ($pbDataObj && isset($pbDataObj['data']['spb_page']['items'])) {
                $createdPages = 0;
                $updatedPages = 0;
                $cmspages = $this->cmsPageFactory->create()->getCollection()->addFieldToFilter('is_active', 1)->toArray();
                foreach ($pbDataObj['data']['spb_page']['items'] as $pbItem) {
                    $urlPath = $pbItem['url_path'];
                    $matched = false;
                    $cmsPageToCreate = $this->cmsPageFactory->create();
                    if ($urlPath && $urlPath !== '') {
                        $urlPath = ltrim($urlPath, $urlPath[0]);
                        foreach ($cmspages['items'] as $cmspage) {
                            if (
                                $cmspage['identifier'] === $urlPath
                            ) {
                                $matched = $cmspage;
                            } else if ($cmspage['identifier'] === 'home' && $urlPath === '') {
                                $urlPath = 'home';
                            }
                        }
                        if ($matched && $matched['page_id']) {
                            if ((int)$matched['is_tapita'] !== 1) {
                                $cmsPageToDisable = $this->cmsPageFactory->create()->load($matched['page_id']);
                                $cmsPageToDisable->setData('identifier', $cmsPageToDisable->getData('identifier') . '_old')
                                    ->setStatus('is_active', 0)->save();
                                $createdPages++;
                            } else {
                                $updatedPages++;
                                $cmsPageToCreate->load($matched['page_id']);
                            }
                        } else {
                            $createdPages++;
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
                            ->setData('stores', [0])
                            ->setData('content', '
                                    <div id="tp_pb_ctn" />
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
                                ')
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
