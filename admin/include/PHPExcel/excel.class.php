<?php
if (!defined('IN_LOTHAR')) die('Hacking attempt');

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
        $objActSheet->getColumnDimension()->setAutoSize(true);
        // $objActSheet->getColumnDimension('A')->setWidth(30);
        // $objActSheet->getColumnDimension('B')->setAutoSize(true);
        // $objActSheet->getColumnDimension('C')->setAutoSize(30);
        // $objActSheet->getColumnDimension('K')->setAutoSize(true);

        // 设置表格标题文字
        $title = $data['title'] ? $data['title'] : $GLOBALS['_CFG']['site_name'] . '-' . $GLOBALS['_LANG'][$module . '_list'];
        $objActSheet->setCellValue('A1', $title);
        $objActSheet->mergeCells('A1:' . $this->n_to_l(count($data['head'])-1).'1'); // 合并单元格，表格标题文字显示区域

        // 设置表格标题栏内容
        foreach ((array)$data['head'] as $key => $value) {
            $objActSheet->getColumnDimension($this->n_to_l($key))->setAutoSize(true);
            // $objActSheet->getColumnDimension($this->n_to_l($key))->setWidth(strlen($value));// 无法统计最长的
            $objActSheet->setCellValue($this->n_to_l($key) .'2', $value);
        }

        // 生成列表
        foreach ((array)$data['list'] as $row_number => $row) {
            foreach ((array)$row as $key => $value) {
                $objActSheet->setCellValue($this->n_to_l($key) . ($row_number+3), $value);
            }
        }

        // 输出内容
        $outputFileName = strtoupper($module) . '_LIST_' . date('Ymdhi').'.xls';

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

        // header('pragma:public');
        // header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$xlsTitle.'.xls"');
        // header("Content-Disposition:attachment;filename=$fileName.xls");//attachment新窗口打印inline本窗口打印
        // $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');//Excel5为xls格式，excel2007为xlsx格式
        // $objWriter->save('php://output');
        // exit;
    }

    /**
     * +----------------------------------------------------------
     * 导出会员资料
     * +----------------------------------------------------------
     */
    function n_to_l($number) {
        $letter = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $box = str_split($letter);
        return $box[$number];
    }
}
?>