<?php

namespace Drupal\auctions_core\Entity;

use Drupal\auctions_core\AuctionToolsTrait;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Auction Items entity.
 *
 * @ingroup auctions_core
 *
 * @ContentEntityType(
 *   id = "auction_item",
 *   label = @Translation("Auction Items"),
 *   handlers = {
 *     "storage" = "Drupal\auctions_core\AuctionItemStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\auctions_core\AuctionItemListBuilder",
 *     "views_data" = "Drupal\auctions_core\Entity\AuctionItemViewsData",
 *     "translation" = "Drupal\auctions_core\AuctionItemTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\auctions_core\Form\AuctionItemForm",
 *       "add" = "Drupal\auctions_core\Form\AuctionItemForm",
 *       "edit" = "Drupal\auctions_core\Form\AuctionItemForm",
 *       "relist" = "Drupal\auctions_core\Form\AuctionItemRelistForm",
 *       "delete" = "Drupal\auctions_core\Form\AuctionItemDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\auctions_core\AuctionItemHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\auctions_core\AuctionItemAccessControlHandler",
 *   },
 *   base_table = "auction_item",
 *   data_table = "auction_item_field_data",
 *   revision_table = "auction_item_revision",
 *   revision_data_table = "auction_item_field_revision",
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_uid",
 *     "revision_created" = "revision_timestamp",
 *     "revision_log_message" = "revision_log"
 *   },
 *   translatable = TRUE,
 *   admin_permission = "administer auction items entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "published" = "status",
 *     "workflow" = "workflow"
 *   },
 *   links = {
 *     "canonical" = "/auction/item/{auction_item}",
 *     "add-form" = "/admin/content/auctions/add",
 *     "edit-form" = "/admin/content/auctions/{auction_item}/edit",
 *     "relist-form" = "/admin/content/auctions/{auction_item}/relist",
 *     "delete-form" = "/admin/content/auctions/{auction_item}/delete",
 *     "version-history" = "/admin/content/auctions/{auction_item}/revisions",
 *     "revision" = "/admin/content/auctions/{auction_item}/revisions/{auction_item_revision}/view",
 *     "revision_revert" = "/admin/content/auctions/{auction_item}/revisions/{auction_item_revision}/revert",
 *     "revision_delete" = "/admin/content/auctions/{auction_item}/revisions/{auction_item_revision}/delete",
 *     "translation_revert" = "/admin/content/auctions/{auction_item}/revisions/{auction_item_revision}/revert/{langcode}",
 *     "collection" = "/admin/content/auctions",
 *   },
 *   field_ui_base_route = "auction_item.settings"
 * )
 */
class AuctionItem extends EditorialContentEntityBase implements AuctionItemInterface {

  use EntityChangedTrait;
  use EntityPublishedTrait;
  use AuctionToolsTrait;

  /**
   *
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   *
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);

    if ($rel === 'revision_revert' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }
    elseif ($rel === 'revision_delete' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }

    return $uri_route_parameters;
  }

  /**
   *
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    foreach (array_keys($this->getTranslationLanguages()) as $langcode) {
      $translation = $this->getTranslation($langcode);

      // If no owner has been set explicitly, make the anonymous user the owner.
      if (!$translation->getOwner()) {
        $translation->setOwnerId(0);
      }
    }

    // Handle workflow to new if after start date on entity creat.
    $dateStatus = $this->getDateStatus();
    if ($this->isNew() && $dateStatus['start'] == 'post') {
      $this->setWorkflow(1);
    }

    // If no revision author has been set explicitly,
    // make the auction_item owner the revision author.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
    }
  }

  /**
   *
   */
  public function getId() {
    return $this->get('id')->value;
  }

  /**
   *
   */
  public function setId($id) {
    $this->set('id', $id);
    return $this;
  }

  /**
   *
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   *
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   *
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   *
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   *
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   *
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   *
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   *
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   *
   */
  public function getDate() {
    return [
      'value' => $this->get('date')->value,
      'end' => $this->get('date')->end_value,
    ];
  }

