<?php

namespace Drupal\custom_module\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\TextareaWidget;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'custom_comments_default_widget' widget.
 *
 * @FieldWidget(
 *   id = "custom_comments_default_widget",
 *   label = @Translation("Custom Comments widget"),
 *   field_types = {
 *     "custom_comments"
 *   }
 * )
 */
class CustomCommentsWidget extends TextareaWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('name'),
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : '',
      '#rows' => 4,
      '#resizable' => 'vertical',
    ];

    return $element;
  }
}
