<?php
	defined('SYSPATH') or die('No direct script access.');

	/**
	 * Hlavny config pre media assety vyuzivajuci Layout triedu. Popis klucov:
	 * - auto_render - ci je automaticky inject do response body pre skupiny - header a footer povoleny
	 * - base_path - cesta k assetom
	 * - js_path - rozsirena cesta k JS assetom
	 * - css_path - rozsirena cesta k CSS assetom
	 * - use_js_object - pouzivanie JS objectu
	 * - js_object_name - nazov JS objectu
	 * - version_key - verzie suborov
	 * - Assets::GROUP_HEADER - zoznam assetov pre hlavicku (kombinovane pre css a js)
	 * - Assets::GROUP_FOOTER - zoznam assetov pre koniec body (kombinovane pre css a js)
	 * - extension_map - rozsireny zoznam pripon, ktory namapuje priponu (napr. .less) k danemu typu assetu
	 * - map_when_in - v ktorych prostrediach sa ma mapovanie aplikovat
	 * - map - mapa suborov
	 *
	 * @package Aria
	 * @author Lukas Homza
	 * @version 1.0
  */
	return array(
		'auto_render' => true,

		'base_path' => '',

		'js_path' => '',
		'css_path' => '',

		'use_js_object' => true,
		'js_object_name' => 'Core',

		'version_key' => 'v',

		Assets::GROUP_HEADER => array(),
		Assets::GROUP_FOOTER => array(),

		'extension_map' => array(
			'less' => Assets::CSS,
			'sass' => Assets::CSS
		),

		'map_when_in' => array(
			Kohana::PRODUCTION, Kohana::STAGING
		),

		'map' => array()

		/*
		Assets::GROUP_HEADER => array(
			'plugin/html5shiv.min.js' => array(
				'condition' => 'if lt IE 9'
			),
			'plugin/respond.min.js' => array(
				'condition' => 'if lte IE 8'
			),

			'bootstrap.css' => array(),
			'core.css' => array(),
		),
		Assets::GROUP_FOOTER => array(
			'plugin/jquery.min.js' => array(),
			'plugin/bootstrap.js' => array(),
		),

		'map' => array(
			'plugin/bootstrap.js' => 'plugin/bootstrap.min.js',
		),
		*/
	);