<?php

/**
 * @file plugins/generic/objectsForReview/pages/ObjectsForReviewEditorHandler.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ObjectsForReviewEditorHandler
 * @ingroup plugins_generic_objectsForReview
 *
 * @brief Handle requests for editor objects for review functions.
 */

import('classes.handler.Handler');

class ObjectsForReviewEditorHandler extends Handler {

	/**
	 * Display objects for review listing pages.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function objectsForReview($args, &$request) {
		$journal =& $request->getJournal();
		$journalId = $journal->getId();

		// Search
		$duplicateParameters = array(
			'searchField', 'searchMatch', 'search'
		);
		$fieldOptions = Array(
			OFR_FIELD_TITLE => 'plugins.generic.objectsForReview.search.field.title',
			OFR_FIELD_ABSTRACT => 'plugins.generic.objectsForReview.search.field.abstract',
			OFR_FIELD_KEYWORDS => 'plugins.generic.objectsForReview.search.field.subjectKeywords'
		);
		$searchField = null;
		$searchMatch = null;
		$search = $request->getUserVar('search');
		if (!empty($search)) {
			$searchField = $request->getUserVar('searchField');
			$searchMatch = $request->getUserVar('searchMatch');
		}

		// Filter by editor
		import('pages.editor.EditorHandler');
		$user =& $request->getUser();
		$filterEditorOptions = array(
			FILTER_EDITOR_ALL => AppLocale::Translate('editor.allEditors'),
			FILTER_EDITOR_ME => AppLocale::Translate('editor.me')
		);
		// Save filter editor options in user settings
		$filterEditor = $request->getUserVar('filterEditor');
		if ($filterEditor != '' && array_key_exists($filterEditor, $filterEditorOptions)) {
			$user->updateSetting('filterEditor', $filterEditor, 'int', $journalId);
		} else {
			$filterEditor = $user->getSetting('filterEditor', $journalId);
			if ($filterEditor == null || !Validation::isEditor($journal->getId())) {
				$filterEditor = FILTER_EDITOR_ALL;
				$user->updateSetting('filterEditor', $filterEditor, 'int', $journalId);
			}
		}
		if ($filterEditor == FILTER_EDITOR_ME) {
			$editorId = $user->getId();
		} else {
			$editorId = null;
		}

		// Filter by review object type
		$reviewObjectTypeDao =& DAORegistry::getDAO('ReviewObjectTypeDAO');
		$allTypes =& $reviewObjectTypeDao->getTypeIdsAlphabetizedByContext($journalId);
		$filterTypeOptions = array(0 => __('common.all'));
		// Consider active types for the creation of a new object for review
		$createTypeOptions = array();
		foreach ($allTypes as $type) {
			$typeId = $type['typeId'];
			$filterTypeOptions[$typeId] = $type['typeName'];
			if ($type['typeActive']) {
				$createTypeOptions[$typeId] = $type['typeName'];
			}
		}
		// Save filter type options in user settings
		$filterType = $request->getUserVar('filterType');
		if ($filterType != '' && array_key_exists($filterType, $filterTypeOptions)) {
			$user->updateSetting('filterReviewObjectType', $filterType, 'int', $journalId);
		} else {
			$filterType = $user->getSetting('filterReviewObjectType', $journalId);
			if ($filterType == null) {
				$filterType = 0;
				$user->updateSetting('filterReviewObjectType', $filterType, 'int', $journalId);
			}
		}

		// Sort
		$sort = $request->getUserVar('sort');
		$sort = isset($sort) ? $sort : 'title';
		$sortDirection = $request->getUserVar('sortDirection');
		$sortDirection = isset($sortDirection) ? $sortDirection : SORT_DIRECTION_ASC;

		$ofrPlugin =& $this->_getObjectsForReviewPlugin();
		$mode = $ofrPlugin->getSetting($journalId, 'mode');

		$ofrPlugin->import('classes.ObjectForReviewAssignment');
		$path = !isset($args) || empty($args) ? null : $args[0];
		$template = 'objectsForReviewAssignments.tpl';
		switch($path) {
			case '':
				$status = null;
				$pageTitle = 'plugins.generic.objectsForReview.objectsForReview.pageTitle';
				$template = 'objectsForReview.tpl';
				break;
			case 'requested':
				$status = OFR_STATUS_REQUESTED;
				$pageTitle = 'plugins.generic.objectsForReview.objectForReviewAssignments.pageTitleRequested';
				break;
			case 'assigned':
				$status = OFR_STATUS_ASSIGNED;
				$pageTitle = 'plugins.generic.objectsForReview.objectForReviewAssignments.pageTitleAssigned';
				break;
			case 'mailed':
				$status = OFR_STATUS_MAILED;
				$pageTitle = 'plugins.generic.objectsForReview.objectForReviewAssignments.pageTitleMailed';
				break;
			case 'submitted':
				$status = OFR_STATUS_SUBMITTED;
				$pageTitle = 'plugins.generic.objectsForReview.objectForReviewAssignments.pageTitleSubmitted';
				break;
			case 'all':
			default:
				$path = 'all';
				$status = null;
				$pageTitle = 'plugins.generic.objectsForReview.objectForReviewAssignments.pageTitleAll';
		}

		$this->setupTemplate($request);
		$templateMgr =& TemplateManager::getManager($request);

		foreach ($duplicateParameters as $param)
			$templateMgr->assign($param, $request->getUserVar($param));
		$templateMgr->assign('fieldOptions', $fieldOptions);

		$templateMgr->assign('editorOptions', $filterEditorOptions);
		$templateMgr->assign('filterEditor', $filterEditor);
		$templateMgr->assign('filterTypeOptions', $filterTypeOptions);
		$templateMgr->assign('createTypeOptions', $createTypeOptions);
		$templateMgr->assign('filterType', $filterType);

		$templateMgr->assign('sort', $sort);
		$templateMgr->assign('sortDirection', $sortDirection);

		$templateMgr->assign('mode', $mode);
		$templateMgr->assign('returnPage', $path);

		$ofrREADao =& DAORegistry::getDAO('ObjectForReviewEditorAssignmentDAO');
		$assignments = $ofrREADao->getAllByUserId($user->getId());
		$templateMgr->assign('assignments', $assignments);

		if ($path == '') {
			$rangeInfo = Handler::getRangeInfo('objectsForReview');
			$ofrDao =& DAORegistry::getDAO('ObjectForReviewDAO');
			$objectsForReview =& $ofrDao->getAllByContextId($journalId, $searchField, $search, $searchMatch, $status, $editorId, $filterType, $rangeInfo, $sort, $sortDirection, $assignments);
			$templateMgr->assign_by_ref('objectsForReview', $objectsForReview);
		} else {
			$rangeInfo = Handler::getRangeInfo('objectForReviewAssignments');
			$ofrAssignmentDao =& DAORegistry::getDAO('ObjectForReviewAssignmentDAO');
			$objectForReviewAssignments =& $ofrAssignmentDao->getAllByContextId($journalId, $searchField, $search, $searchMatch, $status, null, $editorId, $filterType, $rangeInfo, $sort, $sortDirection, $assignments);
			$templateMgr->assign_by_ref('objectForReviewAssignments', $objectForReviewAssignments);
			$templateMgr->assign('counts', $ofrAssignmentDao->getStatusCounts($journalId));
		}

		$templateMgr->assign('pageTitle', $pageTitle);
		$templateMgr->display($ofrPlugin->getTemplatePath() . 'editor' . '/' . $template);
	}

	/**
	 * Edit and update object for review (plug-in) settings.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function objectsForReviewSettings($args, &$request) {
		$journal =& $request->getJournal();
		$journalId = $journal->getId();

		$ofrPlugin =& $this->_getObjectsForReviewPlugin();
		$ofrPlugin->import('classes.form.ObjectsForReviewSettingsForm');
		$settingsForm = new ObjectsForReviewSettingsForm($ofrPlugin, $journalId);
		if ($settingsForm->isLocaleResubmit() || $request->getUserVar('save')) {
			$settingsForm->readInputData();
			if ($request->getUserVar('save')) {
				if ($settingsForm->validate()) {
					$settingsForm->execute();
					// Notification
					$user =& $request->getUser();
					import('classes.notification.NotificationManager');
					$notificationManager = new NotificationManager();
					$notificationManager->createTrivialNotification($user->getId(), NOTIFICATION_TYPE_OFR_SETTINGS_SAVED);

					$request->redirect(null, 'editor', 'objectsForReviewSettings');
				}
			}
		} else {
			$settingsForm->initData();
		}
		$settingsForm->display($request);
	}

	/**
	 * Create/edit object for review.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function createObjectForReview($args, &$request) {
		$this->editObjectForReview($args, &$request);
	}

	/**
	 * Create/edit object for review.
	 * @param $args array
	 * @param $request PKPRequest
	 * @param $importData array optional
	 */
	function editObjectForReview($args, &$request, $importData = array()) {
		$objectId = array_shift($args);
		$reviewObjectTypeId = (int) $request->getUserVar('reviewObjectTypeId');

		$journal =& $request->getJournal();
		$journalId = $journal->getId();

		if (!$this->_ensureObjectExists($objectId, $journalId, $reviewObjectTypeId) && !isset($reviewObjectTypeId)) {
			$request->redirect(null, 'editor', 'objectsForReview');
		}
		$reviewObjectTypeDao =& DAORegistry::getDAO('ReviewObjectTypeDAO');
		if (!$reviewObjectTypeDao->reviewObjectTypeExists($reviewObjectTypeId, $journalId)) {
			$request->redirect(null, 'editor', 'objectsForReview');
		}

		$this->setupTemplate($request, true);
		$templateMgr =& TemplateManager::getManager($request);
		if ($objectId) {
			$templateMgr->assign('pageTitle', 'plugins.generic.objectsForReview.editor.edit');
		} else {
			$templateMgr->assign('pageTitle', 'plugins.generic.objectsForReview.editor.create');
		}
		$ofrPlugin =& $this->_getObjectsForReviewPlugin();
		$ofrPlugin->import('classes.form.ObjectForReviewForm');
		$ofrForm = new ObjectForReviewForm($ofrPlugin->getName(), $objectId, $reviewObjectTypeId, $importData);
		$ofrForm->initData();
		$ofrForm->display($request);
	}

