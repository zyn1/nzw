<?php
  class user_history{
  /**
     * @记录用户浏览记录同一天同一产品不做重复记录
     * @
     */
    public static function set_user_history($goods_id,$user_id=false){
        if(!$user_id){
            ISession::set('user_history',array('goods_id'=>$goods_id,'time'=>ITime::getDateTime('Y-m-d')));
        }else{
            $history = new IQuery('category_extend as ca');
            $time = ITime::getDateTime('Y-m-d');
            $data = array('user_id'=>$user_id,'goods_id'=>$goods_id,'time'=>$time);
            $history->join = 'left join user_history as h on (h.goods_id = ca.goods_id and  h.user_id = '.$user_id. ' and DATEDIFF(NOW(),h.time) < 1)';
            $history->fields = 'h.time,ca.category_id';
            $history->where= ' ca.goods_id = '.$goods_id;
            $history->limit = 1;
            $hisData = $history->find();
            //print_r($hisData);
            if($hisData){//商品有分类
                if(!$hisData[0]['time']){//当日未访问
                    $data['cat_id'] = isset($hisData['category_id'])?$hisData['category_id'] : 0;
                    $history = new IModel('user_history');
                    $history->setData($data);
                    $history->add();
                }
                return false;
            }
            $his = new IModel('user_history');
            if(!$his->getObj('goods_id='.$goods_id.' and user_id = '.$user_id.' and DATEDIFF(NOW(),time) < 1','id')){
                $his->setData($data);
                $his->add();
            }
        }
    }
  }
?>
