<?php

namespace Drupal\Tests\commerce_option\Kernel;

use Drupal\commerce_option\Entity\ProductOption;
use Drupal\commerce_option\Entity\ProductOptionValue;
use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductType;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the option resolver.
 *
 * @group commerce_option
 */
class OptionResolverTest extends CommerceKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_option',
    'commerce_product',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('commerce_option_product_option');
    $this->installEntitySchema('commerce_option_option_value');
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_variation');
    $this->installConfig(['commerce_product']);
  }

  /**
   * Tests the option resolver service.
   */
  public function testOptionResolver() {
    $defaultProductType = ProductType::load('default');

    $product = Product::create([
      'type' => $defaultProductType->id(),
      'title' => 'Default testing product',
    ]);
    $product->save();

    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variation */
    $variation = ProductVariation::create([
      'type' => 'default',
      'product_id' => $product->id(),
      'sku' => 'TEST_' . strtolower($this->randomMachineName()),
      'title' => $this->randomString(),
      'price' => new Price('12.00', 'USD'),
      'status' => TRUE,
    ]);
    $variation->save();

    /** @var \Drupal\commerce_option\Entity\ProductOption $color_option */
    $color_option = ProductOption::create([
      'id' => 'color',
      'label' => 'Color',
      'productTypes' => [
        0 => [
          'target_id' => $defaultProductType->id(),
        ],
      ],
    ]);
    $color_option->save();

    ProductOptionValue::create([
      'product_option' => 'color',
      'name' => 'Black',
      'weight' => 3,
    ])->save();
    ProductOptionValue::create([
      'product_option' => 'color',
      'name' => 'Yellow',
      'weight' => 2,
    ])->save();
    ProductOptionValue::create([
      'product_option' => 'color',
      'name' => 'Magenta',
      'weight' => 1,
    ])->save();
    ProductOptionValue::create([
      'product_option' => 'color',
      'name' => 'Cyan',
      'weight' => 0,
    ])->save();

    // Product option that shouldn't be loaded since no product types are set.
    /** @var \Drupal\commerce_option\Entity\ProductOption $another_option */
    $another_option = ProductOption::create([
      'id' => 'another',
      'label' => 'Another',
      'productTypes' => [],
    ]);
    $another_option->save();

    ProductOptionValue::create([
      'product_option' => 'another',
      'name' => 'another values',
      'weight' => 0,
    ])->save();

    $resolvedOption = \Drupal::getContainer()->get('commerce_option.option_resolver')->resolveOptions($variation);
    $this->assertCount(1, $resolvedOption);
    $this->assertEqual('color', $resolvedOption['color']->id());

    $resolvedOptionValues = \Drupal::getContainer()->get('commerce_option.option_resolver')->resolveOptionValues($variation, $color_option);

    $this->assertCount(4, $resolvedOptionValues);
    $this->assertSame($resolvedOptionValues[4]->getName(), 'Cyan');
    $this->assertSame($resolvedOptionValues[3]->getName(), 'Magenta');
    $this->assertSame($resolvedOptionValues[2]->getName(), 'Yellow');
    $this->assertSame($resolvedOptionValues[1]->getName(), 'Black');
  }

}
