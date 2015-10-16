<?php

/**
 * @file
 * Contains \Drupal\entity_browser\Form\EntityBrowserForm.
 */

namespace Drupal\entity_browser\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_browser\DisplayAjaxInterface;

/**
 * The entity browser form.
 */
class EntityBrowserForm extends EntityForm {

  /**
   * The entity browser object.
   *
   * @var \Drupal\entity_browser\EntityBrowserInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_browser_' . $this->entity->id() . '_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\entity_browser\EntityBrowserInterface $entity_browser */
    $entity_browser = $this->entity;

    $form['selected_entities'] = [
      '#type' => 'value',
      '#value' => array_map(function(EntityInterface $item) {return $item->id();}, $entity_browser->getSelectedEntities()),
    ];

    $form['#browser_parts'] = [
      'widget_selector' => 'widget_selector',
      'widget' => 'widget',
      'selection_display' => 'selection_display',
    ];
    $entity_browser->getWidgetSelector()->setDefaultWidget($this->getCurrentWidget($form_state));
    $form[$form['#browser_parts']['widget_selector']] = $entity_browser->getWidgetSelector()->getForm($form, $form_state);
    $form[$form['#browser_parts']['widget']] = $entity_browser->getWidgets()->get($this->getCurrentWidget($form_state))->getForm($form, $form_state, $entity_browser->getAdditionalWidgetParameters());

    $form['actions'] = [
      'submit' => [
        '#type' => 'submit',
        '#value' => t('Select'),
        '#attributes' => [
          'class' => ['is-entity-browser-submit'],
        ],
      ],
    ];

    $form[$form['#browser_parts']['selection_display']] = $entity_browser->getSelectionDisplay()->getForm($form, $form_state);

    if ($entity_browser->getDisplay() instanceOf DisplayAjaxInterface) {
      $entity_browser->getDisplay()->addAjax($form);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\entity_browser\EntityBrowserInterface $entity_browser */
    $entity_browser = $this->entity;
    $entity_browser->getWidgetSelector()->validate($form, $form_state);
    $entity_browser->getWidgets()->get($this->getCurrentWidget($form_state))->validate($form, $form_state);
    $entity_browser->getSelectionDisplay()->validate($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\entity_browser\EntityBrowserInterface $entity_browser */
    $entity_browser = $this->entity;
    $original_widget = $this->getCurrentWidget($form_state);
    if ($new_widget = $entity_browser->getWidgetSelector()->submit($form, $form_state)) {
      $this->setCurrentWidget($new_widget, $form_state);
    }

    // Only call widget submit if we didn't change the widget.
    if ($original_widget == $this->getCurrentWidget($form_state)) {
      $entity_browser->getWidgets()->get($this->getCurrentWidget($form_state))->submit($form[$form['#browser_parts']['widget']], $form, $form_state);
      $entity_browser->getSelectionDisplay()->submit($form, $form_state);
    }

    // Save the selected entities to the form state.
    $form_state->set('selected_entities', $entity_browser->getSelectedEntities());

    if (!$entity_browser->isSelectionCompleted()) {
      $form_state->setRebuild();
    }
    else {
      $entity_browser->getDisplay()->selectionCompleted($entity_browser->getSelectedEntities());
    }
  }

  /**
   * Returns the widget that is currently selected.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return string
   *   ID of currently selected widget.
   */
  protected function getCurrentWidget(FormStateInterface $form_state) {
    // Do not use has() as that returns TRUE if the value is NULL.
    if (!$form_state->get('entity_browser_current_widget')) {
      $form_state->set('entity_browser_current_widget', $this->entity->getFirstWidget());
    }

    return $form_state->get('entity_browser_current_widget');
  }

  /**
   * Sets widget that is currently active.
   *
   * @param string $widget
   *   New active widget UUID.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  protected function setCurrentWidget($widget, FormStateInterface $form_state) {
    $form_state->set('entity_browser_current_widget', $widget);
  }

}
