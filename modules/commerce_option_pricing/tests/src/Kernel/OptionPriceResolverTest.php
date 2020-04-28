<?php

namespace Drupal\Tests\commerce_option_pricing\Kernel;

use Drupal\commerce\Context;
use Drupal\commerce_option\Entity\ProductOption;
use Drupal\commerce_option\Entity\ProductOptionValue;
use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductType;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the option price resolver.
 *
 * @group commerce_option_pricing
 */
class OptionPriceResolverTest extends CommerceKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_product',
    'commerce_option',
    'commerce_option_pricing',
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
    // Install system tables to test the key/value storage without installing a
    // full Drupal environment.
    $this->installSchema('system', ['key_value_expire']);
  }

  /**
   * Tests the option price resolver service.
   */
  public function testOptionPriceResolver() {
    $optionProductType = ProductType::load('default');

    $product = Product::create([
      'type' => $optionProductType->id(),
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
          'target_id' => $optionProductType->id(),
        ],
      ],
    ]);
    $color_option->save();

    $black = ProductOptionValue::create([
      'product_option' => 'color',
      'name' => 'Black',
      'weight' => 3,
      'pricing' => new Price('1.00', 'USD'),
    ]);
    $black->save();

    $yellow = ProductOptionValue::create([
      'product_option' => 'color',
      'name' => 'Yellow',
      'weight' => 2,
      'pricing' => new Price('2.00', 'USD'),
    ]);
    $yellow->save();
    ProductOptionValue::create([
      'product_option' => 'color',
      'name' => 'Magenta',
      'weight' => 1,
      'pricing' => new Price('3.00', 'USD'),
    ])->save();
    ProductOptionValue::create([
      'product_option' => 'color',
      'name' => 'Cyan',
      'weight' => 0,
      'pricing' => new Price('4.00', 'USD'),
    ])->save();

    $user = \Drupal::getContainer()->get('current_user')->getAccount();
    $data = [
      'field_name' => 'price',
      'commerce_product_option' => [
        $black->id() => $black->id(),
        $yellow->id() => $yellow->id(),
      ],
    ];
    $context = new Context($user, $this->store, NULL, $data);

    $resolvedPrice = \Drupal::getContainer()->get('commerce_option_pricing.option_price_resolver')->resolve($variation, 1, $context);
    // Base price 12 + black color 1 + yellow color 2.
    $this->assertEqual(15, $resolvedPrice->getNumber());
  }

}
