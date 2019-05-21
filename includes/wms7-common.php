<?php
/**
 * Description: Helper Console Build Feature
 *
 * @category    wms7-common.php
 * @package     WatchMan-Site7
 * @author      Oleg Klenitskiy <klenitskiy.oleg@mail.ru>
 * @version     3.1.1
 * @license     GPLv2 or later
 */

// loadable environment WordPress.
$_request_uri   = filter_input( INPUT_SERVER, 'REQUEST_URI', FILTER_SANITIZE_STRING );
$_document_root = filter_input( INPUT_SERVER, 'DOCUMENT_ROOT', FILTER_SANITIZE_STRING );
$params         = explode( 'wp-content', $_request_uri );
$path_to        = $_document_root . $params[0];
require_once $path_to . 'wp-load.php';

if ( ! session_id() ) {
	session_start();
}
if ( ob_get_length() > 0 ) {
	ob_end_clean();
}
set_time_limit( 0 );
/**
 * Console error handler.
 *
 * @param integer $errno Number of error.
 * @param string  $errorstr Message of error.
 */
function console_error_handler( $errno, $errorstr ) {
	error( $errorstr );
}
/**
 * Error.
 *
 * @param string $error Message of error.
 */
function error( $error ) {
	exit( json_encode( array( 'error' => $error ) ) );
}
/**
 * Saves newly defined variables to session.
 *
 * @param array $existing existing.
 * @param array $current current.
 * @param array $ignore ignore.
 */
function save_variables( $existing, $current, $ignore ) {
	$new_vars  = array_diff( array_keys( $current ), array_keys( $existing ) );
	$user_vars = array_diff( $new_vars, $ignore );
	$save_vars = array();

	foreach ( $current as $key => $value ) {
		if ( in_array( $key, $user_vars, true ) ) {
			$save_vars[ $key ] = $value;
		}
	}
	$export = var_export( $save_vars, true );
	// special consideration for variables that are objects.
	// http://www.thoughtlabs.com/2008/02/02/phps-mystical-__set_state-method/ .
	$export                   = preg_replace_callback( '/(\w+)::__set_state/Ums', 'class_set_state_check', $export );
	$_SESSION['console_vars'] = $export;
}
/**
 * Classes to be restored need to implement __set_state() function.
 * If they don't have it, we will convert to stdClass object.
 *
 * @param object $matches Matches.
 * @return object.
 */
function class_set_state_check( $matches ) {
	if ( method_exists( $matches[1], '__set_state' ) ) {
		return $matches[0];
	} else {
		return '(object) ';
	}
}
/**
 * See: http://jan.kneschke.de/projects/php-shell
 * Return int 0 if a executable statement is in the session buffer, non-zero otherwise.
 *
 * @param object $code Code.
 * @throws Exception Variable \'%s\' is not set.
 * @throws Exception Variable \'%s\' is not a class.
 * @throws Exception Variable %s (Class '%s') doesn't have a method named '%s'.
 * @throws Exception Variable \'%s\' is not set.
 * @throws Exception Variable \'%s\' is not a class.
 * @throws Exception Variable %s (Class '%s') doesn't have a method named '%s'.
 * @throws Exception Variable \'%s\' is not a array'.
 * @throws Exception Class \'%s\' doesn\'t exist.
 * @throws Exception Class '%s' doesn't have a method named '%s'.

 * @return integer.
 */
