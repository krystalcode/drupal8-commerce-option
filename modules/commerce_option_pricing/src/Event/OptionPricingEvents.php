<?php

namespace Drupal\commerce_option_pricing\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class OptionPricingEvents.
 *
 * @package Drupal\commerce_option_pricing\Event
 */
final class OptionPricingEvents extends Event {

  /**
   * Name of the event that fires after pricing based on options is resolved.
   *
   * @Event
   *
   * @var string
   */
  const OPTION_PRICING_RESOLVE = 'commerce_option_pricing.option_pricing_resolve';

}
