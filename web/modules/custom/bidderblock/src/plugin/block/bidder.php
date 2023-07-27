<?php

namespace Drupal\bidderblock\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a custom block to display the auction data.
 *
 * @Block(
 *   id = "bidderblock_block",
 *   admin_label = @Translation("Custom Auction Block"),
 * )
 */
class CustomAuctionBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $databaseConnection;

  /**
   * Constructs a new CustomAuctionBlock instance.
   *
   * @param array $configuration
   *   The block configuration.
   * @param string $plugin_id
   *   The plugin ID for the block.
   * @param mixed $plugin_definition
   *   The plugin definition for the block.
   * @param \Drupal\Core\Database\Connection $database_connection
   *   The database connection.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $database_connection) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->databaseConnection = $database_connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $currentUserId = \Drupal::currentUser()->id();
    $current_node = \Drupal::routeMatch()->getParameter('node');
    $node_id = $current_node->id();
  

    // Query the customauctionTable for name and bid fields.
    $query = $this->databaseConnection->select('customauctionTable', 'cb')
    ->fields('cb', ['name', 'bid','nid'])
   ->condition('cb.currID', $currentUserId,"=")
    ->condition('cb.nid',$node_id)->execute()->fetchAll();
   dump($query) ;
    dump("hi");
    // Build the list of names and bids.
    
   $list=[];
    foreach ($query as $result) {
      $name = $result->name;
      $bid = $result->bid;
      $nodeid = $result->nid;
      
      $list[] = "$name: $bid:$nodeid";
    }

    // Build the block content.
    if (!empty($list)) {
      $build['auction_list'] = [
        '#theme' => 'item_list',
        '#items' => $list,
        '#title' => $this->t('Auction Data'),
      ];
    }

    return ;
  }

}
