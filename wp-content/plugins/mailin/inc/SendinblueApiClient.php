<?php


class SendinblueApiClient
{
    const API_BASE_URL = 'https://api.brevo.com/v3';
    const API_STAGING_BASE_URL = 'https://api-staging-rke.51b.dev/v3';
    const HTTP_METHOD_GET = 'GET';
    const HTTP_METHOD_POST = 'POST';
    const HTTP_METHOD_PUT = 'PUT';
    const HTTP_METHOD_DELETE = 'DELETE';
    const CAMPAIGN_TYPE_EMAIL = 'email';
    const CAMPAIGN_TYPE_SMS = 'sms';
    const RESPONSE_CODE_OK = 200;
    const RESPONSE_CODE_CREATED = 201;
    const RESPONSE_CODE_ACCEPTED = 202;
    const RESPONSE_CODE_NO_CONTENT = 204;
    const RESPONSE_CODE_UNAUTHORIZED = 401;
    const PLUGIN_VERSION = '3.3.0';
    const USER_AGENT = 'sendinblue_plugins/wordpress';

    private $apiKey;
    private $lastResponseCode;

    /**
     * SendinblueApiClient constructor.
     */
    public function __construct()
    {
        $this->apiKey = get_option(SIB_Manager::API_KEY_V3_OPTION_NAME);
    }

    /**
     * @return mixed
     */
    public function getAccount()
    {
        $sibAccObj = SendinblueAccount::getInstance();
        if($sibAccObj->getSendinblueAccountData())
        {
            $this->lastResponseCode = $sibAccObj->getLastResponseCode();
            return $sibAccObj->getSendinblueAccountData();
        }
        else
        {
            $accData = $this->get('/account');
            if ($this->getLastResponseCode() === self::RESPONSE_CODE_OK)
            {
                $sibAccObj->setSendinblueAccountData($accData);
                $sibAccObj->setLastResponseCode($this->lastResponseCode);
            }
            return $accData;
        }
    }

    /**
     * @return mixed
     */
    public function getAttributes()
    {
        return $this->get("/contacts/attributes");
    }

