{**
 * templates/editor/enrolPublishers.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form for enroling a publisher
 *
 *
 *}
{strip}
{translate|assign:"pageTitleTranslated" key="plugins.generic.objectsForReview.enrolPublishers"}
{include file="common/header.tpl"}
{/strip}

<form id="enroll" action="{url op="objectsForReviewEnrol"}" method="post">
	<p>
	{translate key="plugins.generic.objectsForReview.editor.enrolInPublisher"} <select name="publisherId" size="1" class="selectMenu">
		<option value=""></option>
		{foreach from=$publishers item=publisher}
			<option value="{$publisher->getId()|escape}">{$publisher->getName()}</option>
		{/foreach}
	</select>
	</p>
<div id="users">
	<table width="100%" class="listing">
		<tr><td colspan="4" class="headseparator">&nbsp;</td></tr>
		<tr class="heading" valign="bottom">
			<td width="5%">&nbsp;</td>
			<td width="25%">{sort_heading key="user.username" sort="username"}</td>
			<td width="30%">{sort_heading key="user.name" sort="name"}</td>
			<td>{sort_heading key="user.email" sort="email"}</td>
		</tr>
		<tr><td colspan="4" class="headseparator">&nbsp;</td></tr>
		{iterate from=users item=user}
		{assign var="userid" value=$user->getId()}
		{assign var="assignments" value=$ofrEADao->getAllByUserId($user->getId())}
		{assign var="stats" value=$statistics[$userid]}
		<tr valign="top">
			<td><input type="checkbox" name="users[]" value="{$user->getId()}" /></td>
			<td><a class="action" href="{url op="userProfile" path=$userid}">{$user->getUsername()|escape}</a></td>
			<td>{$user->getFullName(true)|escape}</td>
			<td class="nowrap">
				{assign var=emailString value=$user->getFullName()|concat:" <":$user->getEmail():">"}
				{url|assign:"url" page="user" op="email" to=$emailString|to_array}
				{$user->getEmail()|truncate:20:"..."|escape}&nbsp;{icon name="mail" url=$url}
			</td>
		</tr>
		{if count($assignments) > 0}
		<tr>
			<td width="10%">&nbsp;</td>
			<td colspan="3">
				{foreach from=$assignments item=assignment name=a}
					<a href="{url op="objectsForReviewUnenrol" publisherId=$assignment->getPublisherId() userId=$user->getId()}" class="action">{translate key="plugins.generic.objectsForReview.editor.unenrol" publisher=$publisher->getName()}</a>
					{if !$smarty.foreach.a.last}, {/if}
				{/foreach}
			</td>
		</tr>
		{/if}
		<tr><td colspan="4" class="{if $users->eof()}end{/if}separator">&nbsp;</td></tr>
		{/iterate}
		{if $users->wasEmpty()}
		<tr>
			<td colspan="4" class="nodata">{translate key="common.none"}</td>
		</tr>
		<tr><td colspan="4" class="endseparator">&nbsp;</td></tr>
		{else}
		<tr>
			<td colspan="2" align="left">{page_info iterator=$users}</td>
			<td colspan="2" align="right">{page_links anchor="users" name="users" iterator=$users searchInitial=$searchInitial searchField=$searchField searchMatch=$searchMatch search=$search dateFromDay=$dateFromDay dateFromYear=$dateFromYear dateFromMonth=$dateFromMonth dateToDay=$dateToDay dateToYear=$dateToYear dateToMonth=$dateToMonth roleId=$roleId sort=$sort sortDirection=$sortDirection}</td>
		</tr>
	{/if}
	</table>
</div>
<input type="submit" value="{translate key="plugins.generic.objectsForReview.editor.enrolSelected"}" class="button defaultButton" /> <input type="button" value="{translate key="common.selectAll"}" class="button" onclick="toggleChecked()" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url page="editor"}'" />
</form>

{include file="common/footer.tpl"}
