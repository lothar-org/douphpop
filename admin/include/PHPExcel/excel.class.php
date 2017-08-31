<?php
/**
 * WincomtechPHP
 * --------------------------------------------------------------------------------------------------
 * 版权所有 2013-2035 XXX网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.wowlothar.cn
 * --------------------------------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在遵守授权协议前提下对程序代码进行修改和使用；不允许对程序代码以任何形式任何目的的再发布。
 * 授权协议：http://www.wowlothar.cn/license.html
 * --------------------------------------------------------------------------------------------------
 * Author: Lothar
 * Release Date: 2015-06-10
 */
if (!defined('IN_LOTHAR')) {
    die('Hacking attempt');
}
class Excel {
    /**
     * +----------------------------------------------------------
     * 导出会员资料
     * +----------------------------------------------------------
     * $module 模块
     * $data 数据源
     * +----------------------------------------------------------
     */
    function export_excel($module, $data = '') {
        require (ROOT_PATH . ADMIN_PATH . '/include/PHPExcel/PHPExcel.php');
        require (ROOT_PATH . ADMIN_PATH . '/include/PHPExcel/Excel5.php');
        
        // 创建一个处理对象实例
        $objExcel = new PHPExcel();
        
        // 创建文件格式写入对象实例, uncomment
        $objWriter = new PHPExcel_Writer_Excel5($objExcel);
        
        //*************************************       
        //设置当前的sheet索引，用于后续的内容操作。       
        //一般只有在使用多个sheet的时候才需要显示调用。       
        //缺省情况下，PHPExcel会自动创建第一个sheet被设置SheetIndex=0       
        $objExcel->setActiveSheetIndex(0);       
        $objActSheet = $objExcel->getActiveSheet();       
    
        // 设置单元格宽度     
        $objActSheet->getColumnDimension('C')->setAutoSize(30);
        $objActSheet->getColumnDimension('K')->setAutoSize(true);
        
        // 表格标题文字
        $objActSheet->setCellValue('A1', $GLOBALS['_CFG']['site_name'] . '-' . $GLOBALS['_LANG'][$module . '_list']);    
        $objActSheet->mergeCells('A1:K1'); // 表格标题文字显示区域
        
        // 设置表格标题栏内容
        foreach ((array)$data['head'] as $key => $value) {
            $objActSheet->setCellValue($this->number_to_letter($key + 1) . '2', $value);
        }
        
        // 生成列表
        foreach ((array)$data['list'] as $row_number => $row) {
            foreach ((array)$row as $key => $value) {
                $objActSheet->setCellValue($this->number_to_letter($key + 1) . ($row_number + 3), $value);
            }
        }
        
        // 输出内容       
        $outputFileName = strtoupper($module) . '_LIST_' . date('Ymdhi').".xls";   
        
        // 到文件       
        $objWriter->save(ROOT_PATH . 'cache/' . $outputFileName);       
       
        // 文件直接输出到浏览器
        header ( 'Pragma:public');
        header ( 'Expires:0');
        header ( 'Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header ( 'Content-Type:application/force-download');
        header ( 'Content-Type:application/vnd.ms-excel');
        header ( 'Content-Type:application/octet-stream');
        header ( 'Content-Type:application/download');
        header ( 'Content-Disposition:attachment;filename='. $outputFileName );
        header ( 'Content-Transfer-Encoding:binary');
        $objWriter->save ( 'php://output');
         
        @unlink(ROOT_PATH . 'cache/' . $outputFileName);
    } 

    /**
     * +----------------------------------------------------------
     * 导出会员资料
     * +----------------------------------------------------------
     */
    function number_to_letter($number) {
        $box = array(1 => 'A', 2 => 'B', 3 => 'C', 4 => 'D', 5 => 'E', 6 => 'F', 7 => 'G', 8 => 'H', 9 => 'I', 10 => 'J', 11 => 'K', 12 => 'L', 13 => 'M', 14 => 'N', 16 => 'O', 16 => 'P', 17 => 'Q', 18 => 'R', 19 => 'S', 20 => 'T', 21 => 'U', 22 => 'V', 23 => 'W', 24 => 'X', 25 => 'Y', 26 => 'Z');
        
        return $box[$number];
    } 
}
?>