/**
 * Copyright (c) 2026 Jiya Mistry
 * Licensed under MIT
 */

<?php
declare(strict_types=1);

namespace MageJ\BrandCarousel\Api\Data;

use Magento\Framework\Api\Search\SearchResultInterface;

/**
 * Interface for brand logo search results.
 * @api
 */
interface BrandSearchResultsInterface extends SearchResultInterface
{
    /**
     * Get brand list.
     *
     * @return \MageJ\BrandCarousel\Api\Data\BrandInterface[]
     */
    public function getItems();

    /**
     * Set brand list.
     *
     * @param \MageJ\BrandCarousel\Api\Data\BrandInterface[] $items
     * @return $this
     */
    public function setItems(array $items = null);

}
