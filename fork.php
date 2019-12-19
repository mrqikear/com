<?php
/**
 * Created by PhpStorm.
 * User: mrqi
 * Date: 2019/12/2
 * Time: 10:34
 */
namespace  comserver;
use function Aws\or_chain;
use Redis;
require_once ('./Encode3762.php');

/**
 * Class fork
 * 分支识别读取串口
 */
class fork
{
    public $redis;
    public $fd; //串口句柄
    public $isOpen=false; //串口是否打开

    public function __construct()
    {

    }



    public function  test2(){

        $str= "68 3B 03 C0 05 00 10 05 05 E8 2D 03 00 96 33 83 20 06 19 03 00 28 11 19 20 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 01 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 01 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 01 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 FA 16";

        $str2 = trim($str);
        echo $str2."\r\n";die;

    }



    public function test(){
       $this->redis->set("mrqi", "222222", 60);
       $res=$this->redis->get("mrqi");
        $this->redis->set("dota",3333,60);
        $res1=$this->redis->get("dota");
        var_dump($res);
        var_dump($res1);
    }

    /**
     *   读取com
     */
    public function ReadCom(){

        $read = ''; //串口读到的数据


        $filePath = "../../web/lock.txt";
        while (true){
            $is_read=file_get_contents($filePath);
            if($is_read){
                //开启串口读取数据
                set_time_limit(0);
                //error_reporting(0);
                if(!$this->isOpen){ //打开串口
                    exec('mode COM1: baud=115200 data=8 stop=1 parity=n xon=off');
                    $this->fd = dio_open('COM1:', O_RDWR);
                    $this->isOpen = true;
                    echo "oepn COM1"."\r\n";
                }
                if(!$this->fd)
                {
                    die("Error when open COM1");
                }
                $ff = dio_stat($this->fd);
                $model3762 = new Encode3762();
                $data = dio_read($this->fd,1);
                if($data == "") continue;  //没有数据跳过
                $data=dechex(ord($data));
                $data =$var = sprintf("%02s", $data);
                //echo ($data)."\r\n";
                $read .= $data;
                switch ($model3762->Check3762($read)){
                    case 1:  //清空数据关闭串口
                        $read = ""; //清空读取到的数据
                        dio_close($this->fd);
                        $this->isOpen = false;//标识关闭
                       file_put_contents($filePath,0);
                        echo "send data parse ok"."\r\n";
                        break 1;
                    case 2:
                        $read = ""; //清空读取到的数据
                        echo "clearn data"."\r\n";
                        break 1;
                }

            }else{
                echo "file  read flag is false"."\r\n";
            }

        }

    }

public function checkTimeoutRedis(){
        if(empty($this->redis)){
            $this->redis = new Redis();
            $this->redis->connect('127.0.0.1', 11981, 3600);
            echo "PHPredisCLient  is restart"."\r\n";
        }
}


public function __destruct() {
      echo "__destruct"."\r\n";
      //关闭串口
      if($this->isOpen){
          dio_close($this->fd);
          echo "close com by destruct"."\r\n";
      }
      //关闭redis
      if( $this->redis){
          $this->redis->close();
      }
    }

  }

$instance = new fork();
//$instance->test2();
$instance->ReadCom();