  /**
   * @param array $daterange
   *   [
   *    'value' => 'Y-m-d\TH:i:s',
   *    'end_value' => 'Y-m-d\TH:i:s',
   *   ].
   */
  public function setDate($daterange) {
    $this->set('date', $daterange);
    return $this;
  }

  /**
   *
   */
  public function getPriceThreshold() {
    $priceThreshold = $this->get('price_threshold')->value;
    return $priceThreshold;
  }

  /**
   *
   */
  public function setPriceThreshold($int) {
    $this->set('price_threshold', $int);
    return $this;
  }

  /**
   * General State of this auction item Dates to determine pre/active/port type logic.
   */
  public function getDateStatus() {
    $date = $this->getDate();
    $now = new DrupalDateTime('now');
    $getUserTimezone = date_default_timezone_get();
    $userTimezone = new \DateTimeZone($getUserTimezone);
    $currently = $now->setTimezone($userTimezone)->format('U');
    $auctionStarts = DrupalDateTime::createFromFormat('Y-m-d\TH:i:s', $date['value'])->setTimezone($userTimezone)->format('U');
    $auctionEnds = DrupalDateTime::createFromFormat('Y-m-d\TH:i:s', $date['end'])->setTimezone($userTimezone)->format('U');
    $status = 'start';
    $start = 'pre';
    $end = 'pre';
    if ($currently > $auctionStarts && $currently < $auctionEnds) {
      $status = 'active';
    }
    if ($currently > $auctionStarts) {
      $status = 'end';
      $start = 'post';
    }

    if ($currently > $auctionEnds) {
      $status = 'post';
      $end = 'post';
    }
    $dateFormatter = \Drupal::service('date.formatter');
    $dateStatus = [
      'status' => $status,
      'start' => $start,
      'end' => $end,
      'date' => $date,
      'now' => $now->format('U'),
      'date_formatted' => [
        'start_time' => DrupalDateTime::createFromFormat('Y-m-d\TH:i:s', $date['value'])->setTimezone($userTimezone)->format('Y-m-d H:i:s'),
        'end_time' => DrupalDateTime::createFromFormat('Y-m-d\TH:i:s', $date['end'])->setTimezone($userTimezone)->format('Y-m-d H:i:s'),
        'start_human' => $dateFormatter->format($auctionStarts, 'long'),
        'end_human' => $dateFormatter->format($auctionEnds, 'long'),
        'start_unix' => $auctionStarts,
        'end_unix' => $auctionEnds,
      ],
    ];
    return $dateStatus;
  }

  /**
   * Bst grouping of if auction item is closed.
   *
   * Return bool.
   */
  public function isClosed() {
    $dateStatus = $this->getDateStatus();
    $workflow = $this->getWorkflow();
    $isClosed = FALSE;
    if ($workflow == -1 || $workflow == 3 || $workflow == 4) {
      $isClosed = TRUE;
    }
    if ($dateStatus['status'] == 'post') {
      $isClosed = TRUE;
    }
    return $isClosed;
  }

  /**
   *
   */
  public function isOpen() {
    $dateStatus = $this->getDateStatus();
    $workflow = $this->getWorkflow();
    $isOpen = FALSE;
    if ($workflow == '1') {
      $isOpen = TRUE;
    }
    if ($dateStatus['start'] == 'post') {
      $isOpen = TRUE;
    }
    return $isOpen;
  }

  /**
   * Get Instant Only value.
   *
   * @return bool
   */
  public function getInstantOnly() {
    $instantOnly = $this->get('instant_only')->value;
    return $instantOnly;
  }

  /**
   * Get Instant Only value.
   *
   * @return false|float - price to buy now
   */
  public function hasBuyNow() {
    $instantOnly = $this->getInstantOnly();
    // Need raw value.
    $buyNowPrice = \floatval($this->get('price_buy_now')->value);
    return $instantOnly || $buyNowPrice !== 0 ? $buyNowPrice : FALSE;
  }

