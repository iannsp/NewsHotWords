<?php
require 'vendor/autoload.php';


$input['terra'] =  file_get_contents("http://terra.com.br");
$input['uol'] =  file_get_contents("http://uol.com.br");

function analyze($input, $portal){
$blacklist = $lines = file("blacklist.txt", FILE_IGNORE_NEW_LINES);
    
$txt = Ngram\Tool\Input\HtmlToText::get($input);
$txt = Ngram\Tool\Input\TextToArray::get($txt, ['split'=>Ngram\Tool\Input\TextToArray::SPLIT_BY_CR]);
$paragraphs = [];
foreach ($txt as $idx =>$line){
    $l = Ngram\Tool\Input\Sanitize::get($line,['by'=>0]);
    $l = Ngram\Tool\Input\BlackList::get($l, ['words'=>$blacklist]);
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
//    if ($i <= $range[0] && $i <= $range[1] )
        $rangeData[$word] = $count;
//    $i++;
}
return $rangeData;
}
//processar conteudos
foreach ($input as $name => $content){
    $t = fopen("{$name}.txt","w");
    fwrite($t, $content);
    fclose($t);
    $input[$name] = analyze($content,$name);
}
$commons=['words'=>[],'portals'=>[]];

foreach ($input['terra'] as $idx => $k){
    if (array_key_exists($idx, $input['uol']))
    {
        $words = implode(" ",unserialize($idx));
        if(strlen($words)<=2)
            continue;
        $commons['words'][]= $words;
        $commons['portals']['terra'][]= (int)$input['terra'][$idx];
        $commons['portals']['uol'][]= (int)$input['uol'][$idx];
        $commons['total'][$words]= (int)$input['terra'][$idx] + (int)$input['uol'][$idx];
    }
}


$graphData = [
  "labels"=>array_values($commons['words']),
  "datasets"=>[
      [ 
          "label"=>"uol",
          "fillColor"=>"rgba(237, 121, 43, 0.4)",
          "highlightFill"=> "rgba(220,220,220,0.75)",
          "highlightStroke"=> "rgba(220,220,220,1)",
          "data"=>array_values($commons['portals']['uol'])
       ],
       [ 
           "label"=>"Terra",
           "fillColor"=>"rgba(232, 200, 96, 0.4)",
           "highlightFill"=> "rgba(220,220,220,0.75)",
           "highlightStroke"=> "rgba(220,220,220,1)",
           "data"=>array_values($commons['portals']['terra'])
        ]
       
  ] 
];
$t = fopen("./public/stat.json","w");
fwrite($t, "var stat=".json_encode($graphData));
fclose($t);
