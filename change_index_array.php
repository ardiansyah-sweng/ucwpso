<?php

function toMultidimensionalArray($file_name)
{
    $read = new ReadFile($file_name);
    $data = $read->dataset();

    $column_indexes = [0, 1, 2, 3, 4, 5, 6];
    $columns = ['simpleUC', 'averageUC', 'complexUC', 'uaw', 'tcf', 'ecf', 'actualEffort'];

    foreach ($data as $key => $val) {
        foreach (array_keys($val) as $subkey) {
            if ($subkey == $column_indexes[$subkey]) {
                $data[$key][$columns[$subkey]] = $data[$key][$subkey];
                unset($data[$key][$subkey]);
            }
        }
    }
    return $data;
}
