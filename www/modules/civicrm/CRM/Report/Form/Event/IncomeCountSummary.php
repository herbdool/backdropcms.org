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
class CRM_Report_Form_Event_IncomeCountSummary extends CRM_Report_Form {

  protected $_add2groupSupported = FALSE;

  protected $_customGroupExtends = [
    'Event',
  ];

  public $_drilldownReport = ['event/participantlist' => 'Link to Detail Report'];

  /**
   * Class constructor.
   */
  public function __construct() {

    $this->_columns = [
      'civicrm_event' => [
        'dao' => 'CRM_Event_DAO_Event',
        'fields' => [
          'title' => [
            'title' => ts('Event'),
            'required' => TRUE,
          ],
          'id' => [
            'title' => ts('Event ID'),
            'no_display' => TRUE,
            'required' => TRUE,
          ],
          'event_type_id' => [
            'title' => ts('Event Type'),
          ],
          'fee_label' => [
            'title' => ts('Fee Label'),
          ],
          'event_start_date' => [
            'title' => ts('Event Start Date'),
          ],
          'event_end_date' => [
            'title' => ts('Event End Date'),
          ],
          'max_participants' => [
            'title' => ts('Capacity'),
            'type' => CRM_Utils_Type::T_INT,
          ],
        ],
        'filters' => [
          'id' => [
            'title' => ts('Event'),
            'operatorType' => CRM_Report_Form::OP_ENTITYREF,
            'type' => CRM_Utils_Type::T_INT,
            'attributes' => ['select' => ['minimumInputLength' => 0]],
          ],
          'event_type_id' => [
            'name' => 'event_type_id',
            'title' => ts('Event Type'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_OptionGroup::values('event_type'),
          ],
          'event_start_date' => [
            'title' => ts('Event Start Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
          ],
          'event_end_date' => [
            'title' => ts('Event End Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
          ],
        ],
      ],
      'civicrm_line_item' => [
        'dao' => 'CRM_Price_DAO_LineItem',
        'fields' => [
          'participant_count' => [
            'title' => ts('Participants'),
            'default' => TRUE,
            'statistics' => [
              'count' => ts('Participants'),
            ],
          ],
          'line_total' => [
            'title' => ts('Income Statistics'),
            'type' => CRM_Utils_Type::T_MONEY,
            'default' => TRUE,
            'statistics' => [
              'sum' => ts('Income'),
              'avg' => ts('Average'),
            ],
          ],
        ],
      ],
      'civicrm_participant' => [
        'dao' => 'CRM_Event_DAO_Participant',
        'filters' => [
          'sid' => [
            'name' => 'status_id',
            'title' => ts('Participant Status'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Event_PseudoConstant::participantStatus(NULL, NULL, 'label'),
          ],
          'rid' => [
            'name' => 'role_id',
            'title' => ts('Participant Role'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Event_PseudoConstant::participantRole(),
          ],
          'participant_register_date' => [
            'title' => ts('Registration Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
          ],
        ],
      ],
    ];

    // Add charts support
    $this->_charts = [
      '' => ts('Tabular'),
      'barChart' => ts('Bar Chart'),
      'pieChart' => ts('Pie Chart'),
    ];

    parent::__construct();
  }

  public function select(): void {
    $select = [];
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (!empty($field['required']) ||
            !empty($this->_params['fields'][$fieldName])
          ) {
            if (!empty($field['statistics'])) {
              foreach ($field['statistics'] as $stat => $label) {
                switch (strtolower($stat)) {
                  case 'count':
                    $select[] = "SUM({$field['dbAlias']}) as {$tableName}_{$fieldName}_{$stat}";
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['type'] = CRM_Utils_Type::T_INT;
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['title'] = $label;
                    $this->_statFields[] = "{$tableName}_{$fieldName}_{$stat}";
                    break;

                  case 'sum':
                    $select[] = "SUM({$field['dbAlias']}) as {$tableName}_{$fieldName}_{$stat}";
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['type'] = CRM_Utils_Type::T_MONEY;

                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['title'] = $label;
                    $this->_statFields[] = "{$tableName}_{$fieldName}_{$stat}";
                    break;

                  case 'avg':
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['title'] = $label;

                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['type'] = CRM_Utils_Type::T_MONEY;
                    $this->_statFields[] = "{$tableName}_{$fieldName}_{$stat}";
                }
              }
            }
            else {
              $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = $field['type'] ?? NULL;
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
            }
          }
        }
      }
    }
    $this->_selectClauses = $select;

    $this->_select = 'SELECT ' . implode(', ', $select);
  }

  public function from(): void {
    $this->_from = "
        FROM civicrm_event {$this->_aliases['civicrm_event']}
             LEFT JOIN civicrm_participant {$this->_aliases['civicrm_participant']}
                    ON {$this->_aliases['civicrm_event']}.id = {$this->_aliases['civicrm_participant']}.event_id AND
                       {$this->_aliases['civicrm_participant']}.is_test = 0
             LEFT JOIN civicrm_line_item {$this->_aliases['civicrm_line_item']}
                    ON {$this->_aliases['civicrm_participant']}.id ={$this->_aliases['civicrm_line_item']}.entity_id AND
                       {$this->_aliases['civicrm_line_item']}.entity_table = 'civicrm_participant' ";
  }

  public function where(): void {
    $clauses = [];
    foreach ($this->_columns as $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;
          if (CRM_Utils_Array::value('type', $field) & CRM_Utils_Type::T_DATE) {
            $relative = $this->_params["{$fieldName}_relative"] ?? NULL;
            $from = $this->_params["{$fieldName}_from"] ?? NULL;
            $to = $this->_params["{$fieldName}_to"] ?? NULL;

            if ($relative || $from || $to) {
              $clause = $this->dateClause($field['name'], $relative, $from, $to, $field['type']);
            }
          }
          else {
            $op = $this->_params["{$fieldName}_op"] ?? NULL;
            if ($op) {
              $clause = $this->whereClause($field,
                $op,
                CRM_Utils_Array::value("{$fieldName}_value", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_min", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_max", $this->_params)
              );
            }
          }

          if (!empty($clause)) {
            $clauses[] = $clause;
          }
        }
      }
    }
    $clauses[] = "{$this->_aliases['civicrm_event']}.is_template = 0";
    $this->_where = 'WHERE  ' . implode(' AND ', $clauses);
  }

  /**
   * @param array $rows
   *
   * @return array
   * @throws \Civi\Core\Exception\DBQueryException
   */
  public function statistics(&$rows): array {
    $statistics = parent::statistics($rows);
    $select = "
         SELECT SUM( {$this->_aliases['civicrm_line_item']}.participant_count ) as count,
                SUM( {$this->_aliases['civicrm_line_item']}.line_total )  as amount";

    $sql = "{$select} {$this->_from} {$this->_where}";

    $dao = CRM_Core_DAO::executeQuery($sql);

    if ($dao->fetch()) {
      $avg = 0;
      if ($dao->count && $dao->amount) {
        $avg = $dao->amount / $dao->count;
      }
      $statistics['counts']['count'] = [
        'value' => $dao->count,
        'title' => ts('Total Participants'),
        'type' => CRM_Utils_Type::T_INT,
      ];
      $statistics['counts']['amount'] = [
        'value' => $dao->amount,
        'title' => ts('Total Income'),
        'type' => CRM_Utils_Type::T_MONEY,
      ];
      $statistics['counts']['avg'] = [
        'value' => $avg,
        'title' => ts('Average'),
        'type' => CRM_Utils_Type::T_MONEY,
      ];
    }
    return $statistics;
  }

  public function groupBy(): void {
    $this->assign('chartSupported', TRUE);
    $this->_rollup = ' WITH ROLLUP';
    $this->_select = CRM_Contact_BAO_Query::appendAnyValueToSelect($this->_selectClauses, "{$this->_aliases['civicrm_event']}.id");
    $this->_groupBy = " GROUP BY {$this->_aliases['civicrm_event']}.id {$this->_rollup}";
  }

  public function postProcess() {

    $this->beginPostProcess();

    $sql = $this->buildQuery(TRUE);

    $dao = CRM_Core_DAO::executeQuery($sql);

    //set pager before execution of query in function participantInfo()
    $this->setPager();

    $rows = [];

    while ($dao->fetch()) {
      $row = [];
      foreach ($this->_columnHeaders as $key => $value) {
        if (($key === 'civicrm_event_start_date') ||
          ($key === 'civicrm_event_end_date')
        ) {
          //get event start date and end date in custom datetime format
          $row[$key] = CRM_Utils_Date::customFormat($dao->$key);
        }
        elseif ($key === 'civicrm_participant_fee_amount_avg') {
          if ($dao->civicrm_participant_fee_amount_sum &&
            $dao->civicrm_line_item_participant_count_count
          ) {
            $row[$key] = $dao->civicrm_participant_fee_amount_sum /
              $dao->civicrm_line_item_participant_count_count;
          }
        }
        elseif ($key === 'civicrm_line_item_line_total_avg') {
          if ($dao->civicrm_line_item_line_total_sum &&
            $dao->civicrm_line_item_participant_count_count
          ) {
            $row[$key] = $dao->civicrm_line_item_line_total_sum /
              $dao->civicrm_line_item_participant_count_count;
          }
        }
        elseif (isset($dao->$key)) {
          $row[$key] = $dao->$key;
        }
      }
      $rows[] = $row;
    }

    // do not call pager here
    $this->formatDisplay($rows, FALSE);
    unset($this->_columnHeaders['civicrm_event_id']);

    $this->doTemplateAssignment($rows);

    $this->endPostProcess($rows);
  }

  /**
   * @param array $rows
   */
  public function buildChart(&$rows) {

    $this->_interval = 'events';
    $countEvent = NULL;
    if (!empty($this->_params['charts'])) {
      foreach ($rows as $key => $value) {
        if ($value['civicrm_event_id']) {
          $graphRows['totalParticipants'][] = ($rows[$key]['civicrm_line_item_participant_count_count']);
          $graphRows[$this->_interval][] = substr($rows[$key]['civicrm_event_title'], 0, 12) . '..(' .
            $rows[$key]['civicrm_event_id'] . ') ';
          $graphRows['value'][] = ($rows[$key]['civicrm_line_item_participant_count_count']);
        }
      }

      if (($rows[$key]['civicrm_line_item_participant_count_count']) == 0) {
        $countEvent = count($rows);
      }

      if ((!empty($rows)) && $countEvent != 1) {
        $chartInfo = [
          'legend' => ts('Participants Summary'),
          'xname' => ts('Event'),
          'yname' => ts('Total Participants'),
        ];
        if (!empty($graphRows)) {
          foreach ($graphRows[$this->_interval] as $key => $val) {
            $graph[$val] = $graphRows['value'][$key];
          }
          $chartInfo['values'] = $graph;
          $chartInfo['tip'] = ts('Participants : %1', [1 => '#val#']);
          $chartInfo['xLabelAngle'] = 20;

          // build the chart.
          CRM_Utils_Chart::buildChart($chartInfo, $this->_params['charts']);
        }
      }
    }
  }

  /**
   * Alter display of rows.
   *
   * Iterate through the rows retrieved via SQL and make changes for display purposes,
   * such as rendering contacts as links.
   *
   * @param array $rows
   *   Rows generated by SQL, with an array for each row.
   */
  public function alterDisplay(&$rows) {

    if (is_array($rows)) {
      $eventType = CRM_Core_OptionGroup::values('event_type');

      foreach ($rows as $rowNum => $row) {
        if (array_key_exists('civicrm_event_title', $row)) {
          if ($value = $row['civicrm_event_id']) {
            CRM_Event_PseudoConstant::event($value, FALSE);
            $url = CRM_Report_Utils_Report::getNextUrl('event/participantlist',
              'reset=1&force=1&event_id_op=eq&event_id_value=' . $value,
              $this->_absoluteUrl, $this->_id, $this->_drilldownReport
            );
            $rows[$rowNum]['civicrm_event_title_link'] = $url;
            $rows[$rowNum]['civicrm_event_title_hover'] = ts('View Event Participants For this Event');
          }
        }

        //handle event type
        if (array_key_exists('civicrm_event_event_type_id', $row)) {
          if ($value = $row['civicrm_event_event_type_id']) {
            $rows[$rowNum]['civicrm_event_event_type_id'] = $eventType[$value];
          }
        }
      }
    }
  }

}
