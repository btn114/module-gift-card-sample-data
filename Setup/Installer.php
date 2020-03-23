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
    private $abandonedCart;

    /**
     * Installer constructor.
     *
     * @param GiftCard $abandonedCart
     */
    public function __construct(
        GiftCard $abandonedCart
    ) {
        $this->abandonedCart = $abandonedCart;
    }

    /**
     * {@inheritdoc}
     * @throws Exception
     */
    public function install()
    {
        $this->abandonedCart->install(['Mageplaza_GiftCardSampleData::fixtures/abandoned_cart.csv']);
    }
}
