<?php
namespace SOS;
include "Eml-Temp.php";
use \TymFrontiers\Data,
    \TymFrontiers\MultiForm,
    \TymFrontiers\Validator,
    \TymFrontiers\Generic,
    \TymFrontiers\MySQLDatabase,
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
  protected $_conn = false;

  public $errors = [];

  function __construct(string $user, string $currency) {
    $gen = new Generic;
    $arg = $gen->requestParam([
      "user" => ["user","username",3,32,[],"UPPER",["-",".","_"]],
      "currency" => ["currency","username",3,5]
    ],[
      "user" => $user,
      "currency" => $currency
    ], ["user","currency"]);
    if ($arg && empty($gen->errors)) {
      $this->_init($arg["user"], $arg["currency"]);
    }
  }
  public function credit(float $amount, string $narration, ? string $alert_email = null, string $bearer = "") {
    // save narration
    if ($this->user == $bearer) throw new \Exception("[user]: '{$this->user}' cannot be same as [bearer]: {$bearer}", 1);

    if (!$this->_conn) $this->_conn = new MySQLDatabase(MYSQL_SERVER, MYSQL_DEVELOPER_USERNAME, MYSQL_DEVELOPER_PASS);
    $data = new Data;
    if (!empty($this->user)) {
      $this->balance += $amount;
      $query  = "UPDATE `".self::$_db_name."`.`".self::$_table_name."` ";
      $query .= "SET `balance` = '{$this->_conn->escapeValue($data->encodeEncrypt($this->balance))}' ";
      $query .= "WHERE `user` = '{$this->_conn->escapeValue($this->user)}' ";
      $query .= "AND `currency` = '{$this->_conn->escapeValue($this->currency)}' LIMIT 1";
      if ($this->_conn->query($query)) {
        if (!empty($this->_conn->errors["query"])) unset($this->_conn->errors["query"]);
        $inset = "INSERT INTO `".self::$_db_name."`.`wallet_history` (`user`, `type`, `currency`, `amount`, `new_balance`, `narration`) VALUES (
          '{$this->_conn->escapeValue($this->user)}',
          'CREDIT',
          '{$this->currency}',
          {$amount},
          {$this->balance},
          '{$this->_conn->escapeValue($narration)}'
        )";
        if (!$this->_conn->query($inset)) {
          if ($na_errs = (new InstanceError($this->_conn,true))->get("query")) {
            foreach ($na_errs as $err) {
              $this->errors["credit"][] = $err;
            }
          }
          $this->errors["credit"][] = [1,256,"Failed to create wallet history.",__FILE__,__LINE__];
          if (!empty($this->_conn->errors["query"])) unset($this->_conn->errors["query"]);
          return false;
        }
        if (!empty($alert_email)) $this->_queue_alert($amount, $alert_email, $narration, "CREDIT");
        if (!empty($bearer)) {
          $debit = new Self($bearer, $this->currency);
          return $debit->debit($amount, $narration, $alert_email);
        }
        return true;
      } else {
        $this->errors["credit"][] = [0,256,"Failed to credit wallet.",__FILE__,__LINE__];
        $this->balance -= $amount;
        if (!empty($this->_conn->errors["query"])) {
          $q_errors = (new InstanceError($this->_conn,true))->get("query");
          foreach($q_errors as $qe) {
            $this->errors["credit"][] = $qe;
          }
        }
      }
    }
    return false;
  }
  public function debit(float $amount, string $narration, ? string $alert_email = null) {
    // save narration
    if (!$this->_conn) $this->_conn = new MySQLDatabase(MYSQL_SERVER, MYSQL_DEVELOPER_USERNAME, MYSQL_DEVELOPER_PASS);
    $data = new Data;
    if (!empty($this->user)) {
      $this->balance -= $amount;
      $query  = "UPDATE `".self::$_db_name."`.`".self::$_table_name."` ";
      $query .= "SET `balance` = '{$this->_conn->escapeValue($data->encodeEncrypt($this->balance))}' ";
      $query .= "WHERE `user` = '{$this->_conn->escapeValue($this->user)}' ";
      $query .= "AND `currency` = '{$this->_conn->escapeValue($this->currency)}' LIMIT 1";
      if ($this->_conn->query($query)) {
        if (!empty($this->_conn->errors["query"])) unset($this->_conn->errors["query"]);
        $inset = "INSERT INTO `".self::$_db_name."`.`wallet_history` (`user`, `type`, `currency`, `amount`, `new_balance`, `narration`) VALUES (
          '{$this->_conn->escapeValue($this->user)}',
          'DEBIT',
          '{$this->currency}',
          {$amount},
          {$this->balance},
          '{$this->_conn->escapeValue($narration)}'
        )";
        if (!$this->_conn->query($inset)) {
          if ($na_errs = (new InstanceError($this->_conn,true))->get("query")) {
            foreach ($na_errs as $err) {
              $this->errors["credit"][] = $err;
            }
          }
          $this->errors["debit"][] = [1,256,"Failed to create wallet history.",__FILE__,__LINE__];
          if (!empty($this->_conn->errors["query"])) unset($this->_conn->errors["query"]);
          return false;
        }
        if (!empty($alert_email)) $this->_queue_alert($amount, $alert_email, $narration, "DEBIT");
        return true;
      } else {
        $this->errors["debit"][] = [0,256,"Failed to debit wallet.",__FILE__,__LINE__];
        $this->balance -= $amount;
        if (!empty($this->_conn->errors["query"])) {
          $q_errors = (new InstanceError($this->_conn,true))->get("query");
          foreach($q_errors as $qe) {
            $this->errors["debit"][] = $qe;
          }
        }
      }
    }
    return false;
  }
  private function _init (string $user, string $currency) {
    self::_checkEnv();
    global $database;
    if ($found = self::findBySql("SELECT * FROM :db:.:tbl: WHERE `user`='{$database->escapeValue($user)}' AND `currency`='{$database->escapeValue($currency)}' LIMIT 1")) {
      $this->_load($found[0]);
    } else {
      $this->_register($user, $currency);
    }
  }
  private function _load(? self $user) {
    if ($user instanceof self) {
      foreach ($user as $key => $value) {
        if (\property_exists($this, $key)) $this->$key = $value;
      }
      $this->balance = (float) (new Data)->decodeDecrypt($this->balance);
    }
  }
  private function _register(string $user, string $currency) {
    global $database;
    $data = new Data;
    $gen = new Generic;
    $params = $gen->requestParam([
      "user" => ["user","username",3,32,[],"UPPER",["-",".","_"]],
      "currency" => ["currency","username", 3, 6]
    ], ["user"=>$user, "currency"=>$currency], ["user","currency"]);
    if (!$params && !empty($gen->errors)) {
      $errors = (new InstanceError($gen,true))->get("requestParam",true);
      throw new \Exception("Invalid string parsed for user/currency: ".\implode(" | ", $errors), 1);
    }
    $this->user = $database->escapeValue($params["user"]);
    $this->currency = $database->escapeValue($params["currency"]);
    $this->balance = $data->encodeEncrypt("0.00");
    if (!$this->_create()) {
      throw new \Exception("Failed to register Wallet user", 1);
    }
    $this->balance = 0.00;
  }
  private function _queue_alert (float $amount, string $email, string $narration, string $type = "CREDIT") {
    $email_prop = Generic::splitEmailName($email);
    if (empty($email_prop["email"])) throw new \Exception("Invalid/empty email parsed as argument.", 1);
    global $email_replace_pattern, $alert_eml;
    if (empty($email_replace_pattern) || \is_array($email_replace_pattern)) {
      $email_replace_pattern = [
        "name" => "%name%",
        "surname" => "%surname%",
        "email" => "%email%",
        "type" => "%type%",
        "currency" => "%currency%",
        "amount" => "%amount%",
        "new_balance" => "%new_balance%",
        "date" => "%date%"
      ];
      $replace_val = [
        "name" => $email_prop["name"],
        "surname" => "",
        "email" => $email_prop["email"],
        "type" => $type,
        "date" => \strftime(\TymFrontiers\BetaTym::MYSQL_DATETYM_STRING,\time()),
        "currency" => $this->currency,
        "amount" => \number_format($amount, (\in_array($this->currency, ["NGN","USD","GBP","EUR"]) ? 2 :8), ".", ","),
        "new_balance" => \number_format($this->balance, (\in_array($this->currency, ["NGN","USD","GBP","EUR"]) ? 2 :8), ".", ",")
      ];
      $message = $alert_eml;
      foreach ($replace_val as $prop=>$value) {
        $message = \str_replace($email_replace_pattern[$prop], $value, $message);
      }
      $subject = "New [{$type}] transaction on your {$this->currency} Wallet";
      $msg_text = "Debit of {$replace_val["currency"]} {$replace_val["amount"]} occured on your {$this->currency} Wallet";
      $receiver = empty($email_prop["name"])
        ? $email_prop["email"]
        : "{$email_prop["name"]} <{$email_prop["email"]}>";
      $queue = new EMailer([
        "sender" => PRJ_AUTO_EMAIL,
        "receiver" => $receiver,
        "subject" => $subject,
        "msg_html" => $message,
        "msg_text" => $msg_text,
      ],1);
      if (!$queue->queue(1)) {
        $errs = (new InstanceError($queue))->get("query", true);
        $err_r = [];
        if (!empty($errs)) {
          foreach ($errs as $err) {
            $err_r[] = $err;
          }
          throw new \Exception(\implode(" | ", $err_r), 1);
        }
      }
    }

  }
  // check environment
  private static function _checkEnv(){
    global $database;
    if ( !$database instanceof \TymFrontiers\MySQLDatabase ) {
      if(
        !\defined("MYSQL_BASE_DB") ||
        !\defined("MYSQL_SERVER") ||
        !\defined("MYSQL_GUEST_USERNAME") ||
        !\defined("MYSQL_GUEST_PASS") ||
        !\defined("MYSQL_DEVELOPER_USERNAME") ||
        !\defined("MYSQL_DEVELOPER_PASS")
      ){
        throw new \Exception("Required defination(s)[MYSQL_BASE_DB, MYSQL_SERVER, MYSQL_GUEST_USERNAME, MYSQL_GUEST_PASS, MYSQL_DEVELOPER_USERNAME, MYSQL_DEVELOPER_PASS] not [correctly] defined.", 1);
      }
      // check if guest is logged in
      $GLOBALS['database'] = new \TymFrontiers\MySQLDatabase(MYSQL_SERVER, MYSQL_GUEST_USERNAME, MYSQL_GUEST_PASS);
    }
  }
}
