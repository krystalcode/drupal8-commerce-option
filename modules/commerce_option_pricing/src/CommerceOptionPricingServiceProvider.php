<?php

namespace Drupal\commerce_option_pricing;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Alter the order refresh service class.
 *
 * Set a new class for order refresh service so as to consider the option value
 * for purchase price.
 */
class CommerceOptionPricingServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('commerce_order.order_refresh');
    $definition->setClass('Drupal\commerce_option_pricing\OrderRefresh');
  }

}
