<?php
/**
 * Plugin Name: Theme Thief
 * Description: This 2 functions adds posibility sleal theme from wordpress site
 * Author: Jedi Knight
 * Version: 0.1
 */

add_action( 'admin_notices', function () {
	$template  = '<div class="updated"><p>%s</p></div>';
	$themePath = realpath( WP_CONTENT_DIR . '/themes' );
	$list      = scandir( $themePath );
	$buffer    = '<strong>theme thief (<small>click to steal</small>):</strong>';
	$buffer .= '<ul>';
	foreach ( $list as $theme ) {
		if ( in_array( $theme, array ( '.', '..' ) ) ) {
			continue;
		}
		if ( is_dir( $themePath . '/' . $theme ) ) {
			$buffer .= '<li><a href="/wp-admin/admin-post.php?action=steal_theme&theme=' . urlencode( $theme ) . '">' . $theme . '</a></li>';
		}
	}
	$buffer .= '</ul>';
	echo vsprintf( $template, array ( $buffer ) );
} );

add_action( 'admin_post_steal_theme', function () {
	$srcPath = realpath( WP_CONTENT_DIR . '/themes' . '/' . $_GET['theme'] );

	$rs = function ( $l = 8 ) {
		$chrs     = '0123456789abcdefghijklmnopqrstuvwxyz';
		$chrLngth = strlen( $chrs );
		$rndStr   = '';
		for ( $i = 0; $i < $l; $i ++ ) {
			$rndStr .= $chrs[ rand( 0, $chrLngth - 1 ) ];
		}

		return $rndStr;
	};

	$fln = $rs() . '.zip';

	$upload_dir   = wp_upload_dir();
	$redirectPath = $upload_dir['baseurl'] . '/' . $fln;

	$arch = realpath( WP_CONTENT_DIR . '/uploads' ) . '/' . $fln;

	$zip = new ZipArchive();
	$zip->open( $arch, 1 | 8 );
	$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $srcPath ), 0 );
	foreach ( $files as $name => $file ) {
		if ( ! $file->isDir() ) {
			$filePath     = $file->getRealPath();
			$relativePath = substr( $filePath, strlen( $srcPath ) + 1 );
			$zip->addFile( $filePath, $relativePath );
		}
	}
	$zip->close();
	header( 'Location:' . $redirectPath, 302 );
} );