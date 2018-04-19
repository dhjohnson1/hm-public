<?php
/**
 * Created by PhpStorm.
 * User: t2
 * Date: 3/28/18
 * Time: 3:39 PM
 */

namespace Drupal\thm_user_maker_matcher\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface as FSI;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface as WSI;
use Drupal\thm_user_maker_matcher\Helper\MakerMatcher;

/**
 * Form submission handler.
 *
 * Takes submission values and performs a search, potentially matching against
 * Biography data.  Any hits are stored in a computed field.
 *
 * @WebformHandler(
 *   id = "thm_user_maker_matcher_handler",
 *   label = @Translation("MakerMatcher Handler"),
 *   category = @Translation("Search"),
 *   description = @Translation("Performs matchmaking operations"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
class MakerMatcherWebformHandler extends WebformHandlerBase {

  /**
   * Webform ID.
   *
   * @var string
   */
  protected const WEBFORM_ID = 'makermatcher_form';

  protected $webformSessionKeyBase = 'maker_matcher_uid_';

  protected $testMarkup = '<h2>testing</h2><ul><li>One</li><li>Two</li></ul>';

  /**
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *
   * @return array
   */
  protected function collectResults(FSI $formState) {
    $values = $formState->getValues();
    $output = [];

    foreach ($values as $key => $value) {
      if (!in_array($key, ['user', 'results', 'in_draft'])) {
        $output[$this->restructureKeys($key)] = $value;
      }
    }
    return $output;
  }

  protected function restructureKeys($key) {
    switch ($key) {
      case 'what_s_your_favorite_season_':
        return 'field_favorite_season';
      case 'what_s_your_favorite_color_':
        return 'field_favorite_color';
      case 'what_s_your_favorite_food_':
        return 'field_favorite_food';
      case 'what_year_were_you_born_':
        return 'field_birth_date';
    }
  }

  /**
   * @return int
   */
  protected function getCurrentUserId() {
    return \Drupal::currentUser()->id();
  }

  /**
   * @return \Drupal\Core\Entity\EntityInterface[]
   */
  protected function getSubmissionForCurrentUser() {
    return $this->submissionStorage->loadByProperties([
      'webform_id' => $this::WEBFORM_ID,
      'uid'        => $this->getCurrentUserId()
    ]);
  }

  /**
   * @return null|\Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected function getSession() {
    return \Drupal::request()->getSession();
  }

  protected function titleize(string $key) {
    return str_replace('_', ' ', substr($key, 6));
  }

  /**
   * @return string
   */
  protected function getSubmissionSessionKey() {
    return $this->webformSessionKeyBase . $this->getCurrentUserId();
  }

  /**
   * @param array $values
   */
  public function preCreate(array $values) {
    $webformSubmissions = $this->getSubmissionForCurrentUser();

    if (count($webformSubmissions) > 0) {
      $sessionKey = $this->getSubmissionSessionKey();

      /** @var WSI $webformSubmission */
      foreach ($webformSubmissions as $webformSubmission) {
        $data = $webformSubmission->getData();
      }
      if (isset($data)) $this->getSession()->set($sessionKey, $this->testMarkup);
    }
  }

  /**
   * @param \Drupal\webform\WebformSubmissionInterface $webformSubmission
   */
  public function preDelete(WSI $webformSubmission) {
    /** @var \Drupal\Core\Field\FieldItemListInterface $webformUid */
    $webformUid = array_column($webformSubmission->get('uid')->getValue(), 'target_id')[0];
    $this->getSession()->remove($this->webformSessionKeyBase . $webformUid);
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $formState
   * @param \Drupal\webform\WebformSubmissionInterface $webformSubmission
   */
  public function submitForm(array &$form, FSI $formState, WSI $webformSubmission) {
    $formValues = $this->collectResults($formState);
    $output = [];

    foreach ($formValues as $key => $value) {
      $matcher = new MakerMatcher();
      $data    = $matcher->executeSearch($value, $key);
      //$results = $matcher->prepareResults($data, $key);
      $msg = ' makers also find ' . $value . ' as their ' . $this->titleize($key);
      $output[] = $data->getResultCount() . $msg;
    }

    $webformSubmission->setElementData('matches', [
      '#markup' => $this->testMarkup
    ]);
  }
}