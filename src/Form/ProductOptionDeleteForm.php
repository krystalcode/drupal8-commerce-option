<?php

namespace Drupal\commerce_option\Form;

use Drupal\Core\Entity\EntityDeleteForm;

/**
 * Builds the form to delete a product option.
 */
class ProductOptionDeleteForm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Deleting a product option will delete all of its values. This action cannot be undone.');
  }

}
