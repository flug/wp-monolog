<?php

/*
Plugin Name: Wordpress Monolog
Plugin URI:
Description: Monolog WordPress Plugin
Version: 1.0
Author: Flug
Author URI: clooder.com
License: MYT
*/

require_once dirname(__FILE__) . '/vendor/autoload.php';


use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

$Mlog = new Logger("Wordpress Log");
add_action( 'init', 'Monolog_setup' );

/**
 * Init function and definition
 *
 *
 * @global Logger $Mlog
 */
function Monolog_setup() {
    global $Mlog;

    $filePath = ABSPATH . 'wp-logs/log.log';
    $max_size = (131072 * 10);

    $dateFormat = "Y m j, g:i a";

    // the default output format is "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n"
    $output = "%datetime% :: %channel% -> %level_name% > %message% %context% %extra%\n";

    $formatter = new LineFormatter($output, $dateFormat);

    if(!file_exists(ABSPATH . 'logs'))
    {
      mkdir(ABSPATH . 'logs', 0777);
    }

    if(filesize($filePath) >= $max_size)
    {
      if (copy($filePath, ABSPATH . 'wp-logs/'.time(). '_log.log')) {
        unlink($filePath);
      }
    }

    $default =[
      'FilePath' => $filePath,
      'QueryLog' => true,
      'level' => Logger::DEBUG
    ];

    $Mlog_Options =  get_option( "Mlog_Options", $default );

    // create a log channel
    $stream = new StreamHandler( $Mlog_Options["FilePath"] , $default['level']);
    $stream->setFormatter($formatter);
    $Mlog->pushHandler($stream);

    if ( $Mlog_Options["QueryLog"] ) {
      add_filter("query", "Monolog_QueryLog"  );
    }

}

/**
 *
 * @param type $query
 */
function Monolog_QueryLog($query) {
  global $Mlog;

  $default = array(
    'QueryLog_filter' => null,
  );

  $Mlog_Options =  get_option( "Mlog_Options", $default );

  //if ( preg_match( '/^\s*(insert|update|delete) /i', $query ) ) {
  //if ( !preg_match( '/\b(wp_options|wp_terms|wp_usermeta|revision)\b/i', $query ) ) {

  if ( is_null( $Mlog_Options["QueryLog_filter"]) ){

    $Mlog->addInfo( $query  );


  } else {
    if ( preg_match( '/^\s*(' . $Mlog_Options["QueryLog_filter"] . ') /i', $query ) ) {

      $Mlog->addInfo( $query );
    }


  }
  return $query;
}
