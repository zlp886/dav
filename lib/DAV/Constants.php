<?php

/* -AFTERLOGIC LICENSE HEADER- */

namespace Afterlogic\DAV;

class Constants
{
	const T_ACCOUNTS = 'awm_accounts';
	const T_PRINCIPALS = 'adav_principals';
	const T_GROUPMEMBERS = 'adav_groupmembers';

	const T_CALENDARS = 'adav_calendars';
	const T_CALENDARCHANGES = 'adav_calendarchanges';
	const T_CALENDAROBJECTS = 'adav_calendarobjects';
	const T_SCHEDULINGOBJECTS = 'adav_schedulingobjects';
	const T_CALENDARSUBSCRIPTIONS = 'adav_calendarsubscriptions';
	const T_CALENDARSHARES = 'adav_calendarshares';
	const T_REMINDERS = 'adav_reminders';

	const T_ADDRESSBOOKS = 'adav_addressbooks';
	const T_ADDRESSBOOKCHANGES = 'adav_addressbookchanges';
	const T_CARDS = 'adav_cards';

	const T_LOCKS = 'adav_locks';
	const T_CACHE = 'adav_cache';
	
	const GLOBAL_CONTACTS = 'Global Contacts';
	const CALENDAR_DEFAULT_NAME = 'My calendar';
	const CALENDAR_DEFAULT_COLOR = '#f09650';

	const TASKS_DEFAULT_NAME = 'My tasks';
	const TASKS_DEFAULT_COLOR = '#f68987';

	const ADDRESSBOOK_DEFAULT_NAME = 'AddressBook';
	const ADDRESSBOOK_DEFAULT_NAME_OLD = 'Default';
	const ADDRESSBOOK_DEFAULT_DISPLAY_NAME = 'Address Book';
	const ADDRESSBOOK_DEFAULT_DISPLAY_NAME_OLD = 'General';
	const ADDRESSBOOK_COLLECTED_NAME = 'Collected';
	const ADDRESSBOOK_COLLECTED_DISPLAY_NAME = 'Collected Addresses';
	const ADDRESSBOOK_SHARED_WITH_ALL_NAME = 'SharedWithAll';
	const ADDRESSBOOK_SHARED_WITH_ALL_DISPLAY_NAME = 'Shared With All';
	
	const DAV_PUBLIC_PRINCIPAL = 'caldav_public_user@localhost';
	const DAV_TENANT_PRINCIPAL = 'dav_tenant_user@localhost';
	
	const DAV_USER_AGENT = 'AfterlogicDAVClient';
	const DAV_SERVER_NAME = 'AfterlogicDAVServer';
	
	const FILESTORAGE_PRIVATE_QUOTA = 104857600;
	const FILESTORAGE_CORPORATE_QUOTA = 1048576000;
	
	const FILESTORAGE_PATH_ROOT = '/files';
	const FILESTORAGE_PATH_PERSONAL = '/private';
	const FILESTORAGE_PATH_CORPORATE = '/corporate';
	const FILESTORAGE_PATH_SHARED = '/shared';
	
	const PRINCIPALS_PREFIX = 'principals';
}
