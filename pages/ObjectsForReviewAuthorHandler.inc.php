<?php

/**
 * @file plugins/generic/objectsForReview/pages/ObjectsForReviewAuthorHandler.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ObjectsForReviewAuthorHandler
 * @ingroup plugins_generic_objectsForReview
 *
 * @brief Handle requests for author object for review functions.
 */

import('classes.handler.Handler');

define('OFR_WS_RESPONSE_OK', 200);

class ObjectsForReviewAuthorHandler extends Handler {

	/**
	 * Display objects for review author listing page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function objectsForReview($args, &$request) {
		$journal =& $request->getJournal();
		$journalId = $journal->getId();

		$user =& $request->getUser();
		$userId = $user->getId();

		// Sort
		$sort = $request->getUserVar('sort');
		$sort = isset($sort) ? $sort : 'title';
		$sortDirection = $request->getUserVar('sortDirection');
		$sortDirection = isset($sortDirection) ? $sortDirection : SORT_DIRECTION_ASC;

		$ofrPlugin =& $this->_getObjectsForReviewPlugin();
		$mode = $ofrPlugin->getSetting($journalId, 'mode');

		$ofrPlugin->import('classes.ObjectForReviewAssignment');
		$path = !isset($args) || empty($args) ? null : $args[0];
		switch($path) {
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

		$rangeInfo = Handler::getRangeInfo($request, 'objectForReview');
		$ofrAssignmentDao =& DAORegistry::getDAO('ObjectForReviewAssignmentDAO');
		$objectForReviewAssignments =& $ofrAssignmentDao->getAllByContextId($journalId, null, null, null, $status, $userId, null, null, $rangeInfo, $sort, $sortDirection);

		$this->setupTemplate($request);
		$templateMgr =& TemplateManager::getManager($request);
		$templateMgr->assign('sort', $sort);
		$templateMgr->assign('sortDirection', $sortDirection);
		$templateMgr->assign('mode', $mode);
		$templateMgr->assign('returnPage', $path);
		$templateMgr->assign('pageTitle', $pageTitle);
		$templateMgr->assign('objectForReviewAssignments', $objectForReviewAssignments);
		$templateMgr->assign('counts', $ofrAssignmentDao->getStatusCounts($journalId, $userId));
		$templateMgr->display($ofrPlugin->getTemplatePath() . 'author' . '/' . 'objectsForReviewAssignments.tpl');
	}

	/**
	 * Author requests an object for review.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function requestObjectForReview($args, &$request) {
		$journal =& $request->getJournal();
		$journalId = $journal->getId();

		$objectId = !isset($args) || empty($args) ? null : (int) $args[0];
		if (!$this->_ensureObjectExists($objectId, $journalId)) {
			$request->redirect(null, 'objectsForReview');
		}
		$ofrDao =& DAORegistry::getDAO('ObjectForReviewDAO');
		$objectForReview =& $ofrDao->getById($objectId, $journalId);

		$redirect = true;
		if ($objectForReview->getAvailable()) {
			// Get the requesting user
			$user =& $request->getUser();
			$userId = $user->getId();
			// Ensure there is no assignment for this object and user
			$ofrAssignmentDao =& DAORegistry::getDAO('ObjectForReviewAssignmentDAO');
			if ($ofrAssignmentDao->assignmentExists($objectId, $userId)) {
				$request->redirect(null, 'objectsForReview');
			}

			import('classes.mail.MailTemplate');
			$email = new MailTemplate('OFR_OBJECT_REQUESTED');
			$send = $request->getUserVar('send');
			// Author has filled out mail form or decided to skip email
			if ($send && !$email->hasErrors()) {
				// Update object for review as requested
				$ofrAssignment = $ofrAssignmentDao->newDataObject();
				$ofrAssignment->setObjectId($objectId);
				$ofrAssignment->setUserId($userId);
				$ofrAssignment->setStatus(OFR_STATUS_REQUESTED);
				$ofrAssignment->setDateRequested(Core::getCurrentDate());
				$ofrAssignmentDao->insertObject($ofrAssignment);
				$email->send();
				$this->_createTrivialNotification(NOTIFICATION_TYPE_OFR_REQUESTED, $request);
			} else {
				$returnUrl = $request->url(null, 'author', 'requestObjectForReview', $objectId);
				$this->_displayEmailForm($email, $objectForReview, $user, $returnUrl, 'OFR_OBJECT_REQUESTED', $request);
				$redirect = false;
			}
		}
		if ($redirect) $request->redirect(null, 'objectsForReview');
	}

	/**
	 * Show form for author to agree to review an object.
	 * @param array $args
	 * @param PKPRequest $request
	 */
	function agreeToReviewObject($args, $request) {
		$objectId = (int) $args[0];
		$user = $request->getUser();
		$journal =& $request->getJournal();
		if ($objectId) {
			$ofrAssignmentDao =& DAORegistry::getDAO('ObjectForReviewAssignmentDAO');
			$objectForReviewAssignment = $ofrAssignmentDao->getByObjectAndUserId($objectId, $user->getId());
			if ($objectForReviewAssignment) {
				$ofrPlugin =& $this->_getObjectsForReviewPlugin();
				$ofrPlugin->import('classes.form.ObjectForReviewReviewAgreementForm');
				$ofrAgreementForm = new ObjectForReviewReviewAgreementForm($ofrPlugin->getName(), $objectForReviewAssignment->getId(), $objectId);
				$ofrAgreementForm->readInputData();
				if ($ofrAgreementForm->validate()) {
					$ofrAgreementForm->execute();
					$request->redirect(null, 'author', 'objectsForReview');
				} else {
					$this->setupTemplate($request, true);
					$templateMgr =& TemplateManager::getManager($request);
					$templateMgr->assign('pageTitle', 'plugins.generic.objectsForReview.author.agreeToReview');
					$mode = $ofrPlugin->getSetting($journal->getId(), 'mode');
					$templateMgr->assign('mode', $mode);
					$ofrAgreementForm->display($request);
				}
			} else {
				$request->redirect(null, 'objectsForReview');
			}
		}
	}

