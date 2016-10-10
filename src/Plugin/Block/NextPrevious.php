<?php
/**
 * @file
 * Contains \Drupal\nextprevious\Plugin\Block\NextPreviousBlock.
 */
namespace Drupal\nextprevious\Plugin\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\node\Entity\NodeType;
use Drupal\field\FieldConfigInterface;

/**
 * Provides a 'Next Previous' block.
 *
 * @Block(
 *   id = "next_previous_links",
 *   admin_label = @Translation("Next Previous link Block"),
 *   category = @Translation("Blocks")
 * )
 */
class NextPrevious extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {
    //Get the created time of the current node
    if($node = \Drupal::request()->attributes->get('node')){
      $created_time = $node->getCreatedTime();
      $node_type = $node->getType();
      $link = "";
      $link .= $this->generatePrevious($created_time, $node_type);
      $link .= $this->generateNext($created_time, $node_type);
      return array(
        '#markup' => $link,
        '#theme' => 'nextprevious_standard',
        '#cache' => array(
          'contexts' => array('url')
        )
      );
    }
    else {
      return array('#markup' => 'Something went wrong');
    }
  }
  /**
   * Lookup the previous node, i.e. youngest node which is still older than the node
   * currently being viewed.
   *
   * @param  string $created_time A unix time stamp
   * @param  array  $node_type
   * @return string               an html link to the previous node
   */
  private function generatePrevious($created_time, $node_type) {
    return $this->generateNextPrevious('prev', $created_time, $node_type);
  }
  /**
   * Lookup the next node, i.e. oldest node which is still younger than the node
   * currently being viewed.
   *
   * @param  string $created_time A unix time stamp
   * @param  array  $node_type
   * @return string               an html link to the next node
   */
  private function generateNext($created_time, $node_type) {
    return $this->generateNextPrevious('next', $created_time, $node_type);
  }
  /**
   * Lookup the next or previous node
   *
   * @param  string $direction    either 'next' or 'previous'
   * @param  string $created_time a Unix time stamp
   * @param  array  $node_type
   * @return string               an html link to the next or previous node
   */
  private function generateNextPrevious($direction = 'next', $created_time, $node_type) {
    global $base_url;
    $module_path = drupal_get_path('module', 'nextprevious');
    if ($direction === 'next') {
      $comparison_opperator = '>';
      $sort = 'ASC';
      $display_text = t('
      <div class="nextprevious next arrow">
        <img src="' . $base_url . '/' . $module_path .'/images/right-arrow.svg">
      </div>
      ');
    }
    elseif ($direction === 'prev') {
      $comparison_opperator = '<';
      $sort = 'DESC';
      $display_text = t('
      <div class="nextprevious previous arrow">
        <img src="' . $base_url . '/' . $module_path .'/images/left-arrow.svg">
      </div>
      ');
    }
    //Lookup 1 node younger (or older) than the current node
    $query = \Drupal::entityQuery('node');
    $next = $query->condition('created', $created_time, $comparison_opperator)
      ->condition('type', $node_type)
      ->sort('created', $sort)
      ->range(0, 1)
      ->execute();
    //If this is not the youngest (or oldest) node
    if (!empty($next) && is_array($next)) {

      $next = array_values($next);
      $next = $next[0];
      //Find the alias of the next node
      $next_url = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $next);
      //Build the URL of the next node
      $next_url = Url::fromUri($base_url . $next_url);
      //Build the HTML for the next node
      return \Drupal::l(
        $display_text,
        $next_url

      );
    }
  }

}