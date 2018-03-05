<?php
/**
 * @product ENHRDC com.maddyhome.idea.copyright.pattern.ProjectInfo@15bf5a9
 * @package ENHRDC.Endpoint
 * @author ENH.Tech
 * @link http://enh-tech.com/ ENH
 * @copyright (c) 2017 Xi'An ENH Technology Co.,Ltd 西安依恩驰网络技术有限公司 All Rights Reserved.
 *
 */

/**
 * 日志操作接口
 */
class api_Log extends MCU\ApiHelper\ApiBase
{
    /**
     * 获取日志文件列表
     *
     * return data array 日志文件列表
     */
    public function log_list($params)
    {
        $log_files = [];
        $log_path_root = MCU\LocalFile::getFilePath("log");
		if(false !== $dir_handle = opendir($log_path_root))
		{
			while (false !== $file_name = @readdir($dir_handle))
			{
				if($file_name == "." || $file_name == "..") continue;
				$full_file_path = "$log_path_root/$file_name";
                $file_size = @filesize($full_file_path);
				$log_files[] = array('name' => $file_name, 'size' => $file_size);
			}
			closedir($dir_handle);
		}

        return R(true, "", $log_files);
    }

    /**
     * 获取日志文件内容
     * @name string 日志文件名
     *
     * return data string 日志文件内容
     */
    public function get_content($params)
    {
        if(false === $file_name = get_param($params, "name"))
        {
            return R(false, "need param 'name'");
        }

        $file_path = MCU\LocalFile::getFilePath("log/$file_name");
        if(false === $file_content = file_get_contents($file_path))
        {
            return R(false, "文件打开失败");
        }
        return R(true, "", $file_content);
    }

    /**
     * 删除日志文件
     * @name string 日志文件名
     */
    public function delete($params)
    {
        if(false === $file_name = get_param($params, "name"))
        {
            return R(false, "need param 'name'");
        }

        $file_path = MCU\LocalFile::getFilePath("log/$file_name");
        if(false === unlink($file_path))
        {
            return R(false, "文件删除失败");
        }
        return R(true, "");
    }
}