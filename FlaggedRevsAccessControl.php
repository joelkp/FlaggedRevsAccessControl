<?php
/*
 Copyright (c) 2014 Joel K. Pettersson <joelkpettersson@gmail.com>

 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License along
 with this program; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 http://www.gnu.org/copyleft/gpl.html
*/

if ( !defined( 'MEDIAWIKI' ) ) {
	echo "FlaggedRevs Access Control extension\n";
	exit( 1 );
}
if ( !defined( 'FLAGGED_REVISIONS' ) ) {
	echo "The FlaggedRevs Access Control extension requires the FlaggedRevs extension\n";
	exit( 1 );
}

# Stable constant to let extensions be aware that this is enabled
define( 'FLAGGEDREVS_ACCESS_CONTROL', true );

$wgExtensionCredits['specialpage'][] = array(
	'path'           => __FILE__,
	'name'           => 'Flagged Revisions Access Control',
	'author'         => array( 'Joel K. Pettersson' ),
	'url'            => 'https://github.com/joelkp/FlaggedRevsAccessControl',
	'descriptionmsg' => 'flaggedrevsaccesscontrol-desc',
	'license-name'   => 'GPLv2+',
);

$wgMessagesDirs['FlaggedRevsAccessControl'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['FlaggedRevsAccessControl'] = __DIR__ . '/FlaggedRevsAccessControl.i18n.php';

# Define user rights
$wgAvailableRights[] = 'readstable'; # read stable pages (checked when 'read' missing)

# Groups granted 'readstable' by default
$wgGroupPermissions['*']['readstable'] = true; # permissive default setting
$wgGroupPermissions['autoreview']['readstable'] = true;
$wgGroupPermissions['editor']['readstable'] = true;
$wgGroupPermissions['reviewer']['readstable'] = true;
$wgGroupPermissions['sysop']['readstable'] = true;

/**
 * Determines whether or not history can be viewed for pages in namespaces
 * with access control.
 *
 * The default is true.
 * @var bool $fracgAllowViewHistory
 */
$fracgAllowViewHistory = true;

require __DIR__ . '/FlaggedRevsAccessControl.body.php';

