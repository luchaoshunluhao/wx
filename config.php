<?php
return [
    "menu" =>[
        "button" =>[
            [    
                "type" =>"click",
                "name" =>"歌曲",
                "key" =>"V1001_TODAY_MUSIC"
            ],
            [
                "name" =>"菜单",
                "sub_button" =>[
                    [    
                    "type" =>"view",
                    "name" =>"搜索",
                    "url" =>"http://eafwwt.natappfree.cc/go.php" //服务器地址
                    ],
                    [
                    "type" => "scancode_waitmsg", 
                    "name" => "扫码带提示", 
                    "key" => "rselfmenu_0_0"
                    ],
                    [
                    "type" => "pic_photo_or_album", 
                    "name" => "拍照或者相册发图", 
                    "key" => "rselfmenu_1_1", 
                    "sub_button" => [ ]
                    ]
                ]
            ]
        ]
    ]
];