<?php

namespace Drupal\auctions_core\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks number input elements values for simple money like signatures.
 *
 * @Constraint(
 *   id = "AuctionsPrice",
 *   label = @Translation("Auctions Price validate", context = "Validation"),
 *   type = "string"
 * )
 */
class AuctionsPrice extends Constraint {

  /**
   * The message that will be shown if the value is not an integer.
   */
  public $notNumeric = '%value is not an number';

  /**
   * The message that will be shown if the value is not unique.
   */
  public $notNegitive = '%value is below zero (0)';

}