	/**
	 * Store decline by author to review an object.
	 * @param array $args
	 * @param PKPRequest $request
	 */
	function declineToReviewObject($args, $request) {
		$journal =& $request->getJournal();
		$journalId = $journal->getId();
		$user =& $request->getUser();

		$objectId = !isset($args) || empty($args) ? null : (int) $args[0];
		if (!$this->_ensureObjectExists($objectId, $journalId)) {
			$request->redirect(null, 'objectsForReview');
		}
		$ofrDao =& DAORegistry::getDAO('ObjectForReviewDAO');
		$objectForReview =& $ofrDao->getById($objectId);
		$ofrAssignmentDao =& DAORegistry::getDAO('ObjectForReviewAssignmentDAO');
		$assignment =& $ofrAssignmentDao->getByObjectAndUserId($objectId, $user->getId());

		$redirect = true;
		if ($assignment) {
			import('classes.mail.MailTemplate');
			$email = new MailTemplate('OFR_OBJECT_DECLINED');
			$send = $request->getUserVar('send');
			// Author has filled out mail form or decided to skip email
			if ($send && !$email->hasErrors()) {
				// Update object for review as requested
				$assignment->setStatus(OFR_STATUS_DECLINED);
				$ofrAssignmentDao->updateObject($assignment);
				$email->send();
				$this->_createTrivialNotification(NOTIFICATION_TYPE_OFR_DECLINED, $request);
			} else {
				$returnUrl = $request->url(null, 'author', 'declineToReviewObject', $objectId);
				$this->_displayEmailForm($email, $objectForReview, $user, $returnUrl, 'OFR_OBJECT_DECLINED', $request);
				$redirect = false;
			}
		}
		if ($redirect) $request->redirect(null, 'author', 'objectsForReview');
	}

	/**
	 * Connector from AnthroNet.  Receives a token in the 'token' query string argument
	 * that is used to fetch author information via SOAP, create/or validate the author login,
	 * and redirect to author home page.
	 * @param array $args
	 * @param PKPRequest $request
	 */
	function objectsForReviewLogin($args, $request) {

		$token = $request->getUserVar('token');
		if ($token) {
			error_log($token);
		}
	}

