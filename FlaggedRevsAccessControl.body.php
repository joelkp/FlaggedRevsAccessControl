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

# Whitelist stable pages and pages outside reviewed namespaces
$wgHooks['TitleReadWhitelist'][] = 'fracfHooks_whitelistTitles';

/**
 * Check whether or not the given title is subject to access control.
 *
 * @return bool Whether the given title is subject to access control
 */
function fracf_isUnrestrictedTitle( Title $title ) {
	# Whitelist page if not subject to review
	return !FlaggedRevs::inReviewNamespace( $title );
}

/**
 * Get the action for this request.
 *
 * @return string The requested action
 */
function fracf_getRequestAction( WebRequest $request ) {
	global $mediaWiki;
	$action = isset( $mediaWiki ) ?
		$mediaWiki->getAction( $request ) :
		$request->getVal( 'action', 'view' ); // cli
	return $action;
}

/**
 * Get the "oldid" revision for this request, or null if none. This also
 * means taking the "direction" parameter into account. Note that this
 * does not check the action type for the request.
 *
 * @return int|null A non-zero "oldid" revision ID, or null if none
 */
function fracf_getRequestOldId( WebRequest $request, Title $title ) {
	$oldId = $request->getIntOrNull( 'oldid' );
	if ( $oldId === null ) {
		return null;
	}
	if ( $oldId == 0 ) {
		$oldId = $title->getLatestRevID();
		if ( $oldId == 0 ) {
			return null;
		}
	}
	switch ( $request->getVal( 'direction' ) ) {
		case 'next':
			# select next revision if there is one
			$nextId = $title->getNextRevisionID( $oldId );
			if ( $nextId ) {
				$oldId = $nextId;
			}
			break;
		case 'prev':
			# select previous revision if there is one
			$prevId = $title->getPreviousRevisionID( $oldId );
			if ( $prevId ) {
				$oldId = $prevId;
			}
			break;
	}

	return $oldId;
}

/**
 * Get the "diff" revision for this request, or null if none. Note that
 * this does not check the action type for the request.
 *
 * Optionally, an $refId variable can be passed. Passing it has two uses:
 * - If non-null, it is used as the "oldid" revision ID in relation to
 *   which the "diff" revision ID is determined.
 * - If null and a "diff" value exists, the variable will be set to
 *   whichever revision ID the "diff" revision is relative to.
 * .
 * If a variable holding the return value of fracf_getRequestOldId() is
 * used, then afterwards, it can be used to check for "oldid" and/or
 * "diff". If non-null, one or both were given in the request. If the
 * return value of this function is also non-null, the request was for
 * a diff and the two values are the two revision IDs.
 *
 * @param numeric|null $refId A variable which either holds an "oldid" to
 *                            use, or if null, will be used to store
 *                            whichever other revision ID to diff with
 * @return int|null A non-zero "diff" revision ID, or null if none
 */
function fracf_getRequestDiffId( WebRequest $request, Title $title, &$refId = null ) {
	$diffId = $request->getVal( 'diff' );
	if ( $diffId === null ) {
		return null;
	}
	if ( $refId === null ) {
		$oldId = fracf_getRequestOldId( $request, $title );
	} else {
		$oldId = intval( $refId );
		if ( $oldId === 0 ) {
			$oldId = $title->getLatestRevID();
		}
	}
	switch ( $diffId ) {
		case 'next':
			if ( $oldId === null ) {
				# MW selects the first revision
				$firstRev = $title->getFirstRevision();
				if ( $firstRev === null ) {
					$diffId = null;
				} else {
					$oldId = $firstRev->getId();
					$diffId = $oldId;
				}
			} else {
				# select next revision if there is one
				$nextId = $title->getNextRevisionID( $oldId );
				$diffId = $nextId
					? $nextId
					: $oldId;
			}
			break;
		case 'prev':
			if ( $oldId === null ) {
				# MW selects the latest revision
				$latestId = $title->getLatestRevID();
				if ( $latestId === 0 ) {
					$diffId = null;
				} else {
					$oldId = $latestId;
					$diffId = $oldId;
				}
			} else {
				# select previous revision if there is one
				$prevId = $title->getPreviousRevisionID( $oldId );
				$diffId = $prevId
					? $prevId
					: $oldId;
			}
			break;
		default:
			$diffId = intval( $diffId );
			if ( $diffId === 0 ) {
				$diffId = $title->getLatestRevID();
			}
			if ( $diffId === 0 ) {
				$diffId = null;
			} elseif ( $oldId === null ) {
				# select previous revision if there is one
				$prevId = $title->getPreviousRevisionID( $diffId );
				$oldId = $prevId
					? $prevId
					: $diffId;
			}
			break;
	}

	if ( $diffId !== null ) {
		$refId = $oldId;
		return $diffId;
	}
	return null;
}

