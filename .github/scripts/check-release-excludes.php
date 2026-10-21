<?php
/**
 * CLAUDE.md Golden Rule 6 — shipped code must never reference a file the
 * release zip excludes.
 *
 * Usage: php .github/scripts/check-release-excludes.php [repo-root]
 *
 * 1. Stages the plugin exactly the way .github/workflows/release.yml does
 *    (same rsync exclude block, same CHANGELOG/README re-add), so the exclude
 *    list is read from release.yml rather than copied here.
 * 2. Resolves every plugin-relative path expression in shipped PHP —
 *    include/require targets, plugin_dir_path( __FILE__ ) . '…',
 *    plugins_url( '…', __FILE__ ), __DIR__ . '…', dirname( … ) . '…',
 *    constants/variables assigned from those — and fails if the target is
 *    missing from the staged zip.
 * 3. Fails if shipped PHP/JS/CSS names an excluded file anywhere outside a
 *    comment (catches links and dynamic includes that step 2 can't resolve).
 *
 * Exits 1 on any violation and prints GitHub Actions ::error annotations.
 * Dev-only scripts are fine; they just can't be wired into shipped code.
 */

$root = rtrim( realpath( $argv[1] ?? getcwd() ), '/' );
if ( ! is_file( "$root/.github/workflows/release.yml" ) ) {
	fwrite( STDERR, "No .github/workflows/release.yml under $root\n" );
	exit( 2 );
}

$errors   = 0;
$warnings = 0;

function annotate( $level, $file, $line, $msg ) {
	global $errors, $warnings;
	$level === 'error' ? $errors++ : $warnings++;
	$loc = $file === null ? '' : " file=$file" . ( $line ? ",line=$line" : '' );
	echo "::$level$loc::$msg\n";
}

// ---------------------------------------------------------------------------
// 1. Stage the release tree from release.yml's own instructions.
// ---------------------------------------------------------------------------

$yml   = file( "$root/.github/workflows/release.yml", FILE_IGNORE_NEW_LINES );
$rules = null;
$readd = null;
foreach ( $yml as $i => $line ) {
	if ( $rules === null && strpos( $line, '--exclude-from=-' ) !== false && strpos( $line, "<<'EOF'" ) !== false ) {
		$rules = array();
		for ( $j = $i + 1; $j < count( $yml ) && trim( $yml[ $j ] ) !== 'EOF'; $j++ ) {
			if ( trim( $yml[ $j ] ) !== '' ) {
				$rules[] = trim( $yml[ $j ] );
			}
		}
	}
	if ( $readd === null && preg_match( '/^\s*cp\s+(.+?)\s+"\$STAGE\/"/', $line, $m ) ) {
		$readd = preg_split( '/\s+/', trim( $m[1] ) );
	}
}
if ( empty( $rules ) || $readd === null ) {
	annotate( 'error', '.github/workflows/release.yml', 0, "Could not find the rsync --exclude-from heredoc and the 'cp … \"\$STAGE/\"' re-add line. release.yml changed shape — update .github/scripts/check-release-excludes.php to match." );
	exit( 1 );
}

$stage = sys_get_temp_dir() . '/tv-rule6-' . getmypid() . '/training-videos';
@mkdir( $stage, 0777, true );
$exclude_file = dirname( $stage ) . '/exclude.txt';
file_put_contents( $exclude_file, implode( "\n", $rules ) . "\n" );
exec( 'rsync -a --exclude-from=' . escapeshellarg( $exclude_file ) . ' ' . escapeshellarg( "$root/" ) . ' ' . escapeshellarg( "$stage/" ) . ' 2>&1', $out, $rc );
if ( $rc !== 0 ) {
	fwrite( STDERR, "rsync failed:\n" . implode( "\n", $out ) . "\n" );
	exit( 2 );
}
foreach ( $readd as $f ) {
	if ( is_file( "$root/$f" ) ) {
		copy( "$root/$f", "$stage/$f" );
	}
}

function tree( $dir ) {
	$files = array();
	$dirs  = array( '' => true );
	$it    = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::SELF_FIRST );
	foreach ( $it as $p ) {
		$rel = substr( $p->getPathname(), strlen( $dir ) + 1 );
		if ( $rel === '.git' || strpos( $rel, '.git/' ) === 0 ) {
			continue;
		}
		if ( $p->isDir() ) {
			$dirs[ $rel ] = true;
		} else {
			$files[ $rel ] = true;
		}
	}
	return array( $files, $dirs );
}

list( $shipped, $shipped_dirs ) = tree( $stage );
list( $repo_files, )            = tree( $root );
$excluded                       = array_diff_key( $repo_files, $shipped );

