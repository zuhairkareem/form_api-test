<?php

/**
 * @file
 * Contains \Drupal\form_api_test\Form\Multistep\MultistepOneForm.
 */

namespace Drupal\form_api_test\Form\Multistep;

use Drupal\Core\Form\FormStateInterface;

class MultistepOneForm extends MultistepFormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'multistep_form_one';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);

    $form['file_upload'] = [
      '#type' => 'managed_file',
      '#title' => 'Image',
      '#upload_location' => 'temporary://',
      '#default_value' => (!empty($this->store->get('file_fid') )) ? [$this->store->get('file_fid') ] : [],
      '#progress_indicator' => 'bar',
      '#progress_message' => 'Please wait while your file is uploaded...',
      '#upload_validators' => [
        'file_validate_extensions' => ['png jpg jpeg'], // Allowed file extensions.
        'file_validate_size' => [10485760], // 10MB
      ],
      '#theme' => 'image_widget',
      '#preview_image_style' => 'medium',
      '#required' => TRUE,
      '#required_error' => $this->t('This field is required.'),
    ];

    $form['actions']['submit']['#value'] = $this->t('Next');

    $form['#cache']['contexts'][] = 'session';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    // Validate the upload field to ensure that at least one file is selected.
    if (empty($form_state->getValue('file_upload'))) {
      $form_state->setErrorByName('file_upload', $this->t('Please select a file to upload.'));
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $file_id = $form_state->getValue('file_upload')[0];

    $form_state->set('file_fid', $file_id);
    $this->store->set('file_fid', $file_id);
    $form_state->setRedirect('form_api_test.multistep_two');
  }
}
