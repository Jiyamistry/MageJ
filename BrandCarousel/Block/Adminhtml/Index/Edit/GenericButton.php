/**
 * Copyright (c) 2026 Jiya Mistry
 * Licensed under MIT
 */

<?php

namespace MageJ\BrandCarousel\Block\Adminhtml\Index\Edit;

use MageJ\BrandCarousel\Api\BrandRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Context;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Block class for generic button classes
 */
class GenericButton
{
    /** @var Context */
    protected $context;

    /** @var BrandRepositoryInterface */
    protected $brandRepository;

    /**
     * @param Context $context
     * @param BrandRepositoryInterface $brandRepository
     */
    public function __construct(
        Context $context,
        BrandRepositoryInterface $brandRepository
    ) {
        $this->context = $context;
        $this->brandRepository = $brandRepository;
    }

    /**
     * Return CMS block ID
     *
     * @return int|null
     * @throws LocalizedException
     */
    public function getBrandId()
    {
        $brandId = $this->context->getRequest()->getParam('brand_id');
        if ($brandId === NULL) {
            return null;
        }
        try {
            return $this->brandRepository->getById($brandId)->getId();
        } catch (NoSuchEntityException $e) {
            throw new LocalizedException(__('The specified logo id does not exist.'));
        }
        return null;
    }

    /**
     * Generate url by route and parameters
     *
     * @param   string $route
     * @param   array $params
     * @return  string
     */
    public function getUrl($route = '', $params = [])
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
}
