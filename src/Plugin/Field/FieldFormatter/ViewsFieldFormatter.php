<?php /**
 * @file
 * Contains \Drupal\views_field_formatter\Plugin\Field\FieldFormatter\ViewsFieldFormatter.
 */

namespace Drupal\views_field_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * @FieldFormatter(
 *  id = "views_field_formatter",
 *  label = @Translation("View"),
 *  field_types = {"comment", "datetime", "file", "image", "list_float", "list_integer", "list_string", "path", "taxonomy_term_reference", "text", "text_long", "text_with_summary", "boolean", "changed", "created", "decimal", "email", "entity_reference", "float", "integer", "language", "map", "string", "string_long", "timestamp", "uri", "uuid"}
 * )
 */
class ViewsFieldFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'view' => '',
      'multiple' => FALSE
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = array();

    $views =  \Drupal\views\Views::getAllViews();
    $options = array();
    foreach ($views as $view) {
      foreach ($view->get('display') as $display) {
        $options[$view->get('label')][$view->get('id') . '::' . $display['id']] = $display['display_title'];
      }
    }

    if (!empty($options)) {
      $element['view'] = array(
        '#title' => t('View'),
        '#description' => t('Select the view (<em><a href="@add_view_url">or create a new one</a></em>) that will be used to get the value of the field. Only views with tag <em>views_field_formatter</em> will be visible.', array('@add_view_url' => \Drupal\Core\Url::fromRoute('views_ui.add')->toString())),
        '#type' => 'select',
        '#default_value' => $this->getSetting('view'),
        '#options' => $options,
      );
      $element['multiple'] = array(
        '#title' => t('Multiple'),
        '#description' => t('If the field is configured as multiple, should we display a view per item ? If selected, there will be one view per item. The arguments passed to that view are in this order: the field item value, the entity id and the item delta.'),
        '#type' => 'checkbox',
        '#default_value' => boolval($this->getSetting('multiple')),
      );
    } else {
      $element['help'] = array(
        '#markup' => t('<p>No available Views were found. <a href="@add_view_url">Create</a> or <a href="@enable_views_url">enable</a> a views with tag <em>views_field_formatter</em>.</p>', array('@add_view_url' => \Drupal\Core\Url::fromRoute('views_ui.add')->toString(), '@enable_views_url' => \Drupal\Core\Url::fromRoute('views_ui.list')->toString()))
      );
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();
    $view = $this->getSetting('view');
    $multiple = $this->getSetting('multiple') ? 'Enabled' : 'Disabled';
    list($view, $view_display) = explode('::', $view);

    if (isset($view)) {
      $summary[] = t('View: @view', array('@view' => $view));
      $summary[] = t('Display: @display', array('@display' => $view_display));
      $summary[] = t('Multiple: @multiple', array('@multiple' => t($multiple)));
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();
    list($view, $view_display) = explode('::', $this->getSetting('view'));

    $columns = array_keys($items->getFieldDefinition()->getFieldStorageDefinition()->getSchema()['columns']);
    $column = array_shift($columns);

    $id = $items->getParent()->getValue()->id();
    $cardinality = $items->getFieldDefinition()->getFieldStorageDefinition()->getCardinality();

    if ($this->getSetting('multiple') && $cardinality != 1) {
      foreach ($items as $delta => $item) {
        $value = isset($item->getValue()[$column]) ? $item->getValue()[$column] : NULL;
        $elements[$delta] = views_embed_view($view, $view_display, $value, $id, $delta);
      }
    } else {
      $item = $items[0];
      $delta = 0;
      $value = isset($item->getValue()[$column]) ? $item->getValue()[$column] : NULL;
      $elements[$delta] = views_embed_view($view, $view_display, $value, $id, $delta);
    }

    return $elements;
  }
}
