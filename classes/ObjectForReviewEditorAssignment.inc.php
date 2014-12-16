<?php

/**
 * @file plugins/generic/objectsForReview/classes/ObjectForReviewEditorAssignment.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ObjectForReviewEditorAssignment
 * @ingroup plugins_generic_objectsForReview
 * @see ObjectForReviewEditorAssignmentDAO
 *
 * @brief Basic class describing a user with 'publisher' privileges within OFR.
 */


class ObjectForReviewEditorAssignment extends DataObject {
	/**
	 * Constructor
	 */
	function ObjectForReviewEditorAssignment() {
		parent::DataObject();
	}

	//
	// Get/set methods
	//
	/**
	 * Get user ID for this assignment.
	 * @return int
	 */
	function getUserId() {
		return $this->getData('userId');
	}

	/**
	 * Set user ID for this assignment.
	 * @param $userId int
	 */
	function setUserId($userId) {
		return $this->setData('userId', $userId);
	}

	/**
	 * Get the user assigned to the object for review.
	 * @return User
	 */
	function &getUser() {
		$userDao =& DAORegistry::getDAO('UserDAO');
		return $userDao->getById($this->getData('userId'));
	}

	/**
	 * Get the publisher assigned to the object for review.
	 * @return ObjectsForReviewOrganization
	 */
	function &getPublisher() {
		$ofrPubDao =& DAORegistry::getDAO('ObjectForReviewOrganizationDAO');
		return $ofrPubDao->getOrganization($this->getData('publisherId'));
	}

	/**
	 * Get user ID for this assignment.
	 * @return int
	 */
	function getPublisherId() {
		return $this->getData('publisherId');
	}

	/**
	 * Set publisher ID for this assignment.
	 * @param $publisherId int
	 */
	function setPublisherId($publisherId) {
		return $this->setData('publisherId', $publisherId);
	}
}

?>