	/**
	 * Update object for review.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function updateObjectForReview($args, &$request) {
		$objectId = (int) $request->getUserVar('objectId');
		$reviewObjectTypeId = (int) $request->getUserVar('reviewObjectTypeId');

		$journal =& $request->getJournal();
		$journalId = $journal->getId();

		if ($objectId && !$this->_ensureObjectExists($objectId, $journalId, $reviewObjectTypeId)) {
			$request->redirect(null, 'editor', 'objectsForReview');
		}

		$ofrPlugin =& $this->_getObjectsForReviewPlugin();
		$ofrPlugin->import('classes.form.ObjectForReviewForm');
		$ofrForm = new ObjectForReviewForm($ofrPlugin->getName(), $objectId, $reviewObjectTypeId);
		$ofrForm->readInputData();

		// Add a role block
		if ($request->getUserVar('addPerson')) {
			$editData = true;
			$persons = $ofrForm->getData('persons');
			array_push($persons, array());
			$ofrForm->setData('persons', $persons);

		// Delete persons
		} else if (($delPerson = $request->getUserVar('delPerson')) && count($delPerson) == 1) {
			$editData = true;
			list($delPerson) = array_keys($delPerson);
			$delPerson = (int) $delPerson;
			$persons = $ofrForm->getData('persons');
			if (isset($persons[$delPerson]['personId']) && !empty($persons[$delPerson]['personId'])) {
				$deletedPersons = explode(':', $ofrForm->getData('deletedPersons'));
				array_push($deletedPersons, $persons[$delPerson]['personId']);
				$ofrForm->setData('deletedPersons', join(':', $deletedPersons));
			}
			array_splice($persons, $delPerson, 1);
			$ofrForm->setData('persons', $persons);

		// Change person order
		} else if ($request->getUserVar('movePerson')) {
			$editData = true;
			$movePersonDir = $request->getUserVar('movePersonDir');
			$movePersonDir = $movePersonDir == 'u' ? 'u' : 'd';
			$movePersonIndex = (int) $request->getUserVar('movePersonIndex');
			$persons = $ofrForm->getData('persons');

			if (!(($movePersonDir == 'u' && $movePersonIndex <= 0) || ($movePersonDir == 'd' && $movePersonIndex >= count($persons) - 1))) {
				$tmpPerson = $persons[$movePersonIndex];
				if ($movePersonDir == 'u') {
					$persons[$movePersonIndex] = $persons[$movePersonIndex - 1];
					$persons[$movePersonIndex - 1] = $tmpPerson;
				} else {
					$persons[$movePersonIndex] = $persons[$movePersonIndex + 1];
					$persons[$movePersonIndex + 1] = $tmpPerson;
				}
			}
			$ofrForm->setData('persons', $persons);
		}

		if (!isset($editData) && $ofrForm->validate()) {
			$ofrForm->execute();
			// Notification
			if ($objectId) {
				$notificationType = NOTIFICATION_TYPE_OFR_UPDATED;
			} else {
				$notificationType = NOTIFICATION_TYPE_OFR_CREATED;
			}
			$this->_createTrivialNotification($notificationType, $request);

			if ($request->getUserVar('createAnother')) {
				$request->redirect(null, 'editor', 'createObjectForReview');
			} elseif ($request->getUserVar('addPerson') || $request->getUserVar('delPerson') || $request->getUserVar('movePerson')) {
				$request->redirect(null, 'editor', 'editObjectForReview', $objectId, array('reviewObjectTypeId' => $reviewObjectTypeId));
			} else {
				$request->redirect(null, 'editor', 'objectsForReview');
			}
		} else {
			$this->setupTemplate($request, true);
			$templateMgr =& TemplateManager::getManager($request);
			if ($objectId) {
				$templateMgr->assign('pageTitle', 'plugins.generic.objectsForReview.editor.edit');
			} else {
				$templateMgr->assign('pageTitle', 'plugins.generic.objectsForReview.editor.create');
			}
			$ofrForm->display($request);
		}
	}

	/**
	 * Remove object for review cover page image.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function removeObjectForReviewCoverPage($args, &$request) {
		$objectId = array_shift($args);

		$journal =& $request->getJournal();
		$journalId = $journal->getId();

		if (!$this->_ensureObjectExists($objectId, $journalId)) {
			$request->redirect(null, 'editor', 'objectsForReview');
		}

		$ofrDao =& DAORegistry::getDAO('ObjectForReviewDAO');
		$objectForReview =& $ofrDao->getById($objectId, $journalId);
		$coverPageSetting = $objectForReview->getCoverPage();
		if ($coverPageSetting) {
			// Delete cover image file from the filesystem
			import('classes.file.PublicFileManager');
			$publicFileManager = new PublicFileManager();
			$publicFileManager->removeJournalFile($journalId, $coverPageSetting['fileName']);
			// Delete object for review setting
			$ofrPlugin =& $this->_getObjectsForReviewPlugin();
			$ofrPlugin->import('classes.ReviewObjectMetadata');
			$metadataId = $objectForReview->getMetadataId(REVIEW_OBJECT_METADATA_KEY_COVERPAGE);
			$ofrSettingsDao =& DAORegistry::getDAO('ObjectForReviewSettingsDAO');
			$ofrSettingsDao->deleteSetting($objectId, $metadataId);
		}
		$request->redirect(null, 'editor', 'editObjectForReview', $objectId, array('reviewObjectTypeId' => $objectForReview->getReviewObjectTypeId()));
	}


	/**
	 * Remove object for review reviewer PDF.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function removeObjectForReviewReviewerPDF($args, &$request) {
		$objectId = array_shift($args);

		$journal =& $request->getJournal();
		$journalId = $journal->getId();

		if (!$this->_ensureObjectExists($objectId, $journalId)) {
			$request->redirect(null, 'editor', 'objectsForReview');
		}

		$ofrDao =& DAORegistry::getDAO('ObjectForReviewDAO');
		$objectForReview =& $ofrDao->getById($objectId, $journalId);
		$reviewerPDFSetting = $objectForReview->getReviewerPDF();
		if ($reviewerPDFSetting) {
			// Delete file from the filesystem
			import('classes.file.PublicFileManager');
			$publicFileManager = new PublicFileManager();
			$publicFileManager->removeJournalFile($journalId, $reviewerPDFSetting['reviewerPDFFileName']);
			// Delete object for review setting
			$ofrPlugin =& $this->_getObjectsForReviewPlugin();
			$ofrPlugin->import('classes.ReviewObjectMetadata');
			$metadataId = $objectForReview->getMetadataId(REVIEW_OBJECT_METADATA_KEY_REVIEWER_PDF);
			$ofrSettingsDao =& DAORegistry::getDAO('ObjectForReviewSettingsDAO');
			$ofrSettingsDao->deleteSetting($objectId, $metadataId);
		}
		$request->redirect(null, 'editor', 'editObjectForReview', $objectId, array('reviewObjectTypeId' => $objectForReview->getReviewObjectTypeId()));
	}

	/**
	 * Delete object for review.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function deleteObjectForReview($args, &$request) {
		$objectId = array_shift($args);

		$journal =& $request->getJournal();
		$journalId = $journal->getId();

		if ($this->_ensureObjectExists($objectId, $journalId)) {
			$ofrDao =& DAORegistry::getDAO('ObjectForReviewDAO');
			$objectForReview =& $ofrDao->getById($objectId, $journalId);
			$ofrDao->deleteObject($objectForReview);
			$this->_createTrivialNotification(NOTIFICATION_TYPE_OFR_DELETED, $request);
		}
		$request->redirect(null, 'editor', 'objectsForReview');
	}

	/**
	 * Display a list of authors from which to choose an object reviewer.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function selectObjectForReviewAuthor($args, &$request) {
		$objectId = array_shift($args);

		$journal =& $request->getJournal();
		$journalId = $journal->getId();

		if (!$this->_ensureObjectExists($objectId, $journalId)) {
			$request->redirect(null, 'editor', 'objectsForReview');
		}

		// Search
		$searchField = null;
		$searchMatch = null;
		$search = $request->getUserVar('search');
		$searchInitial = $request->getUserVar('searchInitial');
		if (!empty($search)) {
			$searchField = $request->getUserVar('searchField');
			$searchMatch = $request->getUserVar('searchMatch');

		} else if (isset($searchInitial)) {
			$searchInitial = String::strtoupper($searchInitial);
			$searchField = USER_FIELD_INITIAL;
			$search = $searchInitial;
		}
		$fieldOptions = Array(
			USER_FIELD_FIRSTNAME => 'user.firstName',
			USER_FIELD_LASTNAME => 'user.lastName',
			USER_FIELD_USERNAME => 'user.username',
			USER_FIELD_EMAIL => 'user.email'
		);

		// Get all and those authors assigned to this object
		$rangeInfo = Handler::getRangeInfo('users');
		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$users =& $roleDao->getUsersByRoleId(ROLE_ID_AUTHOR, $journalId, $searchField, $search, $searchMatch, $rangeInfo);
		$ofrAssignmentDao =& DAORegistry::getDAO('ObjectForReviewAssignmentDAO');
		$usersAssigned = $ofrAssignmentDao->getUserIds($objectId);

		$this->setupTemplate($request, true);
		$templateMgr =& TemplateManager::getManager($request);
		$templateMgr->assign('objectId', $objectId);

		$templateMgr->assign('searchField', $searchField);
		$templateMgr->assign('searchMatch', $searchMatch);
		$templateMgr->assign('search', $search);
		$templateMgr->assign('searchInitial', $searchInitial);
		$templateMgr->assign('searchFieldOptions', $fieldOptions);
		$templateMgr->assign('alphaList', explode(' ', __('common.alphaList')));

		$templateMgr->assign_by_ref('users', $users);
		$templateMgr->assign_by_ref('usersAssigned', $usersAssigned);

		import('classes.security.Validation');
		$templateMgr->assign('isJournalManager', Validation::isJournalManager());

		$ofrPlugin =& $this->_getObjectsForReviewPlugin();
		$templateMgr->display($ofrPlugin->getTemplatePath() . 'editor' . '/' . 'authors.tpl');
	}

	/**
	 * Assign an object for review author.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function assignObjectForReviewAuthor($args, &$request) {
		$objectId = array_shift($args);

		$journal =& $request->getJournal();
		$journalId = $journal->getId();

		if (!$this->_ensureObjectExists($objectId, $journalId)) {
			$request->redirect(null, 'editor', 'objectsForReview');
		}
		$ofrDao =& DAORegistry::getDAO('ObjectForReviewDAO');
		$objectForReview =& $ofrDao->getById($objectId, $journalId);

		$redirect = true;
		if ($objectForReview->getAvailable()) {
			$userId = (int) $request->getUserVar('userId');
			// Ensure there is no assignment for this object and user
			$ofrAssignmentDao =& DAORegistry::getDAO('ObjectForReviewAssignmentDAO');
			if ($ofrAssignmentDao->assignmentExists($objectId, $userId)) {
				$request->redirect(null, 'editor', 'objectsForReview');
			}
			// Ensure the user exists and is an author for this journal
			$userDao =& DAORegistry::getDAO('UserDAO');
			$user =& $userDao->getById($userId);
			$roleDao =& DAORegistry::getDAO('RoleDAO');
			if (isset($user) && $roleDao->userHasRole($journalId, $userId, ROLE_ID_AUTHOR)) {
				$returnUrl = $request->url(null, 'editor', 'assignObjectForReviewAuthor', $objectId, array('userId' => $userId));
				// Assign
				$redirect = $this->_assign(null, $objectForReview, $user, $returnUrl, $request);
			}
		}
		if ($redirect) $request->redirect(null, 'editor', 'objectsForReview', 'assigned');
	}

	/**
	 * Accept an object for review author.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function acceptObjectForReviewAuthor($args, &$request) {
		$returnPage = $this->_getReturnpage($request);

		$assignmentId = array_shift($args);

		$journal =& $request->getJournal();
		$journalId = $journal->getId();

		$redirect = true;
		// Ensure the assignment exists
		$ofrPlugin =& $this->_getObjectsForReviewPlugin();
		$ofrPlugin->import('classes.ObjectForReviewAssignment');
		if (!$this->_ensureAssignmentExists($assignmentId, $journalId, OFR_STATUS_REQUESTED)) {
			$request->redirect(null, 'editor', 'objectsForReview', $returnPage);
		}
		$ofrAssignmentDao =& DAORegistry::getDAO('ObjectForReviewAssignmentDAO');
		$ofrAssignment =& $ofrAssignmentDao->getById($assignmentId);
		// Get the author
		$userDao =& DAORegistry::getDAO('UserDAO');
		$user =& $userDao->getById($ofrAssignment->getUserId());
		$returnUrl = $request->url(null, 'editor', 'acceptObjectForReviewAuthor', $assignmentId, array('returnPage' => $returnPage));
		// Assign
		$redirect = $this->_assign($ofrAssignment, $ofrAssignment->getObjectForReview(), $user, $returnUrl, $request);

		if ($redirect) {
			if ($returnPage != 'all') $returnPage = 'assigned';
			$request->redirect(null, 'editor', 'objectsForReview', $returnPage);
		}
	}

	/**
	 * Deny an object for review request.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function denyObjectForReviewAuthor($args, &$request) {
		$returnPage = $this->_getReturnpage($request);

		$assignmentId = array_shift($args);

		$journal =& $request->getJournal();
		$journalId = $journal->getId();

		// Ensure the assignment exists
		$ofrPlugin =& $this->_getObjectsForReviewPlugin();
		$ofrPlugin->import('classes.ObjectForReviewAssignment');
		if (!$this->_ensureAssignmentExists($assignmentId, $journalId, OFR_STATUS_REQUESTED)) {
			$request->redirect(null, 'editor', 'objectsForReview', $returnPage);
		}
		$ofrAssignmentDao =& DAORegistry::getDAO('ObjectForReviewAssignmentDAO');
		$ofrAssignment =& $ofrAssignmentDao->getById($assignmentId);

		$redirect = true;
		import('classes.mail.MailTemplate');
		$email = new MailTemplate('OFR_OBJECT_DENIED');
		$send = $request->getUserVar('send');
		// Editor has filled out mail form or skipped mail
		if ($send && !$email->hasErrors()) {
			// Delete the assignment
			$ofrAssignmentDao->deleteById($assignmentId);
			$email->send();
			$this->_createTrivialNotification(NOTIFICATION_TYPE_OFR_AUTHOR_DENIED, $request);
		} else {
			$returnUrl = $request->url(null, 'editor', 'denyObjectForReviewAuthor', $assignmentId, array('returnPage' => $returnPage));
			$this->_displayEmailForm($email, $ofrAssignment->getObjectForReview(), $ofrAssignment->getUser(), $returnUrl, 'OFR_OBJECT_DENIED', $request);
			$redirect = false;
		}
		if ($redirect) $request->redirect(null, 'editor', 'objectsForReview', $returnPage);
	}

	/**
	 * Mark an object for review assignment as mailed.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function notifyObjectForReviewMailed($args, &$request) {
		$returnPage = $this->_getReturnpage($request);

		$assignmentId = array_shift($args);

		$journal =& $request->getJournal();
		$journalId = $journal->getId();

		// Ensure the assignment exists
		$ofrPlugin =& $this->_getObjectsForReviewPlugin();
		$ofrPlugin->import('classes.ObjectForReviewAssignment');
		if (!$this->_ensureAssignmentExists($assignmentId, $journalId, OFR_STATUS_ASSIGNED)) {
			$request->redirect(null, 'editor', 'objectsForReview', $returnPage);
		}
		$ofrAssignmentDao =& DAORegistry::getDAO('ObjectForReviewAssignmentDAO');
		$ofrAssignment =& $ofrAssignmentDao->getById($assignmentId);

		$redirect = true;
		import('classes.mail.MailTemplate');
		$email = new MailTemplate('OFR_OBJECT_MAILED');
		$send = $request->getUserVar('send');
		// Editor has filled out mail form or skipped mail
		if ($send && !$email->hasErrors()) {
			// Update status
			$ofrAssignment->setStatus(OFR_STATUS_MAILED);
			// Update due date
			$dueWeeks = $ofrPlugin->getSetting($journalId, 'dueWeeks');
			$dueDateTimestamp = time() + ($dueWeeks * 7 * 24 * 60 * 60);
			$dueDate = date('Y-m-d H:i:s', $dueDateTimestamp);
			$ofrAssignment->setDateDue($dueDate);
			// Set date mailed and update the assignment
			$ofrAssignment->setDateMailed(Core::getCurrentDate());
			$ofrAssignmentDao->updateObject($ofrAssignment);
			$email->send();
			$this->_createTrivialNotification(NOTIFICATION_TYPE_OFR_AUTHOR_MAILED, $request);
		} else {
			$returnUrl = $request->url(null, 'editor', 'notifyObjectForReviewMailed', $assignmentId, array('returnPage' => $returnPage));
			$this->_displayEmailForm($email, $ofrAssignment->getObjectForReview(), $ofrAssignment->getUser(), $returnUrl, 'OFR_OBJECT_MAILED', $request);
			$redirect = false;
		}
		if ($returnPage != 'all') $returnPage = 'mailed';
		if ($redirect) $request->redirect(null, 'editor', 'objectsForReview', $returnPage);
	}

	/**
	 * Remove object reviewer assignment.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function removeObjectForReviewAssignment($args, &$request) {
		$returnPage = $this->_getReturnpage($request);

		$assignmentId = array_shift($args);

		$journal =& $request->getJournal();
		$journalId = $journal->getId();

		// Ensure the assignment exists
		if (!$this->_ensureAssignmentExists($assignmentId, $journalId)) {
			$request->redirect(null, 'editor', 'objectsForReview', $returnPage);
		}
		$ofrAssignmentDao =& DAORegistry::getDAO('ObjectForReviewAssignmentDAO');
		$ofrAssignment =& $ofrAssignmentDao->getById($assignmentId);
		// Ensure the assignment can be removed
		if (!$this->_canBeRemoved($ofrAssignment)) {
			$request->redirect(null, 'editor', 'objectsForReview', $returnPage);
		}

		$redirect = true;
		import('classes.mail.MailTemplate');
		$email = new MailTemplate('OFR_REVIEWER_REMOVED');
		$send = $request->getUserVar('send');
		// Editor has filled out mail form or skipped mail
		if ($send && !$email->hasErrors()) {
			// Delete the assignment
			$ofrAssignmentDao->deleteById($assignmentId);
			$email->send();
			$this->_createTrivialNotification(NOTIFICATION_TYPE_OFR_AUTHOR_REMOVED, $request);
		} else {
			$returnUrl = $request->url(null, 'editor', 'removeObjectForReviewAssignment', $assignmentId, array('returnPage' => $returnPage));
			$this->_displayEmailForm($email, $ofrAssignment->getObjectForReview(), $ofrAssignment->getUser(), $returnUrl, 'OFR_REVIEWER_REMOVED', $request);
			$redirect = false;
		}
		if ($redirect) $request->redirect(null, 'editor', 'objectsForReview', $returnPage);
	}

	/**
	 * Display a list of submissions from which to choose an object review submission.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function selectObjectForReviewSubmission($args, &$request) {
		$returnPage = $this->_getReturnpage($request);

		$journal =& $request->getJournal();
		$journalId = $journal->getId();

		$ofrPlugin =& $this->_getObjectsForReviewPlugin();
		$mode = $ofrPlugin->getSetting($journalId, 'mode');

		$assignmentId = array_shift($args);
		if ($mode == OFR_MODE_FULL) {
			if (!$this->_ensureAssignmentExists($assignmentId, $journalId)) {
				$request->redirect(null, 'editor', 'objectsForReview', $returnPage);
			}
			$ofrAssignmentDao =& DAORegistry::getDAO('ObjectForReviewAssignmentDAO');
			$ofrAssignment =& $ofrAssignmentDao->getById($assignmentId);
			$objectId = $ofrAssignment->getObjectId();
		}
		if ($mode == OFR_MODE_METADATA) {
			$objectId = $request->getUserVar('objectId') == null ? null : $request->getUserVar('objectId');
			// Ensure the object exists
			if (!$this->_ensureObjectExists($objectId, $journalId)) {
				$request->redirect(null, 'editor', 'objectsForReview', $returnPage);
			}
		}

		// Search
		$searchField = null;
		$searchMatch = null;
		$search = $request->getUserVar('search');
		if (!empty($search)) {
			$searchField = $request->getUserVar('searchField');
			$searchMatch = $request->getUserVar('searchMatch');
		}
		import('classes.submission.common.Action');
		$fieldOptions = Array(
			SUBMISSION_FIELD_TITLE => 'article.title',
			SUBMISSION_FIELD_ID => 'article.submissionId',
			SUBMISSION_FIELD_AUTHOR => 'user.role.author',
		);

		// Get submissions assigned to this user/editor
		$user =& $request->getUser();
		$editorId = $user->getId();
		$rangeInfo = Handler::getRangeInfo('submissions');
		$editorSubmissionDao =& DAORegistry::getDAO('EditorSubmissionDAO');
		$submissions =& $editorSubmissionDao->getEditorSubmissions(
			$journalId,
			0,
			$editorId,
			$searchField,
			$searchMatch,
			$search,
			null,
			null,
			null,
			$rangeInfo,
			'id',
			SORT_DIRECTION_DESC
		);

		$this->setupTemplate($request, true);
		$templateMgr =& TemplateManager::getManager($request);
		$templateMgr->assign('assignmentId', $assignmentId);
		$templateMgr->assign('objectId', $objectId);
		$templateMgr->assign('returnPage', $returnPage);

		$templateMgr->assign('searchField', $searchField);
		$templateMgr->assign('searchMatch', $searchMatch);
		$templateMgr->assign('search', $search);
		$templateMgr->assign('searchFieldOptions', $fieldOptions);

		$templateMgr->assign('submissions', $submissions);
		$templateMgr->display($ofrPlugin->getTemplatePath() . 'editor' . '/' . 'submissions.tpl');
	}

	/**
	 * Assign an object for review submission.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function assignObjectForReviewSubmission($args, &$request) {
		$returnPage = $this->_getReturnpage($request);

		$journal =& $request->getJournal();
		$journalId = $journal->getId();

		$ofrPlugin =& $this->_getObjectsForReviewPlugin();
		$mode = $ofrPlugin->getSetting($journalId, 'mode');

		$assignmentId = array_shift($args);
		$ofrAssignmentDao =& DAORegistry::getDAO('ObjectForReviewAssignmentDAO');
		$insert = false;
		if ($mode == OFR_MODE_FULL) {
			if (!$this->_ensureAssignmentExists($assignmentId, $journalId)) {
				$request->redirect(null, 'editor', 'objectsForReview', $returnPage);
			}
			$ofrAssignment =& $ofrAssignmentDao->getById($assignmentId);
			$objectId = $ofrAssignment->getObjectId();
		} elseif ($mode == OFR_MODE_METADATA) {
			$objectId = (int) $request->getUserVar('objectId');
			if (!$this->_ensureObjectExists($objectId, $journalId)) {
				$request->redirect(null, 'editor', 'objectsForReview', $returnPage);
			}
			$ofrAssignment =& $ofrAssignmentDao->newDataObject();
			$ofrAssignment->setObjectId($objectId);
			$insert = true;
		}

		$submissionId = (int) $request->getUserVar('submissionId');
		// Ensure article is for this journal and update object for review assignment
		$articleDao =& DAORegistry::getDAO('ArticleDAO');
		if ($articleDao->getArticleJournalId($submissionId) == $journalId) {
			$ofrAssignment->setSubmissionId($submissionId);
			$ofrAssignment->setStatus(OFR_STATUS_SUBMITTED);
			if ($insert) {
				$ofrAssignmentDao->insertObject($ofrAssignment);
			} else {
				$ofrAssignmentDao->updateObject($ofrAssignment);
			}
			$this->_createTrivialNotification(NOTIFICATION_TYPE_OFR_SUBMISSION_ASSIGNED, $request);
		}

		if ($returnPage != 'all') $returnPage = 'submitted';
		$request->redirect(null, 'editor', 'objectsForReview', $returnPage);
	}

	/**
	 * Edit object for review assignment.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function editObjectForReviewAssignment($args, &$request) {
		$returnPage = $this->_getReturnpage($request);

		$journal =& $request->getJournal();
		$journalId = $journal->getId();

		$assignmentId = array_shift($args);
		$objectId = (int) $request->getUserVar('objectId');
		if (!$this->_ensureAssignmentExists($assignmentId, $journalId) || !$this->_ensureObjectExists($objectId, $journalId)) {
			$request->redirect(null, 'editor', 'objectsForReview', $returnPage);
		}
		$ofrAssignmentDao =& DAORegistry::getDAO('ObjectForReviewAssignmentDAO');
		$ofrAssignment =& $ofrAssignmentDao->getById($assignmentId, $objectId);
		if (!isset($ofrAssignment)) {
			$request->redirect(null, 'editor', 'objectsForReview', $returnPage);
		}

		$this->setupTemplate($request, true);
		$templateMgr =& TemplateManager::getManager($request);
		$templateMgr->assign('pageTitle', 'plugins.generic.objectsForReview.editor.edit');

		$ofrPlugin =& $this->_getObjectsForReviewPlugin();
		$ofrPlugin->import('classes.form.ObjectForReviewAssignmentForm');
		$ofrAssignmentForm = new ObjectForReviewAssignmentForm($ofrPlugin->getName(), $assignmentId, $objectId);
		$ofrAssignmentForm->initData();
		$mode = $ofrPlugin->getSetting($journalId, 'mode');
		$templateMgr->assign('mode', $mode);
		$templateMgr->assign('returnPage', $returnPage);
		$ofrAssignmentForm->display($request);
	}

	/**
	 * Update object for review assignment.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function updateObjectForReviewAssignment($args, &$request) {
		$returnPage = $this->_getReturnpage($request);

		$journal =& $request->getJournal();
		$journalId = $journal->getId();

		$assignmentId = (int) $request->getUserVar('assignmentId');
		$objectId = (int) $request->getUserVar('objectId');
		if (!$this->_ensureAssignmentExists($assignmentId, $journalId) || !$this->_ensureObjectExists($objectId, $journalId)) {
			$request->redirect(null, 'editor', 'objectsForReview', $returnPage);
		}
		$ofrAssignmentDao =& DAORegistry::getDAO('ObjectForReviewAssignmentDAO');
		$ofrAssignment =& $ofrAssignmentDao->getById($assignmentId, $objectId);
		if (!isset($ofrAssignment)) {
			$request->redirect(null, 'editor', 'objectsForReview', $returnPage);
		}

		$ofrPlugin =& $this->_getObjectsForReviewPlugin();
		$ofrPlugin->import('classes.form.ObjectForReviewAssignmentForm');
		$ofrAssignmentForm = new ObjectForReviewAssignmentForm($ofrPlugin->getName(), $assignmentId, $objectId);
		$ofrAssignmentForm->readInputData();
		if ($ofrAssignmentForm->validate()) {
			$ofrAssignmentForm->execute();
			$request->redirect(null, 'editor', 'objectsForReview', $returnPage);
		} else {
			$this->setupTemplate($request, true);
			$templateMgr =& TemplateManager::getManager($request);
			$templateMgr->assign('pageTitle', 'plugins.generic.objectsForReview.editor.edit');
			$mode = $ofrPlugin->getSetting($journalId, 'mode');
			$templateMgr->assign('mode', $mode);
			$templateMgr->assign('returnPage', $returnPage);
			$ofrAssignmentForm->display($request);
		}
	}

	/**
	 * List the publishers currently available.
	 * @param array $args
	 * @param PKPRequest $request
	 */
	function objectsForReviewPublishers($args, &$request) {

		$journal =& $request->getJournal();
		$journalId = $journal->getId();

		$this->setupTemplate($request);

		$templateMgr =& TemplateManager::getManager();

		$ofrOrgDao = DAORegistry::getDAO('ObjectForReviewOrganizationDAO');
		$organizations = $ofrOrgDao->getOrganizations($journal->getId());
		$templateMgr->assign_by_ref('organizations', $organizations);
		$ofrPlugin =& $this->_getObjectsForReviewPlugin();
		$templateMgr->display($ofrPlugin->getTemplatePath() . 'editor/listOrganizations.tpl');
	}

