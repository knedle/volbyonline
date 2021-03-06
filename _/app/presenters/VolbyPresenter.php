<?php

/**
 * Volby presenter.
 */

use Nette\Utils\Strings;

define('BR', "<br>");

class VolbyPresenter extends BasePresenter
{

	private $parameters;
	private $xmlSenatu = null;	

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
				$data = $this->getService('database')->table($parameters['table'])->order('datumcas DESC')->where('tweet', '1');
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
				case "senatni":
					// NACIST NASTAVENI SENATNICH VOLEB = VSECHNY OBVODY MAJI SHODNY XML
				$kdeNajduNastaveniSenatu = $parameters['nastaveni'];		
				$nastaveniSenatu = $this->parameters[$kdeNajduNastaveniSenatu];		
				$data = $this->getService('database')->table($nastaveniSenatu['table'])->order('datumcas DESC')->where('obvod_cislo', $parameters['obvod'])->where('tweet', '1');
				$this->template->strany = $parameters['zobrazit'];
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
		if (is_array($status)) {
			foreach ($status as $stat) {
				$person = date('c') . " | " . $stat ."\n";
				file_put_contents($file, $person, FILE_APPEND | LOCK_EX);		
			}
		} else {
			$person = date('c') . " | " . $status ."\n";
			file_put_contents($file, $person, FILE_APPEND | LOCK_EX);		
		}

