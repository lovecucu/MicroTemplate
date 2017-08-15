#### 目录
1. MicroTemplate是什么
2. MicroTemplate功能详解
3. PHP模板引擎的思考

#### 内容

1. MicroTemplate是什么

    平时在项目中，我们或多或少会乃至模板引擎，其中最常见的诸如smarty,twig,还有一些框架自带的模板引擎。
    
    那么这些模板引擎究竟是如何实现的呢？它们的内部工作原理是什么？
    
    这些问题促使我开发了MicroTemplate这个项目，它是一款简易PHP模板引擎，实现了模板引擎的核心功能：编译和缓存
    
    其中模板文件支持if和foreach等功能语句，实现了简易的编译过程，对于缓存的实现和管理功能也有相关实现。
    
    具体实现代码见2
    
2. MicroTemplate功能详解
    
    熟悉模板引擎的都知道，模板引擎的目录结构相对固定。通常包含cache目录、compile目录、template目录，三者分别用于存放缓存文件、编译后文件、模板文件。
    
    同样，MicroTemplate采用了和上述相同的目录结构，且在实例化MicroTemplate对象后通过调用config方法来设置和获取上述参数，具体实现代码如下：
    ```php
    /**
     * [$arrayConfig 存储配置信息]
     * @var array
     */
    private $_array_config = array(
        'suffix' => '.m', // 模板文件后缀
        'template_dir' => 'template', // 模板所在文件
        'compile_dir' => 'compile', // 编译文件所在目录
        'suffix_compile' => '.html.php', // 编译文件后缀
        'caching' => false, // 是否打开缓存
        'cache_dir' => 'cache', // 缓存文件所在目录
        'suffix_cache' => '.htm', // 缓存文件后缀
        'cache_lifetime' => 3600, // 缓存时间 
    );

    /**
     * [__set 设置配置信息]
     * @param [type] $key   [description]
     * @param [type] $value [description]
     */
    public function config($key, $value=NULL)
    {
        if(is_null($value))
        {
            if(isset($this->_array_config[$key]))
            {
                return $this->_array_config[$key];
            }

            return NULL;
        }
        else
        {
            if(isset($this->_array_config[$key]))
            {
                $this->_array_config[$key] = $value;
                return TRUE;
            }

            return FALSE;
        }
    }
    ```
    上面介绍了MicroTemplate如何配置和获取配置信息的功能实现，那么接下来我们就来解读模板引擎中最核心的两大功能：编译和缓存。
    
    1. 编译功能详解
    
    MicroTemplate中只实现了if和foreach功能语句的解析，其核心知识点就是正则表达式的使用，如果对于正则表达式不熟悉的同学推荐看 [正则表达式 - 教程](http://www.runoob.com/regexp/regexp-tutorial.html)
    
    具体实现代码如下：
    ```php
    private $_array_patterns = array(
        '/\s*\\r?\\n\s*/', // 匹配换行
        '/\{\s*(\$\w+?)\s*\}/', // 匹配变量
        '/\{\s*(else\s?if)\s+(.*?)\s*\}/',  // 匹配elseif
        '/\{\s*(if)\s+(.*?)\s*\}/',  // 匹配if
        '/\{\s*(else)\s*\}/', // 匹配else
        '/\{\s*(foreach)\s+(\$.+?)\s*\}/', // 匹配foreach
        '/\{\s*\/(?:if|foreach)\s*\}/' // 匹配if或者foreach的结尾
    );

    private $_array_replaces = array(
        '',
        '<?php echo $1; ?>',
        '<?php } $1($2) { ?>',
        '<?php $1($2) { ?>',
        '<?php } $1 { ?>',
        '<?php $1($2) { ?>',
        '<?php } ?>',
    );
    
    /**
     * [_compile 生成编译文件]
     * @return [type] [description]
     */
    private function _compile()
    {
        $flag = true;
        $template_file = $this->_array_config['template_dir'].DIRECTORY_SEPARATOR.$this->file.$this->_array_config['suffix'];
        $compile_file = $this->_array_config['compile_dir'].DIRECTORY_SEPARATOR.md5($this->file).$this->_array_config['suffix_compile'];

        ob_start();
        include $template_file;
        $template_str = ob_get_contents();
        ob_end_clean();

        try
        {
            $compile_str = preg_replace($this->_array_patterns, $this->_array_replaces, $template_str);
            file_put_contents($compile_file, $compile_str);
        }
        catch(Exception $e)
        {
            $flag = false;
        }

        return $flag;
    }

    ```
    2. 缓存功能详解
    
    MicroTemplate默认关闭缓存功能，可通过设置caching配置项打开缓存功能,cache_lifetime配置项设置缓存时间，代码如下：
    
    ```php
    
    $template = new MicroTemplate();
    $template->config('caching', true);
    $template->config('cache_lifetime', 3600);
    
    ```
    
    缓存写入时机：开启缓存且缓存文件不存在
    
    缓存更新时机：开启缓存且缓存过期
    
    缓存删除实现：clear_cache实现指定缓存文件的删除，clear_all_caches实现全部缓存的删除
    
    具体代码如下：
    
    ```php
    /**
     * [display 显示页面]
     * @param  [type] $file [description]
     * @return [type]       [description]
     */
    public function display($file)
    {
        error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
        if(empty($file) || ! file_exists($this->_array_config['template_dir'].DIRECTORY_SEPARATOR.$file.$this->_array_config['suffix']))
        {
            exit('template file '.$this->_array_config['template_dir'].DIRECTORY_SEPARATOR.$file.$this->_array_config['suffix'].' does not exist!');
        }

        $this->file = $file;
        $template_file = $this->_array_config['template_dir'].DIRECTORY_SEPARATOR.$file.$this->_array_config['suffix'];
        $cache_file = $this->_array_config['cache_dir'].DIRECTORY_SEPARATOR.md5($file).$this->_array_config['suffix_cache'];
        $compile_file = $this->_array_config['compile_dir'].DIRECTORY_SEPARATOR.md5($file).$this->_array_config['suffix_compile'];

        if($this->_array_config['caching'])
        {
            if(file_exists($cache_file))
            {
                $filemtime = filectime($cache_file);
                if($filemtime+$this->_array_config['cache_lifetime'] < time())
                {
                    unlink($cache_file);
                }
                else
                {
                    include $cache_file;
                    exit;
                }
            }    
        }

        // 查看编译文件是否存在？
        $needReWrite = false;
        if(! file_exists($compile_file))
        {
            $needReWrite = true;
        } 
        else
        {
            if(filemtime($template_file) > filemtime($compile_file))
            {
                unlink($compile_file);
                $needReWrite = true;
            }
        }

        if($needReWrite)
        {
            if(! $this->_compile())
            {
                exit('create compile file '.$compile_file.' failed!');
            }
        }

        ob_start();
        extract($this->_assign_vars, EXTR_OVERWRITE);
        include $compile_file;
        $data = ob_get_contents();
        ob_end_clean();

        if($this->_array_config['caching'])
        {
            file_put_contents($cache_file, $data);
        }

        echo $data;exit;
    }

    /**
     * [clear_cache 删除指定缓存]
     * @param  [type] $file [description]
     * @return [type]       [description]
     */
    public function clear_cache($file)
    {
        $cache_file = $this->_array_config['cache_dir'].DIRECTORY_SEPARATOR.md5($file).$this->_array_config['suffix_cache'];

        if(file_exists($cache_file))
        {
            unlink($cache_file);
        }

        return TRUE;
    }

    /**
     * [clear_all_caches 清空缓存目录]
     * @return [type] [description]
     */
    public function clear_all_caches()
    {
        $cache_dir = $this->_array_config['cache_dir'].DIRECTORY_SEPARATOR;
        if(is_dir($cache_dir))
        {
            $dh = opendir($cache_dir);
            while( ($file = readdir($dh)) !== FALSE )
            {
                if($file == '.' || $file == '..')
                {
                    continue;
                }

                unlink($cache_dir.$file);
            } 
            closedir($dh);
        }
        return TRUE;
    }

    ```
    以上即是MicroTemplate功能的详细介绍，MicroTemplate完整代码见本人github：[lovecucu/MicroTemplate](https://github.com/lovecucu/MicroTemplate.git)
    
3. PHP模板引擎的思考

使用模板引擎的代价：
    
    1. 加重服务器的负载
    2. 编辑器缺少模板引擎语法提示-难以定位(对于开发人员和前端)
    3. 需要花费额外的时间学习和教授前端模板引擎的语法
    4.很难向前端人员解释模板引擎的存在的意义
    5.给项目增加复杂度
    
使用模板引擎的好处：

    1.极度的无聊且有规律的语法可保证模板文件格式的统一和规整
    2.解决MVC中核心思想：逻辑和视图的分离，更适合团队开发。
    
总结：

当团队开发时，为了保证MVC和模板文件格式统一，则推荐使用模板引擎；其他情况一率不推荐使用，力求回归PHP简单快捷的本质。

#### 参考资料

[When to use PHP template engines](https://stackoverflow.com/questions/5888089/when-to-use-php-template-engines?answertab=votes)

[菜鸟教程-正则表达式](http://www.runoob.com/regexp/regexp-tutorial.html)
    
