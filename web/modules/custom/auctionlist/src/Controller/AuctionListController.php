<?php


 namespace Drupal\auctionlist\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\node\NodeInterface;
/**
 * Controller for deleting a record from customauctionTable.
 */
class AuctionListController extends ControllerBase {

    /**
     * Deletes a record from the customauctionTable.
     *
     * @param int $id
     *   The ID of the record to delete.
     * @param int $nid
     * @param int $cu
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *   The redirect response to the auction list page.
     */
   
    public function deleteRecord($id,$nid) {
     
      
      // Call the deleteRecord function in the biddingblock instance.
      $block = \Drupal::service('plugin.manager.block')
        ->createInstance('auctionlist_block');
      $block->deleteRecord($id);
      
      // Redirect back to the auction list page.
      // $url = \Drupal\Core\Url::fromRoute('auctionlist.page');
      $response = new RedirectResponse("http://localhost/membership/web/node/$nid");
      
      return  $response;
    }
    public function claim($id,$nid,$cu) {
    
      // Call the deleteRecord function in the biddingblock instance.
      $block = \Drupal::service('plugin.manager.block')
        ->createInstance('auctionlist_block');
      $block->claim($id,$nid,$cu);
     
     
      // Redirect back to the auction list page.
      // $url = \Drupal\Core\Url::fromRoute('auctionlist.page');
      // $response = new RedirectResponse("http://localhost/membership/web/projects");
      $response = new RedirectResponse("http://localhost/membership/web/node/$nid");
      return $response;
    }
    public function downloadItemTypeExport($filename) {

      // Do some file validation here, like checking for extension.
  
      // File lives in /files/downloads.
      $uri_prefix = 'public://downloads/';
  
      $uri = $uri_prefix . $filename;
  
      $headers = [
        'Content-Type' => 'text/csv', // Would want a condition to check for extension and set Content-Type dynamically
        'Content-Description' => 'File Download',
        'Content-Disposition' => 'attachment; filename=' . $filename
      ];
  
      // Return and trigger file donwload.
      return new BinaryFileResponse($uri, 200, $headers, true );
  
    }
    
  }
  