/**
 * Copyright (c) 2026 Jiya Mistry
 * Licensed under MIT
 */

<?php

declare(strict_types=1);

namespace MageJ\BrandCarousel\Api\Data;

interface BrandStoreInterface
{
    const TABLE_NAME = 'MageJ_brand_store';

    const ID = 'id';
    const BRAND_ID = 'brand_id';
    const STORE_ID = 'store_id';

    /**
     * @return int
     */
    public function getId();

    /**
     * @return int
     */
    public function getStoreId();

    /**
     * @param string $value
     * @return $this
     */
    public function setStoreId($value);
}
