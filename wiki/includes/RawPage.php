<?php
/**
 * Raw page text accessor
 *
 * Copyright © 2004 Gabriel Wicke <wicke@wikidev.net>
 * http://wikidev.net/
 *
 * Based on HistoryPage and SpecialExport
 *
 * License: GPL (http://www.gnu.org/copyleft/gpl.html)
 *
 * @author Gabriel Wicke <wicke@wikidev.net>
 * @file
 */

/**
 * A simple method to retrieve the plain source of an article,
 * using "action=raw" in the GET request string.
 */
class RawPage {
	var $mArticle, $mTitle, $mRequest;
	var $mOldId, $mGen, $mCharset, $mSection;
	var $mSmaxage, $mMaxage;
	var $mContentType, $mExpandTemplates;

	function __construct( Page $article, $request = false ) {
		global $wgRequest, $wgSquidMaxage, $wgJsMimeType, $wgGroupPermissions;

		$allowedCTypes = array( 'text/x-wiki', $wgJsMimeType, 'text/css', 'application/x-zope-edit' );
		$this->mArticle = $article;
		$this->mTitle = $article->getTitle();

		if( $request === false ) {
			$this->mRequest = $wgRequest;
		} else {
			$this->mRequest = $request;
		}

		$ctype = $this->mRequest->getVal( 'ctype' );
		$smaxage = $this->mRequest->getIntOrNull( 'smaxage' );
		$maxage = $this->mRequest->getInt( 'maxage', $wgSquidMaxage );

		$this->mExpandTemplates = $this->mRequest->getVal( 'templates' ) === 'expand';
		$this->mUseMessageCache = $this->mRequest->getBool( 'usemsgcache' );

		$this->mSection = $this->mRequest->getIntOrNull( 'section' );

		$oldid = $this->mRequest->getInt( 'oldid' );

		switch( $wgRequest->getText( 'direction' ) ) {
			case 'next':
				# output next revision, or nothing if there isn't one
				if( $oldid ) {
					$oldid = $this->mTitle->getNextRevisionId( $oldid );
				}
				$oldid = $oldid ? $oldid : -1;
				break;
			case 'prev':
				# output previous revision, or nothing if there isn't one
				if( !$oldid ) {
					# get the current revision so we can get the penultimate one
					$this->mArticle->getTouched();
					$oldid = $this->mArticle->getLatest();
				}
				$prev = $this->mTitle->getPreviousRevisionId( $oldid );
				$oldid = $prev ? $prev : -1 ;
				break;
			case 'cur':
				$oldid = 0;
				break;
		}
		$this->mOldId = $oldid;

		# special case for 'generated' raw things: user css/js
		$gen = $this->mRequest->getVal( 'gen' );

		if( $gen == 'css' ) {
			$this->mGen = $gen;
			if( is_null( $smaxage ) ) {
				$smaxage = $wgSquidMaxage;
			}
			if( $ctype == '' ) {
				$ctype = 'text/css';
			}
		} elseif( $gen == 'js' ) {
			$this->mGen = $gen;
			if( is_null( $smaxage ) ) $smaxage = $wgSquidMaxage;
			if($ctype == '') $ctype = $wgJsMimeType;
		} else {
			$this->mGen = false;
		}
		$this->mCharset = 'UTF-8';

		# Force caching for CSS and JS raw content, default: 5 minutes
		if( is_null( $smaxage ) && ( $ctype == 'text/css' || $ctype == $wgJsMimeType ) ) {
			global $wgForcedRawSMaxage;
			$this->mSmaxage = intval( $wgForcedRawSMaxage );
		} else {
			$this->mSmaxage = intval( $smaxage );
		}
		$this->mMaxage = $maxage;

		# Output may contain user-specific data;
		# vary generated content for open sessions and private wikis
		if( $this->mGen || !$wgGroupPermissions['*']['read'] ) {
			$this->mPrivateCache = $this->mSmaxage == 0 || session_id() != '';
		} else {
			$this->mPrivateCache = false;
		}

		if( $ctype == '' || !in_array( $ctype, $allowedCTypes ) ) {
			$this->mContentType = 'text/x-wiki';
		} else {
			$this->mContentType = $ctype;
		}
	}

