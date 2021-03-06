<?php

/**
* home.php, Home page controller
*
* Home class extends the MVC extController class
*
* This is the version 2.0 of documentation
* @author Tim Berfield <tim@zoomaway.com>
* @version 1.2
* @package WL
*/

/**
* Custom controller for Home page, extends Controller base class.
*
*/
class Home extends extController {
}

// ---------------------------------------------------------------------------------

/**
* Init controller
* @param $this - current WebApp Object
* @param "home" name of controller
*/

// clear old user data
$this->clearData();

// init controller and set page name
$myCtrl = new Home($this, "home");

// set the page title
$myCtrl->pagetitle = "_Core Home";

// add the html view - usually same name as the controller
$myCtrl->addContent($myCtrl->pagename);

// Route the action / execute the function
$myCtrl->routeAction('render');