<?php
$year_now = date("Y");
$month_now = date("m");

$period = array(
    array("month" => "01", "prev_year" => ($year_now-1), "prev_quarter"=>3),"curr_year"=>($year_now-1), "curr_quarter"=>4,
    array("month" => "02", "prev_year" => ($year_now-1), "prev_quarter"=>3, "curr_year"=>($year_now-1), "curr_quarter"=>4),
    array("month" => "03", "prev_year" => ($year_now-1), "prev_quarter"=>3, "curr_year"=>($year_now-1), "curr_quarter"=>4),
    array("month" => "04", "prev_year" => ($year_now-1), "prev_quarter"=>4, "curr_year"=>$year_now, "curr_quarter"=>1),
    array("month" => "05", "prev_year" => $year_now, "prev_quarter"=>1, "curr_year"=>$year_now, "curr_quarter"=>2),
    array("month" => "06", "prev_year" => $year_now, "prev_quarter"=>1, "curr_year"=>$year_now, "curr_quarter"=>2),
    array("month" => "07", "prev_year" => $year_now, "prev_quarter"=>2, "curr_year"=>$year_now, "curr_quarter"=>3),
    array("month" => "08", "prev_year" => $year_now, "prev_quarter"=>2, "curr_year"=>$year_now, "curr_quarter"=>3),
    array("month" => "09", "prev_year" => $year_now, "prev_quarter"=>2, "curr_year"=>$year_now, "curr_quarter"=>3),
    array("month" => "10", "prev_year" => $year_now, "prev_quarter"=>2, "curr_year"=>$year_now, "curr_quarter"=>3),
    array("month" => "11", "prev_year" => $year_now, "prev_quarter"=>2, "curr_year"=>$year_now, "curr_quarter"=>3),
    array("month" => "12", "prev_year" => $year_now, "prev_quarter"=>2, "curr_year"=>$year_now, "curr_quarter"=>3)
);

if ($year_now == 2021){
    print TRUE;
} else {
    print FALSE;
}

//print_r($period);

// foreach($period as $key=>$val){
//     if ($val['month'] == date("m")){
//         print_r($val);
//     }
// }