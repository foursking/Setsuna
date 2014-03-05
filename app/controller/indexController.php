<?php

use Setsuna\Core\Controller;
use Setsuna\Util\Curl;


class indexController extends Controller
{
	public function githubAction() {
		$repositories = $client->api('user')->show('foursking');
		#$$user = $client->api('user')->show('KnpLabs');
		$userAllRepos = $client->api('repo')->find('@foursking', array('language' => 'all'));
		$languageSum = array();
		foreach ($userAllRepos['repositories'] as $userRepos) {
			if(!empty($userRepos['language'])){
				if (isset($languageSum[$userRepos['language']])) {
					$languageSum[$userRepos['language']]++;
				} else {
					$languageSum[$userRepos['language']] = 1;
				}
			}
		}
		$languageTotal = array_sum($languageSum);

		foreach ($languageSum as &$language) {
			$language = ($language / $languageSum);
		}
	}

	public function indexAction() {
		$user = array();
		$url = "https://api.github.com/users/foursking";
		$curl = new Curl($url);
		$curlHander = $curl->curlInit();
		$response = $curl->curlGet($curlHander);
		$user = json_decode($response , true);
		var_dump($user);
		$user['avatar_url_large'] = "https://secure.gravatar.com/avatar/".$user['gravatar_id']."?size=170";
	}




}
