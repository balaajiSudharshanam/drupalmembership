<?php

namespace Drupal\auctions_core\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the UniqueInteger constraint.
 */
class AuctionsPriceValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {

    foreach ($items as $item) {
      // Check if the value is an number.
      if (!\is_numeric($item->value)) {
        $this->context->addViolation($constraint->notNumeric, ['%value' => $item->value]);
      }

      // Check if the value is not negitive.
      if ($this->isNotNegitive($item->value)) {
        $this->context->addViolation($constraint->notNegitive, ['%value' => $item->value]);
      }

    }
  }

  /**
   * Is number less than 0 (contains minus ('-') character)
   *
   * @param string $value
   */
  private function isNotNegitive($float) {
    if (\strpos($float, '-') !== FALSE) {
      return TRUE;
    }
    return FALSE;
  }

}
