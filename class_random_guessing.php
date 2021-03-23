<?php
include "class_read_file.php";

/**
 * Class Random Guessing (P0)
 * 
 * @Input: file_name, column_index
 * @Output: array[actual_effort, predicted_P0, ae_P0]
 * 
 * Usage example:
 *  RandomGuess::predictP0($file_name, 6);
 * 
 */
class RandomGuess
{
    function predictP0($file_name, $index_column)
    {
        $run_times = 1000;
        $read = new ReadFile($file_name);
        $data = $read->dataset($file_name);
        
        foreach ($data as $key => $val) {
            $actual_effort = $val[$index_column];
            $temp = $data;
            unset($data[$key]);

            for ($i = 0; $i <= $run_times - 1; $i++) {
                $indexRandom = array_rand($data);
                $absolute_error[] = abs(floatval($data[$indexRandom][$index_column]) - floatval($actual_effort));
            }

            $mae = array_sum($absolute_error) / $run_times;
            $ret[$key][] = $actual_effort;
            $ret[$key][] = floatval($actual_effort) - $mae;            
            $ret[$key][] = $mae;
            $data = $temp;
            $absolute_error = [];
        }
        return $ret;
    }
}