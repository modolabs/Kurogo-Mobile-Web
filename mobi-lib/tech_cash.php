<?php
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_lib_constants.php";

class OracleInterface {
  protected $use_transaction=False;
  protected $username;
  protected $password;
  protected $db_alias;
  protected $connection=NULL;
 

  public function init() {
    if(!$this->connection) {
      putenv("ORACLE_HOME=" . ORACLE_HOME);
      $this->connection = @oci_connect($this->username, $this->password, $this->db_alias);
      if(!$this->connection) {
        throw new DataServerException("{$this->db_alias} connection failed");
      }

      if($this->use_transaction) {
        $statement = oci_parse($this->connection, "SET TRANSACTION READ ONLY");
        oci_execute($statement);
        oci_free_statement($statement);
      }
    }
  }

  public function close() {
    $statement = oci_parse($this->connection, "COMMIT");
    if($this->use_transaction) {
      oci_execute($statement);
      oci_free_statement($statement);
      oci_close($this->connection);
    }
  }

  protected function getArrayResult($sql) {
    $statement = oci_parse($this->connection, $sql);
    oci_execute($statement);

    $output = array();
    while($next_row = oci_fetch_assoc($statement)) {
      $output[] = $next_row;
    }

    oci_free_statement($statement);
    return $output;
  }

  protected function getSingleResult($sql) {
    $statement = oci_parse($this->connection, $sql);
    oci_execute($statement);

    if($result = oci_fetch_assoc($statement)) {
      oci_free_statement($statement);
      return $result;
    } else {
      oci_free_statement($statement);
      return NULL;
    }
  }
}



class TechCash extends OracleInterface {
  protected $use_transaction=True;
  protected $username=TECHCASH_ORACLE_USER;
  protected $password=TECHCASH_ORACLE_PASS;
  protected $db_alias=TECHCASH_ORACLE_DB;

  public function getAccountNumbers($mit_id) {
    $sql = "SELECT DISTINCT ACCOUNTNUMBER, ACCOUNTTYPE " . self::from_where_clause($mit_id);
    return $this->getArrayResult($sql);
  }
       
  public function getLatestBalance($mit_id, $account_id) {
    // uses the POSTDATE to have the most accurate version of current balance
    $sql = "SELECT * FROM (SELECT BALVALUEAFTERTRAN " . 
         self::from_where_clause($mit_id, $account_id) .
         " ORDER BY POSTDATE DESC) WHERE ROWNUM < 2";

    $row = $this->getSingleResult($sql);
    if(count($row) == 1) {
      return $row['BALVALUEAFTERTRAN'];
    } else {
      return 0;
    }
  }
    

  public function getLatestTransactions($mit_id, $account_id, $limit) {
    $tran_date_field = "MAX(TO_CHAR(TRANDATE,'MM/DD/YYYYHH24:MI:SS'))";

    $limit += 1;

    $sql = "SELECT * FROM (SELECT {$tran_date_field}, MAX(LONGDES), SUM(APPRVALUEOFTRAN), MAX(BALVALUEAFTERTRAN) " . 
           self::from_where_clause($mit_id, $account_id) .
           " GROUP BY TRANSID ORDER BY MAX(TRANDATE) DESC) WHERE ROWNUM < {$limit}";

    $rows = $this->getArrayResult($sql);
    $results = array();

    foreach($rows as $index => $row) {
      $results[] = array( 
	 "UNIX_TRANDATE" => strtotime($row[$tran_date_field]),
         "APPRVALUEOFTRAN" => -1 * $row['SUM(APPRVALUEOFTRAN)'],
         "LOCATION" =>  $row["MAX(LONGDES)"]
      );
    }

    return $results;
  }

  public function getMitID($username) {
    $kerberos = strtoupper($username);
    $sql = "SELECT MITID FROM LOCAL_KERBEROS_V WHERE KERBEROS='$kerberos'";
    return $this->getSingleResult($sql);
  }

  private static function compare_by_date($a, $b) {
    $time_a = $a['UNIX_TRANDATE'];
    $time_b = $b['UNIX_TRANDATE'];

    if ($time_a == $time_b) {
      return 0;
    }
    return ($time_a < $time_b) ? 1 : -1;
  }

  private static function from_where_clause($mit_id, $account_id=NULL) {
    $clause =  "FROM Diebold.Web_GeneralLedger_V WHERE TRANSTATUS = 'C' AND CURRENTPRIMARYKEY ='{$mit_id}'";
    if($account_id) {
      $clause .= " AND ACCOUNTNUMBER='{$account_id}'";
    }
    return $clause;
  }

  public static function dollar_string($cents) {
    $sign = ($cents < 0) ? '-' : '';
    $cents = abs($cents); 

    $dollars = floor($cents / 100);
    $cents = $cents % 100;
    if($cents < 10) {
      $cents = '0' . $cents;
    }
    return "$sign$dollars.$cents";
  }

  public static function dollar_string_rows($rows, $fields) {
    foreach($rows as $index => $row) {
      foreach($fields as $field) {
        $rows[$index][$field] = self::dollar_string($rows[$index][$field]);
      }
    }
    return $rows;
  }

  public function getAccountName($account_type) {
    $sql = "SELECT DESCRIPTION FROM WEB_SVCPLANINFO_V WHERE PLANID={$account_type}";
    $row = $this->getSingleResult($sql);
    return $row["DESCRIPTION"];
  }
}


  

class Warehouse extends OracleInterface {
//  protected $username=TECHCASH_WAREHOUSE_USER;
//  protected $password=TECHCASH_WAREHOUSE_PASS;
//  protected $db_alias=TECHCASH_WAREHOUSE_DB;
  protected $username=TECHCASH_ORACLE_USER;
  protected $password=TECHCASH_ORACLE_PASS;
  protected $db_alias=TECHCASH_ORACLE_DB;

  public function getMitID($name) {
    $sql = "SELECT MIT_ID FROM KRB_MAPPING WHERE KRB_NAME='$name'";
    return $this->getSingleResult($sql);
  }
}

function getMitID($techcash, $username) {
  $row = $techcash->getMitID($username);
  if($row) {
    return $row['MITID'];
  } 

  $warehouse = new Warehouse();
  $warehouse->init();
  $row = $warehouse->getMitID($username);
  $warehouse->close(); 

  if($row) {
    return $row['MIT_ID'];
  }
}

?>