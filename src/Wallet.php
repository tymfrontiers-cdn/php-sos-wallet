<?php
namespace SOS;

class Wallet{
  use \TymFrontiers\Helper\MySQLDatabaseObject,
      \TymFrontiers\Helper\Pagination;

  protected static $_db_name = MYSQL_BASE_DB;
  protected static $_table_name = "wallets";
  protected static $_primary_key='id';
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
    $this->_init($user, $currency);
  }
  public function credit(string $user, string $currency, float $amount, string $naration, string $alert_email = "") {

  }
  public function debit(string $user, string $currency, float $amount, string $naration, string $alert_email = "") {

  }
  private function _init (string $user, string $currency) {
    global $database;
    if (self::findBySql("SELECT id FROM :db:.:tbl: WHERE `user`='{$database->escapeValue($user)}' AND `currency`='{$database->escapeValue($currency)}' LIMIT 1")) {
      $this->_load($user, $currency);
    } else {
      $this->_register($user, $currency);
    }
  }
  private function _load(string $user, string $currency) {}
  private function _register(string $user, string $currency) {}
  private function _queue_alert (string $user, string $naration) {

  }
}