	/**
	 * Create or update a publisher.
	 * @param array $args
	 * @param PKPRequest $request
	 * @return boolean
	 */
	function objectsForReviewManagePublisher($args, &$request) {

		$journal =& $request->getJournal();
		$journalId = $journal->getId();
		$this->setupTemplate($request);
		$ofrPlugin =& $this->_getObjectsForReviewPlugin();

		$organizationId = (int)Request::getUserVar('organizationId');
		$ofrPlugin->import('classes.form.ObjectForReviewOrganizationForm');
		$form = new ObjectForReviewOrganizationForm($ofrPlugin, $journal->getId(), $organizationId);
		if (Request::getUserVar('save')) {
			$form->readInputData();
			if ($form->validate()) {
				$form->execute();
				$request->redirect(null, 'editor', 'objectsForReviewPublishers');
				return false;
			} else {
				$form->display();
			}
		} else {
			$form->initData();
			$form->display();
		}
	}

	/**
	 * Delete a publisher.
	 * @param array $args
	 * @param PKPRequest $request
	 */
	function objectsForReviewDeletePublisher($args, &$request) {
		$organizationId = (int) Request::getUserVar('organizationId');
		$ofrOrgDao = DAORegistry::getDAO('ObjectForReviewOrganizationDAO');
		$ofrOrgDao->deleteOrganizationById($organizationId);
		$request->redirect(null, 'editor', 'objectsForReviewPublishers');
	}

