<?php

/**
 * @file
 * Contains \Drupal\student_registration\Form\RegistrationForm.
 */

namespace Drupal\auctionlist\Form;

use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Session\AccountInterface;
class editForm extends FormBase
{
    
    
    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'editform';
    }

    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $id= \Drupal::request()->query->get('id');
        $database=\Drupal::database();
        $result=$database->select('customauctionTable','cb')->fields('cb',['name','bid','description','file_id'])->condition('id',$id)->execute()->fetchAll();
        foreach($result as $data){
            $name=$data->name;
            $bid=$data->bid;
            $description=$data->description;
            $fileid =(explode(",",$data->file_id));
    $links=[];
            foreach($fileid as $id){
     
                $file=File::load($id);
                ;
              if($file instanceof \Drupal\file\FileInterface){
               
                  // Generate the download link for the file.
                  $uri = $file->getFileUri();
                 
                  $link=\Drupal::service('file_url_generator')->generateString($uri);
                  $links[]="<a href='$link' class='card-link'>view document1</a>";
                  
                
              }
              
              }
        }
        
        
        // $current_node = \Drupal::routeMatch()->getParameter('node');
        // if ($current_node instanceof \Drupal\node\NodeInterface) {
        //     $node_id = $current_node->id();
        // }
        
        // $node = \Drupal\node\Entity\Node::load($node_id);

        // if ($node) {
        //     // Get the content owner ID.
        //     $owner_id = $node->getOwnerId();
        //     $current_user = \Drupal::currentUser()->id();
        //     $user = \Drupal\user\Entity\User::load($current_user);
        //     // $uid = $current_user->id();
           
        // }
        // $roles = $user->getRoles();
        // if (in_array('administrator', $roles)) {
        //     return [];
        // }
        // dump($current_user);
        
        $form['name'] = array(
            '#type' => 'textfield',
            '#title' => t('Enter Name:'),
            '#required' => true,
            '#value'=>$name
        );
        $form['bid'] = array(
            '#type' => 'textfield',
            '#title' => t('bid price:'),
            '#required' => true,
            '#value'=>$bid
        );
        // $form['ownerid'] = [
        //     '#type' => 'hidden',
        //     '#value' => $owner_id,
        // ];
        // $form['currid'] = [
        //     '#type' => 'hidden',
        //     '#value' => $current_user,
        // ];
        // $form['nid'] = [
        //     '#type' => 'hidden',
        //     '#value' => $node_id,
        // ];
        $form['description'] = [
            '#type' => 'textarea',
            '#title' => t('additional informations'),
            '#value'=>$description
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
            "#default_value"=>$fileid,
            // '#element_validate' => [[$this, 'ElementValidate']],
        ];

        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Bid'),
            '#button_type' => 'primary',
        );
        $form['#validate'][] = [$this, 'validateFileUpload'];
        return $form;
    }
    
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        if (strlen($form_state->getValue('description')) > 1000) {
            $form_state->setErrorByName('description', $this->t('please writr shorter description'));
        }
    }
    public function ElementValidate(array &$element, FormStateInterface $form_state) {
      // Get the list of files submitted in the form.
      $submitted_files = $form_state->getValue('file_upload', []);
  
      // Get the list of files that were already stored in the database.
      $stored_files = array_filter(explode(',', $element['#default_value']));
  
      // Find the files that were removed from the form.
      $removed_files = array_diff($stored_files, $submitted_files);
  
      // Delete the files that were removed from the form.
      foreach ($removed_files as $file_id) {
        $file = File::load($file_id);
        if ($file) {
          $file->delete();
        }
      }
    }
    public function validateFileUpload(array &$form, FormStateInterface $form_state) {
        $files = $form_state->getValue('file_upload');
        $delete_files = $form_state->getValue('file_upload');
    
        foreach ($delete_files as $index => $file) {
          if ($file == 0) {
           
            // Remove the file from the managed_file field if set to 0.
            unset($files[$index]);
          }
        }
    
        // Set the updated file values back to the form state.
        $form_state->setValue('file_upload', $files);
      }
    public function submitForm(array &$form, FormStateInterface $form_state,AccountInterface $id = NULL ) {
        // dump($id);
        try {
          $conn = Database::getConnection();
          $files = $form_state->getValue('file_upload');
          $delete_files = $form_state->getValue('file_upload');
        //   dd($delete_files);
          foreach ($delete_files as $index => $file) {
           
              if ($file == 0) {
                
                  unset($file_ids[$index]);
              }
          }
          $file_ids = [];
          dump($files);
          foreach ($files as $file) {
            if (!empty($file)) {
              $file_entity = \Drupal\file\Entity\File::load($file);
              if ($file_entity instanceof \Drupal\file\FileInterface) {
                $file_entity->setPermanent();
                $file_entity->save();
                
                $file_ids[] = $file_entity->id();
              }
            }
          }
          
          $field = $form_state->getValues();
          
          $fields["name"] = $field['name'];
          $fields["bid"] = $field['bid'];
        //   $fields["ownerid"] = $field['ownerid'];
        //   $fields["currid"] = $field["currid"];
        //   $fields["nid"] = $field["nid"];
        //   $fields['claim'] = "0";
          $fields['description'] = $field['description'];
          $fields["file_id"] = implode(',', $file_ids); // Store multiple file IDs as a comma-separated string.
          $id= \Drupal::request()->query->get('id');
          $node=\Drupal::request()->query->get("nid");
        //   dd($id);
          $conn->update('customauctiontable')
            ->fields($fields
            )->condition('id',$id)->execute();
          \Drupal::messenger()->addMessage($this->t('Bid Price :' . $fields["bid"] . 'Name : ' . $fields["name"] . ''));
          $response = new RedirectResponse("http://localhost/membership/web/node/$node");
        $response->send();
        } catch (\Exception $ex) {
          \Drupal::logger('bids')->error($ex->getMessage());
        }
      }
      
}
