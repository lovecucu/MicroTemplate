<?php

/**
 * Project:     MicroTemplate: the PHP compiling template engine
 * File:        MicroTemplate.class.php
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * For questions, help, comments, discussion, etc., please join the
 * mailing list below. Send a blank e-mail to
 * lovecucu1314@gmail.com
 * 
 * @copyright 2001-2005 New Digital Group, Inc.
 * @author lovecucu
 * @package MicroTemplate
 * @version 1.0.0
 */

class MicroTemplate
{
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

    private $_assign_vars = array(); // 存储分配的变量

    public $file; // 模板文件名

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

    /**
     * [assign 分配变量到模板文件]
     * @param  [type] $tpl_var [description]
     * @param  [type] $value   [description]
     * @return [type]          [description]
     */
    public function assign($tpl_var, $value=NULL)
    {
        if (is_array($tpl_var))
        {
            foreach ($tpl_var as $key => $val) 
            {
                if ($key != '') 
                {
                    $this->_assign_vars[$key] = $val;
                }
            }
        } 
        else 
        {
            if ($tpl_var != '') $this->_assign_vars[$tpl_var] = $value;
        }
    }

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
}