<?php
namespace Drupal\auctionlist\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Database\Connection;
use Drupal\file\Entity\File;
use Drupal\Core\Render\Element\Link;
use Drupal\Core\Url;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Tests\Component\Annotation\Doctrine\Fixtures\Annotation\Route;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelInterface;

use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Provides a custom block to display the list of buyers and their bidding amounts.
 *
 * @Block(
 *   id = "auctionlist_block",
 *   admin_label = @Translation("Buyers and Bidding Amounts"),
 * )
 */


class biddingblock extends BlockBase implements ContainerFactoryPluginInterface {

  // ...
  protected $databaseConnection;
  protected $urlGenerator;
  /**
   * Constructs a new biddingblock instance.
   *
   * @param array $configuration
   *   The block configuration.
   * @param string $plugin_id
   *   The plugin ID for the block.
   * @param mixed $plugin_definition
   *   The plugin definition for the block.
   * @param \Drupal\Core\Database\Connection $database_connection
   *   The database connection.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The URL generator.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $database_connection, UrlGeneratorInterface $url_generator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->databaseConnection = $database_connection;
    $this->urlGenerator = $url_generator;
  }
// public static function newconstruct(array $configuration, $plugin_id, $plugin_definition, Connection $database_connection, UrlGeneratorInterface $url_generator ){
//   $this->databaseConnection=$database_connection;
// }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('url_generator')
    );
  }
  

/**
 * {@inheritdoc}
 */
public function build() {
  $build = [];
  $currentUserId = \Drupal::currentUser()->id();
  $user = \Drupal\user\Entity\User::load($currentUserId);
  
  // dd($claimed);
 
  // Get the user's roles.
  $roles = $user->getRoles();
  $current_node = \Drupal::routeMatch()->getParameter('node');
  $node_id = $current_node->id();
  $claimed=$this->databaseConnection->select('auctionlist',"al")->fields('al',['claim'])->condition('al.currid',$currentUserId,'=')->condition('al.nid',$node_id)->execute()->fetchField();
  if($claimed){
    if (in_array('administrator', $roles)) {
      // 
      $text[]=[
        '#type'=>'markup',
        '#markup'=>'<h3>claimed</h3>'
      ];
      $build['buyers_list'] = [
        '#theme' => 'item_list',
        '#items' => $text,
        '#title' => $this->t('bidding '),
      ];
      return $build;
    }
   
  }
  // dump($roles);
  // Check if a specific role exists.
  if (in_array('administrator', $roles)) {
    $query = $this->databaseConnection->select('customauctionTable', 'cb');
  $query->fields('cb', ['id', 'name', 'bid','description','file_id','nid']);
  $query->orderBy('cb.bid', 'DESC');
  $query->condition('cb.ownerid', $currentUserId, '=');
  $query->condition('cb.nid',$node_id,'=');
  $results = $query->execute()->fetchAll();

  
  
  }else if(in_array('subscriber', $roles)){
  
    $query = $this->databaseConnection->select('customauctionTable', 'cb');
  $query->fields('cb', ['id', 'name', 'bid','description','file_id','nid']);
  $query->orderBy('cb.bid', 'DESC');
  $query->condition('cb.currid', $currentUserId, '=');
  $query->condition('cb.nid',$node_id,'=');
  
  $results = $query->execute()->fetchAll();
  
  }
  // // Query the custom table for bidding data.
  // $query = $this->databaseConnection->select('customauctionTable', 'cb');
  // $query->fields('cb', ['id', 'name', 'bid']);
  // $query->orderBy('cb.bid', 'DESC');
  // $query->condition('cb.ownerid', $currentUserId, '=');
  // $results = $query->execute()->fetchAll();

  // Build the list of buyers and their bidding amounts.
  $list = [];
  foreach ($results as $result) {
    $recordId = $result->id;
    $name = $result->name;
    $bid = $result->bid;
    $des= $result->description;
    $nid= $result->nid;
    $fileid =(explode(",",$result->file_id));
    $links=[];
    foreach($fileid as $id){
     
      $file=File::load($id);
      ;
    if($file instanceof \Drupal\file\FileInterface){
     
        // Generate the download link for the file.
        $uri = $file->getFileUri();
       
        $link=\Drupal::service('file_url_generator')->generateString($uri);
        $links[]="<br></br><a href='$link' class='card-link'>view document1</a>";
        
      
    }
    
    }
   
  //  dd($nid);
    
    // Create the delete link for each record.
    $deleteUrl = $this->urlGenerator->generateFromRoute('auctionlist.delete', ['id' => $recordId,'nid'=>$nid]);
    $claimUrl = $this->urlGenerator->generateFromRoute('auctionlist.claim', ['id' => $recordId,'nid'=>$nid,'cu'=>$currentUserId]);
    $editUrl=$this->urlGenerator->generateFromRoute('auctionlist.edit',['nid'=>$nid,'id'=>$recordId]);
   
   
    // Add each item to the list. if
    if (in_array('subscriber', $roles)&& ($links)) {
     
    $list[] = [
      '#type' => 'markup',
      '#markup' => "<div class='card' style='width: 18rem;'>
      <div class='card-body'>
        <h5 class='card-title'>$bid</h5>
        <h6 class='card-subtitle mb-2 text-muted'>$name</h6>
        <p class='card-text'>$des</p>
        <a href='$deleteUrl' class='btn btn-danger'>delete</a>
        <a href='$editUrl' class='btn btn-warning'>edit</a>
        
      
    ",
    ];
    foreach($links as $link){
      $list[count($list) - 1]['#markup'] .= ' ' . $link;
    }
  }else if(in_array('administrator', $roles) && (!$links)){
    
    if (in_array('adminstrator', $roles)&& ($claimed)){
      $list[]=[
        "#type"=>'markup',
        '#markup'=>'<h3>you have rewarded the project</h3>'
      ];
    }
    $list[]=[
      '#type'=>'markup',
      '#markup'=>"<div class='card' style='width: 18rem;'>
      <div class='card-body'>
        <h5 class='card-title'>$bid</h5>
        <h6 class='card-subtitle mb-2 text-muted'>$name</h6>
        <p class='card-text'>$des</p>
        <a href='$claimUrl' class='btn btn-success'>Claim</a>
        
      </div>
    </div>",
     
    ];
  }else if(in_array('administrator', $roles) && ($links)){
    if (in_array('adminstrator', $roles)&& ($claimed)){
      // $list[]=[
      //   "#type"=>'markup',
      //   '#markup'=>'<h3>you have rewarded the project</h3>'
      // ];
      \Drupal::messenger()->addMessage($this->t('running good'));
    }
    $list[]=[
      '#type'=>'markup',
      '#markup'=>"<div class='card' style='width: 18rem;'>
      <div class='card-body'>
        <h5 class='card-title'>$bid</h5>
        <h6 class='card-subtitle mb-2 text-muted'>$name</h6>
        <p class='card-text'>$des</p>
        <a href='$claimUrl' class='btn btn-success'>Claim</a>
        
      
    </div>",
     
    ];
    foreach($links as $link){
      $list[count($list) - 1]['#markup'] .= ' ' . $link;
    }
  }else if(in_array('subscriber', $roles) && (!$links)){
    $list[]=[
      '#type'=>'markup',
      '#markup'=>"<div class='card' style='width: 18rem;'>
      <div class='card-body'>
        <h5 class='card-title'>$bid</h5>
        <h6 class='card-subtitle mb-2 text-muted'>$name</h6>
        <p class='card-text'>$des</p>
        <a href='$deleteUrl' class='btn btn-danger'>Delete</a>
        <a href='$editUrl' class='btn btn-warning'>edit</a>
      </div>
    </div>",
      // $form['claim'] = [
      //   '#type' => 'submit', 
      //   '#id'=>'claimnow',// Change 'textfield' to 'submit'.
      //   '#value' => $this->t('Claim'),
       
      // ]
    ];
  }
  
  
}
  // Build the block content.
  if (!empty($list)) {
    $build['buyers_list'] = [
      '#theme' => 'item_list',
      '#items' => $list,
      '#title' => $this->t('bidding '),
    ];
  }
  
  return $build;
}

public function getCacheMaxAge() {
  // Disable caching for the block.
  return 0;
}

