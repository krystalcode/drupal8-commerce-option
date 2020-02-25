<?php

namespace Drupal\Tests\commerce_option\Functional;

use Drupal\commerce_option\Entity\ProductOption;
use Drupal\commerce_option\Entity\ProductOptionValue;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

/**
 * Tests the cart functionality.
 *
 * @group commerce_option
 */
class OptionWidgetFunctionalityTest extends CommerceBrowserTestBase {

  /**
   * The product entity.
   *
   * @var \Drupal\commerce_product\Entity\Product
   */
  protected $productEntity;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_option',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $variation = $this->createEntity('commerce_product_variation', [
      'type' => 'option',
      'sku' => $this->randomMachineName(),
      'price' => [
        'number' => 350,
        'currency_code' => 'USD',
      ],
    ]);

    $this->productEntity = $this->createEntity('commerce_product', [
      'type' => 'option',
      'title' => $this->randomMachineName(),
      'stores' => [$this->store],
      'variations' => [$variation],
    ]);
  }

  /**
   * Tests the widget functionality.
   */
  public function testWidget() {
    /** @var \Drupal\commerce_option\Entity\ProductOption $color_option */
    $color_option = ProductOption::create([
      'id' => 'color',
      'label' => 'Color',
      'productTypes' => [
        0 => [
          'target_id' => 'option',
        ],
      ],
    ]);
    $color_option->save();

    $black = ProductOptionValue::create([
      'product_option' => 'color',
      'name' => 'Black',
      'weight' => 3,
    ]);
    $black->save();
    $yellow = ProductOptionValue::create([
      'product_option' => 'color',
      'name' => 'Yellow',
      'weight' => 2,
    ]);
    $yellow->save();
    $magenta = ProductOptionValue::create([
      'product_option' => 'color',
      'name' => 'Magenta',
      'weight' => 1,
    ]);
    $magenta->save();
    $cyan = ProductOptionValue::create([
      'product_option' => 'color',
      'name' => 'Cyan',
      'weight' => 0,
    ]);
    $cyan->save();

    /** @var \Drupal\commerce_option\Entity\ProductOption $another_option */
    $another_option = ProductOption::create([
      'id' => 'another',
      'label' => 'Another',
      'productTypes' => [
        0 => [
          'target_id' => 'option',
        ],
      ],
    ]);
    $another_option->save();

    $anotherValue = ProductOptionValue::create([
      'product_option' => 'another',
      'name' => 'another values',
      'weight' => 0,
    ]);
    $anotherValue->save();

    $variation2 = $this->createEntity('commerce_product_variation', [
      'type' => 'option',
      'sku' => $this->randomMachineName(),
      'price' => [
        'number' => 999,
        'currency_code' => 'USD',
      ],
    ]);

    $this->productEntity->addVariation($variation2);
    $this->productEntity->save();

    // We added 2 variations, the widget only support products with 1. Nothing
    // Should be displayed we only log an error to the watchdog.
    $this->drupalGet($this->productEntity->toUrl());
    $this->assertSession()->pageTextNotContains('Color');
    $this->assertSession()->pageTextNotContains('Another');

    $variation2->delete();

    // Test that the widget shows all options and values.
    $this->drupalGet($this->productEntity->toUrl());
    $this->assertSession()->selectExists('Color');
    $this->assertSession()->optionExists('Color' , $black->id());
    $this->assertSession()->optionExists('Color' , $yellow->id());
    $this->assertSession()->optionExists('Color' , $magenta->id());
    $this->assertSession()->optionExists('Color' , $cyan->id());

    $this->assertSession()->selectExists('Another');
    $this->assertSession()->optionExists('Another' , $anotherValue->id());

    $this->getSession()->getPage()->selectFieldOption('Color', $black->id());
    $this->getSession()->getPage()->selectFieldOption('Another', $anotherValue->id());
    $this->getSession()->getPage()->pressButton('Add to cart');

    $this->drupalGet('cart');
    $elements = $this->xpath('//th[@id="view-field-options-table-column"]');
    $this->assertEqual(current($elements)->getText(), 'Options');

    $elements = $this->xpath('//form//tbody//tr');
    $this->assertSame(count($elements), 1);
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-0', 1);

    // Make sure that adding the same options merges the order item and
    // increments the quantity.
    $this->drupalGet($this->productEntity->toUrl());
    $this->getSession()->getPage()->selectFieldOption('Color', $black->id());
    $this->getSession()->getPage()->selectFieldOption('Another', $anotherValue->id());
    $this->getSession()->getPage()->fillField('quantity[0][value]', 4);
    $this->getSession()->getPage()->pressButton('Add to cart');

    $this->drupalGet('cart');
    $elements = $this->xpath('//form//tbody//tr');
    $this->assertSame(count($elements), 1);
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-0', 5);

    // Test that different options dont get merged in.
    $this->drupalGet($this->productEntity->toUrl());
    $this->getSession()->getPage()->selectFieldOption('Color', $yellow->id());
    $this->getSession()->getPage()->selectFieldOption('Another', $anotherValue->id());
    $this->getSession()->getPage()->fillField('quantity[0][value]', 3);
    $this->getSession()->getPage()->pressButton('Add to cart');

    $this->drupalGet('cart');
    // Check that the first item quantity stayed the same.
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-0', 5);
    // Check that we have a new item with correct quantity.
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-1', 3);
  }

}
