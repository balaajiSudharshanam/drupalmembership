<?php

namespace Drupal\auctions_core\Form;

use Drupal\auctions_core\Entity\AuctionItem;
use Drupal\auctions_core\Service\AuctionTools;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Link;
use Drupal\auctions_core\AuctionToolsTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\auctions_core\Plugin\Validation\Constraint\CurbBids;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class HelloForm.
 */
class BiddersForm extends FormBase {

  use AuctionToolsTrait;
  /**
   * Drupal\Core\Messenger\MessengerInterface definition.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;
  /**
   * Entity Type Manager.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * AuctionTools.
   *
   * @var Drupal\auctions_core\Service\AuctionTools
   */
  protected $auctionTools;

  /**
   * Core module handler.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  public $configFactory;

  /**
   * Constructs a new BiddersForm object.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger Factory.
   * @param Drupal\Core\Session\AccountInterface $currentUser
   *   Account Interface.
   * @param Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   Entity Type Manager.
   */
  public function __construct(MessengerInterface $messenger, AccountInterface $currentUser, EntityTypeManagerInterface $entityTypeManager, AuctionTools $auctionTools, ConfigFactoryInterface $configFactory) {
    $this->messenger = $messenger;
    $this->currentUser = $currentUser;
    $this->entityTypeManager = $entityTypeManager;
    $this->auctionTools = $auctionTools;
    $this->configFactory = $configFactory;
  }

  /**
   *
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('auctions_core.tools'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'auctions_core_bidders';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $auctionItem = NULL) {
    $auctionConf = $this->configFactory()->getEditable('auctions.item_settings');
    $dollar = $auctionConf->get('dollar-symbol');
    $thous = $auctionConf->get('thousand-separator');
    $dec = $auctionConf->get('decimal-separator');
    $item = (!empty($auctionItem[0])&& ($auctionItem[0] instanceof AuctionItem)) ? $auctionItem[0] : FALSE;
    $currency = '<small>' . $item->getCurrencyCode() . '</small>';
    if ($item) {
      // Send only id, refresh data.
      $form_state->setFormState([
        'auction_item_id' => $item->getId(),
      ]);

      $date = $item->getDate();
      $hasBuyNow = $item->hasBuyNow();
      $instantOnly = $item->getInstantOnly();
      $canBid = $this->currentUser->hasPermission('add auction bids entities');
      if (!$canBid) {
        $form['permission-denied'] = [
          '#type' => 'inline_template',
          '#template' => '<p>{{ message }}</p>',
          '#context' => [
            'message' => $this->t('You are not able to bid at this time'),
          ],
        ];
      }
      elseif ($this->currentUser->isAnonymous()) {
        $form['is-anom'] = [
          '#type' => 'inline_template',
          '#template' => '<p>{{ message }}</p><ul><li>{{ login }}</li><li>{{ register }}</li></ul><',
          '#context' => [
            'message' => $this->t('Please log in to bid'),
            'login' => Link::createFromRoute($this->t('Login'), 'user.login')->toString(),
            'register' => Link::createFromRoute($this->t('Register'), 'user.register')->toString(),
          ],
        ];

      }
      else {
        if ($item->isClosed()) {
          $form['aution-closed'] = [
            '#type' => 'item',
            '#description' => $this->t('Auction is Closed.'),
          ];
        }
        elseif ($item->isOpen()) {
          $currentHightest = $item->seekCurrentHightest();
          $minPrice = $currentHightest['minPrice'];
          $selfBid = ($currentHightest['leadBid'] && $currentHightest['leadBid']->getOwnerId() == $this->currentUser->id()) ? TRUE : FALSE;
          /**
           * reminder: add a header for general display of related meta bubble's:
           *  - relist count.
           *  - autobidders count.
           */
          $form['welcome'] = [
            '#type' => 'item',
            '#access' => !$instantOnly ,
            '#title' => $this->t('Current Price'),
            '#description' => $dollar . $this->showAsCents($minPrice, $dec, $thous) . ' ' . $currency,
          ];

