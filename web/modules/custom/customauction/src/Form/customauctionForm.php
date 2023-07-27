<?php

/**
 * @file
 * Contains \Drupal\student_registration\Form\RegistrationForm.
 */

namespace Drupal\customauction\Form;

use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

class customauctionForm extends FormBase
{
    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'customauction_registration_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $current_node = \Drupal::routeMatch()->getParameter('node');
        if ($current_node instanceof \Drupal\node\NodeInterface) {
            $node_id = $current_node->id();
        }
        $node = \Drupal\node\Entity\Node::load($node_id);

        if ($node) {
            // Get the content owner ID.
            $owner_id = $node->getOwnerId();
            $current_user = \Drupal::currentUser()->id();
            $user = \Drupal\user\Entity\User::load($current_user);
            // $uid = $current_user->id();
        }
        $roles = $user->getRoles();
        if (in_array('administrator', $roles)) {
            return [];
        }
        // dump($current_user);
        $form['name'] = array(
            '#type' => 'textfield',
            '#title' => t('Enter Name:'),
            '#required' => true,
        );
        $form['bid'] = array(
            '#type' => 'textfield',
            '#title' => t('bid price:'),
            '#required' => true,
        );
        $form['ownerid'] = [
            '#type' => 'hidden',
            '#value' => $owner_id,
        ];
        $form['currid'] = [
            '#type' => 'hidden',
            '#value' => $current_user,
        ];
        $form['nid'] = [
            '#type' => 'hidden',
            '#value' => $node_id,
        ];
        $form['description'] = [
            '#type' => 'textarea',
            '#title' => t('additional informations'),
        ];

        $form['file_upload'] = [
            '#type' => 'managed_file',
            '#title' => t('Upload File'),
            '#upload_location' => 'public://custom_files/',
            '#description' => t('Upload one or more files.'),
            '#multiple' => TRUE, // Enable multiple file uploads.
            '#upload_validators' => [
                'file_validate_extensions' => [],
            ],
        ];

        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Bid'),
            '#button_type' => 'primary',
        );

        return $form;
    }

    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        if (strlen($form_state->getValue('description')) > 1000) {
            $form_state->setErrorByName('description', $this->t('please writr shorter description'));
        }
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
        try {
          $conn = Database::getConnection();
          $files = $form_state->getValue('file_upload');
          
          $file_ids = [];
          foreach ($files as $file_id) {
            if (!empty($file_id)) {
                $file_entity = File::load($file_id);
                
                if ($file_entity instanceof \Drupal\file\FileInterface) {
                    $file_entity->setPermanent();
                    $file_entity->save();
                    $file_ids[] = $file_entity->id();
                }
            }
        }
        
        //   dump($file_entity);
          $field = $form_state->getValues();
          
          $fields["name"] = $field['name'];
          $fields["bid"] = $field['bid'];
          $fields["ownerid"] = $field['ownerid'];
          $fields["currid"] = $field["currid"];
          $fields["nid"] = $field["nid"];
          $fields['claim'] = "0";
          $fields['description'] = $field['description'];
          $fields["file_id"] = implode(',', $file_ids); // Store multiple file IDs as a comma-separated string.
      
          $conn->insert('customauctiontable')
            ->fields($fields)->execute();
          \Drupal::messenger()->addMessage($this->t('Bid Price :' . $fields["bid"] . 'Name : ' . $fields["name"] . ''));
        } catch (\Exception $ex) {
          \Drupal::logger('bids')->error($ex->getMessage());
        }
      }
      
}
