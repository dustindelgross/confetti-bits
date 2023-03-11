<?php

use BuddyBossApp\DeepLinking\Type\TypeAbstract;

class Confetti_Bits_Core_App_Menus extends TypeAbstract {

	public function __construct() {
		parent::__construct();
	}

	public function parse( $url ) {
		$url_meta = $this->get_url_data( $url );
		if ( isset( $url_meta['name'] ) && isset( $url_meta['page'] ) && empty( $url_meta['page'] ) ) {
			foreach ( get_taxonomies( array(), 'objects' ) as $taxonomy => $t ) {
				if ( $t->rewrite['slug'] == $url_meta['name'] ) {

					/**
                     * Filter taxonomy deep linking namespace
                     */
					$namespace = apply_filters( 'bbapp_deeplinking_taxonomy_namespace', 'core', $t );

					/**
                     * Filter taxonomy deep linking data
                     */
					return apply_filters(
						'bbapp_deeplinking_taxonomy',
						array(
							'action'    => 'open_taxonomy',
							'namespace' => $namespace,
							'url'       => $url,
							'taxonomy'  => $t->name,
						),
						$t
					);
				}
			}
		}

		return null;

	}
}