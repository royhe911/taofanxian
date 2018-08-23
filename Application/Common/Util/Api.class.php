<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/12/4
 * Time: 19:10
 *
 * 获取json，xml数据
 */

namespace Common\Util;

class Api{

    public static function getData($code , $message = '' ,$data = array() , $type = 'json'){

         if(!is_numeric($code)){
             return '';
         }

          switch($type){
              case 'json':
                  return self::getJson($code , $message , $data);
                  break;
              case 'xml':
                  return self::getXml($code , $message , $data);
                  break;
              default:
                  return '.....';
          }
    }

    public  static function getJson($code , $message , $data){

            if(!is_numeric($code)) {
                return '';
            }

            $result = array(
                'code' => $code,
                'message' => $message,
                'data' => $data
            );

            return json_encode($result);
    }


    public static function getXml($code , $message , $data){

        if(!is_numeric($code)) {
            return '';
        }

        $result = array(
            'code' => $code,
            'message' => $message,
            'data' => $data,
        );

        header("Content-Type:text/xml");
        $xml = "<?xml version='1.0' encoding='UTF-8'?>\n";
        $xml .= "<root>\n";
        $xml .= self::xmlToEncode($result);
        $xml .= "</root>";
        return $xml;

    }

    public static function xmlToEncode($data) {
        $xml = $attr = "";
        foreach($data as $key => $value) {
            if(is_numeric($key)) {
                $attr = " id='{$key}'";
                $key = "item";
            }
            $xml .= "<{$key}{$attr}>";
            $xml .= is_array($value) ? self::xmlToEncode($value) : $value;
            $xml .= "</{$key}>\n";
        }
        return $xml;
    }
}