<?php
require 'vendor/autoload.php';


$input['terra'] = ['url'=>"http://terra.com.br","encondig"=>'utf-8']; 
$input['uol']   = ['url'=>"http://uol.com.br",'encondig'=>'utf-8'];
$input['folha'] = ['url'=>"http://www.folha.uol.com.br/",'encondig'=>'windows-1252'];
//$input['estadao'] = ['url'=>"http://topicos.estadao.com.br/rss",'encondig'=>'utf-8'];

function analyze($portal, $config, $ngramSize){
    $enc = $config['encondig'];
    $stream = fopen($config['url'], 'r');
    if($enc!='utf-8'){
        $input = mb_convert_encoding(stream_get_contents($stream),"UTF-8", $enc);
        
    }
    else
        $input = stream_get_contents($stream);
$blacklist = $lines = file("blacklist_{$ngramSize}.txt", FILE_IGNORE_NEW_LINES);
    
$txt = Ngram\Tool\Input\HtmlToText::get($input);
$txt = Ngram\Tool\Input\TextToArray::get($txt, ['split'=>Ngram\Tool\Input\TextToArray::SPLIT_BY_CR]);
$paragraphs = [];
foreach ($txt as $idx =>$line){
    $l = Ngram\Tool\Input\Sanitize::get($line,['by'=>0,'normalize'=>Ngram\Tool\Input\Sanitize::NORMALIZETOLOWER]);

    if (strlen($l)>2){
        $paragraphs[$idx]['txt']= $l;
        $w = new Ngram\Frequency\Word($l,$blacklist);
        $paragraphs[$idx]['words']= $w->extract($ngramSize);
        $pAux =[]; 
        foreach($paragraphs[$idx]['words'] as $idxAux=>$wordAux){
            if(strlen($wordAux)>2){
                $pAux[] = trim($wordAux);
            }
        }
        $paragraphs[$idx]['words'] = $pAux;
    }
}
//die();
$wordList = [];
foreach ($paragraphs as $p ){
    $wordList = array_merge($wordList, $p['words']);
}

$frequency = Ngram\Tool\Ngram\Frequency::get($wordList);
$ffinal = $frequency;
return $ffinal;
}

//processar conteudos
$output = [];
function extractData($output){
    
$input = $output;
$commons=['words'=>[],'portals'=>[],'count'=>[]];
foreach ($input as $channel=> $contentChannel){
    $commons['portals'][$channel] = $contentChannel;
    foreach ($contentChannel as $idx => $k){
        $words = $idx;
        if(strlen($words)<=2)
            continue;
        $commons['words'][] = $words; 
    }
}
foreach ($commons['words'] as $word => $k){
    if (strlen($word)<3){
        foreach($commons['portals'] as $name =>$values){
            unset($commons['portals'][$name][$word]);
        }
    }
}

$max = 0;
foreach($commons['portals'] as $name =>$values){
    asort($commons['portals'][$name]);
}

foreach ($commons['words'] as $word){
    foreach($commons['portals'] as $channel=> $inputTerms){
        if (!array_key_exists($word, $inputTerms))
            $commons['portals'][$channel][$word] = 0;
    }
    if (array_key_exists($word,$commons['count']))
        $commons['count'][$word]+= $commons['portals'][$channel][$word];
    else
        $commons['count'][$word]= $commons['portals'][$channel][$word];
    if ($commons['count'][$word] > $max)
        $max = $commons['count'][$word];
}

foreach($commons['portals'] as $name =>$values){
    asort($commons['portals'][$name]);
}
$frequency = $commons['count'];

asort($commons['count']);
$Qr = ceil($max/4);
$Q = [$Qr, $Qr*2, $Qr*3,$Qr*4];
var_dump($Q);
$commonsCopy = $commons;
asort($commons['words']);
foreach ($commons['words'] as $word => $k){
    $ktotal = $commons['count'][$k];
    if (strlen($k)<3 || $ktotal<=1){
        foreach($commons['portals'] as $name =>$values){
            unset($commonsCopy['portals'][$name][$k]);
        }
    }
    $commons = $commonsCopy;
    if ((($ktotal >=2 && $ktotal<= 5)|| $ktotal>$Q[2] )&& $k!='água' ){
            foreach($commons['portals'] as $name =>$values){
                unset($commonsCopy['portals'][$name][$k]);
            }
    }
}
    return $commonsCopy;
}

foreach ($input as $name => $content){
    $output[$name] = analyze($name,$content,2);
}
$outputProcessed = extractData($output);
foreach ($input as $name => $content){
    $output[$name] =analyze($name,$content,1);
}
$outputProcessed2 = extractData($output);
$commons = [];
$commons['words']= array_merge($outputProcessed['words'],$outputProcessed2['words']);
$commons['portals']['terra']= array_merge($outputProcessed['portals']['terra'],$outputProcessed2['portals']['terra']);
$commons['portals']['uol']= array_merge($outputProcessed['portals']['uol'],$outputProcessed2['portals']['uol']);
$commons['portals']['folha']= array_merge($outputProcessed['portals']['folha'],$outputProcessed2['portals']['folha']);
$commons['count'] = array_merge($outputProcessed['count'],$outputProcessed2['count']);

$graphData = [
  "labels"=>array_keys($commons['portals']['uol']),
  "datasets"=>[
      [ 
          "label"=>"uol",
          "fillColor"=>"rgba(215, 40, 40, 0.5)",
          "highlightFill"=> "rgba(220,220,220,0.75)",
          "highlightStroke"=> "rgba(220,220,220,1)",
          "data"=>array_values($commons['portals']['uol'])
       ],
       [ 
           "label"=>"Terra",
           "fillColor"=>"rgba(57, 188, 44, 0.5)",
           "highlightFill"=> "rgba(220,220,220,0.75)",
           "highlightStroke"=> "rgba(220,220,220,1)",
           "data"=>array_values($commons['portals']['terra'])
        ],
       [ 
           "label"=>"folha",
           "fillColor"=>"rgba(57,188,255, 0.5)",
           "highlightFill"=> "rgba(220,220,220,0.75)",
           "highlightStroke"=> "rgba(220,220,220,1)",
           "data"=>array_values($commons['portals']['folha'])
        ]
  ] 
];
$t = fopen("./public/stat.json","w");
fwrite($t, "var stat=".json_encode($graphData));
fclose($t);
