# JWT

https://zhuanlan.zhihu.com/p/95270756

JWT的使用流程

 1. 初次登录：用户初次登录，输入用户名密码
 1. 密码验证：服务器从数据库取出用户名和密码进行验证
 1. 生成JWT：服务器端验证通过，根据从数据库返回的信息，以及预设规则，生成JWT
 1. 返还JWT：服务器的HTTP RESPONSE中将JWT返还
 1. 带JWT的请求：以后客户端发起请求，HTTP REQUEST
 1. HEADER中的Authorizatio字段都要有值，为JWT
 1. 服务器验证JWT


    <?php
    /**
     * PHP实现jwt
     */
    class JwtAuth {
    
      //头部
      private static $header=array(
        'alg'=>'HS256', //生成signature的算法
        'typ'=>'JWT'  //类型
      );
    
      //使用HMAC生成信息摘要时所使用的密钥
      private static $key='root123456';
    
    
      /**
       * 获取jwt token
       * @param array $payload jwt载荷  格式如下非必须
       * [
       * 'iss'=>'jwt_admin', //该JWT的签发者
       * 'iat'=>time(), //签发时间
       * 'exp'=>time()+7200, //过期时间
       * 'nbf'=>time()+60, //该时间之前不接收处理该Token
       * 'sub'=>'www.mano100.cn', //面向的用户
       * 'jti'=>md5(uniqid('JWT').time()) //该Token唯一标识
       * ]
       * @return bool|string
       */
      public static function getToken(array $payload)
      {
        if(is_array($payload))
        {
          $base64header=self::base64UrlEncode(json_encode(self::$header,JSON_UNESCAPED_UNICODE));
          $base64payload=self::base64UrlEncode(json_encode($payload,JSON_UNESCAPED_UNICODE));
          $token=$base64header.'.'.$base64payload.'.'.self::signature($base64header.'.'.$base64payload,self::$key,self::$header['alg']);
          return $token;
        }else{
          return false;
        }
      }
    
    
      /**
       * 验证token是否有效,默认验证exp,nbf,iat时间
       * @param string $Token 需要验证的token
       * @return bool|string
       */
      public static function verifyToken(string $Token)
      {
        $tokens = explode('.', $Token);
        if (count($tokens) != 3)
          return false;
    
        list($base64header, $base64payload, $sign) = $tokens;
    
        //获取jwt算法
        $base64decodeheader = json_decode(self::base64UrlDecode($base64header), JSON_OBJECT_AS_ARRAY);
        if (empty($base64decodeheader['alg']))
          return false;
    
        //签名验证
        if (self::signature($base64header . '.' . $base64payload, self::$key, $base64decodeheader['alg']) !== $sign)
          return false;
    
        $payload = json_decode(self::base64UrlDecode($base64payload), JSON_OBJECT_AS_ARRAY);
    
        //签发时间大于当前服务器时间验证失败
        if (isset($payload['iat']) && $payload['iat'] > time())
          return false;
    
        //过期时间小宇当前服务器时间验证失败
        if (isset($payload['exp']) && $payload['exp'] < time())
          return false;
    
        //该nbf时间之前不接收处理该Token
        if (isset($payload['nbf']) && $payload['nbf'] > time())
          return false;
    
        return $payload;
      }
    
      /**
       * base64UrlEncode  https://jwt.io/ 中base64UrlEncode编码实现
       * @param string $input 需要编码的字符串
       * @return string
       */
      private static function base64UrlEncode(string $input)
      {
        return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
      }
    
      /**
       * base64UrlEncode https://jwt.io/ 中base64UrlEncode解码实现
       * @param string $input 需要解码的字符串
       * @return bool|string
       */
      private static function base64UrlDecode(string $input)
      {
        $remainder = strlen($input) % 4;
        if ($remainder) {
          $addlen = 4 - $remainder;
          $input .= str_repeat('=', $addlen);
        }
        return base64_decode(strtr($input, '-_', '+/'));
      }
    
      /**
       * HMACSHA256签名  https://jwt.io/ 中HMACSHA256签名实现
       * @param string $input 为base64UrlEncode(header).".".base64UrlEncode(payload)
       * @param string $key
       * @param string $alg  算法方式
       * @return mixed
       */
      private static function signature(string $input, string $key, string $alg = 'HS256')
      {
        $alg_config=array(
          'HS256'=>'sha256'
        );
        return self::base64UrlEncode(hash_hmac($alg_config[$alg], $input, $key,true));
      }
    }
    
    
    这里测试一下
    
    //测试和官网是否匹配begin
      $payload=array('sub'=>'1234567890','name'=>'John Doe','iat'=>1516239022);
      $jwt=new Jwt;
      $token=$jwt->getToken($payload);
      echo "<pre>";
      echo $token;
    
      //对token进行验证签名
      $getPayload=$jwt->verifyToken($token);
      echo "<br><br>";
      var_dump($getPayload);
      echo "<br><br>";
      //测试和官网是否匹配end
    
    
      //自己使用测试begin
      $payload_test=array('iss'=>'admin','iat'=>time(),'exp'=>time()+7200,'nbf'=>time(),'sub'=>'www.admin.com','jti'=>md5(uniqid('JWT').time()));;
      $token_test=Jwt::getToken($payload_test);
      echo "<pre>";
      echo $token_test;
    
      //对token进行验证签名
      $getPayload_test=Jwt::verifyToken($token_test);
      echo "<br><br>";
      var_dump($getPayload_test);
      echo "<br><br>";
      //自己使用时候end
      
     
#### other auth method

 - 登录信息保存在session里.使用session_id来索引用户自己的session信息.session_id保存在cookie中。在客户端，服务端之间传递.有时候为了提升安全系数.seesion_id可能会改名.起到混淆作用，增加推测难度.
 - 使用token. token 一般放在头信息中.每次请求都需要带上.
 - jwt 仍然是在cookie中.但是用户登录信息并不是存储在seesion里,由服务器保存.而是被服务器加密后存储在客户端cookie中.每次都需要带上.服务器只需要解密验证`签名`,`用户信息`即可.