  /**
   * Implements hook_menu().
   */
  public function registerRoutes() {
    $routes = [];

    // Define the route for deleting a record.
    $routes['auctionlist.delete'] = new Route(
      '/auctionlist/delete/{id}/{nid}',
      [
        '_controller' => '\Drupal\auctionlist\Plugin\Block\BiddingBlock::deleteRecord',
      ],
      // [
      //   '_permission' => 'administer site configuration',
      // ]
    );
    $routes['auctionlist.claim']=new Route(
      '/auctionlist/claim/{id}/{nid}/{cu}',
      [
        '_controller'=>'\Drupal\auctionlist\Plugin\Block\BiddingBlock::claim'
      ]
    );
    $routes['auctionlist.edit'] = new Route(
      '/auctionlist/edit/{id}/{nid}',
      [
          '_controller' => '\Drupal\auctionlist\Plugin\Block\BiddingBlock::editRecord',
      ],
      // Add necessary permissions if required.
  );
    
    return $routes;
  }

/**
   * Deletes a record from the customauctionTable.
   *
   * @param int $id
   *   The ID of the record to delete.
   */
  public function deleteRecord($id) {
    
    $query = $this->databaseConnection->select('customauctionTable', 'cb');
    $query->fields('cb', ['file_id']);
    $query->condition('id', $id);
    $fileIds = $query->execute()->fetchField();
    $this->deleteFiles($fileIds);
   
   $this->databaseConnection->delete('customauctionTable')
   ->condition('id', $id)
   ->execute();
   \Drupal::messenger()->addMessage($this->t('Record deleted successfully'));
  }

  public function claim($id,$nid,$currentUserId) {
    $this->databaseConnection->update('customauctionTable')->fields(['claim'=>1])
      ->condition('id', $id)

      ->execute();
      $this->databaseConnection->insert('auctionlist')->fields(['nid'=>$nid,'claim'=>1,'currid'=>$currentUserId])->execute();

      \Drupal::messenger()->addMessage($this->t('running good'));
    // $this->databaseConnection->insert('auctionlist')->fields(['nid'=>$node_id,'currentuser'=>$currentUserId,'claim'=>1])->execute();
  
  }
  public function edit($id){
    $form = \Drupal::formBuilder()->getForm('\Drupal\aucctionlist\Form\editForm');
    return $form;
  }
  /**
 * Deletes files associated with the bid.
 *
 * @param string $fileIds
 *   Comma-separated file IDs.
 */
private function deleteFiles($fileIds) {
  $fileIdsArray = explode(',', $fileIds);

  foreach ($fileIdsArray as $fileId) {
    $file = File::load($fileId);
    if ($file) {
      // Delete the file permanently from the system.
      $file->delete();
    }
  }
}

}