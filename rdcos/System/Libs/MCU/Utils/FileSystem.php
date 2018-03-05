<?php

namespace MCU\Utils;

use MCU\Utils\OperationSystem;

class FileSystem
{
    /**
	 * 循环检测并创建文件夹
	 */
	public static function create_dir($dir)
	{
		if (!is_dir($dir))
		{
			self::create_dir(dirname($dir));
			@mkdir($dir, 0777);
		}
	}

    public static function copy_dir($src, $dst)
    {
        if(!is_dir($src)) return 0;
        if(!is_dir($dst)) @mkdir($dst, 0777);

        $dir = opendir($src);
        while(false !== $file = readdir($dir))
        {
            if(($file != ".") && ($file != ".."))
            {
                if(is_dir("$src/$file"))
                {
                    self::copy_dir("$src/$file", "$dst/$file");
                    continue;
                }
                else
                {
                    @copy("$src/$file", "$dst/$file");
                }
            }
        }
        closedir($dir);
    }

    /**
     * 刷新磁盘写入缓存
     *
     * @return void
     */
    public static function refresh_disk_cache()
    {
        OperationSystem::exec("sync");
        OperationSystem::exec("sync");   // 需要指令两次 sync，确保数据写入
    }
}