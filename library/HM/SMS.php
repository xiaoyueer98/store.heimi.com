<?php 
class HM_SMS
{
	private function randConf($confs)
     {
          if(empty($confs))
          {
               return null;
          }
          $key =  array_rand($confs);
          $conf = $confs[$key];
          return $conf;
     }

     private function send($confs,$tel,$msg)
     {
          $conf = $this->randConf($confs);
          try 
          {
      			$url = "http://".$conf."/sender/index";                                                              
                $params = "?telephone=".$tel;                                                                                                           
                $params = $params."&msg=".urlencode($msg);
                $req = $url.$params;
                $result = file_get_contents($req);
                $json = json_decode($result);
                if(intval($json->code) == 200)
                {
                     return 0;
                }
                else
                {
                     return -1;
                }
          }
          catch (Exception $e) 
          {
               return -1;
          }
     }

     public function send_sms($tel,$msg)
     {
          $strConfs = Yaf_Registry::get('config')->sms_cluster;
          $confs = explode(",",$strConfs);
          $len = count($confs);
          if(0 === $len)
          {
               throw new Exception("核心服务失效");
          }
          $result = -1;
          for($i = 0 ; $i < $len ; $i++)
          {
               $result = $this->send($confs,$tel,$msg);
               if($result == 0)
               {
                    $result = true;
                    break;
               }
               else
               {
                    $result = false;
               }
          }
          return $result;
     }
}