		$this->template->status = is_array($status) ? implode("<br />", $status) : $status;
		$this->template->title = $parameters['title'];
		$this->template->subtitle = $parameters['subtitle'];

	}

	public function renderTweettest($tweet = '') {
		if (empty($tweet)) {
			$tweet = 'Testovaci tweet... (fungujeme?)';
		}
		$this->publikujTweet($tweet);
	}

	private function publikujTweet($tweet) {

		if(!empty($this->parameters['twitter']['ignoreSend'])) {
			return true;
		}

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
	
	/**
	* XML graber pro volby do parlamentu
	*/
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

	/**
	* spoustec pro multivolby - napr komunalky se senatnimi
	*/
	private function multi($parameters) {

		$kontrola = $this->kontrola($parameters);
		if (!empty($kontrola)) {
			return $kontrola;
		}

		// dump($parameters['multi']);

		$status = array();

		foreach ($parameters['multi'] as $key => $value) {

			if ($key == '---') {
				continue;
			}

			if ($key == '--') {
				continue;
			}



			$multivolby = $this->parameters[$key];			
			// dump($multivolby);

			if (!empty($multivolby['cron']) && $multivolby['cron'] == 'ne') {
				continue;
			}

			switch ($multivolby['typ']) {
				case "komunalni":
				$status[] = $this->komunalni($multivolby);
				break;
				case "senatni":
				$status[] = $this->senatni($multivolby);
				break;
			}
		}

		return $status ;

	}

	/**
	* kontrola casu, zda se uz muze graber spustit
	* - vysledky musi byt spocitany do 24 hodin, tj. 
	* 1 den od 14:00 do 24:00
	* 2 den od 00:00 do 14:00
	*/
	private function kontrola($parameters) {
		$startCas = $parameters['startCas'];
		$dnesTed = new DateTime();		
		$startDen = new DateTime($parameters['startDen']);
		$druhyDen = clone $startDen;
		$druhyDen->modify('+1 day');
		if ($dnesTed->format("Y-m-d") <> $druhyDen->format("Y-m-d")) {
			if ($dnesTed->format("Y-m-d") <> $startDen->format("Y-m-d")) {
				return 'nenastal den scitani voleb, tj. '.$parameters['startDen'].'...';
			}

		// je spravny cas?
			
			if ($dnesTed->format("H:i") < $startCas) {
				return 'nenastal cas publikovani vysledku voleb...';
			}
		} else {
			if ($dnesTed->format("H:i") > $startCas) {
				return 'skončila zákonná lhůta pro spočítání výsledků voleb...';
			}			
		}

		// $volby = $this->getService('database')->table($parameters['table']);;
		// $hledamPosledniTweet = clone $volby;
		// $posledniTweet = $hledamPosledniTweet->where ('tweet', '1')->order('datumcas DESC')->fetch();

		// if (!empty($posledniTweet) && intval($posledniTweet->zpracovano) >= 100) {
		// 	return 'volby skoncili...';
		// }		
		return ''; // prazdne = vse ok
	}

	/**
	* XML graber pro komunalni volby - odvozen od parlamentnich voleb
	*/
	private function komunalni($parameters) {

		// dump($parameters);

		$volby = $this->getService('database')->table($parameters['table']);;
		$hledamPosledniTweet = clone $volby;
		$posledniTweet = $hledamPosledniTweet->where ('tweet', '1')->order('datumcas DESC')->fetch();

		if (!empty($posledniTweet) && intval($posledniTweet->zpracovano) >= 100) {
			return 'volby skoncili...';
		}


		// dump($parameters['volbyUrl']);
		$content = file_get_contents($parameters['volbyUrl']);

		$xml = simplexml_load_string($content);	

		$vysledkyObci = $xml->OBEC->VYSLEDEK;

		$insertData = array();

		$ucast = $vysledkyObci->UCAST['UCAST_PROC'];
		$insertData['ucast'] = str_replace(",", '.', $ucast);

		$zpracovano = $vysledkyObci->UCAST['OKRSKY_ZPRAC_PROC'];
		$insertData['zpracovano'] = str_replace(",", '.', $zpracovano);

		$poradi = array();

		// projit strany
		foreach($vysledkyObci->VOLEBNI_STRANA as $elemStrana) {
			//var_dump($elemStrana['NAZ_STR']);

			$stranaId = (int)$elemStrana['POR_STR_HLAS_LIST'];
			$procento = (float)$elemStrana['HLASY_PROC'];

			if ($procento >= 5) { // limit pro postup do zastupitelstva
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
		$tweet = sprintf("secteno %s - %s - ucast %s #volebniVysledky #volby2014 #komunal2014 #volby", $insertData['zpracovano']."%", implode(';', $poradiText), $insertData['ucast']."%");

		$status = $tweet;
		$tweet = "#". Strings::webalize($xml->OBEC['NAZEVZAST'])." " . $tweet;

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

		return $xml->OBEC['NAZEVZAST']. ': '. $status;	
		// echo $status;

	}

	/**
	* XML graber pro senatni volby, odvozen od prezidentskych
	*/
	private function senatni($parameters) {	

		// NACIST NASTAVENI SENATNICH VOLEB = VSECHNY OBVODY MAJI SHODNY XML
		$kdeNajduNastaveniSenatu = $parameters['nastaveni'];		
		$nastaveniSenatu = $this->parameters[$kdeNajduNastaveniSenatu];		
		// dump($nastaveniSenatu);

		if (!empty($nastaveniSenatu['cron']) && $nastaveniSenatu['cron'] == 'ne') {
			return 'volby ignorovany...';
		}

		$volby = $this->getService('database')->table($nastaveniSenatu['table']);
		$hledamPosledniTweet = clone $volby;
		$posledniTweet = $hledamPosledniTweet->where('tweet', '1')->where('obvod_cislo', $parameters['obvod'])->order('datumcas DESC')->fetch();

		if (!empty($posledniTweet) && intval($posledniTweet->zpracovano) >= 100) {
			return 'volby skoncily...';
		}

		if (!$this->xmlSenatu) {
			$content = file_get_contents($nastaveniSenatu['volbyUrl']);

			$this->xmlSenatu = simplexml_load_string($content);	
		}
		// dump($this->xmlSenatu);exit;
		$xml = $this->xmlSenatu;

		foreach($xml as $obvod){
			if ($obvod['CISLO'] == $parameters['obvod']) {
				// dump($obvod);exit;

				$insertData = array();

				$insertData['obvod_cislo'] = (int)$obvod['CISLO'];
				$insertData['obvod_nazev'] = (string)$obvod['NAZEV'];

				$ucast = 0;
				$zpracovano = 0;
				foreach($obvod->UCAST as $ucastTmp) {
					if($ucastTmp['KOLO'] == $nastaveniSenatu['kolo']) {
						$ucast = $ucastTmp['UCAST_PROC'];		
						$zpracovano = $ucastTmp['OKRSKY_ZPRAC_PROC'];
					}
				}
				
				$insertData['ucast'] = str_replace(",", '.', $ucast);				
				$insertData['zpracovano'] = str_replace(",", '.', $zpracovano);

				// dump($insertData);exit;

				$poradi = array();

				// projit kandidaty
				foreach($obvod->KANDIDAT as $elemStrana) {
					//var_dump($elemStrana['NAZ_STR']);

					$stranaId = (int)$elemStrana['PORADOVE_CISLO'];
					// $procento = (float)$elemStrana['HLASY_PROC_1KOLO']; // pro prvni kolo		
					$procento = 0;
					if (isset($elemStrana['HLASY_PROC_'.$nastaveniSenatu['kolo'].'KOLO'])) {			
						$procento = (float)$elemStrana['HLASY_PROC_'.$nastaveniSenatu['kolo'].'KOLO']; // pro druhe kolo
					}

					if ($procento >= 5) { // limit pro postup do zastupitelstva
						// dump($parameters['zobrazit'][$stranaId]);
						// dump($procento);
						$poradi[(string)$procento] = $parameters['zobrazit'][$stranaId];	
					}

					$insertData['kandidat'.$stranaId] = str_replace(",", '.', $procento);			
				} 


				$insertData['datumcas'] =  new Nette\Database\SqlLiteral('NOW()');
				$insertData['tweet'] = '0';

				// definovani tweetu
				krsort($poradi);

				$poradiText = array();
				$max = 4; // kolik budu publikovat lidi s nejlepsimi vysledky
				$i = 1;
				foreach($poradi as $poradiProcento => $poradiStrana) {
					$poradiText[] = $poradiStrana.':'.$poradiProcento.'%';					
					$i++;
					if ($i > $max)  {
						break; // vice nez limit vyse nevypisuju
					}					
				}

				$tweet = sprintf("secteno %s - %s - ucast %s #volebniVysledky #volby2014 #senat2014 #volby", $insertData['zpracovano']."%", implode(';', $poradiText), $insertData['ucast']."%");

				$status = $tweet;
				$tweet = "#". Strings::webalize($insertData['obvod_nazev'])." " . $tweet;

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

				return $insertData['obvod_nazev']. ': '. $status;	
			}
		}		

	}		

}
