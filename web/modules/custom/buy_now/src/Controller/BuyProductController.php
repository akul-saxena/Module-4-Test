<?php

namespace Drupal\buy_now\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\file\Entity\File;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Thank You page after a product purchase.
 */
class BuyProductController extends ControllerBase {

  /**
   * The file URL generator service.
   *
   * @var \Drupal\Core\Url
   */
  protected $fileUrlGenerator;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a BuyProductController object.
   *
   * @param \Drupal\Core\Url $file_url_generator
   *   The file URL generator service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct($file_url_generator, EntityTypeManagerInterface $entity_type_manager) {
    $this->fileUrlGenerator = $file_url_generator;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('file_url_generator'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Displays the Thank You page for a product purchase.
   *
   * @param int $node
   *   The node ID of the purchased product.
   *
   * @return array
   *   A render array containing the Thank You page content.
   */
  public function thankYouPage($node) {
    $product = $this->entityTypeManager->getStorage('node')->load($node);
    $user = \Drupal::currentUser();
    $user_name = $user->getDisplayName();

    $image_urls = [];
    if ($product->hasField('field_images') && !$product->get('field_images')->isEmpty()) {
      foreach ($product->get('field_images') as $image_field) {
        $image_file = File::load($image_field->target_id);
        if ($image_file) {
          $image_urls[] = $this->fileUrlGenerator->generateAbsoluteString($image_file->getFileUri());
        }
      }
    }

    $output_items = [
      $this->t('Thank you @user for purchasing: @product', ['@user' => $user_name, '@product' => $product->label()]),
      $this->t('Quantity: 1'),
    ];

    if (!empty($image_urls)) {
      foreach ($image_urls as $image_url) {
        $output_items[] = $this->t('Product image: <img src="@image" alt="Product Image">', ['@image' => $image_url]);
      }
    }
    else {
      $output_items[] = $this->t('No images available.');
    }

    return [
      '#theme' => 'item_list',
      '#items' => $output_items,
      '#allowed_tags' => ['img'],
    ];
  }

}
