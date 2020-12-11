<?php
namespace SOS;
use \TymFrontiers\Data,
    \TymFrontiers\MultiForm,
    \TymFrontiers\Validator,
    \TymFrontiers\Generic,
    \TymFrontiers\InstanceError;

class Wallet{
  use \TymFrontiers\Helper\MySQLDatabaseObject,
      \TymFrontiers\Helper\Pagination;

  protected static $_db_name = MYSQL_BASE_DB;
  protected static $_table_name = "wallets";
  protected static $_primary_key='id';
  protected static $_prop_type = [];
  protected static $_prop_size = [];
  protected static $_db_fields = [
    "id",
    "user",
    "currency",
    "balance",
    "_updated"
  ];

  public $id;
  public $user;
  public $currency;
  public $balance;

  protected $_updated;

  function __construct(string $user, string $currency) {
    if (
      $user = (new Validator)->username($user, ["user","username",3,32,[],"UPPER",["-",".","_"]])
      && $currency = (new Validator)->username($currency, ["currency","username",3,5])
    ) {
      $this->_init($user, $currency);
      var_dump("valid");
    }
  }
  public function credit(string $user, string $currency, float $amount, string $naration, string $alert_email = "") {

  }
  public function debit(string $user, string $currency, float $amount, string $naration, string $alert_email = "") {

  }
  private function _init (string $user, string $currency) {
    self::_checkEnv();
    global $database;
    if ($found = self::findBySql("SELECT * FROM :db:.:tbl: WHERE `user`='{$database->escapeValue($user)}' AND `currency`='{$database->escapeValue($currency)}' LIMIT 1")) {
      $this->_load($found[0]);
    } else {
      echo $database->last_query;
      $this->_register($user, $currency);
    }
  }
  private function _load(self $user) {
    echo "loading";
    var_dump($user);
  }
  private function _register(string $user, string $currency) {
    global $database;
    $data = new Data;
    $gen = new Generic;
    $params = $gen->requestParam([
      "user" => ["user","username",3,32,[],"UPPER",["-",".","_"]],
      "currency" => ["currency","username", 3, 6]
    ], ["user"=>$user, "currency"=>$currency], ["user","currency"]);
    echo "called:";
    if (!$params || !empty($gen->errors)) {
      $errors = (new InstanceError($gen,true))->get("requestParam",true);
      throw new \Exception("Invalid string parsed for user/currency: ".\implode(" | ", $errors), 1);
    }
    $this->user = $database->escapeValue($params["user"]);
    $this->currency = $database->escapeValue($params["currency"]);
    $this->balance = $data->encodeEncrypt("0.00");
    if (!$this->_create()) {
      throw new \Exception("Failed to register Wallet user", 1);
    }
  }
  private function _queue_alert (string $user, string $naration) {

  }
  // check environment
  private static function _checkEnv(){
    global $database;
    if ( !$database instanceof \TymFrontiers\MySQLDatabase ) {
      if(
        !\defined("MYSQL_BASE_DB") ||
        !\defined("MYSQL_SERVER") ||
        !\defined("MYSQL_GUEST_USERNAME") ||
        !\defined("MYSQL_GUEST_PASS")
      ){
        throw new \Exception("Required defination(s)[MYSQL_BASE_DB, MYSQL_SERVER, MYSQL_GUEST_USERNAME, MYSQL_GUEST_PASS] not [correctly] defined.", 1);
      }
      // check if guest is logged in
      $GLOBALS['database'] = new \TymFrontiers\MySQLDatabase(MYSQL_SERVER, MYSQL_GUEST_USERNAME, MYSQL_GUEST_PASS);
    }
  }
}
