<?php

/**
 * user service file
 *
 * @author octopus <zhangguipo@747.cn>
 * @final 2013-3-24
 */
class UserService {

    private $_blackkey = 'user:blacklist';

    const MEMBERSHIP_SCORE = 10;

    /**
     * register
     *
     * @param string $telephone
     * @param string $password
     * @param string $name
     * @return array
     */
    public function register($telephone, $password, $name, $refertelephone, $validate) {
        $usersModel = TZ_Loader::model('Users');
        //查询用户是否注册
        $result     = $usersModel->select(array('telephone:eq' => $telephone), '*', 'ROW');
        if (!empty($result))
        {
            throw new Exception('用户已经注册。');
        }
     for ($i = 0; $i < 1000; $i++)
        {
            $inviteCode = $this->productCode();
            $codeInfo   = $usersModel->select(array('invite_code:eq' => $inviteCode, 'id', 'ROW'));
            if (count($codeInfo) == 0)
            {
                break;
            }
        }
        $idManager = TZ_Loader::service('IdManager');
        $db        = Yaf_Registry::get('user_db');
        $db->transBegin();
        //create user

        $userId                  = $idManager->createUID();
        $userInfo['id']          = $userId;
        $userInfo['telephone']   = $telephone;
        $userInfo['password']    = $password;
        $userInfo['name']        = $name;
        $userInfo['vip']         = 1;
        $userInfo['is_verified'] = $validate;
        $userInfo['created_at']  = $currentTime             = date('Y-m-d H:i:s');
        $userInfo['updated_at']  = $currentTime;
        $userInfo['invite_code'] = $inviteCode;
        $usersModel->insert($userInfo);
        if (false === $db->transStatus())
        {
            $db->rollback();
            throw new Exception('创建用户失败。');
        }
        $relationModel          = TZ_Loader::model('UniqueUsers');
        $uniqueId               = $idManager->createUid();
        $relation['id']         = $uniqueId;
        $relation['mapper_id']  = $userId;
        $relation['old_id']     = $userId;
        $relation['type']       = 1;
        $relation['created_at'] = $currentTime;
        $relation['updated_at'] = $currentTime;
        $relationModel->insert($relation);
        if (false === $db->transStatus())
        {
            $db->rollback();
            throw new Exception('创建用户关系失败。');
        }
		//第四步，给注册人增加积分
		$accountInfo['id'] =TZ_Loader::service('IdManager', 'User')->createUid();
		$accountInfo['unique_user_id'] = $uniqueId;
		$accountInfo['score'] = 0;
		$accountInfo['status'] = 1;
		$accountInfo['created_at'] = $accountInfo['updated_at'] = date('Y-m-d H:i:s');
		TZ_Loader::model('Account')->insert($accountInfo);

		if (false === $db->transStatus()) {
			$db->rollback();
			throw new Exception("error");
		}
		//第五步，写入积分日志
		$balanceInfo['id'] = TZ_Loader::service('IdManager')->createUid();
		$balanceInfo['unique_user_id'] = $uniqueId;
		$balanceInfo['score'] = 0;
		$balanceInfo['original_score'] = 0;
		$balanceInfo['incr'] = 0;
		$balanceInfo['beans'] = 0;
		$balanceInfo['remainders'] = 0;
		$balanceInfo['status'] = 0;
		$balanceInfo['created_at'] = $balanceInfo['updated_at'] = date('Y-m-d H:i:s');
		$balanceInfo['telephone'] = $telephone;
		$balanceInfo['type'] = "注册";
		TZ_Loader::model('AccountBalance')->insert($balanceInfo);
		if (false === $db->transStatus()) {
            $db->rollback();
            throw new Exception("error");
        }
        $db->commit();
        return TZ_Loader::service('SessionManager')->create($uniqueId);
    }

    /**
     * login
     *
     * @param string $telephone
     * @param string $password
     * @return string
     */
    public function login($telephone, $password) {

        $userInfo = TZ_Loader::model('Users')->getInfoByTelephone($telephone);
        if (empty($userInfo))
            throw new Exception('用户不存在，请先注册。');
        if (strtoupper($userInfo['password']) != strtoupper($password))
            throw new Exception('密码错误，请重试。');
        return TZ_Loader::service('SessionManager')->create($userInfo['uid']);
    }

    /**
     * logout
     *
     * @param string $sessionId
     * @return boolean
     */
    public function logout($sessionId) {
        return TZ_Loader::service('SessionManager', 'User')->discard($sessionId);
    }

    /**
     * change password
     *
     * @param string $sessionId
     * @param string $oldPassword
     * @param string $password
     * @return boolean
     */
    public function changePassword($sessionId, $oldPassword, $password) {
        $uid = TZ_Loader::service('SessionManager', 'User')->getUid($sessionId);
        if (!$uid)
            throw new Exception('您还没有登陆，无法执行此操作。');

        $userModel = TZ_Loader::model('Users');
        $userInfo  = $userModel->getInfoByUid($uid);
        if (strtoupper($userInfo['password']) != strtoupper($oldPassword))
            throw new Exception('密码错误，请重试。');

        $set['password'] = $password;
        return $userModel->updateInfoByUid($uid, $set);
    }

