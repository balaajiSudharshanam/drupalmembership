<?php
// src/Plugin/Block/CustomBlock.php

namespace Drupal\customauction\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Validator\Constraints\IsNull;

/**
 * Provides a custom block.
 *
 * @Block(
 *   id = "custom_block",
 *   admin_label = @Translation("bidding form"),
 *   category = @Translation("bidding form")
 * )
 */
class CustomBlock extends BlockBase {

   /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */



  

  /**
   * {@inheritdoc}
   */
  public function build() {

    $currentUserId = \Drupal::currentUser()->id();
    $current_node = \Drupal::routeMatch()->getParameter('node');
  $node_id = $current_node->id();
    // Build the content of the block.
   $db=\Drupal::database()->select('customauctionTable','cb');
   $db->fields('cb',['currid']);
   $db->condition('cb.currid', $currentUserId, '=');
   $db->condition('cb.nid',$node_id,'=');
    $result=$db->execute()->fetchAll();
  //  dump($result);
   if(isset($result[0])){
    // dump("already exist");
    $query=\Drupal::database()->select('customauctionTable','cb');
    $query->fields('cb',['claim']);
    $query->condition('cb.currid', $currentUserId, '=');
    $query->condition('cb.nid',$node_id,'=');
    $claim=$query->execute()->fetchAll();
    foreach ($claim as $key => $value) {
      # code...

      
      if($value->claim==1){
        
         
       return[
          '#markup'=>'<div class="claimed">you offer has been claimed</div>'
       ];
       
      }else{
        
        return [
          '#markup'=>'<div class="bidded">please wait for job provider response</div>'
        ];
      }
    }
    

    
    
  //  }
   }else{
    $form = \Drupal::formBuilder()->getForm('\Drupal\customauction\Form\customauctionForm');
    return $form;
   }

    // $form = \Drupal::formBuilder()->getForm('\Drupal\customauction\Form\customauctionForm');
    // return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    // Set the cache max age for the block.
    return 0; // or any other value as per your requirements.
  }

}
