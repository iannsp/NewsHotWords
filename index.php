<?php
require 'vendor/autoload.php';


$input['terra'] =  file_get_contents("http://terra.com.br");
$input['uol'] =  file_get_contents("http://uol.com.br");

function analyze($input){
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
    $input[$name] = analyze($content);
}
$commons=[];
foreach ($input['terra'] as $idx => $k){
    if (array_key_exists($idx, $input['uol']))
    {
        $commons[$idx]['terra']= $input['terra'][$idx];
        $commons[$idx]['uol']= $input['uol'][$idx];
    }
}
//    printf("%20s%10s%10s\n",    'PALAVRA','Terra','UOL');
$totalCount = [];
$max = 0;
foreach($commons as $word=>$k){
    $words = unserialize($word);
    if (strlen($words[0])>3){
        $word = $words[0];
        $terra = $k['terra'];
        $uol = $k['uol'];
        $totalCount[$word]=((int)$terra+ (int)$uol);
        if ($totalCount[$word]> $max){
            $max = $totalCount[$word];
        }
//        printf("%20s%10s%10s\n",    $word,$terra,$uol);
    }
}
asort($totalCount);
/*
var_dump($max);
var_dump($totalCount);
*/

$graphData = [
  "labels"=>array_keys($totalCount),
  "datasets"=>[
      [ 
          "label"=>"Frequencia",
          "fillColor"=>"rgba(255, 33, 44, 0.9)",
          "highlightFill"=> "rgba(220,220,220,0.75)",
          "highlightStroke"=> "rgba(220,220,220,1)",
          "data"=>array_values($totalCount)
       ]
  ] 
];
$t = fopen("./public/stat.json","w");
fwrite($t, "var stat=".json_encode($graphData));
fclose($t);
