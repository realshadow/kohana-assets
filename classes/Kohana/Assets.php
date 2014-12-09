<?php
	defined('SYSPATH') or die('No direct script access.');

	/**
	 * Trieda sluziaca na spravu assetov
	 *
	 * Assety sa delia na dva zakladne typy a to JS a CSS. Zoznam assetov sa drzi v lubovolnych skupinach
	 * okrem dvoch prednastavenych a to header a footer, ktore su automaticky pridane do response body, ktory
	 * vracia kohana. Assety taktiez podporuju verziovanie. Ku kazdemu assetu sa okrem attributov daneho
	 * typu tagu da nastavit verzia a priorita daneho assetu. Assety z configu su pridane s normalnou prioritou
	 * aby bolo mozne pred aj za nich pridat nove assety. V pripade, ze bude viac assetov s rovnakou prioritou,
	 * berie sa do uvahy poradie, v ktorom boli pridane.
	 *
	 * Je mozne nastavit akukolvek skupinu a naslednu ju rucne vytvorit, avsak treba davat pozor, ze aktivna skupina
	 * sa prenasa celym requestom!
	 *
	 * Priklad volania z controlleru:
	 *
	 *		$this->assets->add('validate.js')->block('alert(4);');
	 *
	 *		// alebo
	 *
	 *		$this->assets->add('core.js', array(
	 *			'priority' => 1,
	 *			'condition' => 'if lt IE 9',
	 *			'version' => '1.3.1'
	 *		))->add('core.css', array(
	 *			'media' => 'print',
	 *			'version' => '1.2.0'
	 *		));
	 *
	 *		// alebo
	 *
	 *		$this->group(Assets::GROUP_FOOTER)->add('validate.js')->group('body')->add('alert(4);');
	 *
	 *		// vykreslenie skupiny vo view
	 *		Aria::$assets->render('body');
	 *
	 * @package Aria
	 * @author Lukas Homza <lhomza@cfh.sk>
	 * @version 1.0
	*/
	class Kohana_Assets {
		/** @var string GROUP_HEADER - skupina assetov */
		const GROUP_HEADER = 'header';
		/** @var string GROUP_FOOTER - skupina assetov */
		const GROUP_FOOTER = 'footer';
		/** @var string PRIORITY_HIGH - nastavenie priority */
		const PRIORITY_HIGH = 10;
		/** @var string PRIORITY_NORMAL - nastavenie priority */
		const PRIORITY_NORMAL = 50;
		/** @var string PRIORITY_LOW - nastavenie priority */
		const PRIORITY_LOW = 90;
		/** @var string JS - typ assetu */
		const JS = 'js';
		/** @var string CSS - typ assetu */
		const CSS = 'css';

		/** @var object $instance - instancia triedy */
		protected static $instance = null;

		/** @var array $register - zoznam pridanych assetov */
		protected $register = array();
		/** @var array $assets - zoznam JS a CSS assetov */
		protected $assets = array(
			self::CSS => array(),
			self::JS => array()
		);
		/** @var array $extensions - zoznam povolenych pripon */
		protected $extensions = array(
			self::CSS, self::JS
		);
		/** @var string $activeGroup - aktivna skupina assetov */
		protected $activeGroup = self::GROUP_HEADER;

		/**
		 * Metoda pripravy vsetky assety nastavene v configu
		 *
		 * @return void
		 *
		 * @since 1.0
		 */
		protected function load() {
			$groups = array(self::GROUP_HEADER, self::GROUP_FOOTER);
			foreach($groups as $group) {
				$items = $this->config->get($group);

				foreach($items as $item => $params) {
					$this->group($group)->add($item, $params);
				}
			}

			# -- reset group
			$this->group(self::GROUP_HEADER);
		}

		/**
		 * Metoda pre validaciu pripony pridavaneho suboru. V ramci assetov je povolena
		 * len pripona .js a .css a ich tienove pripony podla definovanej mapy
		 *
		 * @param string $extension - pripona pridavaneho assetu
		 *
		 * @throws Assets_Exception
		 *
		 * @since 1.0
		 */
		protected function validateExtension($extension) {
			$shadowExtension = Arr::get($this->config->get('extension_map'), $extension);
			if(!in_array($extension, $this->extensions) && empty($shadowExtension)) {
				throw new Assets_Exception('Asset extension :extension is not supported.', array(
					':extension' => $extension
				));
			}
		}

		/**
		 * Metoda vysklada kompletnu cestu k assetu
		 *
		 * @param string $extension - pripona pridavaneho assetu
		 *
		 * @return string
		 *
		 * @since 1.0
		 */
		protected function path($extension) {
			$base = trim($this->config->get('base_path'), '/');
			$extensionPath = trim($this->config->get($extension.'_path'), '/');

			$pattern = '%s/';
			if(!empty($extensionPath)) {
				$pattern = str_repeat($pattern, 2);
			}

			return sprintf($pattern, $base, $extensionPath);
		}

		/**
		 * Getter/setter pre pridanie assetu do zoznamu alebo vratenie celeho zoznamu
		 *
		 * @param string $item - nazov assetu
		 *
		 * @return mixed
		 *
		 * @since 1.0
		 */
		protected function register($item = null) {
			if(!is_null($item)) {
				$this->register[] = $item;
			} else {
				return $this->register;
			}
		}

		/**
		 * Metoda pre zistenie ci ide o externu adresu zacinajucu na http alebo https
		 *
		 * @param string $file - nazov assetu
		 *
		 * @return boolean
		 *
		 * @since 1.0
		*/
		protected function external($file) {
			return (bool) preg_match('#^(http(s)?:\/\/|\/\/|:\/\/)(.*)$#i', $file);
		}

		/**
		 * Metoda sluzi na premapovanie assetu na iny asset
		 *
		 * Priklad nastavenia:
		 *		jquery.js => jquery.min.js
		 *
		 * Zoznam prostredi kde sa ma mapa aktivovat sa nachadza v configu pod
		 * klucom map_when_in
		 *
		 * @param string $path - cesta k assetu
		 *
		 * @return array
		 *
		 * @since 1.0
		*/
		protected function map($path) {
			if(!in_array(Kohana::$environment, $this->config->get('map_when_in'))) {
				return $path;
			}

			$map = $this->config->get('map');

			return Arr::get($map, $path, $path);
		}

		/**
		 * Singleton pre vytvorenie instancie
		 *
		 * @return \Kohana_Assets
		 *
		 * @since 1.0
		 */
		public static function instance() {
			$class = get_called_class();

			return (!is_null(self::$instance) ? self::$instance : self::$instance = new $class);
		}

		/**
		 * Konstruktor - natiahne config, v pripade, ze je povoleny JS objekt, tak bude
		 * pripraveny. V pripade, ze nejde o externy request budu assety z configu automaticky
		 * pripravene a pridane do zoznamu assetov
		 *
		 * @return void
		 *
		 * @since 1.0
		*/
		public function __construct() {
			$this->config = Kohana::$config->load('assets');

			if($this->config->get('use_js_object') === true) {
				$this->object = new Kohana_Assets_Object($this->config->get('js_object_name'));
			}

			$request = Request::current();
			#if($request->is_initial() === true) {
				$this->load();
			#}
		}

		/**
		 * Metoda sluzi na dotiahnutie parametra z configu pre ine triedy
		 *
		 * @param string $key - kluc z configu
		 *
		 * @return mixed
		 *
		 * @since 1.0
		 */
		public function config($key) {
			return $this->config->get($key);
		}

		/**
		 * Metoda sluzi na pridanie assetu do zoznamu assetov. Podla pripony pridavaneho assetu
		 * sa metoda rozhodne o aky asset ide a prida ho do prislusneho zoznamu
		 *
		 * @param string $item - nazov assetu
		 * @param array $params - zoznam dodatocnyh parametrov
		 *
		 * @return \Kohana_Assets
		 *
		 * @since 1.0
		 */
		public function add($item, array $params = array()) {
			$filename = pathinfo($item, PATHINFO_FILENAME);
			$extension = pathinfo($item, PATHINFO_EXTENSION);

			# -- validacia pripony
			$this->validateExtension($extension);

			# -- rtrim kvoli podpore php servovania assetov
			$name = sprintf('%s.%s', $filename, $extension);

			$priority = Arr::get($params, 'priority', self::PRIORITY_NORMAL);

			unset($params['priority']);

			# -- ak uz bol pridany, neries
			if(in_array($name, $this->register())) {
				return $this;
			}

			# -- zaregistruj novy asset
			$this->register($name);

			$file = array(
				'block' => false,
				'path' => $item,
				'params' => $params
			);

			if(!in_array($extension, array(Assets::CSS, Assets::JS))) {
				$extension = Arr::get($this->config->get('extension_map'), $extension);
			}

			if(empty($this->assets[$extension][$this->activeGroup][$priority])) {
				$this->assets[$extension][$this->activeGroup][$priority] = array();
			}

			$this->assets[$extension][$this->activeGroup][$priority][] = $file;

			return $this;
		}

		/**
		 * Metoda na pridanie raw JS kodu do zoznamu assetov
		 *
		 * @param string $jsCode - JS kod
		 * @param array $params - zoznam dodatocnyh parametrov
		 *
		 * @return \Kohana_Assets
		 */
		public function block($jsCode, array $params = array()) {
			$extension = self::JS;

			# -- vytvor hash z kodu a pridaj ho do zoznamu pridanych assetov
			$hash = md5($jsCode);
			if(in_array($hash, $this->register())) {
				return $this;
			}

			# -- zaregistruj hash
			$this->register($hash);

			$priority = Arr::get($params, 'priority', self::PRIORITY_NORMAL);

			unset($params['priority']);

			if(empty($this->assets[$extension][$this->activeGroup][$priority])) {
				$this->assets[$extension][$this->activeGroup][$priority] = array();
			}

			# -- pridaj asset a obal ho do <script> tagu
			$this->assets[$extension][$this->activeGroup][$priority][] = array(
				'block' => true,
				'path' => sprintf('<script type="text/javascript">%s</script>', $jsCode),
				'params' => $params
			);

			return $this;
		}

		/**
		 * Metoda nastavi danu skupinu ako aktivnu
		 *
		 * @param string $group - nazov skupiny
		 *
		 * @return \Kohana_Assets
		 */
		public function group($group) {
			$this->activeGroup = $group;

			return $this;
		}

		/**
		 * Metoda sluzi ako alias pre pristup k JS objektu
		 *
		 * @return \Kohana_Assets_Object
		 *
		 * @throws Assets_Exception
		 *
		 * @since 1.0
		 */
		public function object() {
			if($this->config->get('use_js_object') === false) {
				throw new Assets_Exception('JS object is not enabled.');
			}

			return $this->object;
		}

		/**
		 * Alias metoda pre inject, ktora vykresli assety na obrazovku
		 *
		 * @param string $extension - typ assetu
		 *
		 * @return string
		 *
		 * @since 1.0
		 */
		public function render($extension) {
			print $this->inject($extension);
		}

		/**
		 * Metoda sluziacia na vlozenie zakladnych skupin - header a footer do response body od
		 * kohany
		 *
		 * @param string $extension - typ assetu
		 *
		 * @return string
		 */
		public function inject($extension) {
			$this->validateExtension($extension);

			# -- automaticky pridaj navrh JS object
			if($this->config->get('use_js_object') === true && $this->activeGroup === self::GROUP_HEADER && $extension === self::JS) {
				$this->group(self::GROUP_HEADER)->block($this->object, array(
					'priority' => self::PRIORITY_HIGH
				));
			}

			# -- vytiahni vsetky assety pre dany typ
			$items = Arr::path($this->assets, sprintf('%s.%s', $extension, $this->activeGroup));

			$out = '';
			if(empty($items)) {
				return $out;
			}

			# -- sort podla priority
			ksort($items);

			foreach($items as $files) {
				foreach($files as $asset) {
					$version = Arr::path($asset, 'params.version');
					$condition = Arr::path($asset, 'params.condition');
					$sibling = next($files);

					# -- odstran tieto veci, zvysok su attributy tagu
					unset($asset['params']['condition'], $asset['params']['version']);

					if(!empty($condition) && empty($open)) {
						$out .= '<!--['.$condition.']>'.PHP_EOL;

						$open = true;
					}

					$filepath = $this->map(Arr::get($asset, 'path'));
					$path = $this->path($extension).$filepath;

					# -- vyskladanie cesty k assetu aj s verziou
					if($this->external($filepath) && $extension === self::JS) {
						$path = $filepath;
					} elseif(!empty($version)) {
						$path .= sprintf('?%s=%s', $this->config->get('version_key'), $version);
					}

					# -- vykreslenie
					if($extension === self::CSS) {
						$params = array_merge(array('media' => 'screen'), Arr::get($asset, 'params', array()));

						$out .= HTML::style($path, $params).PHP_EOL;
					} else {
						# -- ak ide o JS block, len ho vypis
						if(Arr::get($asset, 'block') === true) {
							$out .= Arr::get($asset, 'path').PHP_EOL;
						} else {
							$params = Arr::get($asset, 'params', array());

							$out .= HTML::script($path).PHP_EOL;
						}
					}

					# -- uzatvor podmienku ak nasledujuci asset nepatri do tej istej skupiny
					if(!empty($condition) && $condition !== Arr::path($sibling, 'params.condition')) {
						$out .= '<![endif]-->'.PHP_EOL;

						$open = false;
					}
				}
			}

			return $out;
		}
	}