  /**
   *
   */
  public function setInstantOnly($value) {
    $this->set('instant_only', $value);
    return $this;
  }

  /**
   *
   */
  public function getRelistCount() {
    $relistCount = $this->get('relist_count')->value;
    return $relistCount;
  }

  /**
   *
   */
  public function setRelistCount($value) {
    $this->set('relist_count', $value);
    return $this;
  }

  /**
   *
   */
  public function getWorkflow() {
    $workflow = $this->get('workflow')->value;
    return $workflow;
  }

  /**
   * Allow other modules to interact upon auction_item workflow changes.
   * hook_auctions_core_workflow_action.
   */
  public function setWorkflow($int) {
    $this->set('workflow', $int);
    // Send new workflow state into hookl.
    \Drupal::moduleHandler()->invokeAll('auctions_core_workflow_action', [&$this, $int]);
    return $this;
  }

  /**
   *
   */
  public function getCurrencyCode() {
    $currencyCode = $this->get('currency_code')->value;
    return $currencyCode;
  }

  /**
   *
   */
  public function setCurrencyCode($value) {
    $this->set('currency_code', $value);
    return $this;
  }

  /**
   *
   */
  public function getPriceStarting() {
    $priceStarting = $this->get('price_starting')->value;
    return $priceStarting;
  }

  /**
   *
   */
  public function setPriceStarting($float) {
    $this->set('price_starting', $this->roundCents($float));
    return $this;
  }

  /**
   *
   */
  public function getBidStep() {
    $bidStep = $this->get('bid_step')->value;
    return $this->showAsCents($bidStep);
  }

  /**
   *
   */
  public function setBidStep($float) {
    $this->set('bid_step', $this->roundCents($float));
    return $this;
  }

  /**
   *
   */
  public function getPriceBuyNow() {
    $priceBuyNow = $this->get('price_buy_now')->value;
    return $this->showAsCents($priceBuyNow);
  }

  /**
   *
   */
  public function setPriceBuyNow($float) {
    $this->set('price_buy_now', $this->roundCents($float));
    return $this;
  }

  /**
   * Get all bids that belond to 'relist_group' on auction_bid entities.
   *
   * @param $relist=FALSE
   *   to just get all bids.
   * @param $topLimit=INT
   *   to just hot bids.
   *
   * @return array. Empty if none. list of Bid Entities by Highest Amount
   */
  public function getBids($relist = 0, $topLimit = FALSE) {
    $bidIds = $this->getRelistBids($relist, $topLimit);
    $bids = [];
    if ($bidIds) {
      $bidStorage = \Drupal::entityTypeManager()->getStorage('auction_bid');
      $bids = $bidStorage->loadMultiple($bidIds);
    }
    return $bids;
  }

  /**
   * Get bid id's, pre-sorted by amount!
   *
   * @param $relist=FALSE
   *   to just get all bids.
   * @param $topLimit=INT
   *   to get the top bids.
   *
   *   Return entityQuery result.
   */
  public function getRelistBids($relist = 0, $topLimit = FALSE) {
    $query = \Drupal::entityTypeManager()->getStorage('auction_bid')->getQuery();
    $id = $this->getId();
    $query->condition('item', $id);

    // Do not include rejected 'purchase_offer'.
    $query->condition('purchase_offer', '-1', '!=');

    if ($relist !== FALSE) {
      $query->condition('relist_group', $relist);
    }
    if ($topLimit) {
      $query->range(0, $topLimit);
    }
    $query->sort('amount', 'DESC');
    $entity_ids = $query->execute();
    return $entity_ids;
  }

  /**
   *
   */
  public function hasBids() {
    $relistBidsCount = $this->getRelistBids($this->getRelistCount(), 3);
    return !empty($relistBidsCount);
  }

  /**
   *
   */
  public function setBids(array $bids) {
    $this->set('bids', $bids);
    return $this;
  }