	function view() {
		global $wgOut, $wgRequest;

		if( !$wgRequest->checkUrlExtension() ) {
			$wgOut->disable();
			return;
		}

		header( 'Content-type: ' . $this->mContentType . '; charset=' . $this->mCharset );
		# allow the client to cache this for 24 hours
		$mode = $this->mPrivateCache ? 'private' : 'public';
		header( 'Cache-Control: ' . $mode . ', s-maxage=' . $this->mSmaxage . ', max-age=' . $this->mMaxage );

		global $wgUseFileCache;
		if( $wgUseFileCache && HTMLFileCache::useFileCache() ) {
			$cache = new HTMLFileCache( $this->mTitle, 'raw' );
			if( $cache->isFileCacheGood( /* Assume up to date */ ) ) {
				$cache->loadFromFileCache();
				$wgOut->disable();
				return;
			} else {
				ob_start( array( &$cache, 'saveToFileCache' ) );
			}
		}

		$text = $this->getRawText();

		if( !wfRunHooks( 'RawPageViewBeforeOutput', array( &$this, &$text ) ) ) {
			wfDebug( __METHOD__ . ": RawPageViewBeforeOutput hook broke raw page output.\n" );
		}

		echo $text;
		$wgOut->disable();
	}

	function getRawText() {
		global $wgOut, $wgUser;
		if( $this->mGen ) {
			$sk = $wgUser->getSkin();
			if( !StubObject::isRealObject( $wgOut ) ) {
				$wgOut->_unstub( 2 );
			}
			$sk->initPage( $wgOut );
			if( $this->mGen == 'css' ) {
				return $sk->generateUserStylesheet();
			} elseif( $this->mGen == 'js' ) {
				return $sk->generateUserJs();
			}
		} else {
			return $this->getArticleText();
		}
	}

	function getArticleText() {
		$found = false;
		$text = '';
		if( $this->mTitle ) {
			// If it's a MediaWiki message we can just hit the message cache
			if( $this->mUseMessageCache && $this->mTitle->getNamespace() == NS_MEDIAWIKI ) {
				$key = $this->mTitle->getDBkey();
				$msg = wfMessage( $key )->inContentLanguage();
				# If the message doesn't exist, return a blank
				$text = !$msg->exists() ? '' : $msg->plain();
				$found = true;
			} else {
				// Get it from the DB
				$rev = Revision::newFromTitle( $this->mTitle, $this->mOldId );
				if( $rev ) {
					$lastmod = wfTimestamp( TS_RFC2822, $rev->getTimestamp() );
					header( "Last-modified: $lastmod" );

					if( !is_null( $this->mSection ) ) {
						global $wgParser;
						$text = $wgParser->getSection( $rev->getText(), $this->mSection );
					} else {
						$text = $rev->getText();
					}
					$found = true;
				}
			}
		}

		# Bad title or page does not exist
		if( !$found && $this->mContentType == 'text/x-wiki' ) {
			# Don't return a 404 response for CSS or JavaScript;
			# 404s aren't generally cached and it would create
			# extra hits when user CSS/JS are on and the user doesn't
			# have the pages.
			header( 'HTTP/1.0 404 Not Found' );
		}

		return $this->parseArticleText( $text );
	}

	/**
	 * @param $text
	 * @return string
	 */
	function parseArticleText( $text ) {
		if( $text === '' ) {
			return '';
		} else {
			if( $this->mExpandTemplates ) {
				global $wgParser;
				return $wgParser->preprocess( $text, $this->mTitle, new ParserOptions() );
			} else {
				return $text;
			}
		}
	}
}
