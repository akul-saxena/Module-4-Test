<?php

namespace Drupal\buy_product\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class AddressForm to get user Address.
 */
class AddressForm extends FormBase {

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * AddressForm constructor.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(MessengerInterface $messenger, Connection $database) {
    $this->messenger = $messenger;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'address_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $node = NULL) {
    $current_user = \Drupal::currentUser();
    $uid = $current_user->id();

    // Check if the user already has an address in the custom table.
    $query = $this->database->select('customer_address', 'ca')
      ->fields('ca', ['address'])
      ->condition('ca.uid', $uid)
      ->execute()
      ->fetchField();

    if ($query) {
      $this->messenger->addMessage($this->t('Address already exists, Order Successful'));
      return $this->redirectAfterPurchase($node);
    }

    $form['address'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Address'),
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit and Buy'),
    ];

    $form_state->setTemporaryValue('node', $node);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $current_user = \Drupal::currentUser();
    $uid = $current_user->id();
    $address = $form_state->getValue('address');
    $node = $form_state->getTemporaryValue('node');

    // Insert the address into the custom table.
    $this->database->merge('customer_address')
      ->key('uid', $uid)
      ->fields(['address' => $address])
      ->execute();

    // Show a status message.
    $this->messenger->addStatus($this->t('Product ordered successfully!'));

    // Redirect back to the product page.
    return $this->redirectAfterPurchase($node);
  }

  /**
   * Redirects the user back to the product page.
   */
  protected function redirectAfterPurchase($node) {
    $url = Url::fromRoute('entity.node.canonical', ['node' => $node->id()]);
    $response = new RedirectResponse($url->toString());
    return $response;
  }

}
