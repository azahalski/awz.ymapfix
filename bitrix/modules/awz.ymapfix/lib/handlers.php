<?php

namespace Awz\Ymapfix;

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;

class Handlers {

    public static function OnEndBufferContent(&$content){
        $request = Application::getInstance()->getContext()->getRequest();
        if($request->isAdminSection()) return;
        if($request->isPost()) return;
        if($request->isAjaxRequest()) return;

        $url_ymaps = Option::get(Helper::MODULE_ID, 'URL', '', SITE_ID);
        if(!$url_ymaps) return;

        $dsbl_module = explode(",", Option::get(Helper::MODULE_ID, 'SHOW', 'N', SITE_ID));
        if($dsbl_module === 'Y') return;

        $dsbl_get = explode(",", Option::get(Helper::MODULE_ID, 'DSBL_GET', '', SITE_ID));

        if(!empty($dsbl_get)){
            foreach($dsbl_get as $prm){
                $key_get = trim($prm);
                if($key_get && $request->get($key_get))
                    return;
            }
        }

        if(
            mb_strpos(mb_substr($content,-20), '</body>')!==false
        ){
            $curPage = $request->getRequestUri();
            if ($arExcluded = explode("\n", Option::get(Helper::MODULE_ID, 'DSBL_REJ', '', SITE_ID))) {
                foreach ($arExcluded as $exc) {
                    if(!trim($exc) || strlen(trim($exc))<3) continue;
                    try{
                        if (preg_match($exc, $curPage)) {
                            return;
                        }
                    }catch (\Exception $e){

                    }
                }
            }
            $isEnabled = null;
            if ($arExcluded = explode("\n", Option::get(Helper::MODULE_ID, 'ENBL_REJ', '', SITE_ID))) {
                foreach ($arExcluded as $exc) {
                    if(!trim($exc) || strlen(trim($exc))<3) continue;
                    $isEnabled = $isEnabled ?? [];
                    try{
                        if (preg_match($exc, $curPage)) {
                            $isEnabled[] = $exc;
                        }
                    }catch (\Exception $e){

                    }
                }
            }
            if(is_array($isEnabled) && empty($isEnabled)) return;

            if(preg_match_all('/<script[^>]+src="([^"]+)"?(?:[^>]+)><\/script>/is', $content, $matches)){
                $findFirst = false;
                foreach($matches[1] as $k=>$url){

                    if(mb_strpos($url, 'api-maps.yandex.')!==false){
                        if(!$findFirst){
                            $findFirst = true;
                            $end_script = str_replace($url, $url_ymaps.'#awzfixymap', $matches[0][$k]);
                            $content = str_replace($matches[0][$k],$end_script,$content);
                        }else{
                            $content = str_replace($matches[0][$k],'',$content);
                        }
                    }
                }
                $content = str_replace('#awzfixymap', '', $content);
                //echo'<pre>';print_r($matches);echo'</pre>';
                //die();
            }


        }
    }

}