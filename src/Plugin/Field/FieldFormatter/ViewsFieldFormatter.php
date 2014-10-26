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
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = array();

    $views =  \Drupal\views\Views::getAllViews();
    $options1 = array();
    foreach ($views as $view) {
      foreach ($view->get('display') as $display) {
        $options1[$view->get('label')][$view->id . '::' . $display['id']] = $display['display_title'];
      }
    }

    if (!empty($options1)) {
      $element['view'] = array(
        '#title' => t('View'),
        '#description' => t('Select the view (<em><a href="@add_view_url">or create a new one</a></em>) that will be used to get the value of the field. Only views with tag <em>views_field_formatter</em> will be visible.', array('@add_view_url' => \Drupal\Core\Url::fromRoute('views_ui.add'))),
        '#type' => 'select',
        '#default_value' => $this->getSetting('view'),
        '#options' => $options1,
      );
    } else {
      $element['help'] = array(
        '#markup' => t('<p>No available Views were found. <a href="@add_view_url">Create</a> or <a href="@enable_views_url">enable</a> a views with tag <em>views_field_formatter</em>.</p>', array('@add_view_url' => \Drupal\Core\Url::fromRoute('views_ui.add'), '@enable_views_url' => \Drupal\Core\Url::fromRoute('views_ui.list')))
      );
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $view = $this->getSetting('view');
    $summary = array();
    list($view, $view_display) = explode('::', $view);

    if (isset($view)) {
      $summary[] = t('View: @view', array('@view' => $view));
      $summary[] = t('Display: @display', array('@display' => $view_display));
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();
    list($view, $view_display) = explode('::', $this->getSetting('view'));

    $id = $items->getParent()->getValue()->id();

    foreach ($items as $delta => $item) {
      // Passing the ID to the view wont work until: [#2208811] is fixed.
      $elements[$delta] = views_embed_view($view, $view_display, $id);
    }

    return $elements;
  }
}