/**
 * Ensure the stable version of a page is shown by default. When a stable
 * version exists, it also ensures that FlaggablePageView::showingStable()
 * returns true.
 *
 * This a hack, used because FlaggablePageView::showingStableAsDefault()
 * always obeys user preferences and the $wgFlaggedRevsExceptions array.
 * Either of these can make for requesting the latest version of a page by
 * default - and then that method returns false, even when the latest
 * version is also a stable version!
 *
 * To change the behavior of FlaggablePageView::showingStable(), the
 * simplest approach is used: fake the WebRequest data so that by
 * default, the stable version is always requested. Then if a stable
 * version exists, FlaggablePageView::showingStableByRequest() will
 * return true.
 *
 * This accomplishes the aim of overriding user preferences and
 * configuration. This is essential for usability when permissions do not
 * allow viewing draft versions of pages.
 */
function fracf_forceViewStableByDefault( WebRequest $request ) {
	if ( $request->getIntOrNull( 'stable' ) !== null ) {
		return;
	}
	$request->setVal( 'stable', 1 );
}

/**
 * Whitelist:
 * - Any pages not subject to access control.
 * - Stable versions of pages, according to settings.
 *
 * @return true Always true, to allow other extensions to perform checks
 */
function fracfHooks_whitelistTitles( Title $title, $user, &$result ) {
	global $wgTitle;
	# Bypass everything if title is unrestricted.
	if ( fracf_isUnrestrictedTitle( $title ) ) {
		$result = true;
		return true;
	}
	# No whitelisting if user lacks the 'readstable' right.
	if ( !$user->isAllowed( 'readstable' ) ) {
		return true;
	}
	# See if there is a stable version. Also, see if, given the page
	# config and URL params, the page can be overriden. The later
	# only applies on page views of $title.
	if ( !empty( $wgTitle ) && $wgTitle->equals( $title ) ) {
		$view = FlaggablePageView::singleton();
		$request = $view->getRequest();
		$requestAction = fracf_getRequestAction( $request );
		switch ( $requestAction ) {
		// Handle view actions
		case 'view':
		case 'purge':
		case 'render':
			$oldId = fracf_getRequestOldId( $request, $title );
			$diffId = fracf_getRequestDiffId( $request, $title, $oldId );
			// Handle page view for specific revision(s)
			if ( $oldId !== null ) {
				if ( $diffId !== null ) {
					$frev = FlaggedRevision::newFromTitle( $title, $diffId );
					if ( $frev === null ) {
						return true;
					}
				}
				$frev = FlaggedRevision::newFromTitle( $title, $oldId );
				if ( $frev === null ) {
					return true;
				}
				// All revisions part of the request are stable
				$result = true;
				return true;
			}
			// Handle "normal" or "stable" page view, caching
			// stable version while we are at it.
			fracf_forceViewStableByDefault( $request );
			if ( $view->showingStable() ) {
				$result = true;
				return true;
			}
			break;
		// Handle edit actions
		case 'edit':
			$oldId = fracf_getRequestOldId( $request, $title );
			// Handle page view for specific revision
			if ( $oldId !== null ) {
				$frev = FlaggedRevision::newFromTitle( $title, $oldId );
			} else {
				$frev = FlaggedRevision::newFromStable( $title );
				if ( $frev !== null ) {
					# Override revision for "View source"
					$GLOBALS['fracgViewSourceRevision'] = $frev;
				}
			}
			if ( $frev !== null ) {
				$result = true;
				return true;
			}
			break;
		// Handle history action
		case 'history':
			if ( $GLOBALS['fracgAllowViewHistory'] === true ) {
				$result = true;
				return true;
			}
			break;
		}
	} else {
		// Search and such need to know that the reader can view this
		// page
		if ( FlaggedRevision::newFromStable( $title ) ) {
			$result = true;
		}
	}
	return true;
}

$wgHooks['AlternateEdit'][] = 'fracfHooks_overrideEditPageVersion';

/**
 * Override the version of a page shown in the Edit/View source interface
 * when $fracgViewSourceRevision is set.
 *
 * This is used together with the fracfHooks_whitelistTitles() hook.
 * Users who cannot edit are limited to viewing the source of stable
 * revisions of pages. When no "oldid" is included in an 'edit'
 * web request, and a stable revision exists, then this is used to
 * replace the "View source" contents with the last stable version.
 */
function fracfHooks_overrideEditPageVersion( $editPage ) {
	if ( !isset( $GLOBALS['fracgViewSourceRevision'] ) ) {
		return true;
	}
	# Override revision to view source of
	$frev = $GLOBALS['fracgViewSourceRevision'];
	$title = $frev->getTitle();
	$editPage->mTitle = $title;
	$editPage->mArticle = new Article( $title, $frev->getRevID() );
	return true;
}

