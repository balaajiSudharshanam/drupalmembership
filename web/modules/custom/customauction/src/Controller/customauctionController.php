<?php

/**
 * @file
 * Contains \Drupal\student_registration\Form\RegistrationForm.
 */

namespace Drupal\customauction\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Render\Markup;

class customauctionController extends ControllerBase
{
    public function index()
    {
$html = '
<h1>hjhgjhg</h1>
';
$build = [];
$currentUserId = \Drupal::currentUser()->id();
// Query the custom table for bidding data.
$query = $this->$databaseConnection->select('customauctionTable', 'cb');
$query->fields('cb', ['name', 'bid']);
$query->orderBy('cb.bid', 'DESC');
$query->condition('cb.ownerid', $currentUserId, '=');
$results = $query->execute()->fetchAll();

// Build the list of buyers and their bidding amounts.
$list = [];
foreach ($results as $result) {
  $name = $result->name;
  $bid = $result->bid;
  
  // Add each item to the list.
  $list[] = [
    '#type' => 'markup',
    '#markup' => "$name: $bid",
  ];
}

// Build the block content.
if (!empty($list)) {
  $build['buyers_list'] = [
    '#theme' => 'item_list',
    '#items' => $list,
    '#title' => $this->t('Buyers and Bidding Amounts'),
  ];
}

return $build;
}
}