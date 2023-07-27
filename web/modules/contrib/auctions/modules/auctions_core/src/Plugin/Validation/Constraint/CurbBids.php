<?php

namespace Drupal\auctions_core\Plugin\Validation\Constraint;

use Drupal\Core\Entity\Plugin\Validation\Constraint\CompositeConstraintBase;

/**
 * Bid Related Validation.
 *
 * @Constraint(
 *   id = "CurbBids",
 *   label = @Translation("Curb Bids", context = "Validation"),
 *   type = "entity:auction_bid"
 * )
 */
class CurbBids extends CompositeConstraintBase {

  /**
   * @string
   */
  public $auctionFinished = "This auction has been closed!";

  /**
   * String.
   */
  public $auctionNew = "This auction is not yet open!";

  /**
   * String.
   */
  public $lastBidIsYours = "You are the last bidder. Self out bidding not allowed.";

  /**
   * String.
   */
  public $auctionHasClosed = "Auction has closed!";

  /**
   * String.
   */
  public $auctionHasExpired = "Auction has expired! %value";

  /**
   * String.
   */
  public $higherThanLastBid = "This bid is not high enough…";

  /**
   * String.
   */
  public $higherThanThreshold = "Amount is higher than allowed bid threshold. %value";

  /**
   * Fields to validate against.
   */
  public function coversFields() {
    return ["amount", "item"];
  }

}
