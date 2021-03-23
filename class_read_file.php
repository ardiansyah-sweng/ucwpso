<?php

/**
 * Class read file (.txt)
 * 
 * Input: file
 * Output: array
 * 
 * Usage example:
 *      $file_name = 'silhavy_dataset.txt';
 *      $read = new FileReader($file_name);
 *      $read->readFile();
 */
class FileReader
{
    public $file_name;

    function __construct($file_name)
    {
        $this->file_name = $file_name;
    }

    function readFile(){
        $raw_dataset = file($this->file_name);
        foreach($raw_dataset as $val){
            $ret[] = explode(",", $val);
        }
        return $ret;
    }
}