	/**
	 * Display current OJS reader users that can be enrolled as Publisher users.
	 * @param array $args
	 * @param PKPRequest $request
	 */
	function objectsForReviewEnrollPublishers($args, &$request) {
		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');
		$ofrOrgDao =& DAORegistry::getDAO('ObjectForReviewOrganizationDAO');
		$ofrEADao =& DAORegistry::getDAO('ObjectForReviewEditorAssignmentDAO');

		$journal =& $request->getJournal();

		$templateMgr =& TemplateManager::getManager();

		$this->setupTemplate($request, true);

		$rangeInfo = $this->getRangeInfo('users');
		$users =& $roleDao->getUsersByRoleId(ROLE_ID_READER);

		$templateMgr->assign_by_ref('users', $users);
		$templateMgr->assign_by_ref('thisUser', Request::getUser());
		$templateMgr->assign_by_ref('publishers', $ofrOrgDao->getOrganizations($journal->getId()));
		$templateMgr->assign_by_ref('ofrEADao', $ofrEADao);

		$ofrPlugin =& $this->_getObjectsForReviewPlugin();
		$templateMgr->display($ofrPlugin->getTemplatePath() . 'editor/enrollPublishers.tpl');
	}

	/**
	 * enroll a publisher(s).
	 * @param array $args
	 * @param PKPRequest $request
	 */
	function objectsForReviewEnroll($args, &$request) {
		$publisherId = (int) Request::getUserVar('publisherId');
		$ofrOrgDao = DAORegistry::getDAO('ObjectForReviewOrganizationDAO');
		$ofrEdAssOrgDao = DAORegistry::getDAO('ObjectForReviewEditorAssignmentDAO');
		$publisher = $ofrOrgDao->getOrganization($publisherId);
		if ($publisher) {
			$userIds = Request::getUserVar('users');
			if (is_array($userIds)) {
				foreach ($userIds as $userId) {
					$assignment = $ofrEdAssOrgDao->newDataObject();
					$assignment->setPublisherId($publisherId);
					$assignment->setUserId($userId);
					$ofrEdAssOrgDao->insertObject($assignment);
				}
			}
		}
		$request->redirect(null, 'editor', 'objectsForReviewEnrollPublishers');
	}