	/**
	 * Ensure that we have a journal, plugin is enabled, and user is author.
	 * @see PKPHandler::authorize()
	 */
	function authorize(&$request, &$args, $roleAssignments) {
		$journal =& $request->getJournal();
		if (!isset($journal)) return false;

		$ofrPlugin =& $this->_getObjectsForReviewPlugin();

		if (!isset($ofrPlugin)) return false;

		if (!$ofrPlugin->getEnabled()) return false;

		if (!Validation::isAuthor($journal->getId())) Validation::redirectLogin();

		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * Setup common template variables.
	 * @param $request PKPRequest
	 * @param $subclass boolean (optional) set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($request, $subclass = false) {
		$templateMgr =& TemplateManager::getManager($request);
		$pageCrumbs = array(
			array(
				$request->url(null, 'user'),
				'navigation.user'
			),
			array(
				$request->url(null, 'author'),
				'user.role.author'
			)
		);
		$templateMgr->assign('pageHierarchy', $pageCrumbs);
		$ofrPlugin =& $this->_getObjectsForReviewPlugin();
		$templateMgr->addStyleSheet($request->getBaseUrl() . '/' . $ofrPlugin->getStyleSheet());
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

	/** Ensure object for review exists
	 * @param $objectId int
	 * @param $journalId int
	 * @return boolean
	 */
	function _ensureObjectExists($objectId, $journalId) {
		if ($objectId == null) {
			return false;
		}
		$ofrDao =& DAORegistry::getDAO('ObjectForReviewDAO');
		if (!$ofrDao->objectForReviewExists($objectId, $journalId)) {
			return false;
		}
		return true;
	}

	/**
	 * Display email form for the author
	 * @param $email MailTemplate
	 * @param $objectForReview ObjectForReview
	 * @param $user User
	 * @param $returnUrl string
	 * @param $action string
	 * @param $request PKPRequest
	 */
	function _displayEmailForm($email, $objectForReview, $user, $returnUrl, $action, $request) {
		if (!$request->getUserVar('continued')) {
			$editor =& $objectForReview->getEditor();
			$editorFullName = $editor->getFullName();
			$editorEmail = $editor->getEmail();

			if ($action = 'OFR_OBJECT_REQUESTED') {
				$paramArray = array(
					'editorName' => strip_tags($editorFullName),
					'objectForReviewTitle' => '"' . strip_tags($objectForReview->getTitle()) . '"',
					'authorContactSignature' => String::html2text($user->getContactSignature())
				);
			}
			$email->addRecipient($editorEmail, $editorFullName);
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
	 * Do the actual web SOAP service request.
	 * @param $action string
	 * @param $arg string
	 * @param $authToken string
	 * @param $token string
	 * @return boolean|string True for success, an error message otherwise.
	 */
	function _doRequest($action, $arg, $authToken, $token) {
		// Build the multipart SOAP message from scratch.
		$soapMessage =
		'<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns="http://www.avectra.com/2005/">
			<soapenv:Header>
				<ns:AuthorizationToken>
					<!--Optional:-->
					<ns:Token>' . $authToken . '</ns:Token>
			</ns:AuthorizationToken>
		</soapenv:Header>
		<soapenv:Body>
			<ns:BNEGetIndividualInformation>
				<!--Optional:-->
				<ns:SSOToken>' . $token . '</ns:SSOToken>
			</ns:BNEGetIndividualInformation>
		</soapenv:Body>
	</soapenv:Envelope>';

		// Prepare HTTP session.
		$curlCh = curl_init ();
		curl_setopt($curlCh, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curlCh, CURLOPT_POST, true);

		// Set up basic authentication.
		curl_setopt($curlCh, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($curlCh, CURLOPT_USERPWD, $this->_auth);

		// Set up SSL.
		curl_setopt($curlCh, CURLOPT_SSLVERSION, 3);
		curl_setopt($curlCh, CURLOPT_SSL_VERIFYPEER, false);

		// Make SOAP request.
		curl_setopt($curlCh, CURLOPT_URL, $this->_endpoint);
		$extraHeaders = array(
				'SOAPAction: "BNEGetIndividualInformation"',
				'UserAgent: OJS-OFR'
		);
		curl_setopt($curlCh, CURLOPT_HTTPHEADER, $extraHeaders);
		curl_setopt($curlCh, CURLOPT_POSTFIELDS, $request);

		$result = true;
		$response = curl_exec($curlCh);

		// We do not localize our error messages as they are all
		// fatal errors anyway and must be analyzed by technical staff.
		if ($response === false) {
			$result = 'OJS-OFR: Expected string response.';
		}

		if ($result === true && ($status = curl_getinfo($curlCh, CURLINFO_HTTP_CODE)) != OFR_WS_RESPONSE_OK) {
			$result = 'OJS-OFR: Expected ' . OFR_WS_RESPONSE_OK . ' response code, got ' . $status . ' instead.';
		}

		curl_close($curlCh);

		// Check SOAP response by simple string manipulation rather
		// than instantiating a DOM.
		if (is_string($response)) {
			$matches = array();
			String::regexp_match_get('#<faultstring>([^<]*)</faultstring>#', $response, $matches);
			if (empty($matches)) {
				if ($attachment) {
					assert(String::regexp_match('#<returnCode>success</returnCode>#', $response));
				} else {
					$parts = explode("\r\n\r\n", $response);
					$result = array_pop($parts);
					$result = String::regexp_replace('/>[^>]*$/', '>', $result);
				}
			} else {
				$result = 'OFR: ' . $status . ' - ' . $matches[1];
			}
		} else {
			$result = 'OJS-OFR: Expected string response.';
		}

		return $result;
	}
}

?>
