<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AlgorithmWordsController extends Controller
{
    private $ccv = array();
    private $cvv = array();
    private $plural = array();
    private $diphthong = array();
    private $wordsComplete = array();

    /**
     * algorithmWords constructor.
     */
    public function __construct()
    {
        $this->dictionary ();
    }

    /**
     * @param string $stringLetters
     * @param int $limitWord
     * @param bool $singular
     * @param bool $wordStartVocal
     */
    public function findWords(string $stringLetters, int $limitWord,bool $singular,bool $wordStartVocal)
    {
        $letters = strtolower($stringLetters);
        preg_match_all('/[aeiou]/i',$letters,$vocals);
        $vocals = $vocals[0];
        $consonants=str_split ($this->consonants ($letters),1);
        $cv=$this->consonantVocal ($consonants,$vocals);
        $ccv=$this->doubleConsonantVocal ($consonants,$vocals);
        //3-2-2
        $this->combinationWords ($limitWord,$ccv,$cv,$consonants,$singular,$wordStartVocal,$vocals);
        //2-3-2
        $arrOne=$this->muteArray ($ccv,$cv,0);
        $this->combinationWords ($limitWord,$cv,$arrOne,$consonants,$singular,$wordStartVocal,$vocals);
        //2-2-3
        $arrTwo=$this->muteArray ($ccv,$cv,1);
        $this->combinationWords ($limitWord,$cv,$arrTwo,$consonants,$singular,$wordStartVocal,$vocals);
        //2-2-2
        $this->combinationWords ($limitWord,$cv,$cv,$consonants,$singular,$wordStartVocal,$vocals);

    }
    private function completeWord(string $wordIncomplete,bool $singular,bool $wordStartVocal,array $vocals,int $limitWord)
    {
        $newWordOne='';
        if(!$singular){
            $newWordOne.= $wordIncomplete.'s';
        }
        if($limitWord ==  strlen ($newWordOne)){
            $newPlural=$this->validateWord(str_split($wordIncomplete,1),$this->plural,false);
            if(!$newPlural[0]){
                array_push ($this->wordsComplete,$newWordOne);
            }
        }
        if($wordStartVocal) {
            $newVocals=$this->validateWord($vocals,str_split($wordIncomplete,1),true);
            for ($i = 0; $i < count ($newVocals[1]); $i++) {
                $newWordTwo = $newVocals[1][$i].$wordIncomplete;
                if($limitWord ==  strlen ($newWordTwo)){
                    array_push ($this->wordsComplete,$newWordTwo);
                }
            }
        }
    }

    /**
     * @param array $arrayOne
     * @param array $arrayTwo
     * @param int $order
     * @return array
     */
    private function muteArray(array $arrayOne, array $arrayTwo, int $order)
    {
        $newMute=array();
        for ($i = 0; $i < count ($arrayTwo); $i++) {
            switch ($order){
                case 0:
                    if(isset($arrayOne[$i])){
                        $newMute[]=$arrayOne[$i];
                    }
                    if(isset($arrayTwo[$i])){
                        $newMute[] = $arrayTwo[$i];
                    }
                    break;
                case 1:
                    if(isset($arrayTwo[$i])){
                        $newMute[] = $arrayTwo[$i];
                    }
                    if(isset($arrayOne[$i])){
                        $newMute[]=$arrayOne[$i];
                    }
                    break;
            }
        }
        return $newMute;
    }

    /**
     * @param int $limitWord
     * @param array $wordsOne
     * @param array $wordsTwo
     * @param array $consonants
     * @param bool $singular
     * @param bool $wordStartVocal
     * @param array $vocals
     */
    private function combinationWords(int $limitWord, array $wordsOne, array $wordsTwo, array $consonants, bool $singular, bool$wordStartVocal, array $vocals)
    {
        for ($i = 0; $i < count ($wordsOne); $i++) {
            $countInside=count ($wordsOne[$i])-2;
            $lastPosition=count ($wordsOne[$i])-1;
            for ($j = 0; $j < $countInside; $j++) {
                for ($k = 0; $k < count ($wordsTwo); $k++) {
                    $m=1;
                    for ($l = $k+1; $l < count ($wordsTwo); $l++) {
                        if($k!=$l) {
                            $validateWordOne = $this->validateWord ($consonants, $wordsOne[$i][$lastPosition], false);
                            $validateWordTwo = $this->validateWord ($validateWordOne[1], $wordsTwo[$k][$lastPosition], false);
                            for($q=0;$q < count ($wordsTwo);$q++) {
                                for ($p = 0; $p <= $countInside; $p++) {
                                    $validateWordThree = $this->validateWord ($validateWordTwo[1], $wordsTwo[$q][$lastPosition], false);
                                    if ($validateWordTwo[0] && $validateWordThree[0]) {
                                        if ($j != $m && $j != $p && $m != $p) {
                                            $str = $wordsOne[$i][$j] . $wordsTwo[$k][$m] . $wordsTwo[$q][$p];
                                            if (strlen ($str) == $limitWord) {
                                                if (!in_array ($str, $this->wordsComplete)) {
                                                    array_push ($this->wordsComplete, $str);
                                                }
                                            } else {
                                                $this->completeWord ($str, $singular, $wordStartVocal, $vocals, $limitWord);
                                            }
                                        }
                                    }
                                }
                            }
                            if ($m == $countInside) {
                                $m = 0;
                            } else {
                                $m++;
                            }
                        }
                    }
                }
            }

        }
    }

    /**
     * @param array $arrayOne
     * @param array $arrayTwo
     * @param bool $validate
     * @return array
     */
    private function validateWord(array $arrayOne, array $arrayTwo, bool $validate): array
    {
        $response[0] = false;
        $newConsonants = $arrayOne;
        $p=0;
        for ($l = 0; $l < count ($arrayTwo); $l++) {
            if (in_array ($arrayTwo[$l],$newConsonants)) {
                $position = array_search ($arrayTwo[$l], $newConsonants);
                unset($newConsonants[$position]);
                $newConsonants=$array = array_values($newConsonants);
                $p++;
            } else {
                if(!$validate) {
                    $response[1] = $arrayOne;
                    break;
                }
            }
        }
        if(!$validate) {
            if($p == count ($arrayTwo)) {
                $response[0] = true;
                $response[1] = $newConsonants;
            }
        }else{
            $response[0] = true;
            $response[1] = $newConsonants;
        }
        return $response;

    }

    /**
     * @param array $consonants
     * @param array $vocals
     * @return array
     */
    private function doubleConsonantVocal(array $consonants,array $vocals):array
    {
        $doubleConsonantVocal = array();
        $m=0;
        for ($i=0; $i < count($consonants); $i++) {
            for ($x=0; $x < count($consonants); $x++) {
                if($i!=$x) {
                    $cc = $consonants[$i] . $consonants[$x];
                    if (in_array ($cc, $this->ccv)) {
                        $temp = array();
                        $o=0;
                        for ($j = 0; $j < count ($vocals); $j++) {
                            $temp[$o] = $cc . $vocals[$j];
                            $o++;
                        }
                        $temp[$o][] = $consonants[$i];
                        $temp[$o][] = $consonants[$x];
                        $doubleConsonantVocal[$m] = $temp;
                        $m++;
                    }
                }
            }
        }
        return $doubleConsonantVocal;
    }

    /**
     * @param array $consonants
     * @param array $vocals
     * @return array
     */
    private function consonantVocal(array $consonants, array $vocals):array
    {
        $consonantVocal = array();
        for ($j=0; $j < count($consonants); $j++){
            for($x=0; $x < count($vocals); $x++){
                $consonantVocal[$j][]=$consonants[$j].$vocals[$x];
            }
            $consonantVocal[$j][][]=$consonants[$j];
        }
        return $consonantVocal;
    }

    /**
     * @param string $stringLetters
     * @return string
     */
    private function consonants(string $stringLetters):string
    {
        $onlyConsonants = '';
        for ($i = 0; $i < strlen ($stringLetters); $i++) {
            if (!in_array($stringLetters[$i], ["a", "e", "i", "o", "u"])) {
                $onlyConsonants.=$stringLetters[$i];
            }
        }
        return $onlyConsonants;
    }
    private function dictionary(){
        $this->ccv= [
            'br','cc','cl','ch','cr','dr','fr','fl','gr','gl','ll','pr','pl','rr','tr'
        ];
        $this->cvv=[
            'qu','gu'
        ];
        $this->plural = [
            's'
        ];
        $this->diphthong =[
            'ia','ie','io','ue','uo','au','ai','eu','ei','oi'
        ];
    }

    /**
     * @return string
     */
    public function getWordsComplete(): string
    {
        return json_encode ($this->wordsComplete);
    }
    public function index(){
        $string = 'TJEUINGRTSDA';
        $countLetters = 7;
        $singular = false;
        $wordStartVocal = true;
        $this->findWords ($string,$countLetters,$singular,$wordStartVocal);
        $wordsComplete=$this->getWordsComplete ();
        return view('welcome',compact('wordsComplete'));
    }
}