if ( ! isset( $shipped['training-videos.php'] ) ) {
	annotate( 'error', '.github/workflows/release.yml', 0, 'The staged release does not contain training-videos.php — the exclude list strips the plugin itself.' );
}

echo 'Exclude rules from release.yml: ' . implode( ' ', $rules ) . "\n";
echo 'Re-added after rsync: ' . implode( ' ', $readd ) . "\n";
echo count( $shipped ) . ' files ship, ' . count( $excluded ) . " repo files are stripped from the zip.\n";

// Shipped first-party code: everything in the zip except bundled vendor libs.
$scan = array();
foreach ( array_keys( $shipped ) as $rel ) {
	if ( strpos( $rel, 'vendor/' ) !== 0 && preg_match( '/\.(php|js|css)$/', $rel ) ) {
		$scan[] = $rel;
	}
}
sort( $scan );

// ---------------------------------------------------------------------------
// 2. Resolve plugin-relative path expressions in shipped PHP.
// ---------------------------------------------------------------------------

/** Normalise a stage-relative path; null if it climbs out of the plugin. */
function norm( $path ) {
	$path = preg_replace( '/[?#"\'<>\s].*$/s', '', $path );
	$out  = array();
	foreach ( explode( '/', $path ) as $seg ) {
		if ( $seg === '' || $seg === '.' ) {
			continue;
		}
		if ( $seg === '..' ) {
			if ( ! $out ) {
				return null;
			}
			array_pop( $out );
			continue;
		}
		$out[] = $seg;
	}
	return implode( '/', $out );
}

function dir_of( $rel ) {
	$d = dirname( $rel );
	return $d === '.' ? '' : $d;
}

/** Significant tokens only (no whitespace/comments), each as [id, text, line]. */
function sig_tokens( $src ) {
	$toks = array();
	$line = 1;
	foreach ( token_get_all( $src ) as $t ) {
		if ( is_array( $t ) ) {
			$line = $t[2];
			if ( in_array( $t[0], array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT, T_OPEN_TAG ), true ) ) {
				continue;
			}
			$toks[] = array( $t[0], $t[1], $t[2] );
		} else {
			$toks[] = array( null, $t, $line );
		}
	}
	return $toks;
}

/**
 * A value is either a plugin path — array( 'path', rel, complete ) where rel
 * is relative to the plugin root and ends in '/' for a directory — or a
 * literal string — array( 'str', s ) — or null when it can't be resolved.
 */
class Resolver {
	public $toks;
	public $file;
	public $vars;
	public static $consts = array();

	function __construct( $toks, $file ) {
		$this->toks = $toks;
		$this->file = $file;
		$this->vars = array();
	}

	function is( $i, $text ) {
		return isset( $this->toks[ $i ] ) && $this->toks[ $i ][0] === null && $this->toks[ $i ][1] === $text;
	}

	/** Index just past the matching ')' for '(' at $i. */
	function skip_parens( $i ) {
		$depth = 0;
		for ( $n = count( $this->toks ); $i < $n; $i++ ) {
			$t = $this->toks[ $i ][1];
			if ( $this->toks[ $i ][0] === null && $t === '(' ) {
				$depth++;
			} elseif ( $this->toks[ $i ][0] === null && $t === ')' && --$depth === 0 ) {
				return $i + 1;
			}
		}
		return $i;
	}

	/** Split call args at top-level commas: list of [start, end). */
	function args( $open ) {
		$end   = $this->skip_parens( $open ) - 1;
		$args  = array();
		$start = $open + 1;
		$depth = 0;
		for ( $i = $open + 1; $i < $end; $i++ ) {
			$t = $this->toks[ $i ];
			if ( $t[0] === null && in_array( $t[1], array( '(', '[', '{' ), true ) ) {
				$depth++;
			} elseif ( $t[0] === null && in_array( $t[1], array( ')', ']', '}' ), true ) ) {
				$depth--;
			} elseif ( $t[0] === null && $t[1] === ',' && $depth === 0 ) {
				$args[] = array( $start, $i );
				$start  = $i + 1;
			}
		}
		if ( $start < $end ) {
			$args[] = array( $start, $end );
		}
		return array( $args, $end + 1 );
	}

	/** Evaluate a '.'-concatenation starting at $i. Returns [value, nextIndex]. */
	function concat( $i, $limit = PHP_INT_MAX ) {
		list( $val, $i ) = $this->operand( $i );
		while ( $i < $limit && $this->is( $i, '.' ) ) {
			list( $rhs, $i ) = $this->operand( $i + 1 );
			$val             = $this->join( $val, $rhs );
		}
		return array( $val, $i );
	}

