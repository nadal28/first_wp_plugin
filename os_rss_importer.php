<?php
/*
Plugin Name: OS RSS IMPORTER
Plugin URI: http://www.opensistemas.com
Description: Este plugin crea post a partir de un RSS dado
Version: 1.0
Author: Adrián Aragonés
Author email: aaragones@opensistemas.com
Text Domain: os_rss_importer_plugin
License: GPL2
*/

define('RSSIMPPATH', WP_PLUGIN_DIR . '/os_rss_importer/');
require_once RSSIMPPATH . 'os_rss_importer.php';

if (!class_exists('OSRSSImporter')) {

	class OSRSSImporter {

		function __construct () {
			add_action('admin_menu', array(&$this, 'os_rss_importer_menu'));
			add_action('plugins_loaded', array(&$this, 'load_text_domain'), 10);
			add_action('rss_import_posts', array(&$this,'check_feed'));
			register_deactivation_hook(RSSIMPPATH . 'os_rss_importer.php', array(&$this, 'clean_cron_job'));
		}

		// Selecciona Dominio para la traducción
		function load_text_domain() {
			$plugin_dir = basename(dirname(__FILE__));
			load_plugin_textdomain('os_rss_importer_plugin', false, $plugin_dir . "/languages");
		}

		function os_rss_importer_menu(){
			add_options_page(__('RSS Importer', 'os_rss_importer_plugin'), __('RSS Importer','os_rss_importer_plugin'), 'manage_options', 'os-rss-importer-menu', array(&$this,'os_rss_importer_options_page'));
			//call register settings function
			add_action( 'admin_init', array(&$this, 'register_rss_importer_settings' ));

		}

		function os_rss_importer_options_page(){
			include('admin/os_rss_importer_admin.php');
		}

		function register_rss_importer_settings() {
			register_setting( 'os-rss-importer-form', 'url_rss' );
			register_setting( 'os-rss-importer-form', 'frecuencia', array(&$this,'update_cron_job'));
		}

		function update_cron_job($new_val) {
			
			$args = array();

			$timestamp = wp_next_scheduled( 'rss_import_posts', $args );
			if($timestamp != false)
				wp_unschedule_event( $timestamp, 'rss_import_posts', $args);
			wp_schedule_event(time(), $new_val, 'rss_import_posts');

			//$this->check_feed();
			return $new_val;
		}

		function clean_cron_job() {

			$args = array();

			$timestamp = wp_next_scheduled( 'rss_import_posts', $args );
			if($timestamp != false)
				wp_unschedule_event( $timestamp, 'rss_import_posts', $args);			
		}

		// ---------------------------------||||||||||||||||||||||||||||||||-----------------------------------------

		function curl($url) {
			$ch = curl_init();

			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);


			$output = curl_exec($ch);
			curl_close($ch);

			return $output;
		}

		function limpiar($basura){
			return trim(preg_replace(array('/<\!\[CDATA\[/','/(&hellip;)|(\]\]>)/'), '', strip_tags($basura)));
		}

		function check_feed() {

			$xml = $this->curl(get_option('url_rss'));

			$posts = new SimpleXMLElement($xml);
			$posts = $posts->xpath('/rss/channel/item'); //Ruta hacia las etiquetas que vamos a capturar.

			//Obtenemos la fecha más antigua de las noticias traidas
			$fechas = array();
			foreach($posts as &$post)
				$fechas[] = strtotime((string)$post->pubDate);

			$fecha_mas_antigua = min($fechas);
			unset($fechas);


			//Hacemos una query para saber los post que ya teniamos y evitar duplicarlos
			$args = array(
				'post_type' => 'post',
				'orderby' => 'date',
				'order' => 'DESC',
				'posts_per_page' => -1,
				'post_status' => 'any',
				'date_query' => array('after' => date('Y-m-d',$fecha_mas_antigua))
				);

			$query = new WP_Query( $args );
			unset($args,$fecha_mas_antigua);

			//Para identificar a los post usare el link al que apuntan, guardado en un post_meta
			$links = array();
			foreach($query->posts as &$post)
				$links[] = get_post_meta($post->ID,'link_noticia',true);

			foreach($posts as &$post){

				//Campos a capturar del RSS
				$titulo = (string)$post->title;
				$descripcion = $this->limpiar((string)$post->description);
				$date = (string)$post->pubDate;
				$link = (string)$post->link;
				$image = (string)$post->image;
				$contenido = '';

				//Si el link de la noticia tambien se encuentra en la base de datos, significa que la noticia ya está guardada, por tanto omitimos su duplicado.
				if(in_array($link, $links))
					continue;


				$id = wp_insert_post( array(
					'post_status' => 'pending',
					'post_title' => $titulo,
					'post_content' => $contenido,
					'post_excerpt' => $descripcion,
				));

				add_post_meta($id, 'link_imagen', $image);
				add_post_meta($id, 'link_noticia', $link);
				add_post_meta($id, 'fecha_noticia', date('Y-m-d H:i:s', strtotime($date)));

			}
		}

	}

	$OSRSSImporter = new OSRSSImporter();

}
