<?php


 namespace Drupal\bidderblock\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller for deleting a record from customauctionTable.
 */
class bidderblockController extends ControllerBase {

    /**
     * Deletes a record from the customauctionTable.
     *
     * @param int $id
     *   The ID of the record to delete.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *   The redirect response to the auction list page.
     */
    public function deleteRecord($id) {
      // Call the deleteRecord function in the biddingblock instance.
      $block = \Drupal::service('plugin.manager.block')
        ->createInstance('bidder');
      $block->deleteRecord($id);
  
      // Redirect back to the auction list page.
      // $url = \Drupal\Core\Url::fromRoute('auctionlist.page');
      $response = new RedirectResponse("http://localhost/membership/web/projects");
      return $response;
    }
    
  }
  