  /**
   * Summarize bid for data simplicity.
   *
   * @return float
   */
  public function summarizeBids($bids) {
    $processed = [];
    foreach ($bids as $bid) {
      $processed[] = [
        'id' => $bid->getId(),
        'amount' => \floatval($bid->getAmount()),
        'uid' => $bid->getOwnerId(),
        'bid' => $bid,
      ];
    }
    return $processed;
  }

  /**
   * Summarize bid for data simplicity.
   *
   * @return float
   */
  public function seekCurrentHightest() {
    $currentHightest['minPrice'] = \floatval($this->getPriceStarting());
    $currentHightest['leadBid'] = FALSE;

    $highestBid = FALSE;
    $highestBids = $this->getBids($this->getRelistCount(), 1);
    if ($this->hasBids()) {
      $highestBidKeys = \array_keys($highestBids);
      $currentHightest['leadBid'] = $highestBids[$highestBidKeys[0]];
      $highestBid = \floatval($currentHightest['leadBid']->getAmount());
    }

    if ($highestBid > $currentHightest['minPrice']) {
      $currentHightest['minPrice'] = $highestBid;
    }
    return $currentHightest;
  }

  /**
   * Logic to determine if instant bid button display is near it's persentages.
   *  general concept is:
   *   "percent bewtween start and finsh" OR  "percent reached of 'buy now amount'".
   */
  public function seekBidThreshold() {
    $percent = $this->getPriceThreshold();
    $dateStatus = $this->getDateStatus();
    $thresholdStatus = FALSE;

    $start = $dateStatus['date_formatted']['start_unix'];
    $end = $dateStatus['date_formatted']['end_unix'];
    $now = $dateStatus['now'];
    $watch['range'] = $end - $start;
    $watch['at'] = $now - $start;
    $watch['percent'] = $this->roundCents($watch['at'] / $watch['range']) * 100;
    // If time is @percent
    if ($watch['percent'] >= $percent) {
      $thresholdStatus = TRUE;
    }

    // If current price is @percent of instant amount.
    $instantPrice = $this->roundCents($this->getPriceBuyNow());
    $currentPrice = $this->seekCurrentHightest();
    $instantPercent = $instantPrice * ($percent / 100);
    if ($instantPercent >= $currentPrice  && $this->getPriceBuyNow() != 0) {
      $thresholdStatus = TRUE;
    }

    // If is INSTANT ONLY cancel both the above.
    if ($this->getInstantOnly()) {
      $thresholdStatus = FALSE;
    }
    return $thresholdStatus;
  }

