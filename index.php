<?php
require 'vendor/autoload.php';


$input['terra'] =  file_get_contents("http://terra.com.br");
$input['uol'] =  file_get_contents("http://uol.com.br");
$input['globo'] =  file_get_contents("http://globo.com");

function analyze($input){
    
$txt = Ngram\Tool\Input\HtmlToText::get($input);
$txt = Ngram\Tool\Input\TextToArray::get($txt, ['split'=>Ngram\Tool\Input\TextToArray::SPLIT_BY_CR]);
$paragraphs = [];
foreach ($txt as $idx =>$line){
    $l = Ngram\Tool\Input\Sanitize::get($line,['by'=>0]);
    if (strlen($l)>2){
        $paragraphs[$idx]['txt']= $l;
        $w = new Ngram\Frequency\Word($l);
        $paragraphs[$idx]['words']= $w->extract(1);
    }
}
$wordList = [];
foreach ($paragraphs as $p ){
    foreach ($p['words'] as $words){
                $wordList[]= $words;
        }
    } 
    $frequency = Ngram\Tool\Ngram\Frequency::get($wordList);
$ffinal = [];
foreach ($frequency as $idx => $f){
    $ffinal[serialize($f[0])] = $f['count'];
}
arsort($ffinal);
$Q = ceil(count($ffinal)/4);
$range = [$Q,$Q+$Q*2];
$rangeData = [];
$i = 0 ;
foreach ($ffinal as $word => $count){

    if ($i <= $range[0]*2 /*&& $i <= $range[1]*/ )
        $rangeData[$word] = $count;
    $i++;
}
return $rangeData;
}

$input['terra'] = analyze($input['terra']);
$input['uol'] = analyze($input['uol']);
$input['globo'] = analyze($input['globo']);

$commonWords = array_uintersect_assoc($input['uol'],$input['terra'], "strcasecmp");
foreach($commonWords as $cwords=>$v){
    $words = unserialize($cwords);
    echo "
    Palavra: {$words[0]}
    ..Terra  : {$input['terra'][$cwords]} 
    ..UOL    : {$input['uol'][$cwords]}\n";
}