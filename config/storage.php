<?php
return [
    'default' => 'aliyun',
    'list' => [
        'aliyun' => [
            'access_key' => '',
            'secret_key' => '',
            'bucket' => 'szwtdl-test',
            'endpoint' => 'http://oss-cn-qingdao.aliyuncs.com',
            'options' => [
                'domain' => 'http://szwtdl-test.oss-cn-qingdao.aliyuncs.com',
                'region' => 'oss-cn-qingdao',
            ],
        ],
        'tencent' => [
            'access_key' => '',
            'secret_key' => '',
            'bucket' => 'szwtdl',
            'endpoint' => '1251011169',
            'options' => [
                'domain' => 'http://szwtdl-1251011169.cos.ap-guangzhou.myqcloud.com',
                'region' => 'ap-guangzhou',
            ],
        ],
        'qiniu' => [
            'access_key' => '',
            'secret_key' => '',
            'bucket' => 'szwtdl-test',
            'endpoint' => 'z0',
            'options' => [
                'domain' => 'http://si5ggdbok.hd-bkt.clouddn.com',
                'region' => 'z0',
            ],
        ]
    ]
];