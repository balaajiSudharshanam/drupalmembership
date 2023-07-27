<?php

namespace Drupal\auctions_core\Service;

use Drupal\auctions_core\AuctionToolsTrait;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageDefault;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationManager;
use Drupal\Core\Render\RendererInterface;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Site\Settings;
use Drupal\Component\Uuid\UuidInterface;

/**
 * An informational helper.
 *
 * @ingroup auctions_core
 */
class AuctionTools {

  use StringTranslationTrait;
  use AuctionToolsTrait;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  public $entityTypeManager;

  /**
   * Core module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Core module handler.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  public $configFactory;

  /**
   * Current User.
   *
   * @var Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The language default.
   *
   * @var \Drupal\Core\Language\LanguageDefault
   */
  protected $languageDefault;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  public $languageManager;

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  private $database;

  /**
   * The renderer service.
   *
   * This is not injected because that would result in a circular dependency.
   * Instead, the renderer should pass itself to the ThemeManager when it is
   * constructed, using the setRenderer() method.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  public $renderer;

  /**
   * The UUID service.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  public $uuidService;

  /**
   * AuctionTools constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Core module handler.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_service
   *   The UUID service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, ModuleHandlerInterface $module_handler, ConfigFactoryInterface $configFactory, AccountProxyInterface $currentUser, LanguageDefault $language_default, LanguageManagerInterface $language_manager, Connection $database, RendererInterface $renderer, UuidInterface $uuid_service) {
    $this->entityTypeManager = $entityTypeManager;
    $this->moduleHandler = $module_handler;
    $this->configFactory = $configFactory;
    $this->currentUser = $currentUser;
    $this->languageDefault = $language_default;
    $this->languageManager = $language_manager;
    $this->database = $database;
    $this->renderer = $renderer;
    $this->uuidService = $uuid_service;
  }

  /**
   * Wapper for service lookup.
   */
  public function hasModule($moduleName = 'commerce_price') {
    return $this->moduleHandler->moduleExists($moduleName);
  }

  /**
   * {@inheritdoc}
   */
  public function getCommerceCurrencies() {
    $active_currencies = [];
    if ($this->hasModule('commerce_price')) {
      /** @var \Drupal\commerce_price\Entity\CurrencyInterface[] $currencies */
      $currencies = $this->entityTypeManager->getStorage('commerce_currency')->loadMultiple();

      foreach ($currencies as $currency) {
        if ($currency->status()) {
          $active_currencies[$currency->getCurrencyCode()] = $currency->getName();
        }
      }
    }
    return $active_currencies;
  }

  /**
   * Get active currencey as per setting.
   */
  public function getActiveItemCurrencies() {
    $currencyList = self::currencyList();
    /**
     * Note:  activeCurrenciesConfig is a work around for install as config has not
     * been installed before entity is initialized.
     **/
    $activeCurrenciesConfig = $this->configFactory->get('auctions.item_settings')->get('active-currency');
    $activeCurrencies = $activeCurrenciesConfig ? $activeCurrenciesConfig : ['USD' => 'United States Dollar'];
    $activeCurrency = array_flip($activeCurrencies);
    unset($activeCurrency[0]);
    $options = [];
    foreach ($activeCurrency as $currency) {
      $options[$currency] = $currencyList[$currency];
    }
    return $options;
  }

  /**
   * Masks user name by leaving only first and last letter.
   *
   * @return string
   */
  public function activeCurrencyList() {
    $hasCommercePrice = self::hasModule('commerce_price');
    $currenyCodes = self::getActiveItemCurrencies();
    if ($hasCommercePrice) {
      $currenyCodes = self::getCommerceCurrencies();
    }
    return $currenyCodes;
  }

