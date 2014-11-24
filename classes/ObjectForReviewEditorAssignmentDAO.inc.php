<?php

/**
 * @file plugins/generic/objectsForReview/classes/ObjectForReviewEditorAssignmentDAO.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ObjectForReviewEditorAssignmentDAO
 * @ingroup plugins_generic_objectsForReview
 * @see ObjectForReviewEditorAssignment
 *
 * @brief Operations for retrieving and modifying ObjectForReviewEditorAssignment objects.
 */


class ObjectForReviewEditorAssignmentDAO extends DAO {
	/** @var string Name of parent plugin */
	var $parentPluginName;

	/**
	 * Constructor
	 */
	function ObjectForReviewEditorAssignmentDAO($parentPluginName) {
		parent::DAO();
		$this->parentPluginName = $parentPluginName;
	}

	/**
	 * Retrieve assignment by ID.
	 * @param $enrollId int
	 * @return ObjectForReviewEditorAssignment
	 */
	function getById($enrollId) {
		$params = array((int) $enrollId);

		$result =& $this->retrieve(
			'SELECT * FROM object_for_review_enrolled_users WHERE enroll_id = ?',
			$params
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_fromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		return $returner;
	}

	/**
	 * Determine if the assignment exists
	 * @param $publisherId int
	 * @param $userId int (optional)
	 * @return boolean
	 */
	function assignmentExists($publisherId, $userId = null) {
		$params = array((int) $publisherId);
		$sql = 'SELECT COUNT(*) FROM object_for_review_enrolled_users WHERE publisher_id = ?';
		if ($userId) {
			$sql .= ' AND user_id = ?';
			$params[] = (int) $userId;
		}

		$result =& $this->retrieve($sql, $params);

		$returner = isset($result->fields[0]) && $result->fields[0] > 0 ? true : false;
		$result->Close();
		return $returner;
	}

	/**
	 * Construct a new data object corresponding to this DAO.
	 * @return ObjectForReviewEditorAssignment
	 */
	function newDataObject() {
		$ofrPlugin =& PluginRegistry::getPlugin('generic', $this->parentPluginName);
		$ofrPlugin->import('classes.ObjectForReviewEditorAssignment');
		return new ObjectForReviewEditorAssignment();
	}

	/**
	 * Internal function to return an ObjectForReviewEditorAssignment object from a row.
	 * @param $row array
	 * @return ObjectForReviewEditorAssignment
	 */
	function &_fromRow($row) {
		$assignment = $this->newDataObject();
		$assignment->setId($row['enroll_id']);
		$assignment->setPublisherId($row['publisher_id']);
		$assignment->setUserId($row['user_id']);

		HookRegistry::call('ObjectForReviewEditorAssignmentDAO::_fromRow', array(&$assignment, &$row));

		return $assignment;
	}

	/**
	 * Insert a new assignment.
	 * @param $assignment ObjectForReviewEditorAssignment
	 * @return int
	 */
	function insertObject(&$assignment) {
		$this->update(
			sprintf(
				'INSERT INTO object_for_review_enrolled_users
				(publisher_id, user_id)
				VALUES
				(?, ?)'),
			array(
				(int) $assignment->getPublisherId(),
				$this->nullOrInt($assignment->getUserId()),
			)
		);
		$assignment->setId($this->getInsertId());
		return $assignment->getId();
	}

	/**
	 * Update an existing assignment.
	 * @param $assignment ObjectForReviewEditorAssignment
	 * @return boolean
	 */
	function updateObject(&$assignment) {
		$returner = $this->update(
			sprintf(
				'UPDATE	object_for_review_enrolled_users
				SET	publisher_id = ?,
					user_id = ?
				WHERE	enroll_id = ?'),
			array(
				(int) $assignment->getPublisherId(),
				$this->nullOrInt($assignment->getUserId()),
				(int) $assignment->getId()
			)
		);
		return $returner;
	}

	/**
	 * Delete an assignment.
	 * @param $assignment ObjectForReviewEditorAssignment
	 * @return boolean
	 */
	function deleteObject($assignment) {
		return $this->deleteById($assignment->getId(), $assignment->getPublisherId());
	}

	/**
	 * Delete an assignment by ID.
	 * @param $enrollId int
	 * @param $publisherId int (optional)
	 * @return boolean
	 */
	function deleteById($enrollId, $publisherId = null) {
		$params = array((int) $enrollId);
		if ($publisherId !== null) $params[] = (int) $publisherId;

		return $this->update('
			DELETE FROM object_for_review_enrolled_users WHERE enroll_id = ?'
			. ($publisherId !== null?' AND publisher_id = ?':''),
			$params
		);
	}

	/**
	 * Delete all assignments for a publisher.
	 * @param $publisherId int
	 * @return boolean
	 */
	function deleteAllByPublisherId($publisherId) {
		$params = array((int) $publisherId);
		return $this->update('
			DELETE FROM object_for_review_enrolled_users WHERE publisher_id = ?',
			$params
		);
	}

	/**
	 * Retrieve the assignment matching the publisher and the user.
	 * @param $publisherId int
	 * @param $userId int
	 * @return ObjectForReviewEditorAssignment
	 */
	function &getByPublisherAndUserId($publisherId, $userId) {
		$params = array((int) $publisherId, (int) $userId);
		$sql = 'SELECT * FROM object_for_review_enrolled_users WHERE publisher_id = ? AND user_id = ?';
		$result =& $this->retrieve($sql, $params);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = $this->_fromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		return $returner;
	}

	/**
	 * Retrieve all assignments for an object for review
	 * @param $publisherId int
	 * @return array
	 */
	function &getAllByPublisherId($publisherId) {
		$returner =& $this->_getAllInternally($publisherId);
		return $returner;
	}

	/**
	 * Retrieve all assignments for an user
	 * @param $userId int
	 * @return array
	 */
	function &getAllByUserId($userId) {
		$returner =& $this->_getAllInternally(null, $userId);
		return $returner;
	}

	/**
	 * Get all users IDs assigned to  publisher
	 * @param $publisherId int
	 * @return array of user IDs
	 */
	function &getUserIds($publisherId) {
		$result =& $this->retrieve(
				'SELECT user_id FROM object_for_review_enrolled_users WHERE publisher_id = ?',
				(int) $publisherId
		);

		$userIds = array();
		while (!$result->EOF) {
			$userIds[] = $result->fields[0];
			$result->MoveNext();
		}
		$result->Close();
		return $userIds;
	}

	/**
	 * Get all users IDs of users enrolled as publishers.
	 * @return array of user IDs
	 */
	function &getAllUserIds() {
		$result =& $this->retrieve('SELECT user_id FROM object_for_review_enrolled_users');

		$userIds = array();
		while (!$result->EOF) {
			$userIds[] = $result->fields[0];
			$result->MoveNext();
		}
		$result->Close();
		return $userIds;
	}

	/**
	 * Get the ID of the last inserted assignment.
	 * @return int
	 */
	function getInsertId() {
		return parent::getInsertId('object_for_review_enrolled_users', 'enroll_id');
	}


	//
	// Private helper methods.
	//
	/**
	 * Retrieve all assignments matching the specified input parameters
	 * @param $publisherId int (optional)
	 * @param $userId int (optional)
	 * @return DAOResultFactory
	 */
	function &_getAllInternally($publisherId = null, $userId = null) {
		$sql = 'SELECT * FROM object_for_review_enrolled_users';

		if ($publisherId) {
			$conditions[] = 'publisher_id = ?';
			$params[] = (int) $publisherId;
		}

		if ($userId) {
			$conditions[] = 'user_id = ?';
			$params[] = (int) $userId;
		}

		if (count($conditions) > 0) {
			$sql .= ' WHERE ' . implode(' AND ', $conditions);
		}

		$sql .= ' ORDER BY enroll_id';
		$result =& $this->retrieve($sql, $params);

		$assignments = array();
		while (!$result->EOF) {
			$assignments[] =& $this->_fromRow($result->GetRowAssoc(false));
			$result->MoveNext();
		}
		$result->Close();
		return $assignments;
	}

}

?>
