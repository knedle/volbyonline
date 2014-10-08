<?php

/**
 * Volby presenter.
 */

use Nette\Utils\Strings;

define('BR', "<br>");

class VolbyPresenter extends BasePresenter
{

	private $parameters;

	protected function startup() {
		parent::startup();
		$this->parameters = $this->getContext()->getParameters();
		// dump($this->parameters);
	}

	public function renderDefault($identifikatorVoleb = '')	
	{
		if (empty($identifikatorVoleb)) {
			$identifikatorVoleb = $this->parameters['defaultVolby'];
		}
		$parameters = $this->parameters[$identifikatorVoleb];

		// vybrat spravnou sablonu
		$this->setView($parameters['typ']);

		if ($parameters['typ'] <> 'multi') {

			switch ($parameters['typ']) {
				case 'prezident2kolo':
				$data = $this->getService('database')->table($parameters['table'])->order('datumcas DESC');
				$this->template->k1 = $parameters['k1'];
				$this->template->k2 = $parameters['k2'];
				break;
				case 'komunalni':
				case 'parlament':
				$data = $this->getService('database')->table($parameters['table'])->order('datumcas DESC');
				// nacist nazvy stran
				$columns = $this->getService('database')->getSupplementalDriver()->getColumns($parameters['table']);
				foreach($columns as $column) {
					if (preg_match('/strana([0-9]+)/i', $column['name'], $match)) {
						// omezeni na ty co chci zobrazovat
						if (isset($parameters['zobrazit'][$match[1]])) {
							$strany[$match[1]] = $column['vendor']['Comment'];
						}
					}
				}
				$this->template->strany = $strany;
				break;		
				case 'senatni':				
				$data = $this->getService('database')->table($parameters['table'])->order('datumcas DESC');
				$this->template->strany = $strany;
				break;		
			}

			$this->template->data = $data;

		} else {
			$this->template->multiCasti = $this->parameters[$identifikatorVoleb]['multi'];
		}

		$this->template->title = $parameters['title'];
		$this->template->subtitle = $parameters['subtitle'];

	}

	// http://localhost/volbyonline/volby/cron
	public function renderCron($identifikatorVoleb = '') {

		if (empty($identifikatorVoleb)) {
			$identifikatorVoleb = $this->parameters['defaultVolby'];
		}

		$parameters = $this->parameters[$identifikatorVoleb];

		switch($parameters['typ']) {
			case 'prezident2kolo':
			$status = $this->prezident2kolo($parameters);
			break;
			case 'parlament':
			$status = $this->parlament($parameters);
			break;			
			case 'multi':
			$status = $this->multi($parameters);
			break;					
		}

		// zalogovat ze bl spusten cron
		$file = dirname(__FILE__).'/../../log/cron.log';
		$person = date('c') . " | " . $status ."\n";
		file_put_contents($file, $person, FILE_APPEND | LOCK_EX);		

		$this->template->status = $status;
		$this->template->title = $parameters['title'];
		$this->template->subtitle = $parameters['subtitle'];

	}

	public function renderTweettest()
	{
		$tweet = 'Testovaci tweet...';
		$this->publikujTweet($tweet);
	}

	private function publikujTweet($tweet) {

		$tweet = substr($tweet, 0, 140);

		$consumerKey = $this->parameters['twitter']['CONSUMER_KEY'];
		$consumerSecret = $this->parameters['twitter']['CONSUMER_SECRET'];
		$accessToken = $this->parameters['twitter']['ACCOUNT_KEY'];
		$accessTokenSecret = $this->parameters['twitter']['ACCOUNT_SECRET'];

		$twitter = new Twitter($consumerKey, $consumerSecret, $accessToken, $accessTokenSecret);
		$twitter->send($tweet);
	}

	/**
	* prezidentske 2 kolo v roce 2013 bylo delano jako parser html stranky
	 * = stare
	 * aktualne je moznost nacitat xml
	*/
	private function prezident2kolo($parameters) {

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
	
	private function parlament($parameters) {

		// TODO - PREHODIT KONTROLY DO FCE
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

		if (!empty($posledniTweet) && intval($posledniTweet->zpracovano) >= 100) {
			return 'volby skoncili...';
		}

		// konec kontrol

		// ziskavani dat
		$content = file_get_contents($parameters['volbyUrl']);

		$xml = simplexml_load_string($content);	

		$vysledkyCr = $xml->CR;

		$insertData = array();

		$ucast = $vysledkyCr->UCAST['UCAST_PROC'];
		$insertData['ucast'] = str_replace(",", '.', $ucast);

		$zpracovano = $vysledkyCr->UCAST['OKRSKY_ZPRAC_PROC'];
		$insertData['zpracovano'] = str_replace(",", '.', $zpracovano);

		$poradi = array();

		// projit strany
		foreach($vysledkyCr->STRANA as $elemStrana) {
			//var_dump($elemStrana['NAZ_STR']);

			$stranaId = (int)$elemStrana['KSTRANA'];
			$procento = (float)$elemStrana->HODNOTY_STRANA['PROC_HLASU'];

			if ($procento >= 5) {
				$poradi[(string)$procento] = $parameters['zobrazit'][$stranaId];	
			}

			$insertData['strana'.$stranaId] = str_replace(",", '.', $procento);			
		} 


		$insertData['datumcas'] =  new Nette\Database\SqlLiteral('NOW()');
		$insertData['tweet'] = '0';

		// definovani tweetu
		krsort($poradi);

		$poradiText = array();
		foreach($poradi as $poradiProcento => $poradiStrana) {
			$poradiText[] = $poradiStrana.':'.$poradiProcento.'%';
		}
		$tweet = sprintf("secteno %s - %s - ucast %s #volebniVysledky #volby2013 #parlament2013 #volby", $insertData['zpracovano']."%", implode(';', $poradiText), $insertData['ucast']."%");


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
				try {
					$this->publikujTweet($tweet);
					$insertData['tweet'] = '1';
				} catch (exception $e) {
					//dump($e);
				}
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
