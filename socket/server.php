<?php
$host = '192.168.32.10';
$port = '19910';
$null = NULL;
//创建tcp socket
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
// 背后的秘密就在与0.0.0.0这个IP，他可以代表本机的所有IP地址，
socket_bind($socket, 0, $port);
//监听端口
socket_listen($socket);

//连接的client socket 列表
$clients = array($socket);
echo 'before__'.json_encode($clients)."\r\n";
$n = 0;
//设置一个死循环,用来监听连接 ,状态
while (true) {

    $changed = $clients;


    /**
     * @p1 在数组中列出套接字资源，观察是否有 可用的套接字描述符（活跃的socket连接） 当然 ，也取决于读的时候是否阻塞
     * @p2
     * @p3
     * @p4 组成超时参数，如果大于 0 ,则阻塞请求（阻塞 及事件发生才返回响应 ，非阻塞 不管事件有没有发生 都立即返回响应） 如果 为 0 ，非阻塞
     * @p5 返回修改数组中包含套接字资源的数量
     */
    socket_select($changed, $null, $null, 0, 10);
    echo "\r\n".'__block__'.++$n.'__'.json_encode($changed)."\r\n";

    // 如果有新的连接
    if (in_array($socket, $changed)) {

        echo "\r\n".'__new__'.++$n.'__'.json_encode($changed)."\r\n";
        //接受并加入新的socket连接
        $socket_new = socket_accept($socket);
        $clients[] = $socket_new;

        //通过socket获取数据执行handshake
        $header = socket_read($socket_new, 1024);
        perform_handshaking($header, $socket_new, $host, $port);

        // 获取客户端接入socket的 IP port 发送响应通知  peer 伙伴
        socket_getpeername($socket_new, $clientIp,$clientPort);

        $response = mask(json_encode(array(
            'type'=>'system',
            'message'=>$clientIp.' and port '.$clientPort.' connected')
        ));

        send_message($response);

        $found_socket = array_search($socket, $changed);
        unset($changed[$found_socket]);
    }

    //轮询 每个client socket 连接
    foreach ($changed as $changed_socket) {

        //如果有client数据发送过来
        while(socket_recv($changed_socket, $buf, 1024, 0) >= 1)
        {
            //解码发送过来的数据
            $received_text = unmask($buf);
            $tst_msg = json_decode($received_text);
            $user_name = $tst_msg->name;
            $user_message = $tst_msg->message;

            //把消息发送回所有连接的 client 上去
            $response_text = mask(json_encode(array('type'=>'usermsg', 'name'=>$user_name, 'message'=>$user_message)));
            echo "\r\n".$response_text."\r\n";
            send_message($response_text);
            break 2;
        }

        //检查offline的client
        $buf = @socket_read($changed_socket, 1024, PHP_NORMAL_READ);
        if ($buf === false) {
            $found_socket = array_search($changed_socket, $clients);
            socket_getpeername($changed_socket, $ip);
            unset($clients[$found_socket]);
            $response = mask(json_encode(array('type'=>'system', 'message'=>$ip.' disconnected')));
            send_message($response);
        }
    }
}
// 关闭监听的socket
socket_close($socket);
//发送消息的方法
function send_message($msg)
{
    global $clients;
    foreach($clients as $changed_socket)
    {
        @socket_write($changed_socket,$msg,strlen($msg));
    }
    return true;
}
//解码数据
function unmask($text) {
    $length = ord($text[1]) & 127;
    if($length == 126) {
        $masks = substr($text, 4, 4);
        $data = substr($text, 8);
    }
    elseif($length == 127) {
        $masks = substr($text, 10, 4);
        $data = substr($text, 14);
    }
    else {
        $masks = substr($text, 2, 4);
        $data = substr($text, 6);
    }
    $text = "";
    for ($i = 0; $i < strlen($data); ++$i) {
        $text .= $data[$i] ^ $masks[$i%4];
    }
    return $text;
}
//编码数据
function mask($text)
{
    $b1 = 0x80 | (0x1 & 0x0f);
    $length = strlen($text);
    if($length <= 125)
        $header = pack('CC', $b1, $length);
    elseif($length > 125 && $length < 65536)
        $header = pack('CCn', $b1, 126, $length);
    elseif($length >= 65536)
        $header = pack('CCNN', $b1, 127, $length);
    return $header.$text;
}
//握手的逻辑
function perform_handshaking($receved_header,$client_conn, $host, $port)
{
    $headers = array();
    $lines = preg_split("/\r\n/", $receved_header);
    foreach($lines as $line)
    {
        $line = chop($line);
        if(preg_match('/\A(\S+): (.*)\z/', $line, $matches))
        {
            $headers[$matches[1]] = $matches[2];
        }
    }
    $secKey = $headers['Sec-WebSocket-Key'];
    $secAccept = base64_encode(pack('H*', sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
    $upgrade  = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
        "Upgrade: websocket\r\n" .
        "Connection: Upgrade\r\n" .
        "WebSocket-Origin: $host\r\n" .
        "WebSocket-Location: ws://$host:$port/demo/shout.php\r\n".
        "Sec-WebSocket-Accept:$secAccept\r\n\r\n";
    socket_write($client_conn,$upgrade,strlen($upgrade));
}