<?php
	defined('SYSPATH') or die('No direct access allowed.');

	/**
	 * Objekt pristupny cez assety na spravu dat, ktore sa posuvaju do JS
	 *
	 * @package Aria
	 * @author Lukas Homza <lhomza@cfh.sk>
	 * @version 1.0
	 */
	class Kohana_Assets_Object {
		/** @var array $data - zoznam dat */
		protected $data = array();
		/** @var string $name - nazov JS objektu */
		protected $name = null;

		/**
		 * Konstruktor, nastavi nazov objektu podla hodnoty z configu
		 *
		 * @param string $name - nazov objektu
		 *
		 * @return void
		 *
		 * @since 1.0
		 */
		public function __construct($name) {
			$this->name = $name;
		}

		/**
		 * Zkonvertovanie objektu na json ak sa vyuziva ako string
		 *
		 * @return string
		 *
		 * @since 1.0
		 */
		public function __toString() {
			return sprintf('var %s = %s;', $this->name, json_encode($this->data));
		}

		/**
		 * Metoda vytiahne data ulozene pod danym klucom
		 *
		 * @param string $path - array path k datam
		 * @param string $default - defaultna hodnota, ktora sa vrati ak sa nic nenajde
		 *
		 * @return mixed
		 *
		 * @since 1.0
		 */
		public function get($path, $default = null) {
			return Arr::path($this->data, $path, $default);
		}

		/**
		 * Metoda ulozi data pod dany kluc
		 *
		 * @param string $path - array path pre vytvorenie
		 * @param mixed $value - hodnota, ktora sa ma ulozit
		 *
		 * @return Kohana_Assets_Object
		 */
		public function set($path, $value = null) {
			if(is_array($path)) {
				foreach($path as $k => $v) {
					Arr::set_path($this->data, $k, $v);
				}
			} else {
				Arr::set_path($this->data, $path, $value);
			}

			return $this;
		}

		/**
		 * Metoda vymaze data ulozene pod danym klucom
		 *
		 * @param string $path - array_path pre vymazanie
		 *
		 * @return boolean
		 */
		public function delete($path) {
			return Aria::deleteByPath($this->data, $path);
		}

		/**
		 * Metoda vymaze vsetky data
		 *
		 * @return boolean
		 *
		 * @since 1.0
		 */
		public function delete_all() {
			$this->data = array();

			return true;
		}
	}