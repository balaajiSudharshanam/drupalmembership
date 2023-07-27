<?php

namespace Drupal\auctions_core\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\auctions_core\Service\AuctionTools;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AuctionItemSettingsForm.
 *
 * @ingroup auctions_core
 */
class AuctionItemSettingsForm extends ConfigFormBase {

  protected $auctionTools;

  /**
   *
   */
  public function __construct(AuctionTools $auctionTools) {
    $this->auctionTools = $auctionTools;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('auctions_core.tools')
    );
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'auction_item_entity_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'auctions.item_settings',
    ];
  }

  /**
   * Defines the settings form for Auction Items entities.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('auctions.item_settings');
    $hasCommercePrice = $this->auctionTools->hasModule('commerce_price');
    $form['#attributes']['novalidate'] = 'novalidate';

    $form['common'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Currency Settings'),
    ];
    $form['common']['commerce-price-active'] = [
      '#access' => $hasCommercePrice,
      '#markup' => '<br><dl><dt>' . $this->t('Commerce Price is active.') . '</dt>' .
      '<dd>' . $this->t('Be sure to match Currencies with store settings: admin/commerce/config/currencies') . '</dd></dl>',
    ];
    // reminder:  handle language character!
    $form['common']['dollar-symbol'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Dollar Symbol'),
      '#default_value' => $config->get('dollar-symbol'),
      '#maxlength' => 1,
      '#size' => 1,
    ];
    $form['common']['thousand-separator'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Thousand separator'),
      '#default_value' => $config->get('thousand-separator'),
      '#maxlength' => 1,
      '#size' => 1,
    ];
    $form['common']['decimal-separator'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Decimal separator'),
      '#default_value' => $config->get('decimal-separator'),
      '#maxlength' => 1,
      '#size' => 1,
    ];

    $form['common']['currency'] = [
      '#type' => 'details',
      '#title' => $this->t('Auction Item Currency'),
    ];
    $form['common']['currency']['active-currency'] = [
      '#title' => $this->t('Select currencies to allow for Auction Items.'),
      '#type' => 'checkboxes',
      '#required' => TRUE,
      '#default_value' => $config->get('active-currency'),
      '#options' => $this->auctionTools->currencyOptions(),
    ];
    $form['common']['commerce-price-active'] = [
      '#access' => $hasCommercePrice,
      '#markup' => '<br><dl><dt>' . $this->t('Commerce Price is active.') . '</dt>' .
      '<dd>' . $this->t('Adjust active Store currencies as needed admin/commerce/config/currencies') . '</dd></dl>',
    ];

    $form['date'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Auctions Dates'),
    ];
    $range = \range(1, 10);
    $years = \array_combine($range, $range);
    $form['date']['daterange']['years-ahead'] = [
      '#title' => $this->t('How many years forward can a user list an auction item'),
      '#type' => 'select',
      '#required' => TRUE,
      '#default_value' => $config->get('years-ahead'),
      '#options' => $years,
    ];

    $form['autobid'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Autobidding'),
    ];
    $form['autobid']['autobid-mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Global Feature.'),
      '#default_value' => $config->get('autobid-mode'),
    ];
    $form['autobid']['trigger'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Trigger Weight Logic'),
      '#states' => [
        'visible' => [
          ':input[name*="autobid-mode"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $range = \range(1, 1056);
    $slice = ['0' => $this->t('All/Unlimited')] + \array_combine($range, $range);
    $form['autobid']['trigger']['autobid-slice'] = [
      '#type' => 'select',
      '#options' => $slice,
      '#title' => $this->t('Autobid User Limit'),
      '#description' => $this->t('Help reduce flooding.'),
      '#default_value' => $config->get('autobid-slice'),
    ];
    $form['autobid']['trigger']['autobids-ordering'] = [
      '#type' => 'radios',
      '#required' => TRUE,
      '#title' => $this->t('Trigger Autobid By'),
      '#options' => [
        'created' => $this->t('Date Created'),
        'changed' => $this->t('Date Last Updated'),
        'amount_max' => $this->t('Max Amount'),
      ],
      '#default_value' => $config->get('autobids-ordering'),
    ];
    $form['autobid']['trigger']['autobids-direction'] = [
      '#type' => 'radios',
      '#required' => TRUE,
      '#title' => $this->t('Final Sort'),
      '#options' => [
        'ASC' => $this->t('Ascending'),
        'DESC' => $this->t('Desending'),
      ],
      '#default_value' => $config->get('autobids-direction'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable('auctions.item_settings');

    $config->set('active-currency', $form_state->getValue(['active-currency']));
    $config->set('decimal-separator', $form_state->getValue(['decimal-separator']));
    $config->set('dollar-symbol', $form_state->getValue(['dollar-symbol']));
    $config->set('thousand-separator', $form_state->getValue(['thousand-separator']));

    $config->set('years-ahead', $form_state->getValue(['years-ahead']));

    $config->set('autobid-mode', $form_state->getValue(['autobid-mode']));
    $config->set('autobids-ordering', $form_state->getValue(['autobids-ordering']));
    $config->set('autobids-direction', $form_state->getValue(['autobids-direction']));
    $config->set('autobid-slice', $form_state->getValue(['autobid-slice']));

    $config->save();
    parent::submitForm($form, $form_state);
  }

}
