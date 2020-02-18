<?php

namespace Drupal\Tests\commerce_option\Kernel;

use Drupal\commerce_option\Entity\ProductOption;
use Drupal\commerce_option\Entity\ProductOptionValue;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the product option value storage.
 *
 * @group commerce_option
 */
class OptionValueStorageTest extends CommerceKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'path',
    'commerce_option',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('commerce_option_product_option');
    $this->installEntitySchema('commerce_option_option_value');
  }

  /**
   * Tests loadMultipleByOption()
   */
  public function testLoadMultipleByOption() {
    $color_option = ProductOption::create([
      'id' => 'color',
      'label' => 'Color',
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

    /** @var \Drupal\commerce_option\ProductOptionValueStorageInterface $option_value_storage */
    $option_value_storage = $this->container->get('entity_type.manager')->getStorage('commerce_option_option_value');
    /** @var \Drupal\commerce_option\Entity\ProductOptionValueInterface[] $option_values */
    $option_values = $option_value_storage->loadMultipleByOption('color');

    $value = array_shift($option_values);
    $this->assertEquals('Cyan', $value->getName());
    $value = array_shift($option_values);
    $this->assertEquals('Magenta', $value->getName());
    $value = array_shift($option_values);
    $this->assertEquals('Yellow', $value->getName());
    $value = array_shift($option_values);
    $this->assertEquals('Black', $value->getName());
  }

}
