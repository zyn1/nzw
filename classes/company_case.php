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
    public static function typeList()
    {
        return  array(
            0 => '整体',     
            1 => '客厅',     
            2 => '卧室',
            3 => '厨房',
            4 => '卫生间',
            5 => '阳台'
        );
    }
    public static function houseTypeList()
    {
        return  array( 
            1 => '小户型',
            2 => '二居',
            3 => '三居',
            4 => '四居',
            5 => '复式',
            6 => '别墅'
        );
    }
    public static function priceList()
    {                               
        return  array( 
            1 => '3万以下',
            2 => '3-5万',
            3 => '5-8万',
            4 => '8-12万',
            5 => '12-18万',
            6 => '18-30万',
            7 => '30-100万',
            8 => '100万以上',
        );
    }
   
    /**
     * 获取案例类型文字
     * @param $typeCode int 类型码
     * @return string 类型说明
     */
    public static function typeText($typeCode)
    {
        $result =  self::typeList();
        return isset($result[$typeCode]) ? $result[$typeCode] : '';
    }
    
    /**
     * 获取案例房屋类型文字
     * @param $typeCode int 类型码
     * @return string 类型说明
     */
    public static function houseTypeText($typeCode)
    {
        $result = self::houseTypeList();
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
    
    //相关推荐
    public static function getRecommList($user_id, $style, $id=null, $limit=4)
    {                      
        $caseDB = new IModel('case');
        $temp = explode(',',$style);
        $where = 'user_id = '.$user_id;
        if($id)
        {
            $where .= ' and id !='.$id;
        }
        $_where = array();
        foreach($temp as $v)
        {
            $_where[] = 'find_in_set("'.$v.'",style)';
        }
        $where .= ' and ('.implode(' or ', $_where).')';
        $data = $caseDB->query($where,'id,title,photo','id desc', $limit);
        $count = count($data);
        if($id && $count > 0 && $count < $limit)
        {
            $localData = $caseDB->getObj('id = '.$id,'id,title,photo');
            $data[] = $localData;
        }              
        return $data;
    }
}