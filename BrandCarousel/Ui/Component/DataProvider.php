/**
 * Copyright (c) 2026 Jiya Mistry
 * Licensed under MIT
 */

<?php
declare(strict_types=1);

namespace MageJ\BrandCarousel\Ui\Component;

use MageJ\BrandCarousel\Service\ImageService;
use MageJ\BrandCarousel\Api\Data\BrandInterface;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\Reporting;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider as AbstractDataProvider;

class DataProvider extends AbstractDataProvider
{
    /** @var ImageService  */
    private $imageService;

    /** @var AuthorizationInterface */
    private $authorization;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param Reporting $reporting
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RequestInterface $request
     * @param FilterBuilder $filterBuilder
     * @param AuthorizationInterface $authorization
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        Reporting $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        ImageService $imageService,
        AuthorizationInterface $authorization,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $reporting,
            $searchCriteriaBuilder,
            $request,
            $filterBuilder,
            $meta,
            $data
        );

        $this->imageService = $imageService;
        $this->authorization = $authorization;
        $this->meta = array_replace_recursive($meta, $this->prepareMetadata());
    }

    /**
     * {@inheritdoc}
     */
    protected function searchResultToOutput(SearchResultInterface $searchResult)
    {
        $arrItems = [];
        $serialNumber = 1;

        $arrItems['items'] = [];
        /** @var BrandInterface|\Magento\Framework\DataObject $item */
        foreach ($searchResult->getItems() as $item) {
            $itemData = $item->getData();                        
            $itemData['sr_no'] = $serialNumber ++;

            if ($item->getData(BrandInterface::LOGO)) {
                $itemData[BrandInterface::LOGO . '_src'] = $this->imageService->getImageUrl($item->getLogo(), 'logo');
            }

            $arrItems['items'][] = $itemData;
        }

        $arrItems['totalRecords'] = $searchResult->getTotalCount();

        return $arrItems;
    }

    /**
     * Prepares Meta
     *
     * @return array
     */
    public function prepareMetadata()
    {
        $metadata = [];

        if (!$this->authorization->isAllowed('MageJ_BrandCarousel::brandcarousel')) {
            $metadata = [
                'brand_brand_columns' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'editorConfig' => [
                                    'enabled' => false
                                ],
                                'componentType' => \Magento\Ui\Component\Container::NAME
                            ]
                        ]
                    ]
                ]
            ];
        }

        return $metadata;
    }
}
