<?php
/*
Plugin Name: Convert audio files
Plugin URI: https://geek.hellyer.kiwi/plugins/convert-audio-files/
Description: Converts audio files from WAV to MP3
Version: 1.0
Author: Ryan Hellyer
Author URI: https://geek.hellyer.kiwi/

*/

add_filter( 'wp_handle_upload_prefilter', 'acf_filter_audio_on_upload' );
/**
 * Modifying file name and format as it is uploaded.
 * This is a bit hacky, as we're uploading something with a .mp3 extension, even when it isn't an MP3.
 */
function acf_filter_audio_on_upload( $data ) {
	global $caf_extension;

	$caf_extension = substr( $data[ 'name' ], -4 );
	$slug      = substr( $data[ 'name' ], 0, -4 );

	if (
		'.wav' == $caf_extension
		||
		'.ogg' == $caf_extension
	) {
		$dir = wp_upload_dir();
		$slug = sanitize_title( $slug );

		$data[ 'name' ] = $slug . '.mp3';
		$new_temp_name = $dir[ 'path' ] . 'temporary.mp3';

		$data[ 'name' ] = $slug . '.mp3';
		$data[ 'type' ] = 'audio/mp3';

	} else {
		$caf_extension = 'EJECT!';
	}

	return $data;
}

add_filter( 'wp_handle_upload', 'acf_filter_audio_after_upload' );
/**
 * Converting uploaded file to MP3 after it has been uploaded.
 */
function acf_filter_audio_after_upload( $data ) {
	global $caf_extension;

	// Bail out now if it wasn't processed as other format yet.
	if ( 'EJECT!' == $caf_extension ) {
		return;
	}

	$file = $data[ 'file' ];

	$extension = substr( $file, -4 );
	$slug      = substr( $file, 0, -4 );

	rename( $file, $slug );

	$command = 'ffmpeg -i ' . $slug . ' ' . $file;
	$result = shell_exec( $command );

	unlink( $slug );

	return $data;
}
