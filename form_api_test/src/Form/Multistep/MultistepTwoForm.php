<?php

/**
 * @file
 * Contains \Drupal\form_api_test\Form\Multistep\MultistepTwoForm.
 */

namespace Drupal\form_api_test\Form\Multistep;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Entity\File;
use Symfony\Component\HttpFoundation\RedirectResponse;

class MultistepTwoForm extends MultistepFormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'multistep_form_two';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);
    if (empty($this->store->get('file_fid'))) {
      $url = Url::fromRoute('form_api_test.multistep_one');
      $response = new RedirectResponse($url->toString());
      $response->send();
      return;
    }
    $form['name'] = array(
      '#type' => 'textfield',
      '#default_value' => $this->store->get('name') ? $this->store->get('name') : '',
      '#required' => true,
      '#attributes' => array(
        'placeholder' => $this->t('Your name'),
      ),
      '#required_error' => $this->t('This field is required.'),
    );

    $form['email'] = array(
      '#type' => 'email',
      '#default_value' => $this->store->get('email') ? $this->store->get('email') : '',
      '#required' => true,
      '#attributes' => array(
        'placeholder' => $this->t('Your email'),
      ),
      '#required_error' => $this->t('This field is required.'),
    );

    $form['actions']['previous'] = array(
      '#type' => 'link',
      '#title' => $this->t('Previous'),
      '#attributes' => array(
        'class' => ['btn btn-secondary'],
      ),
      '#weight' => 0,
      '#url' => Url::fromRoute('form_api_test.multistep_one'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate the upload field to ensure that at least one file is selected.
    if (empty($form_state->getValue('name'))) {
      $form_state->setErrorByName('name', $this->t('This field is required.'));
    }
    if (empty($form_state->getValue('email'))) {
      $form_state->setErrorByName('email', $this->t('This field is required.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->store->set('name', $form_state->getValue('name'));
    $this->store->set('email', $form_state->getValue('email'));

    try {

      $filesystem = \Drupal::service('file_system');
      // Load the file object using its fid.
      $temporary_file = \Drupal\file\Entity\File::load($this->store->get('file_fid'));

      // Get filename for the permanent file.
      $filename = $filesystem->basename($temporary_file->getFileUri());

      // Move the file to the permanent file directory.
      $dir = 'public://' . date('Y-m') . '/';
      if (!\Drupal::service('file_system')->prepareDirectory($dir)) {
        \Drupal::service('file_system')->prepareDirectory($dir, FileSystemInterface::CREATE_DIRECTORY);
      }
      $destination =  $dir . $filename;
      $filesystem->move($temporary_file->getFileUri(), $destination);

      // Create a new file object for the permanent file.
      $perm_file = File::create([
        'uid' => \Drupal::currentUser()->id(),
        'filename' => $filename,
        'uri' => $destination,
        'status' => 1,
      ]);

      // Save the permanent file object.
      $perm_file->save();

      // Remove the session data
      parent::saveData();
      \Drupal::service('messenger')->addMessage('Thanks for your time, Try again!', 'custom');
      $form_state->setRedirect('form_api_test.multistep_one');
    }
    catch (\Exception $e) {
      // Handle other exceptions.
      $logger = \Drupal::service('logger.factory')->get('form_api_testor');
      $logger->error('Error %message', ['%message' => json_encode($e->getMessage())]);
    }
  }
}
