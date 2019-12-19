<?php
namespace comserver;
/**
 * Created by PhpStorm.
 * User: mrqi
 * Date: 2019/12/02
 * Time: 11:06
 */




/**
 * Class Encode3762
 * @package common\util
 * 3762本地通讯协议
 * 解析
 */


/**
 * Class Encode3762
 * @package common\util
 *
 *
 *
 *
 *
 *      合法3762数据判断是否存在di
 * 起始符号  + 长度 + 控制域 +源地址+目的地址+afn+帧序列域+应用数据(di+n)+校验+结束符号(16)
 *  68       +  2   +   1   +  6   +  6    +  1 + 1  + (4 + n)   + 1+1
 *  应用数据
 *      di（4） + 长度（1）+数据（n）
 *
 */
use ReflectionClass;

require_once ('./Distribute.php');
class Encode3762
{

    public $IssetFile; //是否有地址域
    public $Di;//di
    public $head  =1; //起始帧 68
    public $lenth =2; //帧长度
    public $Clen = 1; //控制域
    public $Afn = 1;
    public $SEQ = 1;//帧序列号
    public $DiFunction = ['10F003E8'=>'startFork', //启动分支识别（或者下游应答）
        '110505E8'=>'closeFork', //下游回复数据已经完分支识别上报关闭串口
        '100505E8'=>'reportForkData',//cco 上报分支识别数据
    ];
    public $Server; //服务模型


    public function __construct()
    {
        $this->Server = Distribute::class;//服务分发类类
    }


    /**
     * Created by PhpStorm.
     * User: mrqi
     * Date: 2019/12/17
     * Time: 16:53
     * return:bool|int|mixed
     * * 返回值决定外侧操作方式
     * 1：清空数据，关闭串口
     * 2:刷新数据，不关闭串口。
     *  3：不做任何操
     */

    public function  Check3762($data){




       /* if(!$this->SumCheck3762($data)){
            //判断为坏帧 清空数据
            return 2;
        }*/

       //r_reporting(0);
        $checkRes = $this->SumCheck3762($data);

        //和校验
        switch ($checkRes){
            case 2:  //验证不通过
                return 2;
            case 3: //继续接受数据
                return 3;
            case 4:
             break;
        }


        //头部校验
         if(!$this->HeadCheck3762($data)){
                //不是68开头的数据包清空重新接收
             return 2;
         }
        //尾部校验

        if(!$this->TailCheck3762($data)){
            //尾部数据不是16 错误帧
            return  2;
        }



        //查询DI并处理数据
        $offsertDi= 0; //di所在的偏移位置
        $di=strtoupper($this->SearchDi($data,$offsertDi));
        if(isset($this->DiFunction["$di"])){
            $Reflection =  new ReflectionClass($this->Server);
            try{
                $method = $Reflection->getMethod($this->DiFunction["$di"]);
                $instance  = $Reflection->newInstance();
                //设置可访问性
                $method->setAccessible(true);
                //执行方法
                $userData=$this->GetUserData($data,$offsertDi);
               return  $method->invoke($instance,$userData);
            }catch (\Exception $exception){
                echo $exception->getMessage();
               echo "is on di funtion\r\n";
               return 2; //清空数据重新接受
            }
        }

        echo "di is  not defiend \r\n";
        return 2;

    }

    /**
     * Created by PhpStorm.
     * User: mrqi
     * Date: 2019/11/28
     * Time: 11:15
     * 检查是否是68开始
     *
     */
    public function HeadCheck3762($data){
        $headStr = substr($data,0,2);
        if($headStr == "68") return true;

        return false;
    }


    public  function TailCheck3762($data){
        $tailStr = substr($data,-2,2);
        if($tailStr =="16") return true;
        return false;
    }


    //和校验
    public  function SumCheck3762($data){
        $hexArr=$this->StringToHexArr($data);
        if(empty($hexArr)) return false;

        $hexArrLen =count($hexArr);
        if($hexArrLen <= 3)  return 3; //长度太少继续等待
        //小端模式

        $hexLen = $hexArr[2].$hexArr[1];
        $hexLen=hexdec($hexLen);


        //检验长度
        if($hexArrLen < $hexLen){
            echo "data length is sort"."\r\n";
            return 3; //找到16但是长度不对继续接受
        }

        if($hexArrLen > $hexLen && $hexLen>0 ){
            //var_dump($hexArrLen);
            //var_dump($hexLen);
            echo "data lenght is error"."\r\n";
            return 2 ; //坏帧 清空数据
        }



        /**
         * ex:
         * 68 0C 00 /40 F0 00 10 F0 03 E8 / =1B 16
         *
         * 长度之后所以数据校
         */

        //和校验
        $sum =0;
          for ($i=3;$i<$hexLen - 2;$i++){
            $sum +=hexdec($hexArr[$i]);
          }
          $sum = dechex($sum);
          //只取地位数据
          $sum = substr($sum,'-2',2);
          $sum =strtoupper($sum);
          $checkSumFlag = strtoupper($hexArr[$hexLen-2]);
          if($checkSumFlag!=$sum){
               echo "check sum is error\r\n";
                return 2;  //和校验失败 清空数据
          }


        return 4; //继续下面的检查
}


/**
 * 是否有地址域
 * D7 D6 D5 D4-D3 D2  D1
 * 0  1  0  0 0   0   0  0
 * D5代表地址域
 * D5 =0 没有地址域
 * D5 = 1 有地址域
 */
public function IssetAddressField($hexArr){

    //控制域
    $CFeild = $hexArr[3];
    $binString = base_convert($CFeild,16,2);
    //补位8个
    $binString=str_pad($binString,8,"0",STR_PAD_LEFT);

    $D5 = (int)$binString[2];
    return $D5;

}



/**
 * 查询DI
 *
 * 68 0C 00 40 F0 00 10 F0 03 E8 1B 16
 *
 * 40:控制域
 * //用户数据
 * AFN:F0
 * 帧序列域:
 * 00
 * DI:
 * 10 F0 03 E8
 *
 */
public function SearchDi($data,&$offsertDi){
    //判断是否存在地址域
    $hexArr=$this->StringToHexArr($data);
    $D5=$isSetFiled=$this->IssetAddressField($hexArr);
    if($D5){
        echo "有地址域的还没确认";
    }else{
        //起始符号  + 长度 + 控制域+afn+帧序列域
        $offset =$this->head+$this->lenth+$this->Clen+$this->Afn+$this->SEQ;
        $offsertDi = $offset+4;
        return $hexArr[$offset].$hexArr[$offset+1].$hexArr[$offset+2].$hexArr[$offset+3];

    }
}




/**
 * 字符串切割成16进制数组
 * ex:['68','00','18']
 *
 */

public function StringToHexArr($data){
    $hexArr= [];
    for ($i=0;$i<strlen($data);$i++){
        if($i%2 ==0 && isset($data[$i+1])){
            $hexArr[]=$data[$i].$data[$i+1];
        }
    }
    return $hexArr;
}



/**
 * 数据处理
 * 取出di 后面的时机数据 - 最后2个字节
 */


public function GetUserData($data,$offsertDi){
    $HexArr = $this->StringToHexArr($data);
    $returnArr = [];
    foreach ($HexArr as $key=>$value){
        if($key>=$offsertDi){
            $returnArr[] = $value;
        }
    }
    //去掉最后两个字段
    array_pop($returnArr);
    array_pop($returnArr);
    return $returnArr;
}



}
