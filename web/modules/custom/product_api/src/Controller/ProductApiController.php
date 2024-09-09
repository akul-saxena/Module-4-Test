<?php

namespace Drupal\product_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class ProductApiController.
 *
 * Provides APIs for retrieving product-related information.
 */
class ProductApiController extends ControllerBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a ProductApiController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Creates an instance of ProductApiController.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   *
   * @return static
   *   Returns an instance of the controller class.
   */
  public static function create($container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Fetches a list of published products.
   *
   * Retrieves nodes of type 'product' that are published and returns them
   * sorted by creation date in descending order.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing product details.
   */
  public function getProducts() {
    try {
      $query = $this->entityTypeManager->getStorage('node')->getQuery()
        ->condition('status', 1)
        ->condition('type', 'product')
        ->sort('created', 'DESC')
        ->accessCheck(FALSE);

      $nids = $query->execute();

      $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);
      $products = [];

      foreach ($nodes as $node) {
        if ($node instanceof Node) {
          $products[] = [
            'title' => $node->getTitle(),
            'description' => $node->get('field_description')->value,
            'price' => $node->get('field_price')->value,
            'images' => $this->getProductImages($node),
          ];
        }
      }

      return new JsonResponse($products);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Retrieves image URLs associated with a product node.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The product node from which to fetch images.
   *
   * @return array
   *   An array of image URLs.
   */
  protected function getProductImages(Node $node) {
    $image_urls = [];
    $images = $node->get('field_images')->referencedEntities();

    foreach ($images as $image) {
      if ($image instanceof File) {
        $file_uri = $image->getFileUri();
        $image_urls[] = \Drupal::service('file_url_generator')->generateAbsoluteString($file_uri);
      }
    }

    return $image_urls;
  }

}