	/**
	 * Unenroll a publisher.
	 * @param array $args
	 * @param PKPRequest $request
	 */
	function objectsForReviewUnenroll($args, &$request) {
		$publisherId = (int) Request::getUserVar('publisherId');
		$userId = (int) Request::getUserVar('userId');

		if ($publisherId && $userId) {
			$ofrEdAssOrgDao = DAORegistry::getDAO('ObjectForReviewEditorAssignmentDAO');
			$assignment = $ofrEdAssOrgDao->getByPublisherAndUserId($publisherId, $userId);
			if ($assignment) {
				$ofrEdAssOrgDao->deleteById($assignment->getId());
			}
		}
		$request->redirect(null, 'editor', 'objectsForReviewEnrollPublishers');
	}

	/**
	 * Batch import from an ONIX XML export.
	 * @param array $args
	 * @param PKPRequest $request
	 */
	function uploadONIXObjectForReview($args, &$request) {
		$user = $request->getUser();
		$journal =& $request->getJournal();
		$ofrOrgDao =& DAORegistry::getDAO('ObjectForReviewOrganizationDAO');

		$ofrPlugin =& $this->_getObjectsForReviewPlugin();
		$ofrPlugin->import('classes.form.ObjectForReviewForm');
		$reviewObjectTypeId = (int) $request->getUserVar('reviewObjectTypeId');

		import('classes.file.TemporaryFileManager');
		$temporaryFileManager = new TemporaryFileManager();
		$temporaryFile = $temporaryFileManager->handleUpload('onixFile', $user->getId());
		$filePath = $temporaryFile->getFilePath();
		$parser = new XMLParser();
		$doc =& $parser->parse($filePath);

		$multiple = $request->getUserVar('multiple');

		if ($doc) {

			// Determine if we have short or long tags.
			$productNodes = $doc->getChildByName('product');
			$shortTags = $productNodes ? true : false;

			for ($index=0; ($productNode = $doc->getChildByName($this->_getOnixTag('Product', $shortTags), $index)); $index++) {

				$importData = array();

				if ($productNode) {
					$publisherNode = $productNode->getChildByName($this->_getOnixTag('Publisher', $shortTags));
					if ($publisherNode) {
						$publisherNameNode = $publisherNode->getChildByName($this->_getOnixTag('PublisherName', $shortTags));
						if ($publisherNameNode) {
							$publisher = $publisherNameNode->getValue();
							$organization =& $ofrOrgDao->getOrganizationByName(trim($publisher));
							if ($organization) {
								$importData['publisherId'] = $organization->getId();
							}
						}
					}
					$websiteNode = $publisherNode->getChildByName($this->_getOnixTag('Website', $shortTags));
					if ($websiteNode) {
						$websiteLinkNode = $websiteNode->getChildByName($this->_getOnixTag('WebsiteLink', $shortTags));
						$websiteLink = $websiteLinkNode->getValue();
						$importData['book_publisher_url'] = $websiteLink;
					}
					$titleNode = $productNode->getChildByName($this->_getOnixTag('Title', $shortTags));
					if ($titleNode) {
						$titleTextNode = $titleNode->getChildByName($this->_getOnixTag('TitleText', $shortTags));
						$title = $titleTextNode->getValue();
						$importData['title'] = $title;
					}
					$subTitleNode = $titleNode->getChildByName($this->_getOnixTag('Subtitle', $shortTags));
					if ($subTitleNode) {
						$subTitle = $subTitleNode->getValue();
						$importData['shortTitle'] = $subTitle;
					}
					$seriesNode = $productNode->getChildByName($this->_getOnixTag('Series', $shortTags));
					if ($seriesNode) {
						$seriesTextNode = $seriesNode->getChildByName($this->_getOnixTag('TitleOfSeries', $shortTags));
						$series = $seriesTextNode->getValue();
						$importData['book_series'] = $series;
					}
					$languageNode = $productNode->getChildByName($this->_getOnixTag('Language', $shortTags));
					if ($languageNode) {
						$languageCodeNode = $languageNode->getChildByName($this->_getOnixTag('LanguageCode', $shortTags));
						$language = $languageCodeNode->getValue();
						$importData['language'] = substr($language, 0, 2);
					} else {
						$importData['language'] = 'en';
					}
					$pageNode = $productNode->getChildByName($this->_getOnixTag('NumberOfPages', $shortTags));
					if ($pageNode) {
						$pages = $pageNode->getValue();
						$importData['book_pages_no'] = $pages;
					}
					// Abstract. Look for OtherText with
					// sub element of TextTypeCode of '01' (main description)

					$abstract = '';

					for ($authorIndex=0; ($node = $productNode->getChildByName($this->_getOnixTag('OtherText', $shortTags), $authorIndex)); $authorIndex++) {
						$typeNode = $node->getChildByName($this->_getOnixTag('TextTypeCode', $shortTags));
						if ($typeNode && $typeNode->getValue() == '01') {
							$textNode = $node->getChildByName($this->_getOnixTag('Text', $shortTags));
							if ($textNode) {
								$abstract = strip_tags($textNode->getValue());
							}
							break;
						}
					}

					$importData['abstract'] = $abstract;

					// ISBN-13
					for ($productIdentifierIndex=0; ($node = $productNode->getChildByName($this->_getOnixTag('ProductIdentifier', $shortTags), $productIdentifierIndex)); $productIdentifierIndex++) {
						$idTypeNode = $node->getChildByName($this->_getOnixTag('ProductIDType', $shortTags));
						if ($idTypeNode && $idTypeNode->getValue() == '15') { // ISBN-13
							$textNode = $node->getChildByName($this->_getOnixTag('IDValue', $shortTags));
							if ($textNode) {
								$importData['book_isbn'] = $textNode->getValue();
							}
							break;
						}
					}

					// Subjects
					$importData['subjectKeywords'] = '';
					$subjects = array();
					for ($subjectIndex=0; ($node = $productNode->getChildByName($this->_getOnixTag('Subject', $shortTags), $subjectIndex)); $subjectIndex++) {
						$textNode = $node->getChildByName($this->_getOnixTag('SubjectHeadingText', $shortTags));
						if ($textNode) {
							$subjects[] = $textNode->getValue();
						}
					}
					$importData['subjectKeywords'] = join(', ', $subjects);

					$publicationDateNode = $productNode->getChildByName($this->_getOnixTag('PublicationDate', $shortTags));
					if ($publicationDateNode) {
						$publicationDate = $publicationDateNode->getValue();
						$importData['date'] = $publicationDate;
					}
					// Contributors.
					$persons = array();
					for ($authorIndex=0; ($node = $productNode->getChildByName($this->_getOnixTag('Contributor', $shortTags), $authorIndex)); $authorIndex++) {
						$firstNameNode = $node->getChildByName($this->_getOnixTag('NamesBeforeKey', $shortTags));
						if ($firstNameNode) {
							$firstName = $firstNameNode->getValue();
						}
						$lastNameNode = $node->getChildByName($this->_getOnixTag('KeyNames', $shortTags));
						if ($lastNameNode) {
							$lastName = $lastNameNode->getValue();
						}
						$seqNode = $node->getChildByName($this->_getOnixTag('SequenceNumber', $shortTags));
						if ($seqNode) {
							$seq = $seqNode->getValue();
						}
						$contributorRoleNode = $node->getChildByName($this->_getOnixTag('ContributorRole', $shortTags));
						$contributorRole = '';
						if ($contributorRoleNode) {
							switch ($contributorRoleNode->getValue()) {
								case 'A01':
									$contributorRole = '1';
									break;
								case 'B01':
									$contributorRole = '3';
									break;
								case 'B09':
									$contributorRole = '4';
									break;
								case 'B06':
									$contributorRole = '5';
									break;
								default:
									$contributorRole = '2'; // Contributor
								break;
							}
						}
						$persons[] = array(
									'personId' => '',
									'role' => $contributorRole,
									'firstName' => $firstName,
									'middleName' => '',
									'lastName' => $lastName,
									'seq' => (int) $seq
								);
						unset($node);
					}

					$importData['persons'] = $persons;
					if (!$multiple) {
						$temporaryFileManager->deleteFile($temporaryFile->getId(), $user->getId());
						$this->editObjectForReview($args, &$request, $importData);
						break;
					} else {
						// we are processing more than one Product.  Instaniate the form and let it
						// handle the object creation.
						$ofrForm = new ObjectForReviewForm($ofrPlugin->getName(), null, $reviewObjectTypeId, $importData);
						$ofrForm->initData();
						$ofrForm->execute();
					}
				} else {
					$request->redirect(null, 'editor', 'objectsForReview', 'onixError');
				}
			}
			$request->redirect(null, 'editor', 'objectsForReview');
		} else {
			// this deleteFile is only called if the document does not parse.
			$temporaryFileManager->deleteFile($temporaryFile->getId(), $user->getId());
			$request->redirect(null, 'editor', 'objectsForReview');
		}
	}

