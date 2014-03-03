<?php

use Setsuna\Core\Controller;

class indexController extends Controller
{
	public function indexAction() {
		$client = $this->container['github'];
		//$repositories = $client->api('user')->show('foursking');
		$user = $client->api('user')->show('KnpLabs');
		$repos = $client->api('repo')->find('php framework', array('language' => 'php'));
		print_r($repos);

	}


	public function shortAction() {



}






}
