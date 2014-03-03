<?php

use Setsuna\Core\Controller;

class indexController extends Controller
{
	public function indexAction() {
		$client = $this->container['github'];
		//$repositories = $client->api('user')->show('foursking');
		$user = $client->api('user')->show('KnpLabs');
		var_dump($user);
		exit;
		$userAllRepos = $client->api('repo')->find('@foursking', array('language' => 'all'));

		var_dump($userAllRepos);

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


		/* $languageTotal = array_sum($languageSum); */

		/* foreach ($languageSum as &$language) { */
		/* 	$language = ($language / $languageSum); */
		/* } */



	}


	public function shortAction() {



	}






}
