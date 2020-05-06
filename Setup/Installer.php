<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Mageplaza
 * @package     Mageplaza_GiftCardSampleData
 * @copyright   Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\GiftCardSampleData\Setup;

use Exception;
use Magento\Framework\Setup;
use Mageplaza\GiftCardSampleData\Model\GiftCard;

/**
 * Class Installer
 * @package Mageplaza\GiftCardSampleData\Setup
 */
class Installer implements Setup\SampleData\InstallerInterface
{
    /**
     * @var GiftCard
     */
    private $giftCard;

    /**
     * Installer constructor.
     *
     * @param GiftCard $giftCard
     */
    public function __construct(
        GiftCard $giftCard
    ) {
        $this->giftCard = $giftCard;
    }

    /**
     * {@inheritdoc}
     * @throws Exception
     */
    public function install()
    {
        $this->giftCard->install([
            'Mageplaza_GiftCardSampleData::fixtures/mageplaza_giftcard_template.csv',
            'Mageplaza_GiftCardSampleData::fixtures/giftcard.csv',
            'Mageplaza_GiftCardSampleData::fixtures/mageplaza_giftcard_pool.csv',
        ]);
    }
}