function parse( $code ) {
	// remove empty lines.
	if ( trim( $code ) === '' ) {
		return 1;
	}

	$t = token_get_all( '<?php ' . $code . ' ?>' );
	// Need a semicolon to complete the statement.
	$need_semicolon = 1;
	// Need to add a return to eval-string.
	$need_return = 1;
	// A open multi-line comment.
	$open_comment = 0;
	// Code to be eval().
	$eval = '';
	// To track if we need more closing braces.
	$braces = array();
	// To track duplicate methods in a class declaration.
	$methods = array();
	// Tokens without whitespaces.
	$ts = array();

	foreach ( $t as $ndx => $token ) {
		if ( is_array( $token ) ) {
			$ignore = 0;

			switch ( $token[0] ) {
				case T_WHITESPACE:
				case T_OPEN_TAG:
				case T_CLOSE_TAG:
					$ignore = 1;
					break;
				case T_FOREACH:
				case T_DO:
				case T_WHILE:
				case T_FOR:
				case T_IF:
				case T_RETURN:
				case T_CLASS:
				case T_FUNCTION:
				case T_INTERFACE:
				case T_PRINT:
				case T_ECHO:
				case T_COMMENT:
				case T_UNSET:
				case T_INCLUDE:
				case T_REQUIRE:
				case T_INCLUDE_ONCE:
				case T_REQUIRE_ONCE:
				case T_TRY:
				case T_SWITCH:
				case T_DEFAULT:
				case T_CASE:
				case T_BREAK:
				case T_DOC_COMMENT:
					$need_return = 0;
					break;
				case T_EMPTY:
				case T_ISSET:
				case T_EVAL:
				case T_EXIT:
				case T_VARIABLE:
				case T_STRING:
				case T_NEW:
				case T_EXTENDS:
				case T_IMPLEMENTS:
				case T_OBJECT_OPERATOR:
				case T_DOUBLE_COLON:
				case T_INSTANCEOF:
				case T_CATCH:
				case T_THROW:
				case T_ELSE:
				case T_AS:
				case T_LNUMBER:
				case T_DNUMBER:
				case T_CONSTANT_ENCAPSED_STRING:
				case T_ENCAPSED_AND_WHITESPACE:
				case T_CHARACTER:
				case T_ARRAY:
				case T_DOUBLE_ARROW:
				case T_CONST:
				case T_PUBLIC:
				case T_PROTECTED:
				case T_PRIVATE:
				case T_ABSTRACT:
				case T_STATIC:
				case T_VAR:
				case T_INC:
				case T_DEC:
				case T_SL:
				case T_SL_EQUAL:
				case T_SR:
				case T_SR_EQUAL:
				case T_IS_EQUAL:
				case T_IS_IDENTICAL:
				case T_IS_GREATER_OR_EQUAL:
				case T_IS_SMALLER_OR_EQUAL:
				case T_BOOLEAN_OR:
				case T_LOGICAL_OR:
				case T_BOOLEAN_AND:
				case T_LOGICAL_AND:
				case T_LOGICAL_XOR:
				case T_MINUS_EQUAL:
				case T_PLUS_EQUAL:
				case T_MUL_EQUAL:
				case T_DIV_EQUAL:
				case T_MOD_EQUAL:
				case T_XOR_EQUAL:
				case T_AND_EQUAL:
				case T_OR_EQUAL:
				case T_FUNC_C:
				case T_CLASS_C:
				case T_LINE:
				case T_FILE:
				case T_BOOL_CAST:
				case T_INT_CAST:
				case T_STRING_CAST:
					/* just go on */
					break;
				default:
					/* debug unknown tags*/
					error_log( sprintf( 'unknown tag: %d (%s): %s' . PHP_EOL, $token[0], token_name( $token[0] ), $token[1] ) );
					break;
			}
			if ( ! $ignore ) {
				$eval .= $token[1] . ' ';
				$ts[]  = array(
					'token' => $token[0],
					'value' => $token[1],
				);
			}
		} else {
			$ts[] = array(
				'token' => $token,
				'value' => '',
			);

			$last = count( $ts ) - 1;

			switch ( $token ) {
				case '(':
					// Walk backwards through the tokens.
					if ( $last >= 4 &&
					T_STRING === $ts[ $last - 1 ]['token'] &&
					T_OBJECT_OPERATOR === $ts[ $last - 2 ]['token'] &&
					')' === $ts[ $last - 3 ]['token'] ) {
						// We can't know what func() is return, so we can't say if the method() exists or not.
					} elseif ( $last >= 3 &&
					// If we are not in a class definition.
					T_CLASS !== $ts[0]['token'] &&
					T_ABSTRACT !== $ts[0]['token'] &&
					T_CLASS !== $ts[1]['token'] &&
					T_STRING === $ts[ $last - 1 ]['token'] &&
					T_OBJECT_OPERATOR === $ts[ $last - 2 ]['token'] &&
					T_VARIABLE === $ts[ $last - 3 ]['token'] ) {

						// $object -> method.
						$in_catch = 0;
						foreach ( $ts as $v ) {
							if ( T_CATCH === $v['token'] ) {
								$in_catch = 1;
							}
						}
						if ( ! $in_catch ) {
							// $object has to exist and has to be a object.
							$objname = $ts[ $last - 3 ]['value'];

							if ( ! isset( $GLOBALS[ ltrim( $objname, '$' ) ] ) ) {
								throw new Exception( sprintf( 'Variable \'%s\' is not set', $objname ) );
							}
							$object = $GLOBALS[ ltrim( $objname, '$' ) ];

							if ( ! is_object( $object ) ) {
								throw new Exception( sprintf( 'Variable \'%s\' is not a class', $objname ) );
							}
							$method = $ts[ $last - 1 ]['value'];

							if ( ! method_exists( $object, $method ) ) {
								throw new Exception(
									sprintf(
										"Variable %s (Class '%s') doesn't have a method named '%s'",
										$objname,
										get_class( $object ),
										$method
									)
								);
							}
						}
					} elseif ( $last >= 3 &&
						// if we are not in a class definition.
					T_CLASS !== $ts[0]['token'] &&
					T_VARIABLE === $ts[ $last - 1 ]['token'] &&
					T_OBJECT_OPERATOR === $ts[ $last - 2 ]['token'] &&
					T_VARIABLE === $ts[ $last - 3 ]['token'] ) {

						// $object has to exist and has to be a object.
						$objname = $ts[ $last - 3 ]['value'];

						if ( ! isset( $GLOBALS[ ltrim( $objname, '$' ) ] ) ) {
							throw new Exception( sprintf( 'Variable \'%s\' is not set', $objname ) );
						}
						$object = $GLOBALS[ ltrim( $objname, '$' ) ];

						if ( ! is_object( $object ) ) {
							throw new Exception( sprintf( 'Variable \'%s\' is not a class', $objname ) );
						}

						$methodname = $ts[ $last - 1 ]['value'];

						if ( ! isset( $GLOBALS[ ltrim( $methodname, '$' ) ] ) ) {
							throw new Exception( sprintf( 'Variable \'%s\' is not set', $methodname ) );
						}
						$method = $GLOBALS[ ltrim( $methodname, '$' ) ];

						if ( ! method_exists( $object, $method ) ) {
							throw new Exception(
								sprintf(
									"Variable %s (Class '%s') doesn't have a method named '%s'",
									$objname,
									get_class( $object ),
									$method
								)
							);
						}
					} elseif ( $last >= 6 &&
						// if we are not in a class definition.
					T_CLASS !== $ts[0]['token'] &&
					T_STRING === $ts[ $last - 1 ]['token'] &&
					T_OBJECT_OPERATOR === $ts[ $last - 2 ]['token'] &&
					']' === $ts[ $last - 3 ]['token'] &&
						// might be anything as index.
					'[' === $ts[ $last - 5 ]['token'] &&
					T_VARIABLE === $ts[ $last - 6 ]['token'] ) {

						// $object has to exist and has to be a object.
						$objname = $ts[ $last - 6 ]['value'];

						if ( ! isset( $GLOBALS[ ltrim( $objname, '$' ) ] ) ) {
							throw new Exception( sprintf( 'Variable \'%s\' is not set', $objname ) );
						}
						$array = $GLOBALS[ ltrim( $objname, '$' ) ];

						if ( ! is_array( $array ) ) {
							throw new Exception( sprintf( 'Variable \'%s\' is not a array', $objname ) );
						}
						$andx = $ts[ $last - 4 ]['value'];

						if ( ! isset( $array[ $andx ] ) ) {
							throw new Exception( sprintf( '%s[\'%s\'] is not set', $objname, $andx ) );
						}
						$object = $array[ $andx ];

						if ( ! is_object( $object ) ) {
							throw new Exception( sprintf( 'Variable \'%s\' is not a class', $objname ) );
						}
						$method = $ts[ $last - 1 ]['value'];

						if ( ! method_exists( $object, $method ) ) {
							throw new Exception(
								sprintf(
									"Variable %s (Class '%s') doesn't have a method named '%s'",
									$objname,
									get_class( $object ),
									$method
								)
							);
						}
					} elseif ( $last >= 3 &&
						// if we are not in a class definition.
					T_CLASS !== $ts[0]['token'] &&
					T_STRING === $ts[ $last - 1 ]['token'] &&
					T_DOUBLE_COLON === $ts[ $last - 2 ]['token'] &&
					T_STRING === $ts[ $last - 3 ]['token'] ) {

						// $object has to exist and has to be a object.
						$classname = $ts[ $last - 3 ]['value'];

						if ( ! class_exists( $classname ) ) {
							throw new Exception( sprintf( 'Class \'%s\' doesn\'t exist', $classname ) );
						}
						$method = $ts[ $last - 1 ]['value'];

						if ( ! in_array( $method, get_class_methods( $classname ) ) ) {
							throw new Exception(
								sprintf(
									"Class '%s' doesn't have a method named '%s'",
									$classname,
									$method
								)
							);
						}
					} elseif ( $last >= 3 &&
						// if we are not in a class definition.
					T_CLASS !== $ts[0]['token'] &&
					T_VARIABLE === $ts[ $last - 1 ]['token'] &&
					T_DOUBLE_COLON === $ts[ $last - 2 ]['token'] &&
					T_STRING === $ts[ $last - 3 ]['token'] ) {

						// $object has to exist and has to be a object.
						$classname = $ts[ $last - 3 ]['value'];

						if ( ! class_exists( $classname ) ) {
							throw new Exception( sprintf( 'Class \'%s\' doesn\'t exist', $classname ) );
						}
						$methodname = $ts[ $last - 1 ]['value'];

						if ( ! isset( $GLOBALS[ ltrim( $methodname, '$' ) ] ) ) {
							throw new Exception( sprintf( 'Variable \'%s\' is not set', $methodname ) );
						}
						$method = $GLOBALS[ ltrim( $methodname, '$' ) ];

						if ( ! in_array( $method, get_class_methods( $classname ) ) ) {
							throw new Exception(
								sprintf(
									"Class '%s' doesn't have a method named '%s'",
									$classname,
									$method
								)
							);
						}
					} elseif ( $last >= 2 &&
						// if we are not in a class definition.
					T_CLASS !== $ts[0]['token'] &&
					T_STRING === $ts[ $last - 1 ]['token'] &&
					T_NEW === $ts[ $last - 2 ]['token'] ) {

						// don't care about this in a class ... { ... }.
						$classname = $ts[ $last - 1 ]['value'];

						if ( ! class_exists( $classname ) ) {
							throw new Exception( sprintf( 'Class \'%s\' doesn\'t exist', $classname ) );
						}
						$r = new ReflectionClass( $classname );

						if ( $r->isAbstract() ) {
							throw new Exception( sprintf( "Can't instantiate abstract Class '%s'", $classname ) );
						}
						if ( ! $r->isInstantiable() ) {
							throw new Exception( sprintf( 'Class \'%s\' can\'t be instantiated. Is the class abstract ?', $classname ) );
						}
					} elseif ( $last >= 2 &&
						// if we are not in a class definition.
					T_CLASS !== $ts[0]['token'] &&
					T_STRING === $ts[ $last - 1 ]['token'] &&
					T_FUNCTION === $ts[ $last - 2 ]['token'] ) {

						// make sure we are not a in class definition.
						$func = $ts[ $last - 1 ]['value'];

						if ( function_exists( $func ) ) {
							throw new Exception( sprintf( 'Function \'%s\' is already defined', $func ) );
						}
					} elseif ( $last >= 4 &&
					T_CLASS === $ts[0]['token'] &&
					T_STRING === $ts[1]['token'] &&
					T_STRING === $ts[ $last - 1 ]['token'] &&
					T_FUNCTION === $ts[ $last - 2 ]['token'] ) {

						// make sure we are not a in class definition.
						$func      = $ts[ $last - 1 ]['value'];
						$classname = $ts[1]['value'];

						if ( isset( $methods[ $func ] ) ) {
							throw new Exception( sprintf( "Can't redeclare method '%s' in Class '%s'", $func, $classname ) );
						}
						$methods[ $func ] = 1;
					} elseif ( $last >= 1 &&
						// if we are not in a class definition.
					T_CLASS !== $ts[0]['token'] &&
					T_ABSTRACT !== $ts[0]['token'] &&
					T_CLASS !== $ts[1]['token'] &&
					T_STRING === $ts[ $last - 1 ]['token'] ) {

						$funcname = $ts[ $last - 1 ]['value'];

						if ( ! function_exists( $funcname ) ) {
							throw new Exception( sprintf( "Function %s() doesn't exist", $funcname ) );
						}
					} elseif ( $last >= 1 &&
						// if we are not in a class definition.
					T_CLASS !== $ts[0]['token'] &&
					T_VARIABLE === $ts[ $last - 1 ]['token'] ) {

						// $object has to exist and has to be a object.
						$funcname = $ts[ $last - 1 ]['value'];

						if ( ! isset( $GLOBALS[ ltrim( $funcname, '$' ) ] ) ) {
							throw new Exception( sprintf( 'Variable \'%s\' is not set', $funcname ) );
						}
						$func = $GLOBALS[ ltrim( $funcname, '$' ) ];

						if ( ! function_exists( $func ) ) {
							throw new Exception( sprintf( "Function %s() doesn't exist", $func ) );
						}
					}

					array_push( $braces, $token );
					break;
				case '{':
					$need_return = 0;

					if ( $last >= 2 &&
					T_STRING === $ts[ $last - 1 ]['token'] &&
					T_CLASS === $ts[ $last - 2 ]['token'] ) {

						$classname = $ts[ $last - 1 ]['value'];

						if ( class_exists( $classname, false ) ) {
							throw new Exception( sprintf( "Class '%s' can't be redeclared", $classname ) );
						}
					} elseif ( $last >= 4 &&
					T_STRING === $ts[ $last - 1 ]['token'] &&
					T_EXTENDS === $ts[ $last - 2 ]['token'] &&
					T_STRING === $ts[ $last - 3 ]['token'] &&
					T_CLASS === $ts[ $last - 4 ]['token'] ) {

						$classname   = $ts[ $last - 3 ]['value'];
						$extendsname = $ts[ $last - 1 ]['value'];

						if ( class_exists( $classname, false ) ) {
							throw new Exception(
								sprintf(
									"Class '%s' can't be redeclared",
									$classname
								)
							);
						}
						if ( ! class_exists( $extendsname, true ) ) {
							throw new Exception(
								sprintf(
									"Can't extend '%s' ... from not existing Class '%s'",
									$classname,
									$extendsname
								)
							);
						}
					} elseif ( $last >= 4 &&
					T_STRING === $ts[ $last - 1 ]['token'] &&
					T_IMPLEMENTS === $ts[ $last - 2 ]['token'] &&
					T_STRING === $ts[ $last - 3 ]['token'] &&
					T_CLASS === $ts[ $last - 4 ]['token'] ) {

						// class name implements interface.
						$classname  = $ts[ $last - 3 ]['value'];
						$implements = $ts[ $last - 1 ]['value'];

						if ( class_exists( $classname, false ) ) {
							throw new Exception(
								sprintf(
									"Class '%s' can't be redeclared",
									$classname
								)
							);
						}
						if ( ! interface_exists( $implements, false ) ) {
							throw new Exception(
								sprintf(
									"Can't implement not existing Interface '%s' for Class '%s'",
									$implements,
									$classname
								)
							);
						}
					}
					array_push( $braces, $token );
					break;
				case '}':
					$need_return = 0;
				case ')':
					array_pop( $braces );
					break;
				case '[':
					// if we are not in a class definition.
					if ( T_CLASS !== $ts[0]['token'] &&
					T_ABSTRACT !== $ts[0]['token'] &&
					T_CLASS !== $ts[1]['token'] &&
					T_VARIABLE === $ts[ $last - 1 ]['token'] ) {

						// $object has to exist and has to be a object.
						$objname = $ts[ $last - 1 ]['value'];

						if ( ! isset( $GLOBALS[ ltrim( $objname, '$' ) ] ) ) {
							throw new Exception( sprintf( 'Variable \'%s\' is not set', $objname ) );
						}
						$obj = $GLOBALS[ ltrim( $objname, '$' ) ];

						if ( is_object( $obj ) ) {
							throw new Exception( sprintf( 'Objects (%s) don\'t support array access operators', $objname ) );
						}
					}
					break;
			}

			$eval .= $token;
		}
	}

	$last = count( $ts ) - 1;
	if ( $last >= 2 &&
		T_STRING === $ts[ $last - 0 ]['token'] &&
		T_DOUBLE_COLON === $ts[ $last - 1 ]['token'] &&
		T_STRING === $ts[ $last - 2 ]['token'] ) {

		// $object has to exist and has to be a object.
		$classname = $ts[ $last - 2 ]['value'];

		if ( ! class_exists( $classname ) ) {
			throw new Exception( sprintf( 'Class \'%s\' doesn\'t exist', $classname ) );
		}
		$constname = $ts[ $last - 0 ]['value'];

		$c = new ReflectionClass( $classname );
		if ( ! $c->hasConstant( $constname ) ) {
			throw new Exception(
				sprintf(
					"Class '%s' doesn't have a constant named '%s'",
					$classname,
					$constname
				)
			);
		}
	} elseif ( 0 === $last &&
		T_VARIABLE === $ts[ $last - 0 ]['token'] ) {

		$varname = $ts[ $last - 0 ]['value'];

		if ( ! isset( $GLOBALS[ ltrim( $varname, '$' ) ] ) ) {
			throw new Exception( sprintf( 'Variable \'%s\' is not set', $varname ) );
		}
	}
	$need_more = ( count( $braces ) > 0 ) || $open_comment;

	if ( $need_more || ';' === $token ) {
		$need_semicolon = 0;
	}
	if ( $need_return ) {
		$eval = 'return ' . $eval;
	}
	if ( $need_more ) {
		$_SESSION['partial'] = $eval;
	} else {
		$_SESSION['code'] = $eval;
	}

	return $need_more;
}
