<?php
/**
 * Created by PhpStorm.
 * User: mrqi
 * Date: 2019/11/29
 * Time: 11:38
 */

namespace comserver;

require_once ('./PDOHelp.php');

class Distribute
{


    /**
     * Created by PhpStorm.
     * User: mrqi
     * Date: 2019/11/29
     * Time: 11:39
     * 分支识别方法
     * 返回值决定外侧操作方式
     * 1：清空数据，关闭串口
     * 2:刷新数据，不关闭串口。
     *  3：不做任何操作。
     *
     *
     *
     */
    public function startFork($data){
        $len = count($data);
        if($len == 1 && $data[0] == "00"){
            echo"rev report data"."\r\n";
            return  2;
        }
        if($len == 1 && $data[0] == "02"){  // cco发送的关闭串口通讯协议
            echo"rev report data close com is over"."\r\n";
            return 1;
        }

    }


    public function closeFork($data){
        echo "rev report end flag close com"."\r\n";
        return 1;
    }

    /**
     * Created by PhpStorm.
     * User: mrqi
     * Date: 2019/12/18
     * Time: 10:25
     * return:void
     * 分支识别上报数据
'     *
     * 上报用户数据表示
     *  长度  +   ro   +  电表地址  + II采地址  +  采集数据
     *   2    +    1    +   6      +   6      +      n
     * ro = 0 :采集成功
     * ro =1 ：采集失败
     *
     */
    public function reportForkData($data){
        $ro = $data[2+1 -1];
        if($ro =="00"){
            echo "rev report data insert db"."\r\n";
            $this->InDb($data);
        }
        if($ro == "01"){
            echo "rev report data erro"."\r\n";

            //电表地址
            $stadr = $this->GetStaStr($data);
            //II采地址
            $IIadr = $this->GetII($data);
            $sql = "INSERT INTO `dlt_fork` (`qrcode`, `IIqrcode`, `data`) VALUES ('{$stadr}','{$IIadr}',' ')";
            $db = new PDOHelp();
            $res =$db->execute($sql);
        }
        return 2;
    }


    /**
     * Created by PhpStorm.
     * User: mrqi
     * Date: 2019/12/18
     * Time: 14:15
     * return:voidc
     * 处理数据并且入库
     */
    public function InDb($data){
        //电表地址
        $stadr = $this->GetStaStr($data);
        //II采地址
       $IIadr = $this->GetII($data);
        //上报数据格式化
        $insertData=$this->FormatData($data);

        if(empty($stadr) || empty($IIadr) ) return 2; //清空数据
        $sql = "INSERT INTO `dlt_fork` (`qrcode`, `IIqrcode`, `data`) VALUES ('{$stadr}','{$IIadr}','{$insertData}')";

        //echo $sql."\r\n";

        $db = new PDOHelp();
      $res =$db->execute($sql);

       return 2;


    }

    public function GetStaStr($data){
        //电表地址起始位置
        $start = 2+1;
        //小端模式
        return $data[$start+5].$data[$start+4].$data[$start+3].$data[$start+2].$data[$start+1].$data[$start];

    }

        //获取II采地址
    public function GetII($data){

        //II地址起始位置
        $start = 2+1+6;
        //小端模式
        return $data[$start+5].$data[$start+4].$data[$start+3].$data[$start+2].$data[$start+1].$data[$start];
    }


    /**
     * Created by PhpStorm.
     * User: mrqi
     * Date: 2019/12/18
     * Time: 14:30
     * 上报数据格式化处理
     */
    public function FormatData($data){
        //用户数据位置
        $start = 2+1+6+6;
        $insert_data = [];
        $insert_str='';

        $temp_arr=[];
        foreach ($data as $key=>$val){
            if($key-$start <0) continue;
            if(($key-$start)%2 ==0){
                $temp= $data[$key+1].$data[$key];
                $temp_arr[] =hexdec($temp);
                $insert_str .= hexdec($temp)."|";
            }
        }


        //print_r($temp_arr);die;

        $insert_str = rtrim($insert_str,'|');

        return $insert_str;


    }
}
