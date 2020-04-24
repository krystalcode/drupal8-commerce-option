<?php

namespace Drupal\commerce_option_pricing\Resolver;

use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_price\Price;
use Drupal\commerce_price\Resolver\PriceResolverInterface;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Calculates the price based on base price and the option price.
 */
class OptionPriceResolver implements PriceResolverInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * OptionPriceResolver constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function resolve(
    PurchasableEntityInterface $entity,
    $quantity,
    Context $context
  ) {
    $options = $context->getData('commerce_product_option');

    $additional_price = new Price(
      '0',
      $entity->getPrice()->getCurrencyCode()
    );

    // Loop through each option and add the prices.
    foreach ($options as $option) {
      // Load the commerce option.
      $commerce_option = $this->entityTypeManager
        ->getStorage('commerce_option_option_value')
        ->load($option);

      if (!$commerce_option) {
        continue;
      }

      // If option has a pricing associated with it, add that to base
      // price.
      if ($commerce_option->get('pricing')->isEmpty()) {
        continue;
      }
      $option_price = $commerce_option->get('pricing')
        ->first()
        ->toPrice();

      $additional_price = $additional_price
        ->add($option_price);
    }

    return $entity->getPrice()->add($additional_price);
  }

}