  /**
   *
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Add the published field.
    $fields += static::publishedBaseFieldDefinitions($entity_type);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the Item was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the Item was last edited.'));

    $fields['revision_translation_affected'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Revision translation affected'))
      ->setDescription(t('Indicates if the last edit of a translation belongs to current revision.'))
      ->setReadOnly(TRUE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    $fields['status']->setDescription(t('Auction Item is published/unpublished trait.'))
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 41,
      ]);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Item Title'))
      ->setRevisionable(TRUE)
      ->setTranslatable(FALSE)
      ->setSettings([
        'max_length' => 1056,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Item Owner'))
      ->setDescription(t('The User ID of for payment of Final Sale.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(FALSE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['date'] = BaseFieldDefinition::create('daterange')
      ->setLabel(t('Auction Open Period'))
      ->setDescription(t('This time is stored at GTM/UTC.  Users will see time/countdown shifted to their Account timezone.  Anonynous will default to site timezone.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(FALSE)
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'daterange_datelist',
        'settings' => [
          'date_order' => 'YMD',
          'time_type' => 12,
          'increment' => 1,
        ],
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['relist_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Relist Count'))
      ->setDescription(t('Number of times this item has been relisted.'))
      ->setSetting('unsigned', TRUE)
      ->setDefaultValue(0)
      ->setRevisionable(TRUE)
      ->setTranslatable(FALSE)
      ->setRequired(TRUE)
      ->setReadOnly(TRUE);

    $fields['workflow'] = BaseFieldDefinition::create('list_integer')
      ->setLabel(t('Auction Status'))
      ->setRevisionable(TRUE)
      ->setTranslatable(FALSE)
      ->setRequired(TRUE)
      ->setCardinality(1)
      ->setDefaultValue(0)
      ->setSettings([
        'allowed_values_function' => ['\Drupal\auctions_core\AuctionToolsBase', 'itemWorkflows'],
      ])
      ->setDisplayOptions('view', [
        'label' => 'visible',
        'type' => 'list_default',
        'weight' => 6,
      ])
      ->setDisplayOptions('form', [
        'type' => 'auctions_core_options_select',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['currency_code'] = BaseFieldDefinition::create('list_string')
      ->setSettings([
        'allowed_values_function' => 'auctions_core_activeCurrencyList',
      ])
      ->setDefaultValueCallback('auctions_core_activeCurrencyListDefault')
      ->setLabel('Currency')
      ->setCardinality(1)
      ->setDisplayOptions('form', [
        'type' => 'auctions_core_options_select',
        'weight' => 5,
      ])
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', FALSE);

    $fields['instant_only'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Instant Buy only mode.'))
      ->setRevisionable(FALSE)
      ->setTranslatable(FALSE)
      ->setDisplayOptions('form', [
        'weight' => 9 ,
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => TRUE,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['price_starting'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Starting Price'))
      ->setRevisionable(FALSE)
      ->setTranslatable(FALSE)
      ->setDisplayOptions('form', [
        'weight' => 10,
        'type' => 'number',
        'settings' => [
          'display_label' => TRUE,
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'number_decimal',
      ])
      ->addConstraint('AuctionsPrice')
      ->addConstraint('NotNull')
      ->setDisplayConfigurable('form', TRUE)
      ->setDefaultValue('0');

    $fields['bid_step'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Bid Step'))
      ->setDefaultValue('5.00')
      ->setRevisionable(FALSE)
      ->setTranslatable(FALSE)
      ->setDisplayOptions('form', [
        'weight' => 11,
        'type' => 'number',
        'settings' => [
          'display_label' => TRUE,
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'number_decimal',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', FALSE)
      ->addConstraint('NotNull');

    $fields['price_buy_now'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Buy Now Price'))
      ->setRevisionable(FALSE)
      ->setTranslatable(FALSE)
      ->setDisplayOptions('form', [
        'weight' => 14,
        'type' => 'number',
        'settings' => [
          'display_label' => TRUE,
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'number_decimal',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->addConstraint('AuctionsPrice')
      ->addConstraint('NotNull')
      ->setDefaultValue('0');

    $fields['price_threshold'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Buy Now Threshold'))
      ->setDescription(t("Buy Now button will now longer be available"))
      ->setRevisionable(FALSE)
      ->setTranslatable(FALSE)
      ->setDisplayOptions('form', [
        'weight' => 15,
        'type' => 'number',
        'settings' => [
          'display_label' => TRUE,
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'number_decimal',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', FALSE)
      ->addConstraint('NotNull')
      ->setDefaultValue('85');

    $fields['bids'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Bids for this Auction'))
      ->setDescription(t("The Bid's this offer is for"))
      ->setRevisionable(TRUE)
      ->setTranslatable(FALSE)
      ->setSetting('target_type', 'auction_bid')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 100,
      ])
      ->setDisplayOptions('form', [
        'type' => 'inline_entity_form_complex',
        'weight' => 100,
        'settings' => [
          'form_mode' => 'default',
          'label_singular' => '',
          'label_plural' => '',
          'allow_new' => TRUE,
          'match_operator' => 'CONTAINS',
          'revision' => FALSE,
          'override_labels' => FALSE,
          'collapsible' => TRUE,
          'collapsed' => FALSE,
          'allow_existing' => FALSE,
          'allow_duplicate' => FALSE,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(FALSE)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    return $fields;
  }

}
