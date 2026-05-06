<?php
namespace MageJ\BrandCarousel\Model;
class Status
{
    public const STATUS_ENABLED = 1;
    public const STATUS_DISABLED = 0;

    /**
     * Get available statuses.
     *
     * @return []
     */
    public function getAvailableStatuses()
    {
        return [
            self::STATUS_ENABLED => __('Enabled')
            , self::STATUS_DISABLED => __('Disabled'),
        ];
    }
}
