<?


require_once LIBDIR . '/db.php';

class NotificationSubscriber {

  public $device_id;
  public $device_type;
  
  protected function __construct($device_id, $device_type) {
    $this->device_id = $device_id;
    $this->device_type = $device_type;
  }

  public static function create() {
    // factory function
  }

  public function subscribe($table, $params) {
    $params['device_id'] = $this->device_id;
    $params['device_type'] = $this->device_type;

    $db = db::$connection;

    $success = TRUE;

    // lock transactions to avoid double-subscribing the same row
    $db->query("START TRANSACTION");
    $sql = sprintf("SELECT * FROM %s WHERE %s FOR UPDATE",
		   $table,
		   implode(' AND ', $this->wrap_criteria($params)));
    $existing = $db->query($sql);
    if (!$existing->fetch_assoc()) {
      $sql = sprintf("INSERT INTO %s ( %s ) VALUES ( %s )", 
		     $table,
		     implode(', ', array_keys($params)),
		     implode(', ', $this->wrap_values(array_values($params))));
      if (!$db->query($sql)) {
	error_log("error when creating subscription: {$db->errno} {$db->error} in $sql");
	$success = FALSE;
      }
    }
    if (!$db->query("COMMIT")) {
      error_log("could not commit transaction: {$db->errno} {$db->error}");
      $success = FALSE;
    }

    return $success;
  }

  public function unsubscribe($table, $params) {
    $params['device_id'] = $this->device_id;
    $params['device_type'] = $this->device_type;
    
    $criteria = $this->wrap_criteria($params);
    $sql = "DELETE FROM $table WHERE " . implode(' AND ', $criteria);

    $db = db::$connection;
    if (!$db->query($sql)) {
      error_log("failed to delete subscription: {$db->errno} {$db->error} in $sql");
      return FALSE;
    }
    return TRUE;
  }

  // format criteria for select/delete/update
  private function wrap_criteria($params) {
    $criteria = array();
    foreach ($params as $field => $value) {
      if (is_int($value)) {
	$criteria[] = "$field=$value";
      } else {
	$criteria[] = "$field='$value'";
      }
    }
    return $criteria;
  }

  // format field values for insert
  private function wrap_values($values) {
    $wrapped_values = Array();
    foreach ($values as $value) {
      if (is_int($value)) {
	$wrapped_values[] = $value;
      } elseif ($value === NULL) {
	$wrapped_values[] = 'NULL';
      } else {
	$wrapped_values[] = "'$value'";
      }
    }
    return $wrapped_values;
  }

}

?>
