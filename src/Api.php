<?php

namespace DrupalCIResultsApi;

use GuzzleHttp\Client;

/**
 * Class ResultsAPI
 * @package DrupalCIResults
 */
class Api {

  /**
   * The username of the user with authorization to push to the results site.
   * @var string
   */
  protected $username = "";

  /**
   * The password of the user with authorization to push to the results site.
   * @var string
   */
  protected $password = "";

  /**
   * The URL of the results site.
   * @var string
   */
  protected $url = "";


  /**
   * Helper function to setup authentication.
   */
  public function setAuth($username, $password) {
    if (empty($username) || empty($password)) {
      throw new Exception('Please provide authentication credentials.');
    }
    $this->setUsername($username);
    $this->setPassword($password);
  }

  /**
   * Creates a build record.
   */
  public function create($title) {
    if (empty($title)) {
      throw new Exception('Please provide a title.');
    }
    $username = $this->getUsername();
    $password = $this->getPassword();
    $url = $this->getUrl();

    $new = $this->getStateId('new');
    $client = new Client(['base_url' => $url]);
    $response = $client->post('entity/node', [
      'headers' => [
        'Content-type' => 'application/hal+json',
      ],
      'body' => json_encode(array(
        '_links' => array(
          'type' => array(
            'href' => $url . 'rest/type/node/result',
          )
        ),
        'title' => array(0 => array('value' => $title)),
        'field_state' => array(0 => array('target_id' => $new)),
      )),
      'auth' => [$username, $password],
    ]);

    return $response->getHeader('Location');
  }

  /**
   * Updates the build in accordance with the workflow.
   */
  public function progress($build, $state) {
    if (empty($build)) {
      throw new Exception('Please provide a build.');
    }
    if (empty($state)) {
      throw new Exception('Please provide a state.');
    }
    $username = $this->getUsername();
    $password = $this->getPassword();
    $url = $this->getUrl();

    $client = new Client(['base_url' => $url]);
    $client->patch('node/' . $build, [
      'headers' => [
        'Content-type' => 'application/hal+json',
      ],
      'body' => json_encode(array(
        '_links' => array(
          'type' => array(
            'href' => $url . 'rest/type/node/result',
          )
        ),
        'field_state' => array(0 => array('target_id' => $state)),
      )),
      'auth' => [$username, $password],
    ]);
  }

  /**
   * Update buidl artefacts.
   * @param $build
   * @param $artefacts
   * @throws Exception
   */
  public function artefacts($build, $artefacts) {
    if (empty($build)) {
      throw new \Exception('Please provide a build.');
    }
    if (empty($artefacts)) {
      throw new \Exception('Please provide a state.');
    }
    $username = $this->getUsername();
    $password = $this->getPassword();
    $url = $this->getUrl();

    $client = new Client(['base_url' => $url]);
    $client->patch('node/' . $build, [
      'headers' => [
        'Content-type' => 'application/hal+json',
      ],
      'body' => json_encode(array(
        '_links' => array(
          'type' => array(
            'href' => $url . 'rest/type/node/result',
          )
        ),
        'field_artefacts' => $artefacts,
      )),
      'auth' => [$username, $password],
    ]);
  }

  /**
   * Updates the build with the following summary message.
   */
  public function summary($build, $summary) {
    if (empty($build)) {
      throw new Exception('Please provide a build.');
    }
    if (empty($summary)) {
      throw new Exception('Please provide a summary message.');
    }
    $username = $this->getUsername();
    $password = $this->getPassword();
    $url = $this->getUrl();

    $client = new Client(['base_url' => $url]);
    $client->patch('node/' . $build, [
      'headers' => [
        'Content-type' => 'application/hal+json',
      ],
      'body' => json_encode(array(
        '_links' => array(
          'type' => array(
            'href' => $url . 'rest/type/node/result',
          )
        ),
        'field_summary' => array(0 => array('value' => $summary)),
      )),
      'auth' => [$username, $password],
    ]);
  }

