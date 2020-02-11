<?php

namespace Drupal\commerce_option;

use Drupal\commerce_option\Entity\ProductOptionInterface;

/**
 * Manages option fields.
 *
 * Option fields are entity reference fields storing values of a specific
 * option on the product variation.
 */
interface ProductOptionFieldManagerInterface {

  /**
   * Gets the option field definitions.
   *
   * The field definitions are not ordered.
   * Use the field map when the field order is important.
   *
   * @param string $variation_type_id
   *   The product variation type ID.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   The option field definitions, keyed by field name.
   */
  public function getFieldDefinitions($variation_type_id);

  /**
   * Gets a map of option fields across variation types.
   *
   * @param string $variation_type_id
   *   (Optional) The product variation type ID.
   *   When given, used to filter the returned maps.
   *
   * @return array
   *   If a product variation type ID was given, a list of maps.
   *   Otherwise, a list of maps grouped by product variation type ID.
   *   Each map is an array with the following keys:
   *   - option_id: The option id;
   *   - field_name: The option field name.
   *   The maps are ordered by the weight of the option fields on the
   *   default product variation form display.
   */
  public function getFieldMap($variation_type_id = NULL);

  /**
   * Clears the options field map and definition caches.
   */
  public function clearCaches();

  /**
   * Creates an option field for the given attribute.
   *
   * @param \Drupal\commerce_option\Entity\ProductOptionInterface $option
   *   The product option.
   * @param string $variation_type_id
   *   The product variation type ID.
   */
  public function createField(ProductOptionInterface $option, $variation_type_id);

  /**
   * Checks whether the option field for the given option can be deleted.
   *
   * An option field is no longer deletable once it has data.
   *
   * @param \Drupal\commerce_option\Entity\ProductOptionInterface $option
   *   The product option.
   * @param string $variation_type_id
   *   The product variation type ID.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the option field does not exist.
   *
   * @return bool
   *   TRUE if the option field can be deleted, FALSE otherwise.
   */
  public function canDeleteField(ProductOptionInterface $option, $variation_type_id);

  /**
   * Deletes the option field for the given option.
   *
   * @param \Drupal\commerce_option\Entity\ProductOptionInterface $option
   *   The product option.
   * @param string $variation_type_id
   *   The product variation type ID.
   */
  public function deleteField(ProductOptionInterface $option, $variation_type_id);

}
