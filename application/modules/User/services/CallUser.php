<?php
/**
 * callUser class file
 *
 * @author octopus <zhangguipo@747.cn>
 * @final 2014-09-23
 */
class CallUserService {

	//查询用户信息
	public function getUserInfo($condition) {
		return TZ_Loader::model('CallUser')->select($condition,'*','ROW');
	}
	//添加用户信息
	public function addUser($data){
		return TZ_loader::model('CallUser')->insert($data);
	}
	
	//添加用户信息
	public function getUserList(){
		//$condition['limit'] = "45,50";
		$condition['id:eq']='0';
		return  TZ_loader::model('Open')->select($condition,'uid,telephone','ALL');
	}
}
