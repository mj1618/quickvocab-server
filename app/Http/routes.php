<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

use Illuminate\Http\Request;
function getRandomWord($npool){
    $rs =  DB::select("select * from (select * from words limit :npool ) as s order by rand() limit 1",['npool'=>$npool]);
    $word = $rs[0]->word;
    $json = json_decode(file_get_contents('https://glosbe.com/gapi/translate?from=it&dest=eng&format=json&phrase='.$word.'&pretty=true&tm=true'), true);

    $defs=[];
    $ms=[];
    $exs = [];

    foreach($json["tuc"] as $t){
        if(isset($t["phrase"])){
            $defs[] = $t["phrase"]["text"];
        }
        if(isset($t["meanings"])){
            foreach($t["meanings"] as $m){
                if($m["language"]=='en'){
                    $ms[] = $m["text"];
                }
            }
        }
    }

    foreach($json["examples"] as $e){
        $exs[] = [
            'first'=>strip_tags($e["first"]),
            'second'=>strip_tags($e["second"])
        ];
    }

    $r = [
        'word'=>$word,
        'translations'=>$defs,
        'meanings'=>$ms,
        'examples'=>$exs
    ];
    \Cache::put($word,$r);
    return $r;
}


$app->get('/api/word', function () use ($app) {
    $request = app(Request::class);
    $npool = $request->input('npool');
    return getRandomWord($npool);
});

$app->get('/api/words', function () use ($app) {
    $request = app(Request::class);
    $npool = $request->input('npool');
    $n = $request->input('nwords');
    $rs = [];

    for($i=0;$i<$n;$i++){
        $rs[] = getRandomWord($npool);
    }

    return $rs;
});