          $hasAutobid = $this->auctionTools->seekAutobid($this->currentUser->id(), $item->id(), $item->getRelistCount());
          $currentAutobid = FALSE;
          $automaxTitle = $this->t('Your autobid maxinum');
          if (!empty($hasAutobid)) {
            $key = \array_keys($hasAutobid);
            $currentAutobid = $hasAutobid[$key[0]];
            $automaxTitle = $this->t('Adjust your autobid maximum');
          }
          $showAutobidding = !$instantOnly &&  $auctionConf->get('autobid-mode') == 1;
          $form['autobid'] = [
            '#type' => 'fieldset' ,
            '#access' => $this->currentUser->hasPermission('add auction autobid entities')  &&  $showAutobidding ,
            '#title' => $this->t('Auto Bidding'),
            '#attributes' => [
              'class' => [
                'autobid-wrapper',
              ],
            ],
          ];
          $amountMax = empty($hasAutobid) ? ($minPrice + $auctionItem[0]->getBidStep()) : $currentAutobid->getAmountMax();
          $form['autobid']['last-bidder'] = [
            '#type' => 'item',
            '#access' => $currentAutobid ? TRUE : FALSE,
            '#markup' => $this->t('Your current maximum:'),
            '#description' => $dollar . $this->showAsCents($amountMax, $dec, $thous) . ' ' . $currency,
          ];

          $form['autobid']['autobid_opt'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Autobidding Opt-in.'),
            '#access' => !$currentAutobid ,
            '#default_value' => $currentAutobid ? 1 : 0,
            '#states' => [
              'invisible' => [
                ':input[name*="autobid_opt"]' => ['checked' => TRUE],
              ],
            ],
          ];
          $form['autobid']['amount_max'] = [
            '#type' => 'number',
            '#field_prefix' => $dollar,
            '#title' => $automaxTitle,
            '#description' => $this->t("This item's Bid Step is @dollar@bidStep:", [
              '@dollar' => $dollar,
              '@bidStep' => $auctionItem[0]->getBidStep(),
            ]),
            '#step' => 'any',
            '#min' => $minPrice + .01,
            '#states' => [
              'invisible' => [
                ':input[name*="autobid_opt"]' => ['checked' => FALSE],
              ],
              /* Reminder:  can't make required or browser validation will loop if opt-out btn is used. */
            ],
          ];
          if ($currentAutobid) {
            $form['autobid']['amount_max']['#attributes']['placeholder'] = $currentAutobid->getAmountMax();
          }

          $form['autobid']['opt_out'] = [
            '#type' => 'submit',
            '#name' => 'opt-out',
            '#access' => $currentAutobid  ,
            '#submit' => ['::optOutSubmit'],
            '#value' => $this->t('Remove my Auto Bidding'),
            '#attributes' => [
              'title' => $this->t("'Opt Out' without placing a bid."),
              'alt' => $this->t("Click here to 'opt out' of Auto Bidding without placing a bid."),
            ],
            '#states' => [
              'invisible' => [
                ':input[name*="autobid_opt"]' => ['checked' => FALSE],
              ],
            ],
          ];

          $form['bids'] = [
            '#type' => 'container',
            '#access' => !$instantOnly && !$selfBid,
            '#attributes' => ['class' => ['standard-bid']],
          ];

          $form['bids']['amount'] = [
            '#type' => 'number',
            '#field_prefix' => $dollar,
            '#required' => TRUE,
            '#field_suffix' => $currency,
            '#title' => $this->t('Your Bid'),
            '#min' => ($minPrice + .01),
            '#size' => 12,
            '#step' => 'any',
          ];
          $default = $minPrice + $item->getBidStep();
          $form['bids']['amount']['#default_value'] = $default;

          $form['bids']['submit'] = [
            '#type' => 'submit',
            '#name' => 'submit',
            '#value' => $this->t('Place your bid'),
          ];

          $form['self-bid'] = [
            '#type' => 'item',
            '#access' => $selfBid,
            '#markup' => '<p>' . $this->t('You hold the current highest bid.') . '</p>',
          ];

