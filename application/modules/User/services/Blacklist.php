<?php
/**
 * Blacklist class file
 *
 * @author octopus <zhangguipo@747.cn>
 * @final 2013-08-08
 */
class BlacklistService {
    
    /**
     * In list
     *
     * @param $telephone string
     * @return bool
     */
    public function inList($telephone) {
        return TZ_Loader::model('Blacklist')->inList($telephone);         
    }
}
