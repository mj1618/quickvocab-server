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
use App\ItWords;
use App\EsWords;
use App\FrWords;
use App\DeWords;

//trnsl.1.1.20160307T115612Z.028476d08a65d95d.a2bd8a81feaa0f2884ebc498c682dd40797534d1

function getRandomWord($npool,$lang='it'){

    if(in_array($lang,['it','de','fr','es'])==false){
        $lang='it';
    }
    $rs =  DB::select("select * from (select * from ".$lang."_words limit :npool ) as s order by rand() limit 1",['npool'=>$npool]);
    $word = strtolower($rs[0]->word);
    $id = $rs[0]->id;


    switch($lang){
        case 'it':
            $row = ItWords::find($id);
            break;
        case 'es':
            $row = EsWords::find($id);
            break;
        case 'fr':
            $row = FrWords::find($id);
            break;
        case 'de':
            $row = DeWords::find($id);
            break;
    }

    if($row->data !== null){
        return (array) json_decode($row->data);
    }


    $json = json_decode(file_get_contents('https://glosbe.com/gapi/translate?from='.$lang.'&dest=eng&format=json&phrase='.$word.'&pretty=true&tm=true'), true);

    $yanex = json_decode(file_get_contents('https://translate.yandex.net/api/v1.5/tr.json/translate?key=trnsl.1.1.20160307T115612Z.028476d08a65d95d.a2bd8a81feaa0f2884ebc498c682dd40797534d1&text='.$word.'&lang='.$lang.'-en'));

    $defs=[];
    $ms=[];
    $exs = [];

    if($yanex->text && strlen($yanex->text)>0)
        $defs[] = $yanex->text;

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
    $row->data = json_encode($r);
    $row->save();
    return $r;
}


$app->get('/api/word', function () use ($app) {
    $request = app(Request::class);
    $n = $request->input('nwords');
    $lang = $request->input('language','it');
    return getRandomWord($n,$lang);
});

// $app->get('/api/words', function () use ($app) {
//     $request = app(Request::class);
//     $lang = $request->input('language','it');
//     $n = $request->input('nwords');
//     $rs = [];

//     for($i=0;$i<$n;$i++){
//         $rs[] = getRandomWord($n,$lang);
//     }

//     return $rs;
// });


