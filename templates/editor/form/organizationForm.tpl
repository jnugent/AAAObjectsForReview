{**
 * plugins/generic/objectsForReview/templates/editor/organizationForm.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Books for Review organization form
 *
 *}
{strip}
{assign var="pageTitle" value="plugins.generic.objectsForReview.manager.organizationSettings"}
{include file="common/header.tpl"}
{/strip}
<div id="organization">

<div class="separator"></div>

<br />

<form method="post" action="{url op="objectsForReviewManagePublisher"}">
{if $organization}
  <input type="hidden" value="{$organization->getId()}" name="organizationId" />
{/if}
{include file="common/formErrors.tpl"}


<table width="100%" class="data">
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="name" key="plugins.generic.objectsForReview.manager.organization.name"}</td>
	<td width="80%" class="value"><input type="text" name="name" id="name" value="{$name|escape}" size="40" maxlength="90" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="street_address" key="common.mailingAddress"}</td>
	<td class="value"><input type="text" name="street_address" id="street_address" value="{$streetAddress|escape}" size="40" maxlength="90" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="city" key="plugins.generic.objectsForReview.manager.organization.city"}</td>
	<td class="value"><input type="text" name="city" id="city" value="{$city|escape}" size="40" maxlength="90" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="state" key="plugins.generic.objectsForReview.manager.organization.state"}</td>
	<td class="value"><input type="text" name="state" id="state" value="{$state|escape}" size="40" maxlength="90" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="country" key="common.country"}</td>
	<td class="value"><input type="text" name="country" id="country" value="{$country|escape}" size="40" maxlength="90" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="phone" key="user.phone"}</td>
	<td class="value"><input type="text" name="phone" id="phone" value="{$phone|escape}" size="40" maxlength="90" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="fax" key="user.fax"}</td>
	<td class="value"><input type="text" name="fax" id="fax" value="{$fax|escape}" size="40" maxlength="90" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="url" key="user.url"}</td>
	<td class="value"><input type="text" name="url" id="url" value="{$url|escape}" size="40" maxlength="90" class="textField" /></td>
</tr>
</table>

<br/>

<input type="submit" name="save" class="button defaultButton" value="{translate key="common.save"}"/><input type="button" class="button" value="{translate key="common.cancel"}" onclick="history.go(-1)"/>
</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
</div>
{include file="common/footer.tpl"}
