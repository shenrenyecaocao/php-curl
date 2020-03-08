<?php
/**
 * 同步发起请求
 * 针对http协议的80端口发起一个curl请求
 * 如果需要其他协议的其他端口请使用Sockwork类库
 * @author bsykc
 * @since 20160825
 * @final 20161227
 */
 
#### 使用方法：
#### CURL同步数据交换CommandLineUniformResourceLocator-start
// $curl = CURL::factory($url)//请求的绝对http地址
//         ->method('POST')//只能够POST或GET.etc...
//         ->header(array("CLIENT-SIGN:xxxxsign"))//文件头信息签名等
//         ->data($data);//数组形式
// try{
//    $result = $curl->execute();//执行并且返回
// }catch (Exception $e){
//     //$this->returnAjax($e->getCode(),null,$e->getMessage());
// }
#### CURL同步数据交换CommandLineUniformResourceLocator-end
 
class Common_CURL
{
    private $_ch = NULL;
    private $_url = NULL;
    private $_data = NULL;
    private $_method = NULL;
    private $_header = NULL;
    private $_timeout = NULL;
     
     
    /**
     * 构造函数
     */
    private function __construct($url)
    {
        //if (ini_get("allow_url_fopen") == "1" and $method='GET'){return file_get_contents($url);}
        if(!function_exists('curl_init')){
            throw new Exception('FunctionCurlNotExists', 1500);
            return FALSE;//FIXME 写个日志-CURL尚未启用
        }
        $this->_url = $url;
         
        try {
            $this->_ch = curl_init();
        }catch (Exception $e){
            throw new Exception('CurlInitError', 1500);
            return FALSE;//FIXME 写个日志-初始化curl失败
        }
        //return TRUE;
    }
 
    /**
     * Create a new CURL instance.
     *     $tfps = CURL::factory($url);
     * @return  CURL
     */
    public static function factory($url=NULL)
    {
        return new self($url);
    }
     
     
    /**
     * 请求类型：POST/GET
     */
    public function method($method)
    {
        $method = strtoupper($method);//强制大写
        switch ($method)
        {
            case 'POST':
                curl_setopt($this->_ch, CURLOPT_CUSTOMREQUEST, 'POST');
                curl_setopt($this->_ch, CURLOPT_POST, TRUE);
                break;
            case 'PUT':
                curl_setopt($this->_ch, CURLOPT_CUSTOMREQUEST, 'PUT');//CURLOPT_PUT
                //curl_setopt($this->_ch, CURLOPT_PUT, TRUE);//不要设置啊啊
                break;
            case 'DELETE':
                curl_setopt($this->_ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
            case 'GET':
                curl_setopt($this->_ch, CURLOPT_CUSTOMREQUEST, 'GET');
                curl_setopt($this->_ch, CURLOPT_HTTPGET, TRUE);
                break;
            default://
                break;
        }
        //exit('_method:'.$method);
        $this->_method = $method;
        return $this;
    }
     
     
    /**
     * 参数类似array("Content-Type: application/x-www-form-urlencoded;charset=UTF-8")
     */
    public function header(array $header)
    {
        curl_setopt($this->_ch, CURLOPT_HTTPHEADER, $header);
        $this->_header = $header;
        return $this;
    }
     
    /**
     * 压缩传输
     * @param string $type
     * @return $this
     */
    public function encoding($type = 'gzip')
    {
        curl_setopt($this->_ch, CURLOPT_ENCODING, $type);
        return $this;
    }
     
    /**
     * 设置请求附带参数:数组格式
     */
    public function data($data)
    {
        if (is_array($data)){
            $data = http_build_query($data);//数组用http_bulid_query()函数处理
        }
        curl_setopt($this->_ch, CURLOPT_POSTFIELDS, $data);
        //print_r($data);die;
        //curl_setopt($this->_ch,CURLOPT_HTTPHEADER,array("X-HTTP-Method-Override: PUT"));//设置HTTP头信息
        $this->_data = $data;
        return $this;
    }
     
    /**
     * 设置超时
     */
    public function timeout($timeout=NULL)
    {
        if (is_numeric($timeout) and $timeout>0){
            //curl_setopt($this->_ch, CURLOPT_CONNECTTIMEOUT, 300);// post的超时断线
            curl_setopt($this->_ch, CURLOPT_TIMEOUT, $timeout);
        }else{
            curl_setopt($this->_ch, CURLOPT_TIMEOUT, 30);
        }
        $this->_timeout = $timeout;
        return $this;
    }
     
     
    /**
     * 在执行curl之前的检查
     */
    private function beforeExecute()
    {
        if ($this->_method == 'GET'){
            //如果发送的是get请求独立拼接串
            if (strpos('?', $this->_url)===false){
                $this->_url .= '?'.$this->_data;
            }else{
                $this->_url .= '&'.$this->_data;
            }
        }
        if ($this->_method == 'DELETE'){
            $this->_url .= '?'.$this->_data;//FIXME 如果发送的是delete请求独立拼接串
        }
        //设置CURL的参数
        curl_setopt($this->_ch, CURLOPT_URL, $this->_url);
        curl_setopt($this->_ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($this->_ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->_ch,CURLOPT_BINARYTRANSFER,true);
         
        return TRUE;
    }
     
     
    /**
     * 同步获取远程url内容
     * 唯CURL可用if (ini_get("allow_url_fopen") == "1" and $method='GET'){return file_get_contents($url);}
     */
    public function execute()
    {
        $this->beforeExecute();
         
        $result = NULL;
        try {
            $result =  curl_exec($this->_ch);
        }catch (Exception $e){
            throw new Exception('CurlExecError', 1500);
            return FALSE;//FIXME 写个日志-
        }
        /*if(!curl_errno($this->_ch)){
            $info = curl_getinfo($this->_ch);
            //echo 'Took ' . $info['total_time'] . ' seconds to send a request to ' . $info['url'];
        } else {
            echo 'Curl error: ' . curl_error($this->_ch);
        }*/
        //exit($result);//test
        return $result;
    }
 
    /**
     * 析构方法
     * @return boolean
     */
    public function __destruct()
    {
        try {
            curl_close($this->_ch);
        }catch (Exception $e){
            //FIXME 写个日志-
        }
        //exit('okokDESCTRUCT');
        return TRUE;
    }
  
}
