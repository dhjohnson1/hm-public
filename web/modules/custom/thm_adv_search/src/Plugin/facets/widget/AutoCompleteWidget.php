<?php
/**
 * Created by PhpStorm.
 * User: t2
 * Date: 5/15/18
 * Time: 4:22 PM
 */

namespace Drupal\thm_adv_search\Plugin\facets\widget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\facets\FacetInterface;
use Drupal\facets\Result\Result;
use Drupal\facets\Widget\WidgetPluginBase;
use Drupal\search_api\Query\ResultSetInterface;
use Symfony\Component\HttpFoundation\JsonResponse;


/**
 * Class AutoComplete
 *
 * @package Drupal\thm_adv_search\Plugin\facets\widget
 *
 * @FacetsWidget(
 *   id = "autocomplete_widget",
 *   label = @Translation("AutoCompleteWidget"),
 *   description = @Translation("An autocomplete widget.")
 * )
 */
class AutoCompleteWidget extends WidgetPluginBase {
  public function defaultConfiguration() {
    return parent::defaultConfiguration(); // TODO: Change the autogenerated stub
  }

  protected function buildResultsJson($results, FacetInterface $facet) {
    $output = [];
    foreach ($results as $result) {
      $output[] = [
        'name' => $result->getDisplayValue(),
        'value' => $facet->id() . '%3A' . $result->getRawValue()
      ];
    }
    return $output;
  }

  public function build(FacetInterface $facet) {
    $jsonData = $this->buildResultsJson($facet->getResults(), $facet);
    $build['#type'] = 'textfield';
    $build['#attached']['library'][] = 'thm_adv_search/chosen-facets-collection';
    $build['#attributes']['class'][] = 'js-facets-autocomplete';
    $build['#attached']['drupalSettings']['facets']['id'] = $facet->id();
    $build['#attached']['drupalSettings']['facets']['autocomplete_widget'][$facet->id()]['facet-data'] = $jsonData;
    return $build;
  }

  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet) {
    return parent::buildConfigurationForm($form, $form_state, $facet); // TODO: Change the autogenerated stub
  }

  public function getQueryType() {
    return parent::getQueryType(); // TODO: Change the autogenerated stub
  }

  public function isPropertyRequired($name, $type) {
    return parent::isPropertyRequired($name, $type); // TODO: Change the autogenerated stub
  }
}