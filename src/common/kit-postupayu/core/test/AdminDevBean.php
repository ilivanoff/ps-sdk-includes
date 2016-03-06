<?php

/**
 * Бин администратора, доступ к которому должен быть только в devmode
 *
 * @author Admin
 */
class AdminDevBean extends BaseBean {

    public function testDates() {
//        $id = $this->insert('INSERT INTO test (dt) values (?)', array('2012-01-01'));
//        $id = $this->insert('INSERT INTO test (dt) values (?)', array('2013-01-01 12:30:45'));

        $arr = $this->getArray('select * from test');
        foreach ($arr as $data) {
            $dt = $data['dt'];
            print_r($data);
            echo strtotime($dt);
            echo ' ';
            print date('d.m.Y', strtotime($dt)) . "\n";
            br();
        }
    }

    public function createTestUser() {
        $id = $this->insert('
insert into users
  (user_name, b_sex, email, passwd, dt_reg, msg)
values
  (?, ?, ?, ?, UNIX_TIMESTAMP(), ?)', array(
            '',
            rand(SEX_BOY, SEX_GIRL),
            '@mail.ru',
            md5('1'), //
            getRandomString(100, true, 10)));

        $this->update('update users set user_name=?, email=? where id_user=?', array(
            "user$id",
            "$id@mail.ru",
            $id
        ));

        return $id;
    }

    public function getUserIds($userId = null) {
        return is_numeric($userId) ? array(1 * $userId) : $this->getIds('select id_user as id from users');
    }

    public function isTestUser($userId) {
        return $this->getCnt('select count(1) as cnt from users where id_user=? and user_name=? and b_admin=0', array($userId, "user$userId")) > 0;
    }

    public function removeTestUser($userId) {
        if ($this->isTestUser($userId)) {
            try {
                $this->update('delete from users where id_user=?', $userId);
            } catch (Exception $e) {
                
            }
        }
    }

    public function unsetAvatarUploads($userId) {
        $this->update('update users SET id_avatar = null WHERE id_user = ?', $userId);
        $this->update('delete from ps_upload where id_user=? and type=?', array($userId, AvatarUploader::inst()->getDbType()));
    }

    public function getRandomUserId() {
        $userId = $this->getRec("select id_user from users u where u.id_user!=2 order by RAND() limit 1");
        return (int) $userId['id_user'];
    }

    /** @return AdminDevBean */
    public static function inst() {
        return parent::inst();
    }

    protected function __construct() {
        PsDefines::assertProductionOff(__CLASS__);
        AuthManager::checkAdminAccess();
        parent::__construct();
    }

}

?>