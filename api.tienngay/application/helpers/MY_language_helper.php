<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

function lang($line, $id = '')
{
    $CI =& get_instance();

    $supported_languages = array(
    'en' => array(
        'name' => 'English',
        'folder' => 'english',
        'direction' => 'ltr',
        'codes' => array('en', 'english', 'en_US'),
        )
    );
    $supported_languages = !empty($CI->config->item('supported_languages')) ? $CI->config->item('supported_languages') : $supported_languages;
    $current_language = !empty($CI->config->item('default_language')) ? $CI->config->item('default_language') : 'en';
    $lang_code = $CI->session->userdata('language');
    $url_ok = false;
    if((!empty($lang_code)) && (array_key_exists($lang_code, $supported_languages)))
    {
        $language = $supported_languages[$lang_code]['folder'];
        $url_ok = TRUE;
    }

    if((!$url_ok) && (!$CI->lang->is_special($lang_code))) // special URI -> no redirect
    {
        $current_language = $CI->lang->default_lang();
        $language = $supported_languages[$current_language]['folder'];
    }
    $lang_file = $CI->config->item('lang_file');
    $CI->lang->load($lang_file, $language);
    $line = $CI->lang->line($line);

    $args = func_get_args();

    if(is_array($args))
    {
        if(count($args) > 1)
        {
            for($i = 0; $i < 2; $i++)
            {
                array_shift($args);
            }
        }
        else
        {
            array_shift($args);
        }
    }

    if(is_array($args) && count($args))
    {
        foreach($args as $arg)
        {
            $line = str_replace_first('%s', $arg, $line);
        }
    }

    if (!empty($id))
    {
        $line = '<label for="'.$id.'">'.$line."</label>";
    }

    return $line;
}

function str_replace_first($search_for, $replace_with, $in)
{
    $pos = strpos($in, $search_for);
    if($pos === false)
    {
        return $in;
    }
    else
    {
        return substr($in, 0, $pos) . $replace_with . substr($in, $pos + strlen($search_for), strlen($in));
    }
}

function get_lang()
{
    $CI =& get_instance();
    return $CI->lang->lang();
}

/* End of file MY_language_helper.php */
/* Location: ./application/helpers/MY_language_helper */