    /**
     * reset password
     *
     * @param string $telephone
     * @param string $password
     * @return boolean
     */
    public function resetPassword($telephone, $password) {
        $userModel = TZ_Loader::model('Users');
        $userInfo  = $userModel->getInfoByTelephone($telephone);
        if (empty($userInfo))
            throw new Exception('用户不存在，无法执行此操作。');

        $set['password'] = $password;
        return $userModel->updateInfoByUid($userInfo['uid'], $set);
    }

    /**
     * get user info
     *
     * @param string $uid
     * @param array $fields
     * @return array
     */
    public function getInfoByUid($uid, $fields = array()) {
        return TZ_Loader::model('Users')->getInfoByUid($uid, $fields);
    }

    /**
     * get user info by telephone
     *
     * @param string $telephone
     * @param array $fields
     * @return array
     */
    public function getInfoByTelephone($telephone, $fields = array()) {
        return TZ_Loader::model('Users')->getInfoByTelephone($telephone, $fields);
    }

    /**
     * update user info
     *
     * @param string $uid
     * @param string $userInfo
     * @return array
     */
    public function updateInfo($uid, $userInfo) {
        return TZ_Loader::model('Users')->updateInfoByUid($uid, $userInfo);
    }

    /**
     * delete user info
     *
     * @param string $telephone
     * @return int
     */
    public function deleteInfo($telephone) {
        return TZ_Loader::model('Users')->deleteInfoByTelephone($telephone);
    }

    /**
     * logout
     *
     * @param string $sessionId
     * @return boolean
     */
    public function validTelphone($telephone) {
        $condition                 = array();
        $condition['telephone:eq'] = $telephone;
        $set['is_verified']        = 1;
        $set['updated_at']         = date('Y-m-d H:i:s');
        TZ_Loader::model('Users')->update($set, $condition);
        return TZ_Loader::model('Users')->delCache($telephone);
    }

    /**
     * delete user info
     *
     * @param string $telephone
     * @return int
     */
    public function delUser($telephone) {

        $db       = Yaf_Registry::get('user_db');
        $db->transBegin();
        //得到用户信息
        $userInfo = TZ_Loader::model('Users')->getInfoByTelephone($telephone, array());
        if (false === $db->transStatus())
        {
            $db->rollback();
            throw new Exception('获取user失败。');
        }

        //删除用户表user
        TZ_Loader::model('Users')->deleteInfoByTelephone($telephone);
        if (false === $db->transStatus())
        {
            $db->rollback();
            throw new Exception('删除user失败。');
        }

        //删除base_user
        TZ_Loader::model('Users')->deleteBaseUser($telephone);
        if (false === $db->transStatus())
        {
            $db->rollback();
            throw new Exception('删除base_user失败。');
        }
        //删除user_card
        TZ_Loader::model('Users')->deleteUserCard($telephone);
        if (false === $db->transStatus())
        {
            $db->rollback();
            throw new Exception('删除user_card失败。');
        }
        //删除account
        TZ_Loader::model('Users')->deleteAccount($userInfo['id']);
        if (false === $db->transStatus())
        {
            $db->rollback();
            throw new Exception('删除account失败。');
        }
        //删除account_balance
        TZ_Loader::model('Users')->deleteAccountBalance($userInfo['id']);
        if (false === $db->transStatus())
        {
            $db->rollback();
            throw new Exception('删除account_balance失败。');
        }
        //commit
        $db->commit();

        return true;
    }

    public function addLottery($you, $pwd, $her) {
        return TZ_Loader::model('Users')->addLottery($you, $her, $pwd);
    }

    //测试rabbitmq
    public function AddRabbitMQ($telephone) {
        TZ_Loader::service('Rabbit', 'User')->testBitWriteRead();
        return true;
    }



    //加入会员
    public function memberShip($uid) {

        $arUserInfo = TZ_Loader::model('Users')->getInfoByUid($uid, array('vip', 'telephone'));
        $sVip       = $arUserInfo['vip'];
        //判断是否已经是vip
        if ($sVip > 1)
        {
            throw new Exception('您已经是会员。');
        }
        $set['vip'] = 2;
        //修改会员信息
        TZ_Loader::model('Users')->updateInfoByUid($uid, $set);
        //成为会员奖励10个银豆
        TZ_Loader::service('Account', 'Score')->setScore($uid, self::MEMBERSHIP_SCORE, "成为会员奖励", $arUserInfo['telephone']);
        return self::MEMBERSHIP_SCORE;
    }

  	public function productCode(){
    		$list =array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
    		$cmax = count($list) - 1;
    		$Code = '';
    		for ( $i=0; $i < 8; $i++ ){
    			$randnum = mt_rand(0, $cmax);
    			$Code .= $list[$randnum];           //取出字符，组合成为我们要的验证码字符
    		}
			return $Code;
    	}
}
