<?php

namespace Drupal\auctions_core;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Common functions.
 */
trait AuctionToolsTrait {
  use StringTranslationTrait;

  /**
   * Rounds up user cents for validation/math simplicity.
   * This is for input from a html number input.
   *
   * @return float
   */
  public function roundCents($float = '0.000') {
    return \round($float, 2);
  }

  /**
   * Take a stored float and formats as readible cents (with 2 zeros after decimal).
   * This is for output to a html number input.
   *
   * @return string
   */
  public function showAsCents($float = '0.000', $dec = '.', $thous = '') {
    return \number_format($float, 2, $dec, $thous);
  }

  /**
   * Masks user name by leaving only first and last letter.
   *
   * @param string $name
   *
   * @return string
   */
  public function maskUsername($name) {
    $format_name = '';
    if (\Drupal::currentUser()->hasPermission('see bidders names')) {
      $format_name = $name;
    }
    else {
      $format_name = $this->replaceUsername($name);
    }
    return $format_name;
  }

  /**
   * Handle masking username depending on string lenght.
   *
   * @param string $name
   *
   * @return string
   */
  public function replaceUsername($name) {
    // If less than 2 character replace all of them.
    $characters = \strlen($name);
    if ($characters <= 2) {
      return \str_repeat("*", $characters);
    }
    return \preg_replace("/(?!^).(?!$)/", "*", $name);
  }

  /**
   * Return a list bid types.
   */
  public static function bidTypeList() {
    return [
      'standard' => t("Standard Bid"),
      'instant' => t("Instant Bid"),
      'auto' => t("Autobid"),
    ];
  }

  /**
   * Return a list bid types.
   */
  public function bidPurchaseOffer() {
    return [
      '0' => t("n/a"),
      '2' => t("Offered | Acceptance Pending"),
      '-1' => t("Rejected Offer to Purchase"),
      '3' => t("Purchesed Item"),
    ];
  }

  /**
   * Return a list of known global currencies.
   */
  public static function itemWorkflows() {
    return [
      '-1' => t("Deleted"),
      '0' => t("Not yet started"),
      '1' => t("Active"),
      '2' => t("Relisted"),
      '3' => t("Finished"),
    ];
  }

  /**
   * Return a list of known global currencies.
   */
  public function itemPriceMonitor() {
    return [
      'price_starting' => 'setPriceStarting',
      'price_buy_now' => 'setPriceBuyNow',
      'bid_step' => 'setBidStep',
    ];
  }

  /**
   * Return a list of allowed tags in email. Master control.
   */
  public function allowedTags() {
    return ['html', 'head', 'body', 'style', 'p', 'a', 'br', 'b', 'u', 'em', 'strong', 'ul', 'ol', 'li', 'dl', 'dt', 'dd', 'div', 'span', 'header', 'main', 'section', 'footer', 'cite', 'blockquote', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'sup', 'sub'];
  }

}
