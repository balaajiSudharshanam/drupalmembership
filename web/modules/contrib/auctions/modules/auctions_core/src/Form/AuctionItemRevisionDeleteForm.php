<?php

namespace Drupal\auctions_core\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting a Auction Items revision.
 *
 * @ingroup auctions_core
 */
class AuctionItemRevisionDeleteForm extends ConfirmFormBase {

  /**
   * The Auction Items revision.
   *
   * @var \Drupal\auctions_core\Entity\AuctionItemInterface
   */
  protected $revision;

  /**
   * The Auction Items storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $auctionItemStorage;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->auctionItemStorage = $container->get('entity_type.manager')->getStorage('auction_item');
    $instance->connection = $container->get('database');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'auction_item_revision_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the revision from %revision-date?', [
      '%revision-date' => format_date($this->revision->getRevisionCreationTime()),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.auction_item.version_history', ['auction_item' => $this->revision->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $auction_item_revision = NULL) {
    $this->revision = $this->AuctionItemStorage->loadRevision($auction_item_revision);
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->AuctionItemStorage->deleteRevision($this->revision->getRevisionId());

    $this->logger('content')->notice('Auction Items: deleted %title revision %revision.', ['%title' => $this->revision->label(), '%revision' => $this->revision->getRevisionId()]);
    $this->messenger()->addMessage(t('Revision from %revision-date of Auction Items %title has been deleted.', ['%revision-date' => format_date($this->revision->getRevisionCreationTime()), '%title' => $this->revision->label()]));
    $form_state->setRedirect(
      'entity.auction_item.canonical',
       ['auction_item' => $this->revision->id()]
    );
    if ($this->connection->query('SELECT COUNT(DISTINCT vid) FROM {auction_item_field_revision} WHERE id = :id', [':id' => $this->revision->id()])->fetchField() > 1) {
      $form_state->setRedirect(
        'entity.auction_item.version_history',
         ['auction_item' => $this->revision->id()]
      );
    }
  }

}