          $bidThreshold = $item->seekBidThreshold();
          $form['instant'] = [
            '#type' => $instantOnly ? 'fieldset' : 'details',
            '#open' => $instantOnly,
            '#title' => $this->t('Instant Buy'),
               /*   todo: format from conf  . */
            '#markup' => $dollar . $this->showAsCents($item->getPriceBuyNow(), $dec, $thous) . ' ' . $currency,
            '#access' => ($instantOnly || $hasBuyNow) && !$bidThreshold, /*   todo: @threshold  . */
            '#attributes' => [
              'class' => [
                'instant-bid',
              ],
            ],
          ];
          $form['instant']['buy_now_verify'] = [
            '#type' => 'checkbox',
            '#title' => $this->t("Yes, I'ld like to Buy Now"),
          ];
          $form['instant']['buy_now'] = [
            '#type' => 'submit',
            '#name' => 'buy-now',
            '#submit' => ['::buyNowSubmit'],
            '#validate' => ['::buyNowValidate'],
            '#value' => $this->t('Buy Now!'),
            '#states' => [
              'invisible' => [
                ':input[name*="buy_now_verify"]' => ['checked' => FALSE],
              ],
            ],
          ];

        }
        else {
          $form['auction-open'] = [
            '#type' => 'item',
            '#description' => $this->t('Bidding is not yet Open.'),
          ];
        }

      }

    }
    $form['#attached']['library'][] = 'auctions_core/bidders';
    return $form;
  }

  /**
   *
   */
  public function buyNowValidate(array &$form, FormStateInterface $form_state) {
    $curbBids = new CurbBids();
    $itemId = $form_state->get('auction_item_id');
    $item = \Drupal::entityTypeManager()->getStorage('auction_item')->load($itemId);
    if ($item->isClosed()) {
      $form_state->setErrorByName('instant-price', $curbBids->auctionHasClosed);
    }
    $verify = $form_state->getValues()['buy_now_verify'];
    if ($verify == 0) {
      $form_state->setErrorByName('buy_now_verify', $this->t('You must pre-verify <q>Buy Now</q> submission.'));
    }
  }

  /**
   *
   */
  public function optOutSubmit(array &$form, FormStateInterface $form_state) {
    $itemId = $form_state->get('auction_item_id');
    $item = \Drupal::entityTypeManager()->getStorage('auction_item')->load($itemId);
    $this->auctionTools->removeAutobid($this->currentUser->id(), $itemId, $item->getRelistCount());
  }

  /**
   * {@inheritdoc}
   */
  public function buyNowSubmit(array &$form, FormStateInterface $form_state) {
    $itemId = $form_state->get('auction_item_id');

    $item = \Drupal::entityTypeManager()->getStorage('auction_item')->load($itemId);
    $values = [
      'user_id' => $this->currentUser->id(),
      'item' => $item->getId(),
      'relist_group' => $item->getRelistCount(),
      'type' => 'instant',
      'purchase_offer' => 0,
      'amount' => $item->getPriceBuyNow(),
    ];
    $bid = $this->saveBid($values, TRUE);
    if ($bid->id()) {
      $this->messenger()->addMessage($this->t('Congratulations! You have placed an Instant Buy! $%price.', [
        '%price' => $this->showAsCents($item->getPriceBuyNow(), '.', ','),
      ]));
    }
    else {
      $this->messenger()->addError($this->t('Bidding failed.'));
    }

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $triggerBtn = $form_state->getTriggeringElement()['#name'];
    if ($triggerBtn == 'submit') {

      $curbBids = new CurbBids();
      $itemId = $form_state->get('auction_item_id');
      $item = \Drupal::entityTypeManager()->getStorage('auction_item')->load($itemId);
      $itemRelist = $item->getRelistCount();
      $currentBids = $item->getBids($itemRelist, 3);
      $processBids = $item->summarizeBids($currentBids);
      $amount = \floatval($form_state->getValue('amount'));
      if (!empty($processBids[0]) && $processBids[0]['uid'] == $this->currentUser->id()) {
        // Since this is the heaviest rule:  check for it first.
        $form_state->setErrorByName('amount', $curbBids->lastBidIsYours);
      }

      $itemPriceStarting = $item->getPriceStarting();
      $itemWorkflow = $item->getWorkflow();
      // Check if the value is an number.
      if ($itemWorkflow == 3 || $itemWorkflow == 4) {
        $form_state->setErrorByName('amount', $curbBids->auctionFinished);

      }
      if ($itemWorkflow == 0) {
        $form_state->setErrorByName('amount', $curbBids->auctionNew);
      }

      // If is past end time.  @page load vs submit post.
      $now = new DrupalDateTime('now');
      $auctionDates = $item->getDate();
      $getUserTimezone = date_default_timezone_get();
      $userTimezone = new \DateTimeZone($getUserTimezone);
      $auctionEnds = DrupalDateTime::createFromFormat('Y-m-d\TH:i:s', $auctionDates['end'])->setTimezone($userTimezone)->format('U');
      if ($now->format('U') > $auctionEnds) {
        $form_state->setErrorByName('amount', $curbBids->auctionDates);
      }
      // If isn't higher than last/threshold.
      $highestCurrent = $item->seekCurrentHightest();
      if ((!empty($processBids[0]) && $amount < $processBids[0]['amount'])
          ||
        (!($amount > ($highestCurrent['minPrice'])))
      ) {
        $form_state->setErrorByName('amount', $curbBids->higherThanLastBid);
      }

      // Autobid.
      $hasAutobid = $this->auctionTools->seekAutobid($this->currentUser->id(), $item->id(), $itemRelist);
      $currentAutobid = FALSE;
      if (!empty($hasAutobid)) {
        $key = \array_keys($hasAutobid);
        $currentAutobid = $hasAutobid[$key[0]];
      }

      $autobidOpt = $form_state->getValue('autobid_opt');
      // Reminder:  all form buttons should have unique #name.
      $triggerBtn = $form_state->getTriggeringElement()['#name'];
      $amountMax = $this->roundCents($form_state->getValue('amount_max'));
      if ($triggerBtn != 'opt-out'
        && $autobidOpt == 1
        && ($currentAutobid && $amountMax != 0)
        && $amountMax < $highestCurrent['minPrice']
      ) {
        $form_state->setErrorByName('amount_max', $this->t('Your Max autobid has to be higher than current highest bid. Try a higher amount or click <q>Opt Out</q> .'));
      }
      parent::validateForm($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $itemId = $form_state->get('auction_item_id');
    $item = \Drupal::entityTypeManager()->getStorage('auction_item')->load($itemId);
    $autobidOpt = $form_state->getValue('autobid_opt');
    // Reminder:  all form buttons should have unique #name.
    $amountMax = \floatval($form_state->getValue('amount_max'));
    if ($autobidOpt == 1 && $amountMax != 0) {
      // Process autobid.
      $this->auctionTools->handleAutobid($this->currentUser->id(), $item->id(), $item->getRelistCount(), $amountMax);
    }
    $amount = \floatval($form_state->getValue('amount'));
    $bid = \Drupal::entityTypeManager()->getStorage('auction_item')->load($itemId);
    $values = [
      'user_id' => $this->currentUser->id(),
      'item' => $item->getId(),
      'relist_group' => $item->getRelistCount(),
      'type' => 'standard',
      'purchase_offer' => 0,
      'amount' => $amount,
    ];
    $bid = $this->saveBid($values);
    if ($bid->id()) {
      $this->messenger()->addMessage($this->t('Congratulations! You have placed your bid! $%price.', [
        '%price' => $this->showAsCents($amount, '.', ','),
      ]));
    }
    else {
      $this->messenger()->addError($this->t('Bidding failed.'));
    }
  }

  /**
   *
   */
  private function saveBid(array $value, $close = FALSE) {
    $bid = \Drupal::entityTypeManager()->getStorage('auction_bid')->create([
      'user_id' => $value['user_id'],
      'item' => ['target_id' => $value['item']],
      'relist_group' => $value['relist_group'],
      'type' => $value['type'],
      'purchase_offer' => $value['purchase_offer'],
    ]);
    $bid->setAmount($value['amount']);
    $bid->save();
    // Corresponding entity ref.
    if ($bid->id()) {
      $item = $bid->getItemEntity();
      $item->get('bids')->appendItem([
        'target_id' => $bid->id(),
      ]);
      if ($close) {
        $item->setWorkflow(3);
      }
    }
    $item->save();
    return $bid;
  }

}