	function join( $a, $b ) {
		if ( $a === null ) {
			return null;
		}
		if ( $a[0] === 'str' ) {
			return ( $b !== null && $b[0] === 'str' ) ? array( 'str', $a[1] . $b[1] ) : null;
		}
		if ( ! $a[2] ) {
			return $a; // Already partial; later operands can't change the known prefix.
		}
		if ( $b !== null && $b[0] === 'str' ) {
			return array( 'path', $a[1] . $b[1], true );
		}
		return array( 'path', $a[1], false );
	}

	function path_of_dir( $rel ) {
		return array( 'path', $rel === '' ? '' : "$rel/", true );
	}

	function operand( $i ) {
		$t = $this->toks[ $i ] ?? array( null, '', 0 );
		if ( $t[0] === null && $t[1] === '(' ) {
			$end = $this->skip_parens( $i );
			list( $v, $j ) = $this->concat( $i + 1, $end - 1 );
			return array( $j === $end - 1 ? $v : null, $end );
		}
		if ( $t[0] === T_CONSTANT_ENCAPSED_STRING ) {
			$q = $t[1][0];
			$s = substr( $t[1], 1, -1 );
			if ( $q === '"' && strpos( $s, '$' ) !== false ) {
				return array( null, $i + 1 );
			}
			return array( array( 'str', stripcslashes( $s ) ), $i + 1 );
		}
		if ( $t[0] === T_FILE ) {
			return array( array( 'path', $this->file, true ), $i + 1 );
		}
		if ( $t[0] === T_DIR ) {
			return array( array( 'path', dir_of( $this->file ), true ), $i + 1 );
		}
		if ( $t[0] === T_VARIABLE ) {
			return array( $this->vars[ $t[1] ] ?? null, $i + 1 );
		}
		if ( $t[0] === T_STRING || ( defined( 'T_NAME_FULLY_QUALIFIED' ) && $t[0] === T_NAME_FULLY_QUALIFIED ) ) {
			$name = strtolower( ltrim( $t[1], '\\' ) );
			if ( $this->is( $i + 1, '(' ) ) {
				list( $args, $next ) = $this->args( $i + 1 );
				$arg                 = function ( $n ) use ( $args ) {
					return isset( $args[ $n ] ) ? $this->concat( $args[ $n ][0], $args[ $n ][1] )[0] : null;
				};
				switch ( $name ) {
					case 'plugin_dir_path':
					case 'plugin_dir_url':
					case 'trailingslashit':
						$v = $arg( 0 );
						if ( $v !== null && $v[0] === 'path' && $v[2] ) {
							// plugin_dir_* drops the filename; trailingslashit keeps a dir.
							$p = $name === 'trailingslashit' ? rtrim( $v[1], '/' ) : ( substr( $v[1], -1 ) === '/' ? rtrim( $v[1], '/' ) : dir_of( $v[1] ) );
							return array( $this->path_of_dir( $p ), $next );
						}
						return array( null, $next );
					case 'dirname':
						$v      = $arg( 0 );
						$levels = 1;
						if ( isset( $args[1] ) && $this->toks[ $args[1][0] ][0] === T_LNUMBER ) {
							$levels = (int) $this->toks[ $args[1][0] ][1];
						}
						if ( $v !== null && $v[0] === 'path' && $v[2] ) {
							$p = rtrim( $v[1], '/' );
							for ( $k = 0; $k < $levels; $k++ ) {
								if ( $p === '' ) {
									return array( null, $next ); // Above the plugin root.
								}
								$p = dir_of( $p );
							}
							return array( array( 'path', $p, true ), $next );
						}
						return array( null, $next );
					case 'plugins_url':
						$rel  = $arg( 0 );
						$base = $arg( 1 );
						if ( $base !== null && $base[0] === 'path' && $base[2] ) {
							$dir = dir_of( $base[1] );
							if ( $rel === null ) {
								return array( array( 'path', $dir === '' ? '' : "$dir/", false ), $next );
							}
							if ( $rel[0] === 'str' ) {
								return array( array( 'path', ( $dir === '' ? '' : "$dir/" ) . ltrim( $rel[1], '/' ), true ), $next );
							}
						}
						return array( null, $next );
				}
				return array( null, $next );
			}
			if ( isset( self::$consts[ $t[1] ] ) ) {
				return array( self::$consts[ $t[1] ], $i + 1 );
			}
		}
		return array( null, $i + 1 );
	}
}

