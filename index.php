<!DOCTYPE HTML>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>
<body>
<?
// парсим авито
$count=0; // кол-во подлитых объявлений
// https://www.avito.ru/tomsk/nedvizhimost
$spis_city=array(1711=>'rostov-na-donu',2188=>'tomsk'); // здесь указываете все города, которые Вас интересуют
// здесь указываете интересующие Вас рубрики
$spis_vid=array(1=>'kvartiry', 19=>'doma_dachi_kottedzhi', 2=>'kommercheskaya_nedvizhimost',12=>'komnaty',3=>'zemelnye_uchastki');
$spis_act=array(1=>'prodam',2=>'sdam');
foreach($spis_city as $city=>$city_url){
    foreach($spis_vid as $vid=>$vid_name){
        echo "<h3>".$vid_name."</h3>";
        foreach($spis_act as $act=>$act_name){
            echo "<h4>".$act_name."</h4>";
            for($p=0;$p<10;$p++){ // иду по первым 10-ти страницам
                $root="/".$city_url."/".$vid_name."/".$act_name.($p?'?p='.$p:''); // https://m.avito.ru/rostov-na-donu/kvartiry/prodam?p=2
                $objs=Avito::get($root);// вторым необязательным параметром передаете массив параметров, например:
                                        // array('agency'=>0) если вы не хотите видеть объявления агентств
                if(is_null($objs))break; // объявления закончились
                if($objs)foreach($objs as $id=>&$obj){
                    if(empty($obj['tel']))continue; // не будет, если это агенство и указано агенства не подливать
                    $obj['city']=$city;
                    $obj['act']=$act;
                    $obj['parent']=$id; // id объекта у донора
                    $obj['cat']=( empty($obj['agency']) ? 1 : 2 );
                    if($vid==1){
                        if(stripos($obj['cost'],'за сутки')!==false || stripos($obj['comment'],'посуточно')!==false){// kvartiry_posutochno
                            $obj['vid']=5;
                            $obj['cost']=trim(str_ireplace('за сутки','',$obj['cost']));
                            $obj['name']=trim(str_ireplace('посуточно','',$obj['name']));
                        }elseif(($komnat=intval($obj['name'])) >0)$obj['vid']=12+min(6,$komnat);
                        elseif(stripos($obj['name'], 'студия')!==false) $obj['vid']=11;
                        elseif(stripos($obj['name'], 'комната')!==false) $obj['vid']=12;
                    }else $obj['vid']=$vid;
                    if($act==1)$obj['name']="Продам ".$obj['name'];
                    elseif($act==2)$obj['name']="Сдам ".$obj['name'];
                    if(!empty($obj['agency'])){
                        if(stripos($obj['comment'],'собственник')!==false) {
                            $obj['comment']=str_ireplace('собственник','',$obj['comment']);
                        }
                        $obj['comment'].="\n".$obj['agency'];
                    }
                    if(!empty($obj['person'])) $obj['fullname']=$obj['person'];
                    if(empty($obj['address'])&&!empty($obj['district'])) $obj['address']=$obj['district'];
                    if(!empty($obj['address'])){
                        $obj['address']=str_ireplace(array('собственник','!'),'',$obj['address']);
                    }
                    echo "<br><a href='".$obj['link']."'>".$obj['name']."</a>, ".$obj['address'].", стоимость: ".$obj['cost'].
                        (empty($obj['latitude'])?'':", GPS:".$obj['latitude'].",".$obj['longitude']) .
                        (empty($obj['tel'])?'':", телефон: ".$obj['tel']).
                        "<br>".$obj['comment'];
                    //var_export($obj);
                    $count++;
                    echo "<br> картинки:";
                    if(!empty($obj['images']))foreach($obj['images'] as $i => $link){
                        echo "<br><a href='".$link."' target=_blank>".$link."</a>";
                    }
                    if($obj['date'] < ($t=date('Y-m-d h:i',strtotime('-1 hour'))) ){  // подлив за один час
                        echo ("<br>подливаю объявления не старше ".$t);
                        $p=100;
                        break;
                    }
                }
            }
        }
    }
} // city

class Avito {
    /** чтение и разбор страницы, возвращает массив данных по каждому объявлению
     * @param string $root - адрес считываемой страницы
     * @param array $options['agency'] =0 только от собственников
     * @return array|bool|null
     */
    static function get($root, $options=array())   {
        // читаю страницу со списком объявлений
        // цикл по всем объявлениям
        //     читаю объявление, выделяю большие картинки, описание, адрес, координаты, свойства недвижимости.
        //     читаю телефон собственника
    }

    static function error($s){
        echo " <b style='color:red'>".$s."</b>";
    }
    static function info($s){
        echo "<br>\n<i>".$s."</i>";
    }
    static function info2($s){
        echo " <u>".$s."</u>";
    }

    static function NormalPhone($tel){
        $tel=str_replace(' ','',str_replace('(','',str_replace(')','',str_replace('-','',str_replace('+','',$tel)))));
        if(substr($tel,0,1)=='7')$tel='8'.substr($tel,1);
        return $tel;
    }

    static function NormalName($str){
        $str=str_ireplace(array('собственник','!'),'',str_replace('м^2','м?',$str));
        $str=str_replace(array(' м? ',' м?',' мВІ '),' кв.м. ',str_replace(' м?,',' кв.м.,',$str));
        return trim($str);
    }
}
?>
</body>
</html>
