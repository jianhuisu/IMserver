## fastcgi_pass_and_proxy_pass

waste two hours ...

    server {
    
    	listen       80;
    	server_name  local.dev.com;
    	root   /home/sujianhui/PhpstormProjects/pfd/;
            index  index.php;
    
    	location ~* \.php$ {
    
    		include        /etc/nginx/fastcgi_params;	
    		fastcgi_pass   127.0.0.1:9000;
    		# proxy_pass   http://127.0.0.1;
    		fastcgi_param  SCRIPT_NAME $fastcgi_script_name;
    		fastcgi_param  PATH_INFO $fastcgi_path_info;
    		fastcgi_param  SCRIPT_FILENAME $document_root$fastcgi_script_name;
    		fastcgi_index  index.php;
    	}
    
    }

 - 网关代理fastcgi_pass
 - 反向代理proxy_pass