  /**
   * Setup to send mail.
   *
   * @return string
   */
  public function sendMail($to, $from, $reply, $subject, array $body, array $params = []) {

    if (empty($to)) {
      return FALSE;
    }

    $default_params = [
      'headers' => [
        'Content-Type' => 'text/html; charset=iso-8859-1',
        'MIME-Version' => '1.0',
        'To' => $to,
        'From' => $from,
        'Reply-To' => $reply,
        'X-Mailer' => 'Drupal Auction',
      ],
      'to' => $to,
      'subject' => $subject,
      'langcode' => $this->languageManager->getCurrentLanguage()->getId(),
      'body' => $body,
    ];
    if (!empty($params['cc'])) {
      $default_params['headers']['Cc'] = $params['cc'];
    }
    if (!empty($params['bcc'])) {
      $default_params['headers']['Bcc'] = $params['bcc'];
    }
    $params = \array_replace($default_params, $params);

    // Change the active language to ensure the email is properly translated.
    if ($params['langcode'] != $default_params['langcode']) {
      $this->changeActiveLanguage($params['langcode']);
    }

    $message = $this->mail($params);
    // Revert back to the original active language.
    if ($params['langcode'] != $default_params['langcode']) {
      $this->changeActiveLanguage($default_params['langcode']);
    }

    return (bool) $message;
  }

  /**
   * Sends an email message. this fn is 'forked' from within phpMail to allow html.
   *
   * @param array $message
   *   A message array, as described in hook_mail_alter().
   *
   * @return bool
   *   TRUE if the mail was successfully accepted, otherwise FALSE.
   */
  protected function mail(array $message) {
    $mail_result = FALSE;
    $request = \Drupal::request();
    $line_endings = Settings::get('mail_line_endings', PHP_EOL);

    // If 'Return-Path' isn't already set in php.ini, we pass it separately
    // as an additional parameter instead of in the header.
    if (isset($message['headers']['Return-Path'])) {
      $return_path_set = \strpos(\ini_get('sendmail_path'), ' -f');
      if (!$return_path_set) {
        $message['Return-Path'] = $message['headers']['Return-Path'];
        unset($message['headers']['Return-Path']);
      }
    }

    $mimeheaders = [];
    foreach ($message['headers'] as $name => $value) {
      $mimeheaders[] = $name . ': ' . Unicode::mimeHeaderEncode($value);
    }
    $mail_headers = \implode("\n", $mimeheaders);

    // Prepare mail commands.
    $mail_subject = Unicode::mimeHeaderEncode($message['subject']);

    // Reminder: email uses CRLF for line-endings. PHP's API requires LF
    // on Unix and CRLF on Windows. Drupal automatically guesses the
    // line-ending format appropriate for your system. If you need to
    // override this, adjust $settings['mail_line_endings'] in settings.php.
    $render_body = '<html><body>' . $this->renderer->render($message['body']) . '</body></html>';
    $mail_body = \preg_replace('@\r?\n@', $line_endings, $render_body);
    // For headers, PHP's API suggests that we use CRLF normally,
    // but some MTAs incorrectly replace LF with CRLF. See #234403.
    // We suppress warnings and notices from mail() because of issues on some
    // hosts. The return value of this method will still indicate whether mail
    // was sent successfully.
    if (!$request->server->has('WINDIR') && \strpos($request->server->get('SERVER_SOFTWARE'), 'Win32') === FALSE) {
      // On most non-Windows systems, the "-f" option to the sendmail command
      // is used to set the Return-Path. There is no space between -f and
      // the value of the return path.
      // We validate the return path, unless it is equal to the site mail, which
      // we assume to be safe.
      $site_mail = $this->configFactory->get('system.site')->get('mail');
      $additional_headers = isset($message['Return-Path']) && ($site_mail === $message['Return-Path'] || static::_isShellSafe($message['Return-Path'])) ? '-f' . $message['Return-Path'] : '';
      $mail_result = @mail(
        $message['to'],
        $mail_subject,
        $mail_body,
        $mail_headers,
        $additional_headers
      );
    }
    else {
      // On Windows, PHP will use the value of sendmail_from for the
      // Return-Path header.
      $old_from = \ini_get('sendmail_from');
      ini_set('sendmail_from', $message['Return-Path']);
      $mail_result = @mail(
        $message['to'],
        $mail_subject,
        $mail_body,
        $mail_headers
      );
      \ini_set('sendmail_from', $old_from);
    }

    return $mail_result;
  }