$php_files = array_values( array_filter( $scan, function ( $f ) {
	return substr( $f, -4 ) === '.php';
} ) );
$tokens    = array();
foreach ( $php_files as $rel ) {
	$tokens[ $rel ] = sig_tokens( file_get_contents( "$stage/$rel" ) );
}

// Plugin constants defined from path expressions, e.g.
// define( 'TRAINING_VIDEOS_PLUGIN_URL', plugin_dir_url( __FILE__ ) ).
foreach ( $tokens as $rel => $toks ) {
	$r = new Resolver( $toks, $rel );
	foreach ( $toks as $i => $t ) {
		if ( $t[0] === T_STRING && strtolower( $t[1] ) === 'define' && $r->is( $i + 1, '(' ) ) {
			list( $args, ) = $r->args( $i + 1 );
			if ( count( $args ) >= 2 && $toks[ $args[0][0] ][0] === T_CONSTANT_ENCAPSED_STRING ) {
				$v = $r->concat( $args[1][0], $args[1][1] )[0];
				if ( $v !== null && $v[0] === 'path' ) {
					Resolver::$consts[ substr( $toks[ $args[0][0] ][1], 1, -1 ) ] = $v;
				}
			}
		}
	}
}

$include_ids = array( T_INCLUDE, T_INCLUDE_ONCE, T_REQUIRE, T_REQUIRE_ONCE );
$anchor_fns  = array( 'plugin_dir_path', 'plugin_dir_url', 'plugins_url', 'dirname', 'trailingslashit' );
$checked     = 0;

$report = function ( $rel, $line, $val, $what ) use ( $root, $stage, &$shipped, &$shipped_dirs, &$excluded ) {
	$p = norm( $val[1] );
	if ( $p === null ) {
		return; // Climbs out of the plugin (WP core etc.) — not ours to check.
	}
	if ( ! $val[2] && substr( $val[1], -1 ) !== '/' ) {
		// Partial path ('templates/single-' . $name): only the directory is known.
		$p = dir_of( $p );
	}
	if ( isset( $shipped[ $p ] ) || isset( $shipped_dirs[ $p ] ) ) {
		echo "  ok  $rel:$line -> " . ( $p === '' ? '(plugin root)' : $p ) . "\n";
		return;
	}
	if ( isset( $excluded[ $p ] ) || is_dir( "$root/$p" ) ) {
		annotate( 'error', $rel, $line, "$what '$p', which .github/workflows/release.yml strips from the release zip. Works on the symlinked dev site, breaks on every install from a GitHub Release (CLAUDE.md Golden Rule 6)." );
	} else {
		annotate( 'error', $rel, $line, "$what '$p', which does not exist in the plugin." );
	}
};

foreach ( $tokens as $rel => $toks ) {
	$r       = new Resolver( $toks, $rel );
	$covered = -1;
	$n       = count( $toks );
	for ( $i = 0; $i < $n; $i++ ) {
		$t = $toks[ $i ];

		// Track `$var = <path expression>;` so later `require $var` resolves.
		if ( $t[0] === T_VARIABLE && $r->is( $i + 1, '=' ) ) {
			list( $v, $j ) = $r->concat( $i + 2 );
			if ( $r->is( $j, ';' ) ) {
				$r->vars[ $t[1] ] = $v;
				if ( $v !== null && $v[0] === 'path' ) {
					$report( $rel, $t[2], $v, 'References' );
					$checked++;
					$covered = $j;
				}
			} else {
				unset( $r->vars[ $t[1] ] );
			}
			continue;
		}

		if ( in_array( $t[0], $include_ids, true ) ) {
			$first = $toks[ $i + 1 ] ?? array( null, '', 0 );
			if ( $r->is( $i + 1, '(' ) ) {
				$first = $toks[ $i + 2 ] ?? $first;
			}
			if ( $first[0] === T_STRING && preg_match( '/^(ABSPATH|WPINC|WP_[A-Z_]+)$/', $first[1] ) ) {
				echo "  --  $rel:{$t[2]} -> WordPress core ({$first[1]}), not ours to check\n";
				continue;
			}
			list( $v, $j ) = $r->concat( $i + 1 );
			$covered       = max( $covered, $j );
			$checked++;
			if ( $v === null ) {
				annotate( 'warning', $rel, $t[2], 'Could not statically resolve this ' . $t[1] . ' target; make sure it is not a release-excluded file.' );
			} elseif ( $v[0] === 'str' ) {
				// Bare relative string: PHP resolves it against include_path / cwd,
				// which on a site is not the plugin dir. Check it as plugin-relative
				// to the including file anyway.
				$report( $rel, $t[2], array( 'path', ( dir_of( $rel ) === '' ? '' : dir_of( $rel ) . '/' ) . $v[1], true ), "`" . strtolower( $t[1] ) . "` of" );
			} else {
				$report( $rel, $t[2], $v, "`" . strtolower( $t[1] ) . "` of" );
			}
			continue;
		}

		if ( $i <= $covered ) {
			continue;
		}
		$anchor = in_array( $t[0], array( T_DIR, T_FILE ), true )
			|| ( $t[0] === T_STRING && in_array( strtolower( $t[1] ), $anchor_fns, true ) && $r->is( $i + 1, '(' ) )
			|| ( $t[0] === T_STRING && isset( Resolver::$consts[ $t[1] ] ) );
		if ( ! $anchor ) {
			continue;
		}
		list( $v, $j ) = $r->concat( $i );
		if ( $v !== null && $v[0] === 'path' ) {
			$report( $rel, $t[2], $v, 'References' );
			$checked++;
			$covered = $j - 1;
		}
	}
}
echo "Resolved $checked include/require targets and plugin-relative path expressions in " . count( $php_files ) . " shipped PHP files.\n";

