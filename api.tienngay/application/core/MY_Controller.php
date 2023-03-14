<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class MY_Controller extends CI_Controller
{

    public $user;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Gen view với layout có head và footer
     *
     * @param string $view
     * @param array $data
     * @return load->view
     */
    public function view($view, $data = array())
    {
    }

    /**
     * Trả về response json
     *
     * @param string $view
     * @param array $data
     * @return json
     */
    public function json($data = array(), $headStatus = 200){
    }
}