	/**
	 * Return valid landing/return pages
	 * @return array
	 */
	function &getValidReturnPages() {
		$validPages = array(
			'all',
			'requested',
			'assigned',
			'mailed',
			'submitted'
		);
		return $validPages;
	}

	/**
	 * Ensure that we have a journal, plugin is enabled, and user is editor.
	 * @see PKPHandler::authorize()
	 */
	function authorize(&$request, &$args, $roleAssignments) {
		$journal =& $request->getJournal();
		if (!isset($journal)) return false;

		$ofrPlugin =& $this->_getObjectsForReviewPlugin();

		if (!isset($ofrPlugin)) return false;

		if (!$ofrPlugin->getEnabled()) return false;

		$ofrEADao =& DAORegistry::getDAO('ObjectForReviewEditorAssignmentDAO');
		$user =& $request->getUser();
		$assignments = $ofrEADao->getAllByUserId($user->getId());

		if (!Validation::isEditor($journal->getId()) && count($assignments) == 0) Validation::redirectLogin();

		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * Setup common template variables.
	 * @param $request PKPRequest
	 * @param $subclass boolean (optional) set to true if caller is below this handler in the hierarchy
	 * @param $objectId int (optional)
	 */
	function setupTemplate(&$request, $subclass = false, $objectId = null) {
		$templateMgr =& TemplateManager::getManager($request);
		$pageCrumbs = array(
			array(
				$request->url(null, 'user'),
				'navigation.user'
			),
			array(
				$request->url(null, 'editor'),
				'user.role.editor'
			)
		);
		if ($subclass) {
			$returnPage = $request->getUserVar('returnPage');
			if ($returnPage != null) {
				$validPages =& $this->getValidReturnPages();
				if (!in_array($returnPage, $validPages)) {
					$returnPage = null;
				}
			}
			$pageCrumbs[] = array(
				$request->url(null, 'editor', 'objectsForReview', $returnPage),
				AppLocale::Translate('plugins.generic.objectsForReview.displayName'),
				true
			);
		}
		if ($objectId) {
			$pageCrumbs[] = array(
				$request->url(null, 'editor', 'objectsForReview', $objectId),
				$reviewObjectType->getLocalizedName(),
				true
			);
		}
		$templateMgr->assign('pageHierarchy', $pageCrumbs);
		$ofrPlugin =& $this->_getObjectsForReviewPlugin();
		$templateMgr->addStyleSheet(Request::getBaseUrl() . '/' . $ofrPlugin->getStyleSheet());
	}

	//
	// Private helper methods
	//
	/**
	 * Get the objectForReview plugin object
	 * @return ObjectsForReviewPlugin
	 */
	function &_getObjectsForReviewPlugin() {
		$plugin =& PluginRegistry::getPlugin('generic', OBJECTS_FOR_REVIEW_PLUGIN_NAME);
		return $plugin;
	}

	/**
	 * Get return page
	 * @param $request PKPRequest
	 * @return string
	 */
	function _getReturnpage(&$request) {
		$returnPage = $request->getUserVar('returnPage') == null ? null : $request->getUserVar('returnPage');
		if ($returnPage != null) {
			$validPages =& $this->getValidReturnPages();
			if (!in_array($returnPage, $validPages)) {
				$returnPage = null;
			}
		}
		return $returnPage;
	}

	/**
	 * Ensure object for review exists
	 * @param $objectId int
	 * @param $journalId int
	 * @param $reviewObjectTypeId int (optional)
	 * @return boolean
	 */
	function _ensureObjectExists($objectId, $journalId, $reviewObjectTypeId = null) {
		if (!$objectId) {
			return false;
		}
		$ofrDao =& DAORegistry::getDAO('ObjectForReviewDAO');
		$objectForReview =& $ofrDao->getById($objectId, $journalId);
		if (!isset($objectForReview)) {
			return false;
		}
		if ($reviewObjectTypeId && ($objectForReview->getReviewObjectTypeId() != $reviewObjectTypeId)) {
			return false;
		}
		return true;
	}

	/**
	 * Ensure object for review assignment exists
	 * @param $assignmentId int
	 * @param $journalId int
	 * @param $status int (optional)
	 * @return boolean
	 */
	function _ensureAssignmentExists($assignmentId, $journalId, $status = null) {
		if (!$assignmentId) {
			return false;
		}
		$ofrAssignmentDao =& DAORegistry::getDAO('ObjectForReviewAssignmentDAO');
		$ofrAssignment =& $ofrAssignmentDao->getById($assignmentId);
		if (!isset($ofrAssignment)) {
			return false;
		}
		// Ensure status
		if ($status && ($ofrAssignment->getStatus() != $status)) {
			return false;
		}
		// Ensure the object exists
		return $this->_ensureObjectExists($ofrAssignment->getObjectId(), $journalId);
	}

	/**
	 * Assign an author to an object for review.
	 * @param $ofrAssignment ObjectForReviewAssignment
	 * @param $objectForReview ObjectForReview
	 * @param $author User
	 * @param $returnUrl string
	 * @param $request PKPRequest
	 */
	function _assign($ofrAssignment, $objectForReview, $author, $returnUrl, &$request) {
		import('classes.mail.MailTemplate');
		$email = new MailTemplate('OFR_OBJECT_ASSIGNED');
		$send = $request->getUserVar('send');

		// Editor has filled out mail form or skipped mail
		if ($send && !$email->hasErrors()) {
			// Update object for review
			$ofrPlugin =& $this->_getObjectsForReviewPlugin();
			$journal =& $request->getJournal();
			$dueWeeks = $ofrPlugin->getSetting($journal->getId(), 'dueWeeks');
			$dueDateTimestamp = time() + ($dueWeeks * 7 * 24 * 60 * 60);
			$dueDate = date('Y-m-d H:i:s', $dueDateTimestamp);

			$ofrAssignmentDao =& DAORegistry::getDAO('ObjectForReviewAssignmentDAO');
			if (!isset($ofrAssignment)) {
				$ofrAssignment = $ofrAssignmentDao->newDataObject();
				$ofrAssignment->setObjectId($objectForReview->getId());
				$ofrAssignment->setUserId($author->getId());
			}
			$ofrAssignment->setStatus(OFR_STATUS_ASSIGNED);
			$ofrAssignment->setDateAssigned(Core::getCurrentDate());
			$ofrAssignment->setDateDue($dueDate);
			if ($ofrAssignment->getId() == null) {
				$ofrAssignmentDao->insertObject($ofrAssignment);
			} else {
				$ofrAssignmentDao->updateObject($ofrAssignment);
			}
			$email->send();
			$this->_createTrivialNotification(NOTIFICATION_TYPE_OFR_AUTHOR_ASSIGNED, $request);
			return true;
		} else {
			$this->_displayEmailForm($email, $objectForReview, $author, $returnUrl, 'OFR_OBJECT_ASSIGNED', $request);
			return false;
		}
	}

	/**
	 * Is remove action allowed
	 * @param $ofrAssignment ObjectForReviewAssignment
	 * @return boolean
	 */
	function _canBeRemoved($ofrAssignment) {
	 	return ($ofrAssignment->getStatus() == OFR_STATUS_ASSIGNED) || ($ofrAssignment->getStatus() == OFR_STATUS_MAILED) || ($ofrAssignment->getStatus() == OFR_STATUS_SUBMITTED);
	}

	/**
	 * Display email form for the editor
	 * @param $email MailTemplate
	 * @param $objectForReview ObjectForReview
	 * @param $user User
	 * @param $returnUrl string
	 * @param $action string
	 * @param $request PKPRequest
	 */
	function _displayEmailForm($email, $objectForReview, $user, $returnUrl, $action, $request) {
		if (!$request->getUserVar('continued')) {
			$userFullName = $user->getFullName();
			$userEmail = $user->getEmail();
			$userMailingAddress = $user->getMailingAddress();
			$userCountryCode = $user->getCountry();
			if (empty($userMailingAddress)) {
				$userMailingAddress = __('plugins.generic.objectsForReview.editor.noMailingAddress');
			} else {
				$countryDao =& DAORegistry::getDAO('CountryDAO');
				$countries =& $countryDao->getCountries();
				$userCountry = $countries[$userCountryCode];
				$userMailingAddress .= "\n" . $userCountry;
			}

			$editor =& $objectForReview->getEditor();
			$editorFullName = $editor->getFullName();
			$editorEmail = $editor->getEmail();
			$editorContactSignature = $editor->getContactSignature();

			if ($action == 'OFR_OBJECT_ASSIGNED') {
				$ofrPlugin =& $this->_getObjectsForReviewPlugin();
				$journal =& $request->getJournal();
				$dueWeeks = $ofrPlugin->getSetting($journal->getId(), 'dueWeeks');
				$dueDateTimestamp = time() + ($dueWeeks * 7 * 24 * 60 * 60);
				$paramArray = array(
					'authorName' => strip_tags($userFullName),
					'authorMailingAddress' => String::html2text($userMailingAddress),
					'objectForReviewTitle' => '"' . strip_tags($objectForReview->getTitle()) . '"',
					'objectForReviewDueDate' => date('l, F j, Y', $dueDateTimestamp),
					'submissionUrl' => $request->url(null, 'author', 'objectsForReview'),
					'editorialContactSignature' => String::html2text($editorContactSignature)
				);
			} elseif ($action == 'OFR_OBJECT_DENIED') {
				$paramArray = array(
					'authorName' => strip_tags($userFullName),
					'objectForReviewTitle' => '"' . strip_tags($objectForReview->getTitle()) . '"',
					'submissionUrl' => $request->url(null, 'author', 'submit'),
					'editorialContactSignature' => String::html2text($editorContactSignature)
				);
			} elseif ($action == 'OFR_OBJECT_MAILED') {
				$paramArray = array(
					'authorName' => strip_tags($userFullName),
					'authorMailingAddress' => String::html2text($userMailingAddress),
					'objectForReviewTitle' => '"' . strip_tags($objectForReview->getTitle()) . '"',
					'submissionUrl' => $request->url(null, 'author', 'submit'),
					'editorialContactSignature' => String::html2text($editorContactSignature)
				);
			} elseif ($action == 'OFR_REVIEWER_REMOVED') {
				$paramArray = array(
					'authorName' => strip_tags($userFullName),
					'objectForReviewTitle' => '"' . strip_tags($objectForReview->getTitle()) . '"',
					'editorialContactSignature' => String::html2text($editorContactSignature)
				);
			}
			$email->addRecipient($userEmail, $userFullName);
			$email->setFrom($editorEmail, $editorFullName);
			$email->assignParams($paramArray);
		}
		$email->displayEditForm($returnUrl);
	}

	/**
	 * Create trivial notification
	 * @param $notificationType int
	 * @param $request PKPRequest
	 */
	function _createTrivialNotification($notificationType, &$request) {
		$user =& $request->getUser();
		import('classes.notification.NotificationManager');
		$notificationManager = new NotificationManager();
		$notificationManager->createTrivialNotification($user->getId(), $notificationType);
	}

	/**
	 * Returns the actual tag name based on whether or not this is a short or long DTD version.
	 * @param string $tagName
	 * @param boolean $short
	 * @return boolean
	 */
	function _getOnixTag($tagName, $short = true) {

		if (!$short) {
			return $tagName;
		}

		$tags = array(
				'Product' => 'product',
				'OtherText' => 'othertext',
				'TextTypeCode' => 'd102',
				'Text' => 'd104',
				'NumberOfPages' => 'b061',
				'Publisher'=> 'publisher',
				'PublisherName' => 'b081',
				'Website' => 'website',
				'WebsiteLink' => 'b295',
				'Title' => 'title',
				'TitleText' => 'b203',
				'Subtitle' => 'b029',
				'Language' => 'language',
				'LanguageCode' => 'b252',
				'PublicationDate' => 'b003',
				'Contributor' => 'contributor',
				'NamesBeforeKey' => 'b039',
				'KeyNames' => 'b040',
				'SequenceNumber' => 'b034',
				'ContributorRole' => 'b035',
				'Series' => 'series',
				'TitleOfSeries' => 'b018',
				'ProductIdentifier' => 'productidentifier',
				'ProductIDType' => 'b221',
				'IDValue' => 'b244',
				'Subject' => 'subject',
				'SubjectHeadingText' => 'b070',
			);

		return $tags[$tagName];
	}
}

?>