// ---------------------------------------------------------------------------
// 3. No shipped code may name an excluded file outside a comment.
// ---------------------------------------------------------------------------

// Match on basename when it is unique to the excluded set, otherwise on the
// relative path (docs/README.md is excluded, ./README.md ships).
$shipped_basenames = array();
foreach ( array_keys( $shipped ) as $f ) {
	$shipped_basenames[ basename( $f ) ] = true;
}
$needles = array();
foreach ( array_keys( $excluded ) as $f ) {
	$needles[ isset( $shipped_basenames[ basename( $f ) ] ) ? $f : basename( $f ) ] = $f;
}

$hits = function ( $rel, $text, $line ) use ( $needles ) {
	foreach ( $needles as $needle => $f ) {
		$at = 0;
		while ( ( $pos = strpos( $text, $needle, $at ) ) !== false ) {
			$at     = $pos + 1;
			$before = $pos > 0 ? $text[ $pos - 1 ] : '';
			$after  = substr( $text, $pos + strlen( $needle ), 1 );
			if ( preg_match( '/[A-Za-z0-9_.-]/', $before ) || preg_match( '/[A-Za-z0-9_-]/', $after ) ) {
				continue; // Part of a longer name.
			}
			$ls     = strrpos( substr( $text, 0, $pos ), "\n" );
			$ls     = $ls === false ? 0 : $ls + 1;
			$prefix = substr( $text, $ls, $pos - $ls );
			if ( preg_match( '#[a-z][a-z0-9+.-]*://\S*$#i', $prefix ) ) {
				continue; // Absolute URL (e.g. a github.com link) — not a plugin path.
			}
			$ln = $line + substr_count( substr( $text, 0, $pos ), "\n" );
			annotate( 'error', $rel, $ln, "Names '$needle' ($f), which .github/workflows/release.yml strips from the release zip. Dev-only helpers must not be included, required or linked from shipped code — run them with `wp eval-file` instead (CLAUDE.md Golden Rule 6)." );
		}
	}
};

$string_ids = array( T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE, T_INLINE_HTML );
foreach ( $scan as $rel ) {
	$src = file_get_contents( "$stage/$rel" );
	if ( substr( $rel, -4 ) === '.php' ) {
		foreach ( token_get_all( $src ) as $t ) {
			if ( is_array( $t ) && in_array( $t[0], $string_ids, true ) ) {
				$hits( $rel, $t[1], $t[2] );
			}
		}
	} else {
		// JS/CSS: drop /* */ and // comments, keeping line numbers.
		$src = preg_replace_callback( '#/\*.*?\*/|(?<![:\'"\\\\])//[^\n]*#s', function ( $m ) {
			return str_repeat( "\n", substr_count( $m[0], "\n" ) );
		}, $src );
		$hits( $rel, $src, 1 );
	}
}
echo 'Scanned ' . count( $scan ) . ' shipped PHP/JS/CSS files for ' . count( $needles ) . " excluded file names.\n";

exec( 'rm -rf ' . escapeshellarg( dirname( $stage ) ) );

if ( $errors ) {
	echo "\nRule 6 FAILED: $errors violation(s). Either stop referencing the file from shipped code, or (if it really must ship) remove it from the exclude list in .github/workflows/release.yml.\n";
	exit( 1 );
}
echo "\nRule 6 OK: nothing shipped references a release-excluded file" . ( $warnings ? " ($warnings warning(s) above)" : '' ) . ".\n";
