###如何使用
>Window: 右键桌面[我的电脑/此电脑] —— 映射网络驱动器 —— 粘贴上述webdav地址,点击完成——输入账号密码即可; 
>推荐使用:[RaiDrive](https://www.raidrive.com/download), 更强大,兼容性更好.
>首次使用需要取消上传及http限制,下载此文件后双击运行 [webdav.bat](./webdav.bat); 

>Mac: 右键finder —— 连接服务器 —— 粘贴上述webdav地址,点击连接 —— 输入账号密码即可
>其他客户端及系统: 明确地址为上述webdav地址,账号密码为自己登陆账号即可，基本流程类似 
>Android,iOS移动端设备推荐:[ES文件浏览器](http://www.estrongs.com)

###提示说明
>可道云文档可以通过挂在到当前电脑或APP,文件管理可以和本地硬盘一样方便快捷;同时可以实时编辑保存文件
>上传下载限制: 支持上传最大文件取决于服务器上传限制及超时时间; 具体在服务器上传相关配置.
>读写编辑等权限: 读写等权限完全同于web端; 由于协议没有报错机制,操作不成功基本等同于没有权限

###NGINX配置示例
    server {
        listen 8083;
        root   /www/webdav;
        index  index.php;
        
        #access_log  logs/access.log;
        access_log off;
        
        #大文件上传
        client_max_body_size 10240m;
        client_body_timeout 1024s;
        
        location / {   
            rewrite (.*) /index.php?$query_string last;
        }
    
        location ~ index\.php(.*)$ {
            fastcgi_pass   127.0.0.1:8098;
            fastcgi_index  index.php;
            include        fastcgi_params;
            
            if (!-f $document_root$fastcgi_script_name) {
                return 404;
            }
            #禁用缓冲 用于大文件上传
            fastcgi_request_buffering off; # Disable request buffering
            fastcgi_read_timeout 3600;
            
            fastcgi_split_path_info ^(.+?\.php)(/.*)$;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        }
    }

###TODO
LOCK、UNLOCK、PROPPATCH 未实现