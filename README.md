# ayaf
ayaf framwork for yaf

1.先安装yaf环境	
2.配置主机目录	
3.复制框架文件
	

文件目录结构	
/application	
    /controllers #控制器		
	  /library		
		  /Core #框架核心文件					
			/Addons #插件库			
		/models  #模型文件			
		/modules #模块文件			
		/plugins	
/conf #配置文件			
		application.ini #应用配置				
		db.ini      #数据库配置			
		errorcode.ini #错误码			
		pay.ini   #支付配置			
		redis.ini #redis配置			
		routes.ini #路由规则			
/data	
/doc	
/public	
		/default	
			/lib #静态库文件	
			/views #静态html文件	



1.具体数据库层加载规则可以model下Index.php里面的dbSample	
2.具体redis加载规则可以model下Index.php里面的redisSample

