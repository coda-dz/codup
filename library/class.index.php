<?php

class index extends Codup\main
{

    static function getInstance ()
    {
        return new self();
    }

    function index ()
    {
        
        $this->view->text = "here's some text!";
    }

    function action ()
    {
        $this->stack("default:" . __method__);
    
    }
}

