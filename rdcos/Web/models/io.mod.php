<?php
use MCU\ApiHelper\ApiClient;

class mod_io extends WebModule
{
    public function __init()
    {
        $this -> title = "IO";
        $this -> powers = [];
        $this -> ignore_main = ['yuantu'];
        $this -> ignore_view = ['yuantu_download'];
    }

    public function view_yuantu()
    {
        $source_file_path = MCU\LocalFile::getFilePath("yuantu/submit_faild.log");
        $action = Http::param("action");

        if($action == "remove")
        {
            unlink($source_file_path);
            header("Location: /?um=io&ua=yuantu");
            return;
        }

        $device_name = ApiClient::req("SysConfig.get", ["key" => "device_name", "default" => "unknow"]);
        $data_size = 0;
        clearstatcache();
        if(file_exists($source_file_path)) $data_size = @filesize($source_file_path);

        $this -> data['device_name'] = $device_name;
        $this -> data['size'] = $data_size;
        $this -> data['size_text'] = size_text($data_size);
    }

    public function view_yuantu_download()
    {
        if(false === $source_file_path = MCU\LocalFile::getExistsPath("yuantu/submit_faild.log"))
        {
            echo "没有数据文件";
            return;
        }

        $operator = Http::param("operator");
        MCU\LocalFile::putLine("record/databackup.log", date("Y-m-d H:i:s") . "\t" . @filesize($source_file_path) . "\t$operator", true);  // 记录下载历史

        $device_name = Http::param("device_name");
        if(empty($device_name)) $device_name = ApiClient::req("SysConfig.get", ["key" => "device_name", "default" => "unknow"]);
        $show_name = "$device_name.databackup." . date("YmdHis") . ".log";
        $file_path_encrypt = encrypt($source_file_path, "download");
        header("Location: /?um=sys&ua=download&file_path=$file_path_encrypt&show_name=$show_name");
    }

    public function view_yuantu_clear()
    {
        if(false === $source_file_path = MCU\LocalFile::getExistsPath("yuantu/submit_faild.log"))
        {
            return;
        }
        unlink($source_file_path);
    }
}
