<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Catalog\Test\Unit\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Model\Config\Source\ProductPriceOptionsInterface;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\TierPrice;

/**
 * Class TierPriceTest.
 */
class TierPriceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductPriceOptionsInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $productPriceOptions;

    /**
     * @var ArrayManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $arrayManager;

    /**
     * @var TierPrice
     */
    private $tierPrice;

    /**
     * Set Up.
     * @return void
     */
    protected function setUp()
    {
        $this->productPriceOptions = $this->getMock(ProductPriceOptionsInterface::class);
        $this->arrayManager = $this->getMock(ArrayManager::class, [], [], '', false);

        $this->tierPrice = (new ObjectManager($this))->getObject(TierPrice::class, [
            'productPriceOptions' => $this->productPriceOptions,
            'arrayManager' => $this->arrayManager,
        ]);
    }

    /**
     * Test modifyData.
     */
    public function testModifyData()
    {
        $data = [1, 2];
        $this->assertEquals($data, $this->tierPrice->modifyData($data));
    }

    /**
     * Test modifyMeta.
     */
    public function testModifyMeta()
    {
        $meta = [1, 2];
        $tierPricePath = 'tier_price';
        $priceWrapperPath = 'tier_price/some-wrapper';
        $pricePath = $priceWrapperPath . '/price';
        $priceMeta = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'visible' => true,
                        'validation' => ['validate-zero-or-greater' => true],
                    ],
                ],
            ],
        ];

        $this->productPriceOptions->expects($this->once())->method('toOptionArray')->willReturn([
            [
                'value' => ProductPriceOptionsInterface::VALUE_FIXED,
                'label' => 'label1',
            ],
        ]);

        $this->productPriceOptions->expects($this->once())->method('toOptionArray')->willReturn([
            [
                'value' => ProductPriceOptionsInterface::VALUE_FIXED,
                'label' => 'label1',
            ],
        ]);

        $this->arrayManager
            ->expects($this->exactly(2))
            ->method('findPath')
            ->willReturnMap([
                [
                    ProductAttributeInterface::CODE_TIER_PRICE,
                    $meta,
                    null,
                    'children',
                    ArrayManager::DEFAULT_PATH_DELIMITER,
                    $tierPricePath
                ],
                [
                    ProductAttributeInterface::CODE_TIER_PRICE_FIELD_PRICE,
                    $meta,
                    $tierPricePath,
                    null,
                    ArrayManager::DEFAULT_PATH_DELIMITER,
                    $pricePath
                ],
            ]);
        $this->arrayManager
            ->expects($this->once())
            ->method('get')
            ->with($pricePath, $meta)
            ->willReturn($priceMeta);
        $this->arrayManager
            ->expects($this->once())
            ->method('remove')
            ->with($pricePath, $meta)
            ->willReturn($meta);
        $this->arrayManager
            ->expects($this->once())
            ->method('slicePath')
            ->with($pricePath, 0, -1)
            ->willReturn($priceWrapperPath);
        $this->arrayManager
            ->expects($this->once())
            ->method('merge')
            ->with($priceWrapperPath, $meta, $this->isType('array'))
            ->willReturnArgument(2);

        $modifiedMeta = $this->tierPrice->modifyMeta($meta);
        $children = $modifiedMeta['price_value']['children'];

        $this->assertNotEmpty($children[ProductAttributeInterface::CODE_TIER_PRICE_FIELD_VALUE_TYPE]);
        $this->assertNotEmpty($children[ProductAttributeInterface::CODE_TIER_PRICE_FIELD_PERCENTAGE_VALUE]);
        $this->assertEquals($priceMeta, $children[ProductAttributeInterface::CODE_TIER_PRICE_FIELD_PRICE]);
    }
}
