<?php

/**
 * Volby presenter.
 */

define('BR', "<br>");

class VolbyPresenter extends BasePresenter
{

	private $parameters;

	protected function startup() {
		parent::startup();
		$this->parameters = $this->getContext()->getParameters();
	}

	public function renderDefault($identifikatorVoleb = '')
	{
		if (empty($identifikatorVoleb)) {
			$identifikatorVoleb = $this->parameters['defaultVolby'];
		}
		$parameters = $this->parameters[$identifikatorVoleb];
		$this->template->data = $this->getService('database')->table($parameters['table']);
		$this->template->k1 = $parameters['k1'];
		$this->template->k2 = $parameters['k2'];
		$this->template->title = $parameters['title'];
		$this->template->subtitle = $parameters['subtitle'];

	}

	public function renderCron($identifikatorVoleb = '') {

		if (empty($identifikatorVoleb)) {
			$identifikatorVoleb = $this->parameters['defaultVolby'];
		}

		$parameters = $this->parameters[$identifikatorVoleb];

		switch($parameters['typ']) {
			case 'prezident':
			$status = $this->prezident($parameters);
			break;
		}

		$this->template->status = $status;
		$this->template->title = $parameters['title'];
		$this->template->subtitle = $parameters['subtitle'];

	}

	public function renderTweettest()
	{
		$tweet = 'Testovaci tweet - vzor: secteno 6.70% - MZ 51.00% / KS 49.00% (rozdil 2.00%) - ucast 53.00% #volebniVysledky #volby2013 #prezident2013 #volby';
		$this->publikujTweet($tweet);
	}

	private function publikujTweet($tweet) {

		$consumerKey = $this->parameters['twitter']['CONSUMER_KEY'];
		$consumerSecret = $this->parameters['twitter']['CONSUMER_SECRET'];
		$accessToken = $this->parameters['twitter']['ACCOUNT_KEY'];
		$accessTokenSecret = $this->parameters['twitter']['ACCOUNT_SECRET'];

		$twitter = new Twitter($consumerKey, $consumerSecret, $accessToken, $accessTokenSecret);
		//$twitter->send($tweet);
	}

	private function prezident($parameters) {

		$dnesTed = new DateTime();

		// je spravne datum?
		$startDen = new DateTime($parameters['startDen']);
		if ($dnesTed->format("Y-m-d") <> $startDen->format("Y-m-d")) {
			return 'nenastal den scitani voleb, tj. '.$parameters['startDen'].'...';
		}

		// je spravny cas?
		$startCas = $parameters['startCas'];
		if ($dnesTed->format("H:i") < $startCas) {
			return 'nenastal cas publikovani vysledku voleb...';
		}


		$volby = $this->getService('database')->table($parameters['table']);;
		$hledamPosledniTweet = clone $volby;
		$posledniTweet = $hledamPosledniTweet->where ('tweet', '1')->order('datumcas DESC')->fetch();

		if (!empty($posledniTweet) && intval($posledniTweet->zpracovano) == 100) {
			return 'volby skoncili...';
		}

		// na locale
		$html = file_get_contents($parameters['volbyUrl']);
		$html = str_get_html($html);

		// na ostre tohle
		//$html = file_get_html($parameters['volbyUrl']);

		$insertData = array();

		$ucast = $html->find($parameters['tagUcast'], 0);
		//echo "ucast: ". $ucast . '<br>';
		$insertData['ucast'] = str_replace(",", '.', $ucast->plaintext);

		$zpracovano = $html->find($parameters['tagZpracovano'], 0);
		//echo "zpracovano: ".$zpracovano . '<br>';
		$insertData['zpracovano'] = str_replace(",", '.', $zpracovano->plaintext);



		foreach($html->find($parameters['tagOsoby']) as $td) {

			$procento = '';

			if ($td->headers == $parameters['attrJmenoOsoby']) {
				//echo "jmeno ".$td->plaintext . '<br>';
			}


			//echo $parameters['attrProcentaOsoby'];

			if ($td->headers == $parameters['attrProcentaOsoby']) {
				//echo "procenta ".$td->plaintext . '<br>';
				$procento = $td->plaintext;
			}


			if ($procento != '') {
				$procento = str_replace(",", '.', $procento);
				switch(true) {
					case !isset($insertData['kandidat1']):
					$insertData['kandidat1'] = $procento;
					break;
					case !isset($insertData['kandidat']) :
					$insertData['kandidat2'] = $procento;
					break;
					default:
					// nic
					break;
				}
			}
		}

		if ($insertData['kandidat2'] == $insertData['kandidat1']) {
			$poradi = sprintf(" %s i %s mají shodně %s ", $parameters['k1'], $parameters['k2'], $insertData['kandidat1']."%");
		} elseif ($insertData['kandidat2'] > $insertData['kandidat1']) {
			$rozdil = $insertData['kandidat2'] - $insertData['kandidat1'];
			$poradi = sprintf(" %s %s / %s %s (rozdil %s)", $parameters['k2'], $insertData['kandidat2']."%", $parameters['k1'], $insertData['kandidat1']."%", $rozdil."%");
		} else {
			$rozdil = $insertData['kandidat1'] - $insertData['kandidat2'];
			$poradi = sprintf(" %s %s / %s %s (rozdil %s)", $parameters['k1'], $insertData['kandidat1']."%", $parameters['k2'], $insertData['kandidat2']."%", $rozdil."%");
		}

		$tweet = sprintf("secteno %s - %s - ucast %s #volebniVysledky #volby2013 #prezident2013 #volby", $insertData['zpracovano']."%", $poradi, $insertData['ucast']."%");
		//$tweet = sprintf("secteno %s - %s - ucast %s #test bude smazano", $insertData['zpracovano']."%", $poradi, $insertData['ucast']."%s");

		//var_dump($insertData);

		$insertData['datumcas'] =  new Nette\Database\SqlLiteral('NOW()');
		$insertData['tweet'] = '0';

		// pravidla
		// - start po prekroceni 1 procenta
		// a pak kazdych X procent dle nastaveni / vhodne po 5 procentech spocitanych hlasu

		$status = $tweet;

		if($posledniTweet == false) {
			// nebyl publikovan zadny tweet
			if (floatval($insertData['zpracovano']) >= 1) {
				$this->publikujTweet($tweet);
				$insertData['tweet'] = '1';
			} else {
				$status = 'stale cekame zpracovani prvniho procenta volebnich dat...';
			}
		} else {
			//dump($posledniTweet->zpracovano);

			$posledniHodnota = $posledniTweet->zpracovano;
			$musiPresahnout = floor($posledniHodnota / $parameters['publikovat']) * $parameters['publikovat'] + $parameters['publikovat'];
			if (floatval($insertData['zpracovano']) >= $musiPresahnout) {
				$this->publikujTweet($tweet);
				$insertData['tweet'] = '1';
			} else {
				$status = "neprekrocili jsme ".$musiPresahnout."% zpracovanych volebnich dat...";
			}
		}

		$volby->insert($insertData);

		if ($insertData['tweet'] != '1') {
			$tweet = '';
		}

		return $status;
	}

}
