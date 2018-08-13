<?php

require_once __DIR__ . '/../../vendor/autoload.php';


$worker = new \Workerman\Worker('tcp://0.0.0.0:8321');
$worker->name = 'Tcp Tunnel';
$worker->count = 10;
$worker->onConnect = function (\Workerman\Connection\TcpConnection $connection) {
    $remoteIp = $connection->getRemoteIp();
    if (!Helper::isAllowed($remoteIp)) {
        $connection->send('Address Not Allowed!');
        $connection->close();

        return;
    }

};

$worker->onMessage = function (\Workerman\Connection\TcpConnection $connection, $buffer) {
    list($method, $addr, $http_version) = explode(' ', $buffer);
    $urlData = parse_url($addr);
    if (!isset($urlData['host'])) {
        $findHost = '';
        preg_match('/Host: (.*)/i', $buffer, $findHost);
        if (!isset($findHost[1])) {
            $connection->send($buffer);
            $connection->close();

            return;
        }
        $urlData = parse_url($findHost[1]);
        if (!isset($urlData['host'])) {
            $connection->send($buffer);
            $connection->close();

            return;
        }
    }
    if (!isset($urlData['port'])) {
        $urlData['port'] = 80;
    }
    $remoteHost = $urlData['host'] . ':' . (int)$urlData['port'];
    $contextOptions = [
        'socket' => [
            'bindto' => $connection->getLocalIp() . ':0',
        ],
    ];
    $remoteAsyncTcp = new \Workerman\Connection\AsyncTcpConnection('tcp://' . $remoteHost, $contextOptions);

    if ($method !== 'CONNECT') {
        $remoteAsyncTcp->send($buffer);
    } else {
        $connection->send("HTTP/1.1 200 Connection Established\r\n\r\n");
    }

    $remoteAsyncTcp->pipe($connection);
    $connection->pipe($remoteAsyncTcp);
    $remoteAsyncTcp->connect();
};