    /**
     * @param $type ,$name,$data
     * @return mixed
     */
    public function createAttribute($type, $name, $data)
    {
        return $this->post("/contacts/attributes/" . $type . "/" . $name, $data);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function getEmailTemplate($id)
    {
        return $this->get("/smtp/templates/" . $id);
    }

    /**
     * @param string $type
     * @param array $data
     * @return array
     */
    public function getAllCampaignsByType($type = self::CAMPAIGN_TYPE_EMAIL, $data = [])
    {
        $campaigns = [];

        if (!isset($data['offset'])) {
            $data['offset'] = 0;
        }

        do {
            if ($type === self::CAMPAIGN_TYPE_SMS) {
                $response = $this->getSmsCampaigns($data);
            } else {
                $response = $this->getEmailCampaigns($data);
            }

            if (isset($response['campaigns']) && is_array($response['campaigns'])) {
                $campaigns = array_merge($campaigns, $response['campaigns']);
                $data['offset']++;
            }
        } while (!empty($response['campaigns']));

        return $campaigns;
    }

    /**
     * @param $data
     * @return mixed
     */
    public function getEmailCampaigns($data)
    {
        return $this->get("/emailCampaigns", $data);
    }

    /**
     * @param $data
     * @return mixed
     */
    public function getSmsCampaigns($data)
    {
        return $this->get("/smsCampaigns", $data);
    }

    /**
     * @param $data
     * @return mixed
     */
    public function getEmailTemplates($data)
    {
        return $this->get("/smtp/templates", $data);
    }

    /**
     * @param $data
     * @return mixed
     */
    public function sendEmail($data)
    {
        return $this->post("/smtp/email", $data);
    }

    /**
     * @param $email
     * @return mixed
     */
    public function getUser($email)
    {
        return $this->get("/contacts/" . urlencode($email));
    }

    /**
     * @param $data
     * @return mixed
     */
    public function createUser($data)
    {
        return $this->post("/contacts", $data);
    }

    /**
     * @return mixed
     */
    public function getSenders()
    {
        return $this->get("/senders");
    }

    /**
     * @param $email ,$data
     * @return mixed
     */
    public function updateUser($email, $data)
    {
        return $this->put("/contacts/" . $email, $data);
    }

    /**
     * @param $data
     * @return mixed
     */
    public function createInstallationInfo($data)
    {
        return $this->post("/account/partner/information", $data);
    }

    /**
     * @param $installationId ,$data
     * @return mixed
     */
    public function updateInstallationInfo($installationId, $data)
    {
        return $this->put("/account/partner/information/" . $installationId, $data);
    }

    /**
     * @param $data
     * @return mixed
     */
    public function createList($data)
    {
        return $this->post("/contacts/lists", $data);
    }

    /**
     * @param $data
     * @return mixed
     */
    public function getLists($data)
    {
        return $this->get("/contacts/lists", $data);
    }

    /**
     * @param $data
     * @return mixed
     */
    public function getAllLists()
    {
        $lists = array("lists" => array(), "count" => 0);
        $offset = 0;
        $limit = 50;
        do {
            $list_data = $this->getLists(array('limit' => $limit, 'offset' => $offset));
            if (isset($list_data["lists"]) && is_array($list_data["lists"])) {
                $lists["lists"] = array_merge($lists["lists"], $list_data["lists"]);
                $offset += 50;
                $lists["count"] = $list_data["count"];
            }
        } while (!empty($lists['lists']) && count($lists["lists"]) < $list_data["count"]);

        return $lists;
    }


	/**
	 * @param $data
	 * @return mixed
	 */
	public function getSegments($data)
	{
		return $this->get("/contacts/segments", $data);
	}

	/**
	 * @param $data
	 * @return mixed
	 */
	public function getAllSegments()
	{
		$segments = array("segments" => array(), "count" => 0);
		$offset = 0;
		$limit = 50;
		do {
			$segment_data = $this->getSegments(array('limit' => $limit, 'offset' => $offset));
			if (isset($segment_data["segments"]) && is_array($segment_data["segments"])) {
				$segments["segments"] = array_merge($segments["segments"], $segment_data["segments"]);
				$offset += 50;
				$segments["count"] = $segment_data["count"];
			}
		} while (!empty($segments['segments']) && count($segments["segments"]) < $segment_data["count"]);

		return $segments;
	}

   /**
     * @param $data
     * @return mixed
     */
    public function createFolder($data)
    {
        return $this->post("/contacts/folders", $data);
    }

    /**
     * @param $data
     * @return mixed
     */
    public function getFolders($data)
    {
        return $this->get("/contacts/folders", $data);
    }

    /**
     * @param $data
     * @return mixed
     */
    public function getAllFolders()
    {
        $folders = array("folders" => array(), "count" => 0);
        $offset = 0;
        $limit = 50;
        do {
            $folder_data = $this->getFolders(array('limit' => $limit, 'offset' => $offset));
            if (isset($folder_data["folders"]) && is_array($folder_data["folders"])) {
                $folders["folders"] = array_merge($folders["folders"], $folder_data["folders"]);
                $offset += 50;
                $folders["count"] = $folder_data["count"];
            }
        } while (!empty($folders['folders']) && count($folders["folders"]) < $folder_data["count"]);

        return $folders;
    }

    /**
     * @param $data
     * @return mixed
     */
    public function importContacts($data)
    {
        return $this->post('/contacts/import', $data);
    }

    /**
     * @param $endpoint
     * @param array $parameters
     * @return mixed
     */
    public function get($endpoint, $parameters = [])
    {
        if ($parameters) {
            foreach ($parameters as $key => $parameter) {
                if (is_bool($parameter)) {
                    // http_build_query converts bool to int
                    $parameters[$key] = $parameter ? 'true' : 'false';
                }
            }
            $endpoint .= '?' . http_build_query($parameters);
        }
        return $this->makeHttpRequest(self::HTTP_METHOD_GET, $endpoint);
    }

    /**
     * @param $endpoint
     * @param array $data
     * @return mixed
     */
    public function post($endpoint, $data = [])
    {
        return $this->makeHttpRequest(self::HTTP_METHOD_POST, $endpoint, $data);
    }

    /**
     * @param $endpoint
     * @param array $data
     * @return mixed
     */
    public function put($endpoint, $data = [])
    {
        return $this->makeHttpRequest(self::HTTP_METHOD_PUT, $endpoint, $data);
    }

    /**
     * @param $method
     * @param $endpoint
     * @param array $body
     * @return mixed
     */
    private function makeHttpRequest($method, $endpoint, $body = [])
    {
        $url = ( SIB_Manager::is_staging() ? self::API_STAGING_BASE_URL : self::API_BASE_URL ) . $endpoint;

        $args = [
            'timeout' => 10000,
            'method' => $method,
            'headers' => [
                'api-key' => $this->apiKey,
                'sib-plugin' => 'wp-'.self::PLUGIN_VERSION,
                'Content-Type' => 'application/json',
                'User-Agent' => self::USER_AGENT
            ],
        ];

        if ($method != self::HTTP_METHOD_GET && $method != self::HTTP_METHOD_DELETE) {
            if (isset($body['listIds'])) {
                $body['listIds'] = $this->getListsIds($body['listIds']);
            }
            if (isset($body['unlinkListIds'])) {
                $body['unlinkListIds'] = $this->getListsIds($body['unlinkListIds']);
            }
            if(is_array($body)) {
                foreach($body as $key => $val) {
                    if(empty($val) && $val!==false && $val!==0) {
                        unset($body[$key]);
                    }
                }
            }
            $args['body'] = wp_json_encode($body);
        }

        $response = wp_remote_request($url, $args);
        $this->lastResponseCode = wp_remote_retrieve_response_code($response);

        if (is_wp_error($response)) {
            $data = [
                'code' => $response->get_error_code(),
                'message' => $response->get_error_message()
            ];
        } else {
            $data = json_decode(wp_remote_retrieve_body($response), true);
        }

        return $data;
    }

    private function getListsIds($listIds)
    {
        return array_unique(array_values(array_map('intval', (array)$listIds)));
    }

    /**
     * @return int
     */
    public function getLastResponseCode()
    {
        return $this->lastResponseCode;
    }

    /**
     * @param $data
     * @return mixed all template list
     */
    public function getAllEmailTemplates() {
        $templates = array(
            'templates' => array(),
            'count'     => 0,
        );
        $offset    = 0;
        $limit     = 50;
        do {
            $template_data = $this->getEmailTemplates(
                array(
                    'templateStatus' => true,
                    'limit'          => $limit,
                    'offset'         => $offset,
                )
            );
            if ( empty( $template_data ) ) {
                break;
            }
            $templates['templates'] = array_merge( $templates['templates'], $template_data['templates'] );
            $offset                += 50;
        } while ( ! empty( $templates['templates'] ) && count( $templates['templates'] ) < $template_data['count'] );
        $templates['count'] = count( $templates['templates'] );

        return $templates;
    }
}
