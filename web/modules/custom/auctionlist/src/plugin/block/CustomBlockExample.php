<?php

namespace Drupal\auctionlist\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Annotation\RenderElement;


/**
 * Provides a custom block example.
 *
 * @Block(
 *   id = "auctionlist",
 *   admin_label = @Translation("Custom Block Example")
 * )
 */
class CustomBlockExample extends BlockBase implements ContainerFactoryPluginInterface
{

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $databaseConnection;

  /**
   * Constructs a new CustomBlockExample instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the block.
   * @param string $plugin_id
   *   The plugin_id for the block.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $database_connection
   *   The database connection.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $database_connection)
  {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->databaseConnection = $database_connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
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
  public function build()
  {

    $build = [];
    $build['click'] = [
      "#type" => "submit",
      "#value" => "submit"
    ];
    $list = [
      "#type" => "button",
      "#value" => "submit"
    ];

    $current_node = \Drupal::routeMatch()->getParameter('node');
    $node_id = $current_node->id();
    $currentUserId = \Drupal::currentUser()->id();
    $user = \Drupal\user\Entity\User::load($currentUserId);
    $roles = $user->getRoles();

    // Query the custom table for name and bid data.

    if (in_array('administrator', $roles)) {

      $query = $this->databaseConnection->select('customauctionTable', 'cat');
      $query->fields('cat', ['name', 'bid']);
      $query->condition('cat.ownerid', $currentUserId, '=');
      $query->condition('cat.nid', $node_id, '=');
      $query->orderBy('cat.bid', 'ASC');
      $results = $query->execute()->fetchAll();
    }

    // Build the block content.

    if (!empty($results)) {

      $table = [
        '#type' => 'table',
        '#header' => [
          $this->t('Name'),
          $this->t('Bid'),
          $this->t('favirote'),

        ],
        '#rows' => [],
        '#cache' => [
          'max-age' => 0,
        ],
      ];

      $lowest_bid = $results[0]->bid;

      foreach ($results as $result) {
        $row = [
          $result->name,
          $result->bid,
          
         
          


        ];
        
        // Highlight the row if it is the lowest bidder.
        if ($result->bid == $lowest_bid) {
          $row['#attributes']['class'][] = 'lowest-bid-row';
        }
        $table["#row"][2] = $list;
        $table['#rows'][] = $row;
      }

      $build['table'] = $table;

      // Attach CSS library to highlight the lowest bid row.
      $build['#attached']['library'][] = 'custom_block_example/lowest_bid_highlight';
    }

    return $build;
  }
  public function getCacheMaxAge()
  {
    // Disable caching for the block.
    return 0;
  }
  public function onsubmit()
  {
  }
  /**
   * Creates a button with an onclick function.
   *
   * @param string $buttonText
   *   The text to display on the button.
   * @param string $onClickFunction
   *   The JavaScript function to be executed when the button is clicked.
   *
   * @return array
   *   The render array representing the button element.
   */
  protected function createButton($buttonText, $onClickFunction)
  {
    $button = [
      '#type' => 'button',
      '#value' => $buttonText,
      '#attributes' => [
        'onclick' => $onClickFunction,
      ],
    ];

    return $button;
  }
}
