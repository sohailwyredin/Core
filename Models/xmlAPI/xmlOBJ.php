<?php
/**
* xmlOBJ.php, base bpXML XML/API class
* 
* xmlOBJ.php contains the xmlOBJ class definition 
* 
* This is the version 1.0 of documentation
* @author Tim Berfield <tberfield@prodasol.com>
* @version 1.0
* @package FireBalancer
*/
       
/**
* xmlOBJ is the base XML Object class
* It is extended by the xmlAPI class
* @package Models
* @subpackage bpXML
*/

class xmlOBJ {

	protected $results = array();
	protected $userData;
	protected $userid;
	protected $password;
    protected $transmit = true;
	
    var $xmit;
	var $fieldsArray    = array();
	var $row			= array();
	var $errors			= array();
	var $sessionid;
	var $xml;
	var $reqDoc;
    var $server_url;
    var $apiname;
    var $api_ver;
    var $api_pass;

    /** @var string $repDoc xml response doc from api call or forced */
    var $respDoc;

	var $Status;
	var $rowCount	= 0;
	var $rowIndex	= -1;
    var $ExecTime;

	var $eof = true;
	var $bof = true;
	
	//-------------------------------------------------------------------------------------------
	
	function __construct($fields = null, $xmlString=null) {
        if ($this->fieldsArray == null)
		$this->fieldsArray = $fields;
		$this->reqDoc = $xmlString;
        
	}

	//-------------------------------------------------------------------------------------------

	public function send() {

	}
	
	//-------------------------------------------------------------------------------------------

	public function display() {
        if (is_object($this->xmit)) {
            $this->xmit->displayRequest();
            $this->xmit->displayResponse();
            print_r ($this->xmit->errors);
        } elseif (!$this->xmit) {
            die ($this->xml->asXML());
        }
        
	}

    //-------------------------------------------------------------------------------------------

    public function createFieldsArray() {

        $this->fieldsArray = array();
        $xml = simplexml_load_string( trim($this->respDoc),"SimpleXMLIterator");
        
        if (isset($xml->Rows[0]->Row[$this->rowIndex])) {
            $nodes = $xml->Rows[0]->Row[$this->rowIndex];

            // put the nodes onto the array of fields
            foreach ($nodes as $key=>$value) {
                array_push($this->fieldsArray,$key);            
            }
        }
    }
	
	//-------------------------------------------------------------------------------------------
	
    public function setDoc($doc) {
        $this->respDoc = $doc;
        $this->transmit = false;
        $this->parseStatus($doc);
        $this->next(); // move to the first record
    }
    
    //-------------------------------------------------------------------------------------------

	public function parseStatus() {

        if ($this->xml = @simplexml_load_string(trim($this->respDoc),"SimpleXMLIterator")) {
            
			$this->Status = (string)$this->xml->Status;
            $this->StartTime = (string)$this->xml->Execution->StartTime;
            $this->EndTime = (string)$this->xml->Execution->EndTime;
            
            // catch user defined errors
            if (isset($this->xmit->errors)) {
                $this->errors = $this->xmit->errors;                
            }
			
			// set the max number of rows for next / prior / last reference
            $this->rowCount = count($this->xml->xpath('//Rows/Row'));
            
            // make this is not a single, empty row
            if ( $this->rowCount == 1 && count($this->xml->Rows[0]->Row[0]->children()) == 0 ){
                $this->rowCount = 0;
            }
            
			// loop the errors
			foreach ($this->xml->xpath('//Error') as $error) {
				array_push($this->errors, (string)$error);
			}

		}		
		
		if ($this->Status == "") {
			$this->Status = "Error"; 
		}
	}
	
	//-------------------------------------------------------------------------------------------

	public function parseRow() {
		//used by child function to fill field array 
	}
	
	//-------------------------------------------------------------------------------------------

    private function recurseResults($name, $obj, &$out, $counter = 0 ) {

        foreach ($obj->children() as $child) {
        
            $childName = $child->getName();
            
            // use a counter only if there are multiple sub rows
            if ( count($child->children()) > 0 ) {
                
                // if the next child is a Row then set the counter to 0
                if ($child->Row) {
                    $counter = 0;
                }
                
                $this->recurseResults( $childName, $child, $out[$childName][$counter]);    
                $counter ++;
            } 

            // recurse any child nodes
            else {
                $out[$childName] = (string)$child;
            }
        }
        
    }
	
    //-------------------------------------------------------------------------------------------

	public function parseResults() {
		
        $this->createFieldsArray();
        $this->row = array();
                              
        // loop each field in the fields array
    	foreach ($this->fieldsArray as $field) {
            if ( $this->xml->Rows[0]->Row[$this->rowIndex]->$field->children() )  {
                $this->recurseResults( $field, $this->xml->Rows[0]->Row[$this->rowIndex]->$field, $this->row[$field] );    
            } 
            else {
                $this->row[$field] = (string)$this->xml->Rows[0]->Row[$this->rowIndex]->$field;
            }
		}
	}
		
	//-------------------------------------------------------------------------------------------
	
	public function next() {
		if ($this->rowIndex < $this->rowCount - 1) {
			++ $this->rowIndex;
		 	$this->eof = false;
		 	$this->bof = false;
			$this->parseResults();
		}
		else {
			$this->eof = true;
		}
	}
	
	//-------------------------------------------------------------------------------------------

	public function prior() {
		if ($this->rowIndex > 0) {
			-- $this->rowIndex;
		 	$this->eof = false;
		 	$this->bof = false;
			$this->parseResults();
		}
		else {
			$this->bof = true;
		}
	}

	//-------------------------------------------------------------------------------------------

    public function first() {
		$this->rowIndex = 0;
	 	$this->eof = false;
	 	$this->bof = true;
		$this->parseResults();
	}

	//-------------------------------------------------------------------------------------------

    public function last() {
		$this->rowIndex = $this->rowCount - 1;
	 	$this->eof = true;
	 	$this->bof = false;
		$this->parseResults();
	}
	
	//-------------------------------------------------------------------------------------------

    public function __get($name) {

		switch ($name) {
		    case "rowIndex":
			    return($this->rowIndex);
			    break;
		    
		    case "rowCount":
			    return($this->rowCount);
			    break;
	    
		    case "Status":
			    return($this->Status);
			    break;

            case "ExecTime":
                return($this->ExecTime);
                break;
	    
		    case "Errors":
			    return($this->errors + $this->xmit->errors);
			    break;
	    
		    default:
			    if (array_key_exists($name, $this->row)) {
                    return($this->row[$name]);
			    }
			    break;
		}
	}
	
	//-------------------------------------------------------------------------------------------
}