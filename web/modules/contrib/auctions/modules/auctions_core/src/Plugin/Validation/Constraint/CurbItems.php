<?php

namespace Drupal\auctions_core\Plugin\Validation\Constraint;

use Drupal\Core\Entity\Plugin\Validation\Constraint\CompositeConstraintBase;

/**
 * Item Related Validation.
 *
 * @Constraint(
 *   id = "CurbItems",
 *   label = @Translation("Curbs item submissions.", context = "Validation"),
 *   type = "entity:auction_item"
 * )
 */
class CurbItems extends CompositeConstraintBase {

  /**
   * @var string
   */
  public $buyNowLowerThanStartingPrice = 'Buy Now amount is lower than Starting Price.';

  /**
   * @var string
   */
  public $instantOnlyWithoutBuyNowPrice = 'Auction Item is Set as Instant Only without Buy Now Price';
  /**
   * @var string
   */
  public $startingPriceIsZero = 'Starting Price can not be nothing (zero).';

  /**
   * {@inheritdoc}
   */
  public function coversFields() {
    return ['price_buy_now', 'price_starting', 'instant_only'];
  }

}
