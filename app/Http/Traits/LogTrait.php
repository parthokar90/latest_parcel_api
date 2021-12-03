<?php

namespace App\Http\Traits;

trait LogTrait {

    public function logCreate($ext,$value) {

        // log file create start
        $path_to_file = './logs/' .$ext. '.json';
        if (file_exists($path_to_file)) {
            $phoneCheckLogFailed = $value;
            $getContents = file_get_contents('./logs/' .$ext. '.json');
            $jsonDecode = json_decode($getContents,true);
            array_push($jsonDecode, $phoneCheckLogFailed);
            $data = json_encode($jsonDecode,JSON_PRETTY_PRINT);
            file_put_contents('./logs/' .$ext. '.json', $data);
        } else {
            $phoneCheckLogFailed["1"] = $value;
            $data = json_encode($phoneCheckLogFailed,JSON_PRETTY_PRINT);
            file_put_contents('./logs/' .$ext. '.json', $data);
        }
    }
}
