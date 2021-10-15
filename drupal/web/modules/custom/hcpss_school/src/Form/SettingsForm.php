<?php

namespace Drupal\hcpss_school\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure HCPSS School settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hcpss_school_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['hcpss_school.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['acronym'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Acronym'),
      '#default_value' => $this->config('hcpss_school.settings')->get('acronym'),
    ];
    
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('hcpss_school.settings')
      ->set('acronym', $form_state->getValue('acronym'))
      ->save();
    
    parent::submitForm($form, $form_state);
  }
}
