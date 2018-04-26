<?php

namespace Drupal\block_field_extra\Plugin\views\area;

use Drupal\views\Plugin\views\area\AreaPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;

/**
 * Views area block_field_attachments handler.
 *
 * @ingroup views_area_handlers
 *
 * @ViewsArea("block_field_attachments")
 */
class BlockFieldAttachments extends AreaPluginBase {

  /**
   * The entity object
   *
   * @var object
   */
  public $entity = NULL;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['title'] = ['default' => ''];
    $options['label_from_argument'] = ['default' => []];
    $options['link_label'] = ['default' => 'See all'];
    $options['link_label_html_tag'] = ['default' => 'h2'];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {
    return isset($this->view->block_field_attachments) ? $this->view->block_field_attachments : [];
  }

}
