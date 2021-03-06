<?php
          
namespace debug;

//-------------------------------------------------------

/**
* class to output session debug info
* $this->WebApp is passed
* 
*/
class Debug {

    /** @var string $version - Current version */
    private $version = 'v1.0';
    
    /** @var boolean $display_server - True to show server info */
    public $display_server = true;
    
    /** @var boolean $display_const - True to show defined constants */
    public $display_const = true;

    /** @var mixed $data - Working array of session data */
    public $data = array();

    /** @var mixed $sections - List of default data catagories */
    public $sections = array(   'Posted Form Data (postVars) : $controller->{fieldname}' => 'postVars',
                                'Client Contract' => 'contract',
                                'Email Templates' => 'templates');
    
    //-------------------------------------------------------

    /**
    * Initiate debug class
    * 
    * @param mixed $data
    */
    public function __construct($data) {
        $this->data = (object)$data; 
    }

    //-------------------------------------------------------

    /**
    * Add a custom catagory to display
    * 
    * @param mixed $title - Display title of section
    * @param mixed $section - Array name of data
    * @param mixed $data - Data to display
    */
    public function add($title, $section, $data) {
        $this->sections[$title] = $section;
        $this->data->{$section} = $data;
    }

    //-------------------------------------------------------

    /**
    * Display the debug info
    * Loop the arrays of data, format and print them
    * 
    */
    public function display() {
        // render a title
        print "<div style='font-size:.8em; font-family:Tahoma; color:#333;'>debug module $this->version<h2>Debug Info:</h2>"; // header

        // display constants defined in index.php
        $out = get_defined_constants(true);
        $this->displayArray('Constants (index.php defined constants)', $out['user']);

        // display config values set in index
        $this->displayArray('Website Configuration (index.php $config)', $this->data->config);

        // display app values stored in WebApp object
        $this->displayArray('WebApp Object', $this->data);

        // display values stored directly on the session, such as persistent fields
        $this->displayArray('Session Variables', $_SESSION);

        // loop the sections
        foreach ($this->sections as $title => $section) {
            if (is_object($section)) $section = (array)$section; // convert object to array
            if (is_array($section)) {
                $this->displayArray($title, $section);
            } else {
                if (isset($this->data->{$section})) {
                    $this->displayArray($title, $this->data->{$section});
                } else {
                    print "<br /><div style='padding:8px; border:1px solid silver; font-size:1.2em; background-color:#eee;'>$title</div><br />";
                    print "-- no data --<br />";
                }
            }
        }
        
        // display $_SERVER array
        if ($this->display_server) {
            $this->displayArray('PHP $_SERVER', $_SERVER);
        }

        print "</div>"; // close the html
    }

    //-------------------------------------------------------

    /**
     * @param $title
     * @param $array
     */
    private function displayArray($title, $array) {
        print "<br /><div style='padding:8px; border:1px solid silver; font-size:1.2em; background-color:#eee;'>$title</div><br />";
        foreach ( $array as $field => $val) {
            print "<b>$field:</b> ";
            if (is_array($val)) {
                var_dump($val);
                print "<br />";
            }
            else {
                print "$val<br />";
            }
        }
    }

    //-------------------------------------------------------
}