  /**
   * Disallows potentially unsafe shell characters.
   *
   * Functionally similar to PHPMailer::isShellSafe() which resulted from
   * CVE-2016-10045. Note that escapeshellarg and escapeshellcmd are inadequate
   * for this purpose.
   *
   * @param string $string
   *   The string to be validated.
   *
   * @return bool
   *   True if the string is shell-safe.
   *
   * @see https://github.com/PHPMailer/PHPMailer/issues/924
   * @see https://github.com/PHPMailer/PHPMailer/blob/v5.2.21/class.phpmailer.php#L1430
   *
   * @todo Rename to ::isShellSafe() and/or discuss whether this is the correct
   *   location for this helper.
   */
  protected static function _isShellSafe($string) {
    if (\escapeshellcmd($string) !== $string || !\in_array(\escapeshellarg($string), [
      "'{$string}'",
      "\"{$string}\"",
    ])) {
      return FALSE;
    }
    if (\preg_match('/[^a-zA-Z0-9@_\\-.]/', $string) !== 0) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Changes the active language for translations.
   *
   * @param string $langcode
   *   The langcode.
   */
  private function changeActiveLanguage($langcode) {
    if (!$this->languageManager->isMultilingual()) {
      return;
    }
    $language = $this->languageManager->getLanguage($langcode);
    if (!$language) {
      return;
    }
    // The language manager has no method for overriding the default
    // language, like it does for config overrides. We have to change the
    // default language service's current language.
    // @see https://www.drupal.org/project/drupal/issues/3029010
    $this->languageDefault->set($language);
    $this->languageManager->setConfigOverrideLanguage($language);
    $this->languageManager->reset();

    // The default string_translation service, TranslationManager, has a
    // setDefaultLangcode method. However, this method is not present on
    // either of its interfaces. Therefore we check for the concrete class
    // here so that any swapped service does not break the application.
    // @see https://www.drupal.org/project/drupal/issues/3029003
    $string_translation = $this->getStringTranslation();
    if ($string_translation instanceof TranslationManager) {
      $string_translation->setDefaultLangcode($language->getId());
      $string_translation->reset();
    }
  }

  /**
   * Return a general list of recommendind Bid Step increments.
   * # relist group:  allows to GET ALL, ever...
   */
  public function uniqueUserList($auctionItemID, $relistCount = FALSE) {
    $auctionUsers = $this->database->select('auction_bid', 'bids');
    $auctionUsers->fields('bids', ['user_id']);
    $auctionUsers->distinct();
    $auctionUsers->condition('bids.item', $auctionItemID, '=');
    if ($relistCount) {
      $auctionUsers->condition('bids.relist_group', $relistCount, '=');
    }
    $result = $auctionUsers->execute()->fetchCol();
    return $result;
  }

  /**
   * Return a general list of users who have autobid max opt-ins.
   *
   *  @relist group:  allows to GET ALL, ever...
   *
   * return false|array. groupResultBy fn will be keyed in sequence of 'next offer'
   */
  public function autobidsGroupedByUser($auctionItemID, $relistCount = FALSE, $amountMax, $uidExclude) {
    $conf = $this->configFactory->get('auctions.item_settings');
    $autobidsOrdering = $conf->get('autobids-ordering');
    $autobidsDirection = $conf->get('autobids-direction');

    $auctionUsers = $this->database->select('auction_autobid', 'auto');
    $auctionUsers->fields('auto', ['id', 'item', 'amount_max', 'user_id']);
    $auctionUsers->condition('auto.item', $auctionItemID, '=');
    $auctionUsers->condition('auto.status', 1, '=');
    $auctionUsers->condition('auto.user_id', $uidExclude, '!=');  /* exclude self */
    $auctionUsers->condition('auto.amount_max', $amountMax, '>=');
    if ($relistCount) {
      $auctionUsers->condition('auto.relist_group', $relistCount, '=');
    }
    // reminder: is by who opted int first.
    $auctionUsers->orderBy($autobidsOrdering, $autobidsDirection);
    $result = $auctionUsers->execute()->fetchAll(\PDO::FETCH_ASSOC);
    $groupby = $result ? $this->groupResultBy('user_id', $result) : FALSE;
    return $groupby;
  }

  /**
   * Return a general list of bds grouped by user.
   *
   *  @relist group:  allows to GET ALL, ever...
   *
   * return false|array. groupResultBy fn will be keyed in sequence of 'next offer'
   */
  public function bidsGroupedByUser($auctionItemID, $relistCount = FALSE) {
    $auctionUsers = $this->database->select('auction_bid', 'bids');
    $auctionUsers->fields('bids', ['id', 'item', 'amount', 'relist_group', 'user_id', 'type']);
    $auctionUsers->condition('bids.item', $auctionItemID, '=');
    if ($relistCount) {
      $auctionUsers->condition('bids.relist_group', $relistCount, '=');
    }
    $auctionUsers->orderBy('id', 'DESC');
    $result = $auctionUsers->execute()->fetchAll(\PDO::FETCH_ASSOC);
    $groupby = $result ? $this->groupResultBy('user_id', $result) : FALSE;
    return $groupby;
  }

  /**
   * Internal:  grouping for bidsGroupedByUser(),.
   */
  private function groupResultBy($key, $data) {
    $result = [];
    foreach ($data as $val) {
      if (\array_key_exists($key, $val)) {
        $result[$val[$key]][] = $val;
      }
    }
    return $result;
  }

  /**
   * Get 'any' autobid by uid, relist - order by highest (as current)
   */
  public function seekAutobid($uid, $auctionItemId, $relistGroup = FALSE) {
    $autobids = $this->entityTypeManager->getStorage('auction_autobid');
    $seekAutobid = $autobids->getQuery();
    $seekAutobid->condition('user_id', $uid);
    $seekAutobid->condition('item', $auctionItemId);
    if ($relistGroup) {
      $seekAutobid->condition('relist_group', $relistGroup);
    }
    $seekAutobid->sort('amount_max', 'DESC');
    $autobidList = $seekAutobid->execute();
    $hasAutobid = $autobids->loadMultiple($autobidList);
    return $hasAutobid;
  }

  /**
   * Flush thru and remove 'any' autobid triggers that could possibly exists.
   *
   * Reminder/direction:  change to handle thru publishing state @keep history..
   */
  public function removeAutobid($uid, $auctionItemId, $relistGroup = FALSE) {
    $autobids = $this->entityTypeManager->getStorage('auction_autobid');
    $seekAutobid = $autobids->getQuery();
    $seekAutobid->condition('user_id', $uid);
    $seekAutobid->condition('item', $auctionItemId);
    if ($relistGroup) {
      $seekAutobid->condition('relist_group', $relistGroup);
    }
    $autobidList = $seekAutobid->execute();
    $removals = $autobids->loadMultiple($autobidList);
    $ids = [];
    foreach ($removals as $id => $entity) {
      $entity->delete();
      $ids[$id] = $id;
    }
    return $ids;
  }

  /**
   *
   */
  public function handleAutobid($uid, $auctionItemId, $relistGroup, $amountMax) {
    $autobid = FALSE;
    $hasAutobid = $this->seekAutobid($uid, $auctionItemId, $relistGroup);
    if (!empty($hasAutobid)) {
      $key = \array_keys($hasAutobid);
      $autobid = $hasAutobid[$key[0]];
      $autobid->setAmountMax($amountMax);
      $autobid->setName($this->showAsCents($amountMax, '.', ','));
      $autobid->save();
    }
    else {
      $autobid = $this->createAutobid($uid, $auctionItemId, $relistGroup, $amountMax);
    }
    return $autobid;
  }

  /**
   * Return a general list of recommendind Bid Step increments.
   */
  private function createAutobid($uid, $auctionItemId, $relistGroup, $amountMax) {
    $auctionAutobid = $this->entityTypeManager->getStorage('auction_autobid');
    $amount = $this->roundCents($amountMax);
    return $auctionAutobid->create([
      'user_id' => $uid,
      'name' => $this->showAsCents($amount, '.', ',') /* Reminder:  always create a name. */,
      'amount_max' => $amount,
      'item' => $auctionItemId,
      'relist_group' => $relistGroup,
      'uuid' => $this->uuidService->generate(),
      'status' => 1,
      'created' => \time(),
    ])->save();
  }

  /**
   * Return a general list of recommendind Bid Step increments. concept, unused @fees.
   */
  public function suggestedBidIncrements() {
    return [
      '$50 — 300' => '$25',
      '$300 — 500' => '$50',
      '$500 — 2,000' => '$100',
      '$2,000 — 5,000' => '$250',
      '$5,000 — 10,000' => '$500',
      '$10,000 — 20,000' => '$1,000',
      '$20,000 — 50,000' => '$2,500',
      '$50,000 — 100,000' => '$5,000',
      '$100,000 — 300,000' => '$10,000',
      '$300,000 — 1,000,000' => '$25,000',
      '$1,000,000 — 2,000,000' => '$50,000',
      '$2,000,000 — 3,000,000' => '$100,000',
      '$3,000,000 — 5,000,000' => '$250,000',
      '$5,000,000 — 10,000,000' => '$500,000',
      '$10,000,000+' => '$1,000,000',
    ];
  }

  /**
   * A helper to build checkbox/select list options.
   */
  public function currencyOptions() {
    $currencies = self::currencyList();
    foreach ($currencies as $k => $v) {
      $currencies[$k] = $k . ' | ' . $v;
    }
    return $currencies;
  }

  /**
   * Return a list of known global currencies.
   */
  public function currencyList() {
    $currencies = [
      "AED" => t("United Arab Emirates dirham"),
      "AFN" => t("Afghan afghani"),
      "ALL" => t("Albanian lek"),
      "AMD" => t("Armenian dram"),
      "ANG" => t("Netherlands Antillean guilder"),
      "AOA" => t("Angolan kwanza"),
      "ARS" => t("Argentine peso"),
      "AUD" => t("Australian Dollar"),
      "AWG" => t("Aruban florin"),
      "AZN" => t("Azerbaijani manat"),
      "BAM" => t("Bosnia and Herzegovina convertible mark"),
      "BBD" => t("Barbados Dollar"),
      "BDT" => t("Bangladeshi taka"),
      "BGN" => t("Bulgarian lev"),
      "BHD" => t("Bahraini dinar"),
      "BIF" => t("Burundian franc"),
      "BMD" => t("Bermudian Dollar"),
      "BND" => t("Brunei Dollar"),
      "BOB" => t("Boliviano"),
      "BRL" => t("Brazilian real"),
      "BSD" => t("Bahamian Dollar"),
      "BTN" => t("Bhutanese ngultrum"),
      "BWP" => t("Botswana pula"),
      "BYN" => t("New Belarusian ruble"),
      "BYR" => t("Belarusian ruble"),
      "BZD" => t("Belize Dollar"),
      "CAD" => t("Canadian Dollar"),
      "CDF" => t("Congolese franc"),
      "CHF" => t("Swiss franc"),
      "CLF" => t("Unidad de Fomento"),
      "CLP" => t("Chilean peso"),
      "CNY" => t("Renminbi|Chinese yuan"),
      "COP" => t("Colombian peso"),
      "CRC" => t("Costa Rican colon"),
      "CUC" => t("Cuban convertible peso"),
      "CUP" => t("Cuban peso"),
      "CVE" => t("Cape Verde escudo"),
      "CZK" => t("Czech koruna"),
      "DJF" => t("Djiboutian franc"),
      "DKK" => t("Danish krone"),
      "DOP" => t("Dominican peso"),
      "DZD" => t("Algerian dinar"),
      "EGP" => t("Egyptian pound"),
      "ERN" => t("Eritrean nakfa"),
      "ETB" => t("Ethiopian birr"),
      "EUR" => t("Euro"),
      "FJD" => t("Fiji Dollar"),
      "FKP" => t("Falkland Islands pound"),
      "GBP" => t("Pound sterling"),
      "GEL" => t("Georgian lari"),
      "GHS" => t("Ghanaian cedi"),
      "GIP" => t("Gibraltar pound"),
      "GMD" => t("Gambian dalasi"),
      "GNF" => t("Guinean franc"),
      "GTQ" => t("Guatemalan quetzal"),
      "GYD" => t("Guyanese Dollar"),
      "HKD" => t("Hong Kong Dollar"),
      "HNL" => t("Honduran lempira"),
      "HRK" => t("Croatian kuna"),
      "HTG" => t("Haitian gourde"),
      "HUF" => t("Hungarian forint"),
      "IDR" => t("Indonesian rupiah"),
      "ILS" => t("Israeli new shekel"),
      "INR" => t("Indian rupee"),
      "IQD" => t("Iraqi dinar"),
      "IRR" => t("Iranian rial"),
      "ISK" => t("Icelandic króna"),
      "JMD" => t("Jamaican Dollar"),
      "JOD" => t("Jordanian dinar"),
      "JPY" => t("Japanese yen"),
      "KES" => t("Kenyan shilling"),
      "KGS" => t("Kyrgyzstani som"),
      "KHR" => t("Cambodian riel"),
      "KMF" => t("Comoro franc"),
      "KPW" => t("North Korean won"),
      "KRW" => t("South Korean won"),
      "KWD" => t("Kuwaiti dinar"),
      "KYD" => t("Cayman Islands Dollar"),
      "KZT" => t("Kazakhstani tenge"),
      "LAK" => t("Lao kip"),
      "LBP" => t("Lebanese pound"),
      "LKR" => t("Sri Lankan rupee"),
      "LRD" => t("Liberian Dollar"),
      "LSL" => t("Lesotho loti"),
      "LYD" => t("Libyan dinar"),
      "MAD" => t("Moroccan dirham"),
      "MDL" => t("Moldovan leu"),
      "MGA" => t("Malagasy ariary"),
      "MKD" => t("Macedonian denar"),
      "MMK" => t("Myanmar kyat"),
      "MNT" => t("Mongolian tögrög"),
      "MOP" => t("Macanese pataca"),
      "MRO" => t("Mauritanian ouguiya"),
      "MUR" => t("Mauritian rupee"),
      "MVR" => t("Maldivian rufiyaa"),
      "MWK" => t("Malawian kwacha"),
      "MXN" => t("Mexican peso"),
      "MXV" => t("Mexican Unidad de Inversion"),
      "MYR" => t("Malaysian ringgit"),
      "MZN" => t("Mozambican metical"),
      "NAD" => t("Namibian Dollar"),
      "NGN" => t("Nigerian naira"),
      "NIO" => t("Nicaraguan córdoba"),
      "NOK" => t("Norwegian krone"),
      "NPR" => t("Nepalese rupee"),
      "NZD" => t("New Zealand Dollar"),
      "OMR" => t("Omani rial"),
      "PAB" => t("Panamanian balboa"),
      "PEN" => t("Peruvian Sol"),
      "PGK" => t("Papua New Guinean kina"),
      "PHP" => t("Philippine peso"),
      "PKR" => t("Pakistani rupee"),
      "PLN" => t("Polish złoty"),
      "PYG" => t("Paraguayan guaraní"),
      "QAR" => t("Qatari riyal"),
      "RON" => t("Romanian leu"),
      "RSD" => t("Serbian dinar"),
      "RUB" => t("Russian ruble"),
      "RWF" => t("Rwandan franc"),
      "SAR" => t("Saudi riyal"),
      "SBD" => t("Solomon Islands Dollar"),
      "SCR" => t("Seychelles rupee"),
      "SDG" => t("Sudanese pound"),
      "SEK" => t("Swedish krona"),
      "SGD" => t("Singapore Dollar"),
      "SHP" => t("Saint Helena pound"),
      "SLL" => t("Sierra Leonean leone"),
      "SOS" => t("Somali shilling"),
      "SRD" => t("Surinamese Dollar"),
      "SSP" => t("South Sudanese pound"),
      "STD" => t("São Tomé and Príncipe dobra"),
      "SVC" => t("Salvadoran colón"),
      "SYP" => t("Syrian pound"),
      "SZL" => t("Swazi lilangeni"),
      "THB" => t("Thai baht"),
      "TJS" => t("Tajikistani somoni"),
      "TMT" => t("Turkmenistani manat"),
      "TND" => t("Tunisian dinar"),
      "TOP" => t("Tongan paʻanga"),
      "TRY" => t("Turkish lira"),
      "TTD" => t("Trinidad and Tobago Dollar"),
      "TWD" => t("New Taiwan Dollar"),
      "TZS" => t("Tanzanian shilling"),
      "UAH" => t("Ukrainian hryvnia"),
      "UGX" => t("Ugandan shilling"),
      "USD" => t("United States Dollar"),
      "UYI" => t("Uruguay Peso en Unidades Indexadas"),
      "UYU" => t("Uruguayan peso"),
      "UZS" => t("Uzbekistan som"),
      "VEF" => t("Venezuelan bolívar"),
      "VND" => t("Vietnamese đồng"),
      "VUV" => t("Vanuatu vatu"),
      "WST" => t("Samoan tala"),
      "XAF" => t("Central African CFA franc"),
      "XCD" => t("East Caribbean Dollar"),
      "XOF" => t("West African CFA franc"),
      "XPF" => t("CFP franc"),
      "XXX" => t("No currency"),
      "YER" => t("Yemeni rial"),
      "ZAR" => t("South African rand"),
      "ZMW" => t("Zambian kwacha"),
      "ZWL" => t("Zimbabwean Dollar"),
    ];
    return $currencies;
  }

}
