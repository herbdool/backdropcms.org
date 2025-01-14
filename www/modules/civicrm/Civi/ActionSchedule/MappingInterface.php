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

namespace Civi\ActionSchedule;

/**
 * Interface MappingInterface
 * @package Civi\ActionSchedule
 */
interface MappingInterface {

  /**
   * Unique identifier of this mapping type.
   *
   * Should return a "machine name" style string (older implementations return an int -- don't follow their example).
   * @return string|int
   */
  public function getId();

  /**
   * Name of the table belonging to the main entity e.g. `civicrm_activity`
   * @return string
   */
  public function getEntityTable(): string;

  /**
   * Main entity name e.g. `Activity`
   * @return string
   */
  public function getEntityName(): string;

  /**
   * Label of this mapping type as shown in the "Entity" dropdown-select on the form.
   * @return string
   */
  public function getLabel();

  /**
   * Label of the primary filter field on the form, e.g. "Activity Type"
   * @return string
   */
  public function getValueHeader(): string;

  /**
   * Get a printable label to use as the header on the 'status' filter.
   *
   * @return string
   */
  public function getStatusHeader(): string;

  /**
   * Get a list of value options.
   *
   * @return array
   *   Array(string $value => string $label).
   *   Ex: array(123 => 'Phone Call', 456 => 'Meeting').
   */
  public function getValueLabels(): array;

  /**
   * Get a list of status options.
   *
   * @param string|int $value
   *   The list of status options may be contingent upon the selected filter value.
   *   This is the selected filter value.
   * @return array
   *   Array(string $value => string $label).
   *   Ex: Array(123 => 'Completed', 456 => 'Scheduled').
   */
  public function getStatusLabels($value): array;

  /**
   * Get a list of available date fields.
   *
   * @return array
   *   Array(string $fieldName => string $fieldLabel).
   */
  public function getDateFields(): array;

  /**
   * Get a list of recipient types.
   *
   * Note: A single schedule may filter on *zero* or *one* recipient types.
   * When an admin chooses a value, it's stored in $schedule->recipient.
   *
   * @return array
   *   array(string $value => string $label).
   *   Ex: array('assignee' => 'Activity Assignee').
   */
  public function getRecipientTypes(): array;

  /**
   * Get a list of recipients which match the given type.
   *
   * Note: A single schedule may filter on *multiple* recipients.
   * When an admin chooses value(s), it's stored in $schedule->recipient_listing.
   *
   * @param string $recipientType
   *   Ex: 'participant_role'.
   * @return array
   *   Array(mixed $name => string $label).
   *   Ex: array(1 => 'Attendee', 2 => 'Volunteer').
   * @see getRecipientTypes
   */
  public function getRecipientListing($recipientType): array;

  /**
   * Determine whether a schedule based on this mapping is sufficiently
   * complete.
   *
   * @param \CRM_Core_DAO_ActionSchedule $schedule
   * @return array
   *   Array (string $code => string $message).
   *   List of error messages.
   */
  public function validateSchedule($schedule): array;

  /**
   * Generate a query to locate contacts who match the given
   * schedule.
   *
   * @param \CRM_Core_DAO_ActionSchedule $schedule
   * @param string $phase
   *   See, e.g., RecipientBuilder::PHASE_RELATION_FIRST.
   * @param array $defaultParams
   *   Default parameters that should be included with query.
   * @return \CRM_Utils_SQL_Select
   * @see RecipientBuilder
   */
  public function createQuery($schedule, $phase, $defaultParams): \CRM_Utils_SQL_Select;

  /**
   * Determine whether a schedule based on this mapping should
   * reset the reminder state if the trigger date changes.
   *
   * @return bool
   *
   * @param \CRM_Core_DAO_ActionSchedule $schedule
   */
  public function resetOnTriggerDateChange($schedule): bool;

  /**
   * Determine whether a schedule based on this mapping should
   * send to additional contacts.
   */
  public function sendToAdditional($entityId): bool;

}
