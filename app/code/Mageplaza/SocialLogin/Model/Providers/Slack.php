<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | https://github.com/hybridauth/hybridauth
*  (c) 2009-2011 HybridAuth authors | hybridauth.sourceforge.net/licenses.html
*/

namespace Mageplaza\SocialLogin\Model\Providers;

use Exception;
use Hybrid_Provider_Model_OAuth2;
use Hybrid_User_Profile;
use stdClass;

/**
 * Hybrid_Providers_Slack
 */
class Slack extends Hybrid_Provider_Model_OAuth2
{
    // default permissions
    // (no scope) => public read-only access (includes public user profile info, public repo info, and gists).
    public $scope = "identity.basic identity.email identity.avatar";

    /**
     * IDp wrappers initializer
     *
     * @throws Exception
     */
    function initialize()
    {
        parent::initialize();

        // Provider api end-points
        $this->api->api_base_url  = "https://slack.com/api/";
        $this->api->authorize_url = "https://slack.com/oauth/authorize";
        $this->api->token_url     = "https://slack.com/api/oauth.access";
        $this->api->sign_token_name = "token";
    }

    /**
     * load the user profile from the IDp api client
     *
     * @return Hybrid_User_Profile
     * @throws Exception
     */
    function getUserProfile()
    {
        // refresh tokens if needed
        $this->refreshToken();

        try {
            $authTest = $this->api->api("auth.test");
            $data = $this->api->get("users.info", [ "user" => $authTest->user_id ]);
        } catch (SlackException $e) {
            throw new Exception("User profile request failed! {$this->providerId} returned an error: $e", 6);
        }


        if ($this->api->http_code != 200) {
            throw new Exception("User profile request failed! {$this->providerId} returned an error. " . $this->errorMessageByStatus($this->api->http_code), 6);
        }

        $this->user->profile->identifier = $data->user->id;
        $this->user->profile->displayName = $data->user->profile->real_name;
        $this->user->profile->photoURL = $data->user->profile->image_original;
        $this->user->profile->email = $data->user->profile->email;


        if (empty($this->user->profile->displayName)) {
            $this->user->profile->displayName = $data->user->profile->first_name." ".$data->user->profile->last_name;
        }

        return $this->user->profile;
    }
}
