<?php
/**
* The Enormail API wrapper base class
* 
* @author Enormail
* @version 1.0
* @docs http://developers.enormail.eu/
*/
abstract class EM_Base {
    
    /**
	* Default response format
	*/
    public $format = 'json';
    
    /**
    * Constructor
    *
    * @access public
    * @param object $rest em_rest object
    * @return nill
    */
    public function __construct(em_rest $rest)
    {
        $this->rest = $rest;
    }
    
}