  /**
   * Updates the build with the following summary message.
   */
  public function tag($build, $tags) {
    if (empty($build)) {
      throw new Exception('Please provide a build.');
    }
    if (empty($tags)) {
      throw new Exception('Please provide at least one tag.');
    }
    $username = $this->getUsername();
    $password = $this->getPassword();
    $url = $this->getUrl();

    $client = new Client(['base_url' => $url]);
    $client->patch('node/' . $build, [
      'headers' => [
        'Content-type' => 'application/hal+json',
      ],
      'body' => json_encode(array(
        '_links' => array(
          'type' => array(
            'href' => $url . 'rest/type/node/result',
          )
        ),
        'field_tags' => $this->buildTags($tags),
      )),
      'auth' => [$username, $password],
    ]);
  }

  /**
   * Gets a list of states that the remote site can use for build progression.
   * @return array
   */
  public function states() {
    $url = $this->getUrl();
    $client = new Client(['base_url' => $url]);
    $response = $client->get('states', [
      'headers' => [
        'Accept'     => 'application/hal+json',
      ]
    ]);

    // Format the remote API into something useful.
    $states = $response->json();
    $return = array();
    foreach ($states as $state) {
      $return[$state['field_machine']] = array(
        'id' => $state['tid'],
        'name' => $state['name'],
        'percentage' => $state['field_percentage'] . '%',
      );
    }
    return $return;
  }

  /**
   * Builds the tag list for the JSON.
   */
  private function buildTags($tags) {
    $full_tags = $this->getTags();

    // Create any non-existent tags.
    $new_tags = array_diff($tags, array_keys($full_tags));
    if (!empty($new_tags)) {
      foreach ($new_tags as $tag) {
        $this->createTag($tag);
      }

      // Reload all tags.
      $full_tags = $this->getTags();
    }

    // Generates the JSON references.
    $list = array();
    foreach ($tags as $tag) {
      $list[] = array('target_id' => $full_tags[$tag]);
    }
    return $list;
  }

  /**
   * Helper function to get all the tags.
   */
  private function getTags() {
    $url = $this->getUrl();
    $client = new Client(['base_url' => $url]);
    $response = $client->get('tags', [
      'headers' => [
        'Accept'     => 'application/hal+json',
      ]
    ]);

    // Format the remote API into something useful.
    $tags = $response->json();
    $return = array();
    foreach ($tags as $tag) {
      $return[$tag['name']] = $tag['tid'];
    }
    return $return;
  }

  /**
   * Helper function to create a non-existent tag.
   */
  private function createTag($tag) {
    $url = $this->getUrl();
    $client = new Client(['base_url' => $url]);
    $response = $client->post('entity/taxonomy_term', [
      'headers' => [
        'Content-type' => 'application/hal+json',
      ],
      'body' => json_encode(array(
        '_links' => array(
          'type' => array(
            'href' => $url . 'rest/type/taxonomy_term/tags',
          )
        ),
        'name' => array(['value' => $tag]),
        'vid' => array(['value' => 'tags']),
      )),
      'auth' => [$this->getUsername(), $this->getPassword()],
    ]);
  }

  /**
   * Helper function to
   */
  private function getStateId($state) {
    $states = $this->states();
    if (empty($states[$state])) {
      throw new \Exception('Cannot find this states ID.');
    }
    return $states[$state]['id'];
  }

  /**
   * @return string
   */
  public function getUsername() {
    return $this->username;
  }

  /**
   * @param string $username
   */
  public function setUsername($username) {
    $this->username = $username;
  }

  /**
   * @return string
   */
  public function getPassword() {
    return $this->password;
  }

  /**
   * @param string $password
   */
  public function setPassword($password) {
    $this->password = $password;
  }

  /**
   * @return string
   */
  public function getUrl() {
    return $this->url;
  }

  /**
   * @param string $url
   */
  public function setUrl($url) {
    $this->url = rtrim($url, '/') . '/';
  }

}
