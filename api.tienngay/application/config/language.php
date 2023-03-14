<?php if(!defined('BASEPATH')) exit('No direct script access allowed');

/*
  |--------------------------------------------------------------------------
  | Supported Languages
  |--------------------------------------------------------------------------
  |
  | Contains all languages your site will store data in. Other languages can
  | still be displayed via language files, thats totally different.
  |
  | Check for HTML equivilents for characters such as � with the URL below:
  |    http://htmlhelp.com/reference/html40/entities/latin1.html
  |
  |
  |    array('en'=> 'English', 'fr'=> 'French', 'de'=> 'German')
  |
 */
$config['supported_languages'] = array(
    'en' => array(
        'name' => 'English',
        'folder' => 'english',
        'direction' => 'ltr',
        'codes' => array('en', 'english', 'en_US'),
    ),
    'vn' => array(
        'name' => 'Việt Nam',
        'folder' => 'vietnamese',
        'direction' => 'ltr',
        'codes' => array('vn', 'vietnam', 'vi_VN'),
    )
);

/*
  |--------------------------------------------------------------------------
  | Default Language
  |--------------------------------------------------------------------------
  |
  | If no language is specified, which one to use? Must be in the array above.
  |
  |    en
  |
 */
$config['default_language'] = 'en';

/*
  |--------------------------------------------------------------------------
  | Detect Browser Language
  |--------------------------------------------------------------------------
  |
  | If enabled detecting browser language and disable default language
  |
  |    FALSE
  |
 */
$config['detect_language'] = TRUE;


/*
 | Load file lang
 |
 */

 $config['lang_file'] = [
    'unit_test',
    'upload',
    'profiler',
    'form_validation',
    'db',
    'date', 
    'calendar',
    'user',
    'chat',
  ];
/*
  |--------------------------------------------------------------------------
  | Default URI
  |--------------------------------------------------------------------------
  |
  | Where to redirect if no language in URI.
  | Example if default_uri 'welcome' => /en/weclome
  |
  |    welcome
  |
 */
$config['default_uri'] = '/';

/*
  |--------------------------------------------------------------------------
  | Special URIs
  |--------------------------------------------------------------------------
  |
  | This URIs is not be localized
  |
  |    array('admin', 'auth', 'api')
  |
 */
$config['special_uris'] = array(

);
