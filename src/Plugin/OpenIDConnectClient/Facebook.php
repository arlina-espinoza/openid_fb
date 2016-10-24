<?php

namespace Drupal\openid_fb\Plugin\OpenIDConnectClient;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use Drupal\openid_connect\Plugin\OpenIDConnectClientBase;

/**
 * OpenID Connect client for Facebook.
 *
 * Implements OpenID Connect Client plugin for Facebook.
 *
 * @OpenIDConnectClient(
 *   id = "facebook",
 *   label = @Translation("Facebook")
 * )
 */
class Facebook extends OpenIDConnectClientBase {

  /**
   * {@inheritdoc}
   */
  public function getEndpoints() {
    return array(
      'authorization' => 'https://www.facebook.com/v2.8/dialog/oauth',
      'token' => 'https://graph.facebook.com/v2.8/oauth/access_token',
      'userinfo' => 'https://graph.facebook.com/me',
    );
  }

  /**
   * Implements OpenIDConnectClientInterface::authorize().
   *
   * @param string $scope
   *   A string of scopes.
   *
   * @return \Drupal\Core\Routing\TrustedRedirectResponse
   *   A trusted redirect response object.
   */
  public function authorize($scope = 'openid email') {
    $scope = $this->alterScopes($scope);

    return parent::authorize($scope);
  }

  /**
   * Implements OpenIDConnectClientInterface::retrieveUserInfo().
   *
   * @param string $access_token
   *   An access token string.
   *
   * @return array|bool
   *   A result array or false.
   */
  public function retrieveUserInfo($access_token) {
    $scopes = \Drupal::service('openid_connect.claims')->getScopes();
    $fields = $this->convertScopeToFields($scopes);

    $request_options = array(
      'headers' => array(
        'Authorization' => 'Bearer ' . $access_token,
      ),
      'query' => array(
        'fields' => $fields,
      ),
    );
    $endpoints = $this->getEndpoints();

    $client = $this->httpClient;
    try {
      $response = $client->get($endpoints['userinfo'], $request_options);
      $response_data = (string) $response->getBody();

      $user_info = json_decode($response_data, TRUE);

      if ($user_info['first_name']) {
        $user_info['given_name'] = $user_info['first_name'];
      }
      if ($user_info['last_name']) {
        $user_info['family_name'] = $user_info['last_name'];
      }
      if (empty($user_info['preferred_username'])) {
        $user_info['preferred_username'] = $user_info['given_name'] . ' ' . $user_info['family_name'];
      }

      return $user_info;
    }
    catch (Exception $e) {
      $variables = array(
        '@message' => 'Could not retrieve user profile information',
        '@error_message' => $e->getMessage(),
      );
      $this->loggerFactory->get('openid_connect_' . $this->pluginId)
        ->error('@message. Details: @error_message', $variables);
      return FALSE;
    }
  }

  /**
   * Implements OpenIDConnectClientInterface::decodeIdToken().
   */
  public function decodeIdToken($id_token) {
    $info = array();

    // Parse unique identifier into open-id friendly format.
    if (isset($id_token['data']['user_id'])) {
      $info['sub'] = $id_token['data']['user_id'];
    }

    return $info;
  }

  /**
   * Implements OpenIDConnectClientInterface::retrieveIDToken().
   *
   * @param string $authorization_code
   *   A authorization code string.
   *
   * @return array|bool
   *   A result array or false.
   */
  public function retrieveTokens($authorization_code) {
    // Exchange `code` for access token and ID token.
    $language_none = \Drupal::languageManager()
      ->getLanguage(LanguageInterface::LANGCODE_NOT_APPLICABLE);
    $redirect_uri = Url::fromRoute(
      'openid_connect.redirect_controller_redirect',
      array(
        'client_name' => $this->pluginId,
      ),
      array(
        'absolute' => TRUE,
        'language' => $language_none,
      )
    )->toString();
    $endpoints = $this->getEndpoints();

    $request_options = array(
      'form_params' => array(
        'code' => $authorization_code,
        'client_id' => $this->configuration['client_id'],
        'client_secret' => $this->configuration['client_secret'],
        'redirect_uri' => $redirect_uri,
        'grant_type' => 'authorization_code',
      ),
    );

    /* @var \GuzzleHttp\ClientInterface $client */
    $client = $this->httpClient;
    try {
      $response = $client->post($endpoints['token'], $request_options);
      $response_data = json_decode((string) $response->getBody(), TRUE);

      // Expected result.
      $tokens = array(
//        'id_token' => $response_data['id_token'],
        'access_token' => $response_data['access_token'],
      );
      if (array_key_exists('expires_in', $response_data)) {
        $tokens['expire'] = REQUEST_TIME + $response_data['expires_in'];
      }

      $request_options = array(
        'query' => array(
          'input_token' => $tokens['access_token'],
        ),
        'headers' => array(
          'Authorization' => 'Bearer ' . $tokens['access_token'],
        ),
      );
      $response = $client->get('https://graph.facebook.com/debug_token', $request_options);
      $tokens['id_token'] = json_decode((string) $response->getBody(), TRUE);

      return $tokens;
    }
    catch (Exception $e) {
      $variables = array(
        '@message' => 'Could not retrieve tokens',
        '@error_message' => $e->getMessage(),
      );
      $this->loggerFactory->get('openid_connect_' . $this->pluginId)
        ->error('@message. Details: @error_message', $variables);
      return FALSE;
    }
  }

  protected function alterScopes($scope = '') {
    $scopes = explode(' ', $scope);
    $scopes = array_combine($scopes, $scopes);

    if (isset($scopes['profile'])) {
      $scopes['profile'] = 'public_profile';
    }
    unset($scopes['openid']);
    return implode(' ', $scopes);
  }

  protected function convertScopeToFields($scope = '') {
    $scopes = explode(' ', $scope);
    $scopes = array_combine($scopes, $scopes);

    if (isset($scopes['profile'])) {
      $scopes['first_name'] = 'first_name';
      $scopes['last_name'] = 'last_name';
      unset($scopes['profile']);
    }
    unset($scopes['openid']);
    return implode(',', $scopes);
  }

}
