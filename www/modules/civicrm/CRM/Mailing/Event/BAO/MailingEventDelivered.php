<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Mailing_Event_BAO_MailingEventDelivered extends CRM_Mailing_Event_DAO_MailingEventDelivered {

  /**
   * Record a new delivery event.
   *
   * @param array $params
   *   Associative array of delivery event values.
   *
   * @return \CRM_Mailing_Event_BAO_MailingEventDelivered
   */
  public static function recordDelivery(&$params) {
    $q = CRM_Mailing_Event_BAO_MailingEventQueue::verify($params['job_id'],
      $params['event_queue_id'],
      $params['hash']
    );

    if (!$q) {
      return NULL;
    }

    $delivered = new CRM_Mailing_Event_BAO_MailingEventDelivered();
    $delivered->time_stamp = date('YmdHis');
    $delivered->copyValues($params);
    $delivered->save();

    $queue = new CRM_Mailing_Event_BAO_MailingEventQueue();
    $queue->id = $params['event_queue_id'];
    $queue->find(TRUE);

    while ($queue->fetch()) {
      $email = new CRM_Core_BAO_Email();
      $email->id = $queue->email_id;
      $email->hold_date = '';
      $email->reset_date = date('YmdHis');
      $email->save();
    }

    return $delivered;
  }

  /**
   * Create function was renamed `recordDelivery` because it's not a standard CRUD create function
   *
   * @param array $params
   * @deprecated
   *
   * @return \CRM_Mailing_Event_BAO_MailingEventDelivered
   */
  public static function create(&$params) {
    CRM_Core_Error::deprecatedFunctionWarning('recordDelivery');
    return self::recordDelivery($params);
  }

  /**
   * Get row count for the event selector.
   *
   * @param int $mailing_id
   *   ID of the mailing.
   * @param int $job_id
   *   Optional ID of a job to filter on.
   * @param string $toDate
   *
   * @return int
   *   Number of rows in result set
   */
  public static function getTotalCount($mailing_id, $job_id = NULL, $toDate = NULL) {
    $dao = new CRM_Core_DAO();

    $delivered = self::getTableName();
    $bounce = CRM_Mailing_Event_BAO_MailingEventBounce::getTableName();
    $queue = CRM_Mailing_Event_BAO_MailingEventQueue::getTableName();
    $mailing = CRM_Mailing_BAO_Mailing::getTableName();
    $job = CRM_Mailing_BAO_MailingJob::getTableName();

    $query = "
            SELECT      COUNT($delivered.id) as delivered
            FROM        $delivered
            INNER JOIN  $queue
                    ON  $delivered.event_queue_id = $queue.id
            LEFT JOIN   $bounce
                    ON  $delivered.event_queue_id = $bounce.event_queue_id
            INNER JOIN  $job
                    ON  $queue.job_id = $job.id
                    AND $job.is_test = 0
            INNER JOIN  $mailing
                    ON  $job.mailing_id = $mailing.id
            WHERE       $bounce.id IS null
                AND     $mailing.id = " . CRM_Utils_Type::escape($mailing_id, 'Integer');

    if (!empty($toDate)) {
      $query .= " AND $delivered.time_stamp <= $toDate";
    }

    if (!empty($job_id)) {
      $query .= " AND $job.id = " . CRM_Utils_Type::escape($job_id, 'Integer');
    }

    // query was missing
    $dao->query($query);

    if ($dao->fetch()) {
      return $dao->delivered;
    }

    return NULL;
  }

  /**
   * Get rows for the event browser.
   *
   * @param int $mailing_id
   *   ID of the mailing.
   * @param int $job_id
   *   Optional ID of the job.
   * @param bool $is_distinct
   *   Group by queue id?.
   * @param int $offset
   *   Offset.
   * @param int $rowCount
   *   Number of rows.
   * @param array $sort
   *   Sort array.
   *
   * @param int $is_test
   *
   * @return array
   *   Result set
   */
  public static function &getRows(
    $mailing_id, $job_id = NULL,
    $is_distinct = FALSE, $offset = NULL, $rowCount = NULL, $sort = NULL, $is_test = 0
  ) {

    $dao = new CRM_Core_DAO();

    $delivered = self::getTableName();
    $bounce = CRM_Mailing_Event_BAO_MailingEventBounce::getTableName();
    $queue = CRM_Mailing_Event_BAO_MailingEventQueue::getTableName();
    $mailing = CRM_Mailing_BAO_Mailing::getTableName();
    $job = CRM_Mailing_BAO_MailingJob::getTableName();
    $contact = CRM_Contact_BAO_Contact::getTableName();
    $email = CRM_Core_BAO_Email::getTableName();

    $query = "
            SELECT      $delivered.id as id,
                        $contact.display_name as display_name,
                        $contact.id as contact_id,
                        $email.email as email,
                        $delivered.time_stamp as date
            FROM        $contact
            INNER JOIN  $queue
                    ON  $queue.contact_id = $contact.id
            INNER JOIN  $email
                    ON  $queue.email_id = $email.id
            INNER JOIN  $delivered
                    ON  $delivered.event_queue_id = $queue.id
            LEFT JOIN   $bounce
                    ON  $bounce.event_queue_id = $queue.id
            INNER JOIN  $job
                    ON  $queue.job_id = $job.id
                    AND $job.is_test = $is_test
            INNER JOIN  $mailing
                    ON  $job.mailing_id = $mailing.id
            WHERE       $bounce.id IS null
                AND     $mailing.id = " . CRM_Utils_Type::escape($mailing_id, 'Integer');

    if (!empty($job_id)) {
      $query .= " AND $job.id = " . CRM_Utils_Type::escape($job_id, 'Integer');
    }

    $orderBy = "sort_name ASC, {$delivered}.time_stamp DESC";
    if ($sort) {
      if (is_string($sort)) {
        $sort = CRM_Utils_Type::escape($sort, 'String');
        $orderBy = $sort;
      }
      else {
        $orderBy = trim($sort->orderBy());
      }
    }

    $query .= " ORDER BY {$orderBy} ";

    if ($offset || $rowCount) {
      //Added "||$rowCount" to avoid displaying all records on first page
      $query .= ' LIMIT ' . CRM_Utils_Type::escape($offset, 'Integer') . ', ' . CRM_Utils_Type::escape($rowCount, 'Integer');
    }

    $dao->query($query);

    $results = [];

    while ($dao->fetch()) {
      $url = CRM_Utils_System::url('civicrm/contact/view',
        "reset=1&cid={$dao->contact_id}"
      );
      $results[$dao->id] = [
        'contact_id' => $dao->contact_id,
        'name' => "<a href=\"$url\">{$dao->display_name}</a>",
        'email' => $dao->email,
        'date' => CRM_Utils_Date::customFormat($dao->date),
      ];
    }
    return $results;
  }

  /**
   * @param $eventQueueIDs
   * @param null $time
   */
  public static function bulkCreate($eventQueueIDs, $time = NULL) {
    if (!$time) {
      $time = date('YmdHis');
    }

    // construct a bulk insert statement
    $values = [];
    foreach ($eventQueueIDs as $eqID) {
      $values[] = "( $eqID, '{$time}' )";
    }

    while (!empty($values)) {
      $input = array_splice($values, 0, CRM_Core_DAO::BULK_INSERT_COUNT);
      $str = implode(',', $input);
      $sql = "INSERT INTO civicrm_mailing_event_delivered ( event_queue_id, time_stamp ) VALUES $str;";
      CRM_Core_DAO::executeQuery($sql);
    }
  }

  /**
   * Since we never know when a mailing really bounces (hard bounce == NOW, soft bounce == NOW to NOW + 3 days?)
   * we cannot decide when an email address last got an email.
   *
   * We want to avoid putting on hold an email address which had a few bounces (mbox full) and then got quite a few
   * successful deliveries before starting the bounce again. The current code does not set the resetDate and hence
   * the above scenario results in the email being put on hold
   *
   * This function rectifies that by considering all non-test mailing jobs which have completed between $minDays and $maxDays
   * and setting the resetDate to the date that an email was delivered
   *
   * @param int $minDays
   *   Consider mailings that were completed at least $minDays ago.
   * @param int $maxDays
   *   Consider mailings that were completed not more than $maxDays ago.
   */
  public static function updateEmailResetDate(int $minDays = 3, int $maxDays = 7) {

    if ($minDays < 0 || $maxDays < 0 || $maxDays <= $minDays) {
      throw new \InvalidArgumentException("minDays and maxDays must be >=0 and maxDays > minDays");
    }

    $temporaryTable = CRM_Utils_SQL_TempTable::build()
      ->setCategory('mailingemail')
      ->setMemory()
      ->createWithColumns('id int primary key, reset_date datetime');
    $temporaryTableName = $temporaryTable->getName();

    // also exclude on_hold = opt-out (2)
    $query = "
            INSERT INTO {$temporaryTableName} (id, reset_date)
            SELECT      civicrm_email.id as email_id,
                        max(civicrm_mailing_event_delivered.time_stamp) as reset_date
            FROM        civicrm_mailing_event_queue
            INNER JOIN  civicrm_email ON  civicrm_mailing_event_queue.email_id = civicrm_email.id
            INNER JOIN  civicrm_mailing_event_delivered ON civicrm_mailing_event_delivered.event_queue_id = civicrm_mailing_event_queue.id
            LEFT JOIN   civicrm_mailing_event_bounce ON civicrm_mailing_event_bounce.event_queue_id = civicrm_mailing_event_queue.id
            INNER JOIN  civicrm_mailing_job ON civicrm_mailing_event_queue.job_id = civicrm_mailing_job.id AND civicrm_mailing_job.is_test = 0
            WHERE       civicrm_mailing_event_bounce.id IS NULL
              AND       civicrm_mailing_job.status = 'Complete'
              AND       civicrm_mailing_job.end_date BETWEEN DATE_SUB(NOW(), INTERVAL $maxDays day) AND DATE_SUB(NOW(), INTERVAL $minDays day)
              AND       (civicrm_email.reset_date IS NULL OR civicrm_email.reset_date < civicrm_mailing_job.start_date)
              AND       civicrm_email.on_hold != 2
            GROUP BY    civicrm_email.id
         ";
    CRM_Core_DAO::executeQuery($query);

    $query = "
UPDATE     civicrm_email e
INNER JOIN {$temporaryTableName} et ON e.id = et.id
SET        e.on_hold = 0,
           e.hold_date = NULL,
           e.reset_date = et.reset_date
";
    CRM_Core_DAO::executeQuery($query);
    $temporaryTable->drop();
  }

}
