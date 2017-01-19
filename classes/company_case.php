<?php
/**                                  
 * @file company_sace.php
 * @brief 装修公司管理
 * @author zyn
 * @date 2017-1-19 11:28:34
 * @version 1.0
 */
class company_case
{   
    /**
     * 获取案例类型文字
     * @param $typeCode int 类型码
     * @return string 类型说明
     */
    public static function typeText($typeCode)
    {
        $result = array(
            0 => '整体',     
            1 => '客厅',     
            2 => '卧室',
            3 => '厨房',
            4 => '卫生间',
            5 => '阳台'
        );
        return isset($result[$typeCode]) ? $result[$typeCode] : '';
    }
    
    /**
     * 获取案例房屋类型文字
     * @param $typeCode int 类型码
     * @return string 类型说明
     */
    public static function houseTypeText($typeCode)
    {
        $result = array( 
            1 => '小户型',
            2 => '二居',
            3 => '三居',
            4 => '四居',
            5 => '复式',
            6 => '别墅'
        );
        return isset($result[$typeCode]) ? $result[$typeCode] : '';
    }
    
    /**
     * 获取案例房屋类型文字
     * @param $styleCode varchar 风格码
     * @return string 类型说明
     */
    public static function styleText($styleCode)
    {
        $db = new IModel('case_style');       
        $data = $styleCode ? $db->query('id in ('.$styleCode.')', 'name') : '';
        $result = '';
        if($data)
        {
             foreach($data as $v)
             {
                $result .= $v['name'].'&nbsp;&nbsp;'; 
             }
        }
        return